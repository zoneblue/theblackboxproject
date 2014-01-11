
/**
 * NEWMODBUSD
 *
 * Daemon version of RossW's newmodbus C command line tool.
 * Communicates with Midnite classic at a specified sample interval
 * and maintains two sets of text files containing the register listings.
 * - data.txt is the current snapshot, one entry per line, complete range.
 * - in /data/ there is one file per day, one tab seperated sample per line, register subset.
 *
 * Revision: $Rev$
 * License: GPLv3
 *
 **/

#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include <sys/time.h>
#include <time.h>

#include <fcntl.h>
#include <signal.h>
#include <unistd.h>

#include <errno.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <netdb.h>

#define LOCK_FILE	"newmodbusd.lock"
#define LOG_FILE	"newmodbusd.log"
#define CONF_FILE	"newmodbusd.conf"

//declare globals
char working_dir[60];  //working directory
char host[15];         //ip address of classic
int  port;             //modbus port
int  interval;         //sample interval

int slen,i,n,r;        //sleep length, gen purpuse ints
long long unsigned millis, millis2; //millisec unix timestamps
char datalogfile[80];  //data filename
FILE *fp_t, *fp_l;     //file pointers, temp and log
struct timeval tv;     //microtimestamp
time_t rawtime;        //datestamp
struct tm *lt;         //localtime

int sock= 0;           //classic socket
struct sockaddr_in sa; //internet socket addr. structure
struct hostent *hp;	   //result of host name lookup
char buf[302*2];	   //buffer to read modbus information (302 16-bit registers)
char id[101*2];	       // device ID and other scratchings.
int len;		       //length of received data
int addr;              //regsiter iterator

//function declarations
int dotask(void);
int modbus_read_registers(int addr, int number, int offset);
unsigned int modbus_register(int reg);
void init(void);
void daemonize(void);
void log_message(char *filename, char *message);
void signal_handler(int sig);



/**
 * MAIN
 * @arg nil
 * @return nil
 *
 */
int main(void) {

	//parse config and check for key files
	init();

	//start daemon
	daemonize();

	while (1) {

		//sleep til start of new sample interval
		gettimeofday(&tv,NULL);
		millis= (long long unsigned)tv.tv_sec*1000 + (tv.tv_usec/1000);
		slen= interval - (millis % interval);
		i= (millis+slen) % 1000;
		usleep (slen*1000);

		//make sample timestamp
		time(&rawtime);
		lt= localtime(&rawtime);

		//open data log file
		sprintf(datalogfile, "data/%04d-%02d-%02d.txt", lt->tm_year+1900,lt->tm_mon+1,lt->tm_mday);
		fp_l= fopen(datalogfile,"a");
		if (fp_l<0) {
			log_message(LOG_FILE, "Data file write error");
			exit(1);
		}

		//open temp data file
		fp_t= fopen("tempdata.txt","w");
		if (fp_t<0) {
			log_message(LOG_FILE, "Temp file write error");
			exit(1);
		}

		//print date headers
		fprintf(fp_l, "[%02d:%02d:%02d.%03d] - ",lt->tm_hour,lt->tm_min,lt->tm_sec,i);
		fprintf(fp_t, "[%04d-%02d-%02d %02d:%02d:%02d.%03d]\n",lt->tm_year+1900,lt->tm_mon+1,lt->tm_mday,lt->tm_hour,lt->tm_min,lt->tm_sec,i);

		//read device and record data
		r= dotask();

		//log the duration
		gettimeofday(&tv,NULL);
		millis2= (long long unsigned)tv.tv_sec*1000 + (tv.tv_usec/1000);
		fprintf(fp_l, "%d - %llu ms \n", r, millis2-millis-slen);

		//close both files
		fclose(fp_l);
		fclose(fp_t);

		//finalise data file
		//rename is fast, thus data file has high availability to other processes
		//that is the plan anyway
		if (!r) rename("tempdata.txt","data.txt");
	}

	exit(0);
}


/**
 * DOTASK
 * Reads classic once per interval. Creates socket if not already open.
 * Reads entire modbus range, and writes to data file.
 *
 * @arg nil
 * @return (int) 0 good read, 1 partial read, 2 bad read
 *
 **/
int dotask(void){

	//open a new network socket
	if (!sock) {

		log_message(LOG_FILE, "New classic socket...");

		//check host
		if ((hp = gethostbyname(host)) == NULL) {
			log_message(LOG_FILE, "Host unreachable");
			return(2);
		}
		//create pointer
		bcopy((char *)hp->h_addr,(char *)&sa.sin_addr, hp->h_length);
		sa.sin_family = hp->h_addrtype;
		sa.sin_port=port>>8 | (port<<8 & 0xffff);

		//get network socket
		if((sock= socket(hp->h_addrtype, SOCK_STREAM,0)) < 0) {
			log_message(LOG_FILE, "Socket failed");
			return(2);
		}

		//connect to it
		if (connect(sock, (struct sockaddr *)&sa, sizeof(sa)) < 0) {
			log_message(LOG_FILE, "Socket connect failed");
			return(2);
		}

		log_message(LOG_FILE, "Socket successful");
	}


	//read the entire main register range into the buffer array
	//for now ignore the half doz regs at 16385-16390
	i=0;
	if (modbus_read_registers(4101, 50, 0  )) i=1;
	if (modbus_read_registers(4151, 50, 50 )) i=1;
	if (modbus_read_registers(4201, 50, 100)) i=1;
	if (modbus_read_registers(4251, 50, 150)) i=1;
	if (modbus_read_registers(4301, 50, 200)) i=1;
	if (modbus_read_registers(4351, 25, 250)) i=1;

	//any problems skip write, so the prev entry stands
	//however we need to consider pre 1609 firmware, todo
	if (i) {
		log_message(LOG_FILE, "Modbus read error");
		return(1);
	}

	//write registers to our data files
	for (addr=4101; addr<=4371; addr++) {
		i= modbus_register(addr);
		//all to 'current' data file
		fprintf(fp_t, "%d:%d\n", addr, i);

		//important ones to data log file
		if (addr==4115 || addr==4116 || addr==4117 || addr==4120 || addr==4121 || addr==4132 || addr==4371 ) {
			fprintf(fp_l, "%d:%d\t", addr, i);
		}
	}

	//entry endings
	fprintf(fp_l, "\n");
	fprintf(fp_t, "--\n");

	return (0);
}


/**
 * MODBUS_READ_REGISTERS
 * Reads modbus registers via established network socket
 * Store in buffer array.
 *
 * @arg: (int) addr   , starting *register*
 * @arg: (int) number , number of 16-bit registers to read
 * @arg: (int) offset , where registers to be stored in the buffer
 * @return (int) 0 ok, 1 fail
 *
 */
int modbus_read_registers(int addr, int number, int offset) {
	id[0]=0;               // Transaction ID MSB
	id[1]=2;               // Transaction ID LSB
	id[2]=0;               // modbus protocol 0 MSB
	id[3]=0;               // modbus protocol 0 LSB
	id[4]=0;               // bytes following MSB
	id[5]=6;               // bytes following LSB
	id[6]=255;             // ident
	id[7]=3;               // read multiple registers/addresses
	id[8]= (addr-1)>>8;    // starting address (MSB)
	id[9]= (addr-1)&0xff;  // starting address (LSB)
	id[10]=0;              // Addresses to read (MSB)
	id[11]=number;         // Addresses to read (LSB)

	if (write(sock,&id, 12) != 12) return(1); //socket request error
	len= read(sock, id, 7);		    // read modbus encapsulation
	len= read(sock, id, 2);		    // read response code and bytecount
	if (len<2 || (id[0]) != 3 || (id[1]&0xff) != number<<1) return(1); //socket reply error

	n= id[1]&0xff;			            // number of bytes to read
	len= read(sock, buf+(offset)*2, n);	// read response as advised

	return(0);
}


/**
 * MODBUS_REGISTER
 * Gets a single register value from the buffer array
 *
 * @arg (int) register
 * @return (int) value
 *
 **/
unsigned int modbus_register(int reg){

	if (reg>=4101 && reg<=4371) {
		return((buf[(reg-4101)<<1]&0xff)<<8 | (buf[((reg-4101)<<1)+1] & 0xff));
	}
	else return (0);
}


/**
 * INIT
 * Parses config file, and checks logfile, and data writability.
 *
 * @arg nil
 * @return nil
 *
 **/
void init(void) {

	char line[80];
	FILE *fp_c;
	struct stat st= {0};

	//parse config file
	printf("Parsing config file: %s\n",CONF_FILE);
	fp_c= fopen(CONF_FILE, "rt");
	if (!fp_c) {printf("Config file not found\n");exit(1);}
	while (fgets(line, 80, fp_c) != NULL) {
		if      (strstr(line,"classic_ip"))      sscanf(line, "%*s %s", host);
		else if (strstr(line,"working_dir"))     sscanf(line, "%*s %s", working_dir);
		else if (strstr(line,"classic_port"))    sscanf(line, "%*s %d", &port);
		else if (strstr(line,"sample_interval")) sscanf(line, "%*s %d", &interval);
	}
	fclose(fp_c);
	if (strlen(host)<7)        {printf("Invalid classic_ip\n");exit(1);}
	if (strlen(working_dir)<7) {printf("Invalid working_dir\n");exit(1);}
	if (port <=0)              {printf("Invalid classic_port\n");exit(1);}
	if (interval<1000)         {printf("Invalid sample_interval\n");exit(1);}
	printf("Config ok: %s:%d @ %d ms\n",host,port,interval);

	//check logfile and data directory
	log_message(LOG_FILE,"Daemon starting...");
	if (stat("data", &st) == -1) {
		printf("Creating data directory: %s/data/\n",working_dir);
		mkdir("data", 0755);
	}
	if (stat("data", &st) == -1) {printf("Data directory create failed\n");exit(1);}
}


/**
 * DAEMONIZE
 * Routines to fork off daemon child process,
 * handles chdir, io, locks and signals.
 *
 * @arg (int) sig
 * @return nil
 *
 **/
void daemonize(void) {
	int lfp;
	char str[10];

	if (getppid()==1) { /* already a daemon */
		log_message(LOG_FILE,"Daemon aready running");
		return;
	}
	i=fork();
	if (i<0) exit(1); /* fork error */
	if (i>0) exit(0); /* parent exits */
	/* child (daemon) continues */
	setsid(); /* obtain a new process group */
	for (i=getdtablesize();i>=0;--i) close(i); /* close all descriptors */
	i=open("/dev/null",O_RDWR); dup(i); dup(i); /* handle standard I/O */
	umask(022); /* set newly created file permissions */
	chdir(working_dir); /* change running directory */
	lfp=open(LOCK_FILE,O_RDWR|O_CREAT,0640);
	if (lfp<0) exit(1); /* can not open */
	if (lockf(lfp,F_TLOCK,0)<0) exit(0); /* can not lock */
	/* first instance continues */
	sprintf(str,"%d\n",getpid());
	write(lfp,str,strlen(str)); /* record pid to lockfile */
	signal(SIGCHLD,SIG_IGN); /* ignore child */
	signal(SIGTSTP,SIG_IGN); /* ignore tty signals */
	signal(SIGTTOU,SIG_IGN);
	signal(SIGTTIN,SIG_IGN);
	signal(SIGHUP,signal_handler); /* catch hangup signal */
	signal(SIGTERM,signal_handler); /* catch kill signal */
	log_message(LOG_FILE,"Daemon started");
}



/**
 * LOG_MESSAGE
 * Logger for daemon.
 *
 * @arg (char) filename
 * @arg (char) message
 * @return nil
 *
 **/
void log_message(char *filename, char *message) {
	time(&rawtime);
	lt= localtime(&rawtime);
	FILE *fp_log;
	fp_log= fopen(filename,"a");
	if (!fp_log) exit(1);
	fprintf(fp_log,"[%04d-%02d-%02d %02d:%02d:%02d] - %s\n",lt->tm_year+1900,lt->tm_mon+1,lt->tm_mday,lt->tm_hour,lt->tm_min,lt->tm_sec, message);
	fclose(fp_log);
}


/**
 * SIGNAL_HANDLER
 * Handler to catch kill process.
 *
 * @arg (int) sig
 * @return nil
 *
 **/
void signal_handler(int sig) {
	switch(sig) {
		case SIGHUP:
			log_message(LOG_FILE,"Hangup signal caught");
			break;
		case SIGTERM:
			log_message(LOG_FILE,"Terminate signal caught");
			exit(0);
			break;
	}
}

