
/**
 * NEWMODBUSD
 *
 * Daemon version of RossW's newmodbus C command line tool.
 * Querys midnite classic at specified interval and maintains two sets
 * of text files of the registers.
 * - use /var/tmp/ or similar ramdisk for prelim data directory as it writes about 1MB per day
 * - a blackbox folder is created inside /var/tmp containing...
 * - one current live file data.txt is the current snapshot, one register per line
 * - one file per day, yyyy-mm-dd.txt, one tab seperated sample per line
 *
 * License: GPLv3
 * Revision: $Rev$
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

#define VERSION 	"0.6"
#define LOG_FILE	"newmodbusd.log"
#define CONF_FILE	"newmodbusd.conf"
#define LOCK_FILE	"newmodbusd.lock"
#define ENABLE_FILE	"newmodbusd.enable"

int debug= 0;

//declare globals
char working_dir[255];  //working directory
char data_dir[255];     //data directory
char dfile[255];        //gen purpose filename
char dfile2[255];       //gen purpose filename
FILE *fp_t, *fp_l;      //file pointers, temp and log
struct stat st= {0};

int  interval;         //sample interval
int slen,c,i,n,r;      //sleep length, gen purpuse ints
long long unsigned millis, millis2; //millisec timestamps
struct timeval tv;     //microtimestamp
time_t rawtime;        //datestamp
struct tm *lt;         //localtime

char host[16];         //ip address of classic
int  port;             //modbus port
int sock= 0;           //classic socket
struct sockaddr_in sa; //internet socket addr. structure
struct hostent *hp;	   //result of host name lookup
char buf[302*2];	   //buffer to read modbus information (302 16-bit registers)
char id[101*2];	       //device ID and other scratchings.
int rs[21];            //log registers
int len;		       //length of received data
int addr;              //regsiter iterator

//functions
int dotask(void);
int modbus_read_registers(int addr, int number, int offset);
int classic_connect(void);
unsigned int modbus_register(int reg);
void init(void);
void daemonize(void);
void log_message(char *filename, char *message);
void signal_handler(int sig);

//Register 4391 prerounded wbjr
//Register 4373 is the SOC%


/**
 * MAIN
 * @arg nil
 * @return nil
 *
 */
int main(void) {

	//parse config and check dependencys
	init();

	//start daemon
	if (!debug) daemonize();

	while (1) {

		//wait for start of each sample interval
		gettimeofday(&tv,NULL);
		millis= (long long unsigned)tv.tv_sec*1000 + (tv.tv_usec/1000);
		slen= interval - (millis % interval);
		i= (millis+slen) % 1000;
		usleep (slen*1000);

		//make sample timestamp
		time(&rawtime);
		lt= localtime(&rawtime);

		//open data log file
		sprintf(dfile, "%s/%04d-%02d-%02d.txt", data_dir,lt->tm_year+1900,lt->tm_mon+1,lt->tm_mday);
		fp_l= fopen(dfile,"a");
		if (fp_l<0) {
			log_message(LOG_FILE, "Data file write error");
			exit(1);
		}

		//open temp data file
		sprintf(dfile, "%s/tempdata.txt", data_dir);
		fp_t= fopen(dfile,"w");
		if (fp_t<0) {
			log_message(LOG_FILE, "Temp file write error");
			exit(1);
		}

		//print date headers
		fprintf(fp_l, "[%02d:%02d:%02d.%03d] - ",lt->tm_hour,lt->tm_min,lt->tm_sec,i);
		fprintf(fp_t, "[%04d-%02d-%02d %02d:%02d:%02d.%03d]\n",lt->tm_year+1900,lt->tm_mon+1,lt->tm_mday,lt->tm_hour,lt->tm_min,lt->tm_sec,i);

		//read device and record data
		r= dotask(); //r is the error code

		if (debug) printf("[%02d:%02d:%02d] - %d\n",lt->tm_hour,lt->tm_min,lt->tm_sec,r);

		//entry endings
		fprintf(fp_l, "\n");
		fprintf(fp_t, "--\n");

		//log the duration
		gettimeofday(&tv,NULL);
		millis2= (long long unsigned)tv.tv_sec*1000 + (tv.tv_usec/1000);
		fprintf(fp_l, "%d - %llu ms \n", r, millis2-millis-slen);

		//close both files
		fclose(fp_l);
		fclose(fp_t);

		//finalise data file
		//rename is fast, thus data file has high availability to other processes
		//that is the plan anyway ;)
		sprintf(dfile,  "%s/tempdata.txt", data_dir);
		sprintf(dfile2, "%s/data.txt", data_dir);
		if (!r) rename(dfile,dfile2);
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

	//to allow local app to run:
	//presence of DISABLE file disconnects the modbus connection
	if (stat(ENABLE_FILE, &st) == -1) {
		if (sock) {	close(sock); sock=0; } //if still open, disconnect
		return(9);                         //skip this sample regardless
	}

	//read the entire main register range into the buffer array
	//for now ignore the half doz regs at 16385-16390
	i=0;
	if (!i) i= modbus_read_registers(4101, 50, 0  );
	if (!i) i= modbus_read_registers(4151, 50, 50 );
	if (!i) i= modbus_read_registers(4201, 50, 100);
	if (!i) i= modbus_read_registers(4251, 50, 150);
	if (!i) i= modbus_read_registers(4301, 50, 200);
	if (!i) i= modbus_read_registers(4351, 44, 250); ///highest reg is currently 4395

	//any problems skip write, so the prev entry stands
	//however we need to consider pre 1609 firmware, todo
	if (i) {
		close(sock); sock=0; //this will force a new socket attempt next interval
		log_message(LOG_FILE, "Modbus read error");
		return(i);
	}

	//write registers to our data files
	for (addr=4101; addr<=4395; addr++) {
		i= modbus_register(addr);

		//all to 'current' data file
		fprintf(fp_t, "%d:%d\n", addr, i);

		//write daily log registers
		c=0;
		while (rs[c]) {
			if (addr==rs[c]) {
				fprintf(fp_l, "%d:%d\t", addr, i);
				break;
			}
			c++;
		}
	}

	//done
	return (0);
}


/**
 * MODBUS_READ_REGISTERS
 * Reads modbus registers via network socket
 * Store in buffer array.
 *
 * @arg: (int) addr   , starting *register*
 * @arg: (int) number , number of 16-bit registers to read
 * @arg: (int) offset , where registers to be stored in the buffer
 * @return (int) 0 ok, >0 fail
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

	//open a new network socket to the classic
	if (!sock && classic_connect()) return(1);

	//socket request
	if (write(sock,&id, 12) != 12) return(2);

	//read modbus encapsulation
	len= read(sock, id, 7);
	if (len<0) return(3);

	//read response code and bytecount
	len= read(sock, id, 2);
	if (len<2 || (id[0]) != 3 || (id[1]&0xff) != number<<1) {
		return(4); //socket reply error
	}

	//read response as advised
	n= id[1]&0xff;			          // number of bytes to read
	len= read(sock, buf+(offset)*2, n);
	if (len <0) return(5);

	return(0);
}

/**
 * CLASSIC_CONNECT
 * Opens a network socket to the classic
 *
 * @arg: nil
 * @return (int) 0 on sucess, 1 on fail
 *
 */
int classic_connect(void) {

	if (debug) printf("Creating socket...\n");
	log_message(LOG_FILE, "Creating socket... ");

	//get & check host
	if ((hp = gethostbyname(host)) == NULL) { //this doesnt appear to trip on dead host????
		if (debug) printf("Host unreachable\n");
		log_message(LOG_FILE, "Host unreachable");
		return(1);
	}

	//create pointer
	bcopy((char *)hp->h_addr,(char *)&sa.sin_addr, hp->h_length);
	sa.sin_family = hp->h_addrtype;
	sa.sin_port= port>>8 | (port<<8 & 0xffff);

	//get network socket
	if ((sock = socket(hp->h_addrtype, SOCK_STREAM,0)) < 0) {
		if (debug) printf("Socket failed\n");
		log_message(LOG_FILE, "Socket failed"); //eg local app tieing up modbus
		return(1);
	}

	//connect to it
	if (connect(sock, (struct sockaddr *)&sa, sizeof(sa)) < 0) {
		if (debug) printf("Socket connect failed\n");
		log_message(LOG_FILE, "Socket connect failed");
		return(1);
	}

	//good to go
	if (debug) printf("Socket successful\n");
	log_message(LOG_FILE, "Socket successful");
	return (0);
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

	if (reg>=4101 && reg<=4395) {
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

	printf("Newmodbusd Version %s\n", VERSION);

	char line[90];
	FILE *fp_c;

	//already running
	if (getppid()==1) {
		printf("Daemon aready running\n");
		return;
	}

	//get working directory/pwd
	if (getcwd(working_dir, sizeof(working_dir)) == NULL) {printf("Get working dir failed\n");exit(1);}

	//parse config file
	printf("Parsing config file: %s\n",CONF_FILE);
	fp_c= fopen(CONF_FILE, "rt");
	if (!fp_c) {printf("Config file not found\n");exit(1);}
	while (fgets(line, 80, fp_c) != NULL) {
		if      (strstr(line,"classic_ip"))      sscanf(line, "%*s %15s", host);
		else if (strstr(line,"data_dir"))        sscanf(line, "%*s %59s", data_dir);
		else if (strstr(line,"classic_port"))    sscanf(line, "%*s %d", &port);
		else if (strstr(line,"sample_interval")) sscanf(line, "%*s %d", &interval);
		else if (strstr(line,"log_registers"))   sscanf(line, "%*s %d,%d,%d,%d,%d,%d,%d,%d,%d,%d", &rs[0],&rs[1],&rs[2],&rs[3],&rs[4],&rs[5],&rs[6],&rs[7],&rs[8],&rs[9]);
	}
	fclose(fp_c);
	if (strlen(host)<7)         {printf("Invalid classic_ip\n");exit(1);}
	if (strlen(data_dir)<7)     {printf("Invalid data_dir\n");exit(1);}
	if (port <=0)               {printf("Invalid classic_port\n");exit(1);}
	if (interval<1000)          {printf("Invalid sample_interval\n");exit(1);}
	if (interval>60000)         {printf("Invalid sample_interval\n");exit(1);}
	printf("Config ok: %s:%d @ %d ms\n",host,port,interval);

	//check dataroot directory
	if (stat(data_dir, &st) == -1) {printf("Data directory does not exist\n");exit(1);}

	//check (or create) bb  directory
	sprintf(data_dir, "%s/blackbox/", data_dir);
	if (stat(data_dir, &st) == -1) {
		printf("Creating data directory: %s\n",data_dir);
		mkdir(data_dir, 0775);
	}
	if (stat(data_dir, &st) == -1) {printf("Data directory create failed\n");exit(1);}
	log_message(LOG_FILE,"Daemon starting...");
}


/**
 * DAEMONIZE
 * Routines to fork off daemon child process,
 * handles chdir, io, locks and signals etc.
 *
 * @arg (int) sig
 * @return nil
 *
 **/
void daemonize(void) {
	int lfp;
	char str[10];

	/* already a daemon */
	if (getppid()==1) return;
	i=fork();
	if (i<0) exit(1); /* fork error */
	if (i>0) exit(0); /* parent exits */
	/* child (daemon) continues */
	setsid(); /* obtain a new process group */
	for (i=getdtablesize();i>=0;--i) close(i); /* close all descriptors */
	i=open("/dev/null",O_RDWR); dup(i); dup(i); /* handle standard I/O */
	umask(002); /* set newly created file permissions */
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
 * Logger for daemon messages.
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
 * Handler to catch signals.
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

