
/**
 * NEWMODBUS
 *
 * RossW's newmodbus C command line tool.
 * Communicates with Midnite classic.
 * Its priamry mode is to take one sample, return it to stdout/log/http then close.
 *
 * Revision: $Rev: 33 $
 * License: GPLv3
 *
 **/


#define Version "1.0.19"		// version

/* Changes.
 7-Jun-13 1.0.10	Recoded socket open to classic to retry every 100ms rather than 1s
 			and if -d specified, display a dot each failed connection attempt.
 8-Jun-13 1.0.11	Added mdbus_internal_write in prep for setting date/time
 8-Jun-13 1.0.12	Added -c for continuous operation, displays data every second,
 			writes data (if requested) on the 5-minute.
			Added -r for "close-and-reopen" classic mode. (more co-operative
			with other modbus applications)
10-Jun-13 1.0.14	Changed logging of reset registers to hex
11-Jun-13 1.0.15	Don't abort on error opening remote logging
13-Jun-13 1.0.16	Add -l (local log) and -ixxx (interval) switches
23-Jun-13 1.0.17	Change -l to require path to save to
23-Jun-13 1.0.18	Modbus reads are not fatal now, in the pre-fetch phase anyway.
11-Aug-13 1.0.19	Fixed error in resting reason text, updated the online help,
			added -W to set remote logging host, made -r and -i set -c,
			detect no host and die gracefully, added some extra debugging points.
*/

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <stdlib.h>
#include <errno.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <netdb.h>
#include <time.h>

// Easier to declare these here, I re-use several in various functions.
int len;               //length of received data
// communications with classic
int s=0;                 //socket descriptor
struct sockaddr_in sa; //internet socket addr. structure
// communications to RW
int ranges;                 //socket descriptor
struct sockaddr_in rangesa; //internet socket addr. structure

struct hostent *hp;	//result of host name lookup
char buf[302*2];	//buffer to read modbus information (202 16-bit registers)
char id[101*2];	// device ID and other scratchings.
char *host;            //pointer to remote host name
char *replyptr;	// used in http reply stripping
int port=502;		// modbus port number on classic
char val2[20];	// value parsing
int addr;
int loop=1;
int n;
int endaddr;
int scale=1;
int s1, s2;
int serialno=0;	// serial number (required if jumpers not set to unlock)
int timeset=0;	// time set?
int debug=0;		// debug flag
int writeremote=0;	// http write data when we are done
char storefour[4];	// when unlocking modbus, store data here
int showleds=0;	// simulate LEDs?

/* Uses the "id" buffer to write (n) bytes */
void modbus_write_registers(int n, int dst)
{
	if(serialno)		// if we have a serial number, we should use it
	{
		if(debug) printf("Unlocking classic Serial No %d\n",serialno);
		storefour[0]=id[13]; storefour[1]=id[14]; storefour[2]=id[15]; storefour[3]=id[16];
		id[13]=(serialno>>24)&0xff;
		id[14]=(serialno>>16)&0xff;
		id[15]=(serialno>>8)&0xff;
		id[16]=(serialno>>0)&0xff;
		serialno=0;
		//modbus_write_registers(4,28673);
		//printf("Sleep 1000ms\n"); sleep(1);
		modbus_write_registers(4,20492);
		id[13]=storefour[0];id[14]=storefour[1];id[15]=storefour[2];id[16]=storefour[3];
		//printf("Sleep 1000ms\n"); sleep(1);
  	}
	id[0]=0;		// Transaction ID MSB
	id[1]=2;		// Transaction ID LSB
	id[2]=0;		// modbus protocol 0 MSB
	id[3]=0;		// modbus protocol 0 LSB
	id[4]=0;		// bytes following MSB
	id[5]=n+7;		// bytes following LSB
	id[6]=255;		// ident
	id[7]=16;		// write multiple registers
	id[8]= (dst-1)>>8;	// address MSB
	id[9]= (dst-1)&0xff;	// address LSB
	id[10]=0;		// number of 16-bit registers MSB
	id[11]=n>>1;		// number of 16-bit registers LSB
	id[12]=n;		// byte count.

	// data already written to id[13] onwards.
	if(debug)
	{
		printf("Write");
		for(n=0; n<(id[5]&0xff)+6; n++) printf(" %02x", id[n]&0xff); printf("\n");
	}
	n=(id[5]&0xff)+6;	// number of bytes to write
	if(write(s,&id, n) != n) {
	      fprintf(stderr, "write error\n");
	      exit(1);
	}

	len = read(s, id, 7);
	if(debug)
	{
		printf("Read %d (header) bytes: %0x %0x %0x %0x %0x %0x %0x\n",
			len, id[0], id[1], id[2], id[3], id[4], id[5], id[6]&0xff);
	}
	len = read(s, id, 5);		// read response
	if(debug)
	{
		printf("Read %d response bytes:", len);
		for(n=0; n<len; n++) printf(" %02x", id[n]&0xff); printf("\n");
	}
	if(len<5 || id[0]!=16)
	{
		fprintf(stderr, "modbus write error reply=%d bytes, codes %02x %02x\n", len,id[0]&0xff,id[1]&0xff);
		exit(1);
	}
}

// Write data to id[16] to id[16+n-1] before calling.
void modbus_write_internal(int device, int n, int dst)	// device 7 for datetime, n=number of OCTETS to write, dst=addr to start
{
	int x;
	if(debug) printf("Device=%d, %d bytes to addr %d\n",device,n,dst);
	id[0]=0;		// Transaction ID MSB
	id[1]=2;		// Transaction ID LSB
	id[2]=0;		// modbus protocol 0 MSB
	id[3]=0;		// modbus protocol 0 LSB
	id[4]=0;		// bytes following MSB
	id[5]=n+9;		// bytes following LSB
	id[6]=255;		// ident
	id[7]=105;		// write internal command
	id[8]=device;		// device ID
	id[9]=n;		// number of bytes of data
	id[10]=0; id[11]=0;	// 16 bits reserved
	id[15]= (dst-0)>>24;	// address MSB bits 31-24
	id[14]= ((dst-0)>>16)&0xff;	// address MSB bits 23-16
	id[13]= ((dst-0)>>8)&0xff;	// address MSB bits 15-8
	id[12]= (dst-0)&0xff;	// address LSB bits 7-0

	// data already written to id[16] onwards.
	if(debug)
	{
		printf("Write");
		for(x=0; x<(id[5]&0xff)+7; x++) printf(" %02x", id[x]&0xff); printf("\n");
	}
	x=(id[5]&0xff)+7;	// number of bytes to write
	if(write(s,&id, x) != x) {
	      fprintf(stderr, "write error\n");
	      exit(1);
	}
	len = read(s, id, 7);
	if(debug)
	{
		printf("Read %d (header) bytes: %0x %0x %0x %0x %0x %0x %0x\n",
			len, id[0], id[1], id[2], id[3], id[4], id[5], id[6]&0xff);
	}
	len = read(s, id, 200);		// read response
	if(debug)
	{
		printf("Read %d response bytes:", len);
		for(x=0; x<len; x++) printf(" %02x", id[x]&0xff); printf("\n");
	}
	if(len!=9 || id[0]!=0x69 || id[1]!=device || id[2]!=n)
	{
		fprintf(stderr, "modbus write error reply=%d bytes, device=%d, expected %d data. Codes %02x %02x\n", len, device,n,id[0]&0xff,id[1]&0xff);
		exit(1);
	}
}


int modbus_read_registers(int addr, int number, int offset)	// starting *register*, number of 16-bit reg to read, offset to buffer
{
	id[0]=0;		// Transaction ID MSB
	id[1]=2;		// Transaction ID LSB
	id[2]=0;		// modbus protocol 0 MSB
	id[3]=0;		// modbus protocol 0 LSB
	id[4]=0;		// bytes following MSB
	id[5]=6;		// bytes following LSB
	id[6]=255;		// ident
	id[7]=3;		// read multiple registers/addresses
	id[8]= (addr-1)>>8;	// starting address (MSB)
	id[9]= (addr-1)&0xff;	// starting address (LSB)
	id[10]=0;		// Addresses to read (MSB)
	id[11]=number;		// Addresses to read (LSB)

	if(write(s,&id, 12) != 12) {
	      fprintf(stderr, "write error\n");
	      exit(1);
	}

	len = read(s, id, 7);		// read modbus encapsulation
	len = read(s, id, 2);		// read response code and bytecount
	if(len<2 || (id[0]) != 3 || (id[1]&0xff) != number<<1)
	{
		fprintf(stderr, "modbus reply error\n");
		return(1);
	}
	n=id[1]&0xff;			// number of bytes to read
	len=read(s, buf+(offset)*2, n);	// read response as advised
	if(debug)
	{
		printf("Read %d (data) bytes from addr %d", len,addr);
		//for(n=0; n<len; n++) printf(" %02x", buf[n+addr-4101]&0xff);  printf("\n");
		if(debug>1)
		{
			for(n=0; n<(len>>1); n++)
			{
				if(n%25 ==0) printf("\n%04d:",addr+n);
				printf(" %02x%02x", buf[(offset+n)<<1]&0xff, buf[((offset+n)<<1)+1]&0xff);
			}
		}
		printf("\n");
	}
	return(0);
}

unsigned int modbus_register(int reg)
{
	if(reg<=4350 && reg>4100)
		return((buf[(reg-4101)<<1]&0xff)<<8 | (buf[((reg-4101)<<1)+1] & 0xff));
	else	// otherwise it's a special register read not from the buffer
	{
		if(debug>1) printf("Reading additional register outside cached range: %d\n",reg);
		if(modbus_read_registers(reg, 1, 201))		// read device serial number
		{
			printf("Failed to read serial number\n");
			exit(1);
		}
		return((buf[(201)<<1]&0xff)<<8 | (buf[((201)<<1)+1] & 0xff));
	}
}


int main(int argc, char **argv)
{
        extern char *optarg;
        extern int optind, opterr;
        register int op;
	typedef union
	{
		struct
		{
			unsigned int sec : 6;	// bits 5-0 are seconds
			unsigned int void1 : 2;	// unused
			unsigned int min : 6;	// bits 13-8 are minutes
			unsigned int void2 : 2;	// unused
			unsigned int hrs : 5;	// bits 20-16 are hours
			unsigned int void3 : 3;	// unused
			unsigned int dow : 3;	// bits 26-24 are day-of-week
			unsigned int void4 : 5;	// unused
		}time;
		struct
		{
			unsigned int day : 5;	// bits 4-0 are day of month
			unsigned int void1 : 3;	// unused
			unsigned int month : 4;	// bits 11-8 are month
			unsigned int void2 : 4;	// unused
			unsigned int year : 12;	// bits 27-16 are hours
			unsigned int void3 : 4;	// unused
		}date;
		unsigned int raw;
	}classictime;
	classictime midnite;
	time_t rawtime;
	struct tm *systemtime;
	int mode=8|1;		// mode 1 says write data once then exit
	int loginterval=0;	// if interval=0 log on the 5-minute. Otherwise, every (loginterval) seconds.
	FILE *logfile;		// local log output
	char *log_path="";	// path to prepend to file.
	char remoteloghost[128]="ranges.albury.net.au";		// hostname of remote log
	char remotelogpath[128]="/midnight/ranges-submit.cgi";	// path of remote log

	while ((op = getopt(argc, argv, "f:i:l:s:W:cdprtvw?")) != EOF)
	{
	if(debug>1) printf("scan %c\n",op);
	switch (op)
	{
		case 'c':	/* continuous mode */
			mode|=2;	// flag continuous mode
			mode&=0xfe;	// clear log data now
			break;
		case 'd':	/* debug information */
			debug++;
			break;
		case 'f':	/* use config file named */
			printf("Unimplemented\n"); exit(0);
			printf("Use config file %s\n",optarg);
			break;
		case 'i':	/* logging interval (seconds) */
			loginterval=atoi(optarg);
			if(debug) printf("Log interval %d seconds.\n",loginterval);
			mode|=2;	// flag continuous mode
			mode&=0xfe;	// clear log data now
			break;
		case 'l':	/* Write data to local file */
			writeremote|=2;
			log_path=optarg;	// record the path for later
			break;
		case 'p':	/* Print-Info */
			showleds++;
			break;
		case 'r':	/* Close-and-REOPEN connection each time */
			mode|=8;
			mode|=2;	// flag continuous mode
			mode&=0xfe;	// clear log data now
			break;
		case 's':	/* Serial number */
			serialno=atoi(optarg);
			printf("Serial No %d\n",serialno);
			break;
		case 't':	/* set classic time */
			timeset++;
			break;
		case 'v':	/* show Version */
			printf("Version %s\n",Version);
			break;
		case 'w':	/* Write data to RossW */
			writeremote|=1;
			//writeremote=atoi(optarg);
			break;
		case 'W':	/* Write data to other server */
			writeremote|=1;
			strncpy(remoteloghost, optarg, strchr(optarg,'/')-optarg);	// get hostname
			remoteloghost[strchr(optarg,'/')-optarg]=0;	// terminate string
			strcpy(remotelogpath,strchr(optarg,'/'));	// and path
			if(debug) printf("Remote logging to host %s path %s\n", remoteloghost, remotelogpath);
			break;
		case '?':	/* help */
		default:
			printf("Usage: %s [-cdprtvw?] [-filsW value] fqdn|ip [register[-register][op]]\n",argv[0]);
			printf("  -c       continuous mode. Loops forever.\n");
			printf("  -d       debugging info (repeat for more verbosity)\n");
		//	printf("  -f       read configuration file\n");
			printf("  -i x     Local log Interval (seconds) (forces -c continuous mode)\n");
			printf("  -l path  Sets local log path, default is current directory.\n");
			printf("  -p       print human-readable summary.\n");
			printf("  -r       close and re-open connection each read (forces -c).\n");
			printf("  -s       set serial number (if classic is locked) (normally, code will read serial number from device)\n");
			printf("  -t       set classic date/time (mngp seems to overwrite time every 30 seconds anyway)\n");
			printf("  -v       Show version (%s)\n",Version);
			printf("  -w       write data to RossW\n");
			exit(0);
	}
	}


	if(debug>1) printf("Command line scanned\n");



  if(argc<2)
  {
	printf("Incorrect Usage: %s -? for help\n",argv[0]);
	exit(0);
  }

	do
	{
	if(!s)
	{
	host=argv[optind];
	if(host==0) { printf("No host specified\n"); exit(0); }
	if(debug>1) printf("Looking up host %s\n",host);

	if((hp = gethostbyname(host)) == NULL) {
       		fprintf(stderr,"%s: no such host?\n", host);
       		exit(1);
	}


 	if(debug>1) printf("creating pointer\n");
	bcopy((char *)hp->h_addr, (char *)&sa.sin_addr, hp->h_length);
	sa.sin_family = hp->h_addrtype;

	sa.sin_port=port>>8 | (port<<8 & 0xffff);

	if((s = socket(hp->h_addrtype, SOCK_STREAM,0)) < 0) {
	        perror("socket");
	        exit(1);
	}

		// Attempt to connect up to 5 seconds in case something else is already using it
	n=50;	// timeout/retry counter
	while(connect(s, &sa, sizeof(sa)) < 0) {
		if(!--n) {
			if(debug) printf("\n");
	        	perror("connect");
	        	exit(1);
		}
		usleep(100000);		// 100,000 us = 100ms
		if(debug) {printf("."); fflush(stdout); }
		// if connect failed, we need to re-create the socket
		if((s = socket(hp->h_addrtype, SOCK_STREAM,0)) < 0) {
			if(debug) printf("\n");
	        	perror("socket");
	        	exit(1);
		}
	}
}

	if(debug && n<50) printf("\n");
	serialno=modbus_register(28673)<<16 | modbus_register(28674);	// read device serial number
	if(debug) printf("Read serial number %d from device\n",serialno);

	modbus_read_registers(4101, 50, 0);
	modbus_read_registers(4151, 50, 50);
	modbus_read_registers(4201, 50, 100);
	modbus_read_registers(4251, 50, 150);
	modbus_read_registers(4301, 50, 200);

  for(n=0; n<8; n++) id[n]=buf[(4210-4101)*2+n^1];	// read ID string
  id[8]=0;						// force termination in case it wasn't already


  if(showleds)			// print system info, and quit
  {
	printf("%s Display Panel %s, RossW\n",id,Version);
	char const* BatChargeState[]={"Resting","","","Absorb","BulkMppt","Float","FloatMppt","Equalize","","","HyperVoc","","","","","","","","EqMppt"};
	n=modbus_register(4120)>>8;
	printf("State %s ",BatChargeState[n]);
	if(n==0)
	{
	char const* RestingReason[]={
		"Not Defined",	// 0
		"Not enough power available",
		"Insane Ibatt Measurement",
		"Negative Current (load on PV input ?)",
		"PV Input Voltage lower than Battery V",
		"Too low of power out",
		"FET temperature too high",
		"Ground Fault Detected",
		"Arc Fault Detected",
		"Too much negative current while operating (backfeed from battery out of PV input)",
		"Battery is less than 8.0 Volts",
		"V is rising too slowly. Low Light or bad connection",
		"Voc has gone down from last Voc or low light.",
		"Voc has gone up from last Voc enough to be suspicious.",
		"V is rising too slowly. Low Light or bad connection",		// 14 same as 11
		"Voc has gone down from last Voc or low light.",		// 15 same as 12
		"Mppt MODE is OFF",
		"PV input is higher than operation range",
		"PV input is higher than operation range",
 		"PV input is higher than operation range",
		"","",
		"Average Battery Voltage is too high above set point",		//22
		"",""
		"Battery Voltage too high Overshoot",
 		"Mode changed while running",
		"bridge center == 1023 (R132 fail)",
		"NOT Resting but RELAY is not engaged",
		"WIND GRAPH is illegal",
		"Detected too high PEAK output current",
		"Peak negative battery current > 90.0 amps",
		"Aux 2 input commanded Classic off.",
		"OCP in a mode other than Solar or PV-Uset",
		"Peak negative battery current > 90.0 amps"};
		printf("because %s ",RestingReason[modbus_register(4275)]);
	}
	printf("\n");


	printf("Firmware %d\n",modbus_register(16388)<<16 | modbus_register(16387));
  	midnite.raw=modbus_register(4214) | modbus_register(4215)<<16;
  	printf("ClassicTime %02d:%02d:%02d ", midnite.time.hrs, midnite.time.min, midnite.time.sec);

  	midnite.raw=modbus_register(4216) | modbus_register(4217)<<16;
  	printf(" %02d/%02d/%04d\n", midnite.date.day, midnite.date.month, midnite.date.year);
	printf("%5d Watts out\n",modbus_register(4119));
	printf("%5.1f Volts (Battery)\n",(float)modbus_register(4115)/10);
	printf("%5.1f Volts (PV)\n",(float)modbus_register(4116)/10);
	printf("%5.1f Amps (Battery)\n",(float)modbus_register(4117)/10);
	printf("%5.1f kWh today (%d amphours)\n",(float)modbus_register(4118)/10, modbus_register(4125));
	exit(0);
	//4275 = resting reason
  }



  printf("ID %s\n",id);


  midnite.raw=modbus_register(4214) | modbus_register(4215)<<16;
  printf("ClassicTime %02d:%02d:%02d ", midnite.time.hrs, midnite.time.min, midnite.time.sec);

  midnite.raw=modbus_register(4216) | modbus_register(4217)<<16;
  printf(" %02d/%02d/%04d\n", midnite.date.day, midnite.date.month, midnite.date.year);

  time(&rawtime);
  systemtime=localtime(&rawtime);
  if(loginterval)
  {
	if(!(rawtime%loginterval))
  	{
		if(debug) printf("Time to log\n");
		if(! (mode&5) ) mode|=1;
	} else {
		if(mode&4) mode&=0xfb;
	}
  } else {
	if(! (systemtime->tm_min%2))
	{
		if(! (mode&5) ) mode|=1;
	} else {
		if(mode&4) mode&=0xfb;
	}
  }

  if(timeset)
  {
  	printf("SystemTime ");
  	printf("%02d:%02d:%02d %02d/%02d/%04d\n", systemtime->tm_hour, systemtime->tm_min, systemtime->tm_sec,
					systemtime->tm_mday, systemtime->tm_mon+1, systemtime->tm_year+1900);

	id[16]=0;	// table entries 0-7 unused but must be sent
	id[17]=0;
	id[18]=0;
	id[19]=0;
	id[20]=0;
	id[21]=0;
	id[22]=0;
	id[23]=0;
	id[24]=systemtime->tm_wday;		// [8]=day of week
	id[25]=systemtime->tm_hour;		// [9]=hour
	id[26]=systemtime->tm_min;		// [10]=minute
	id[27]=systemtime->tm_sec;		// [11]=second
	id[28]=(systemtime->tm_year+1900)>>8;	// [12]=year top 8 bits
	id[29]=(systemtime->tm_year+1900)&0xff;	// [13]=year lower 8 bits
	id[30]=systemtime->tm_mon+1;		// [14]=month
	id[31]=systemtime->tm_mday;		// [15]=day-of-month
	id[32]=0;				// [16]=undef
	id[33]=0;				// [17]=undef
	id[34]=systemtime->tm_yday>>8;		// [18]=day-of-year top 8 bits
	id[35]=systemtime->tm_yday&0xff;	// [19]=day-of-year lower 8 bits

	if(debug>1)
	{
		printf("DateTime data to classic:");
		for(n=0; n<20; n++)
		{
			if(n%25 ==0) printf("\n%04d:",8+n);
			printf(" %02x", id[16+n]&0xff);
		}
		printf("\n");
	}

	modbus_write_internal(7, 20, 0);	// device 7 for datetime, 20 bytes from address 0
  }

  loop=optind;
  while(++loop < argc)
  {
	n=sscanf(argv[loop], "%d%[-=/%>]%[^f=/%>]%[f=/%>]%d", &addr, &s1, &val2, &s2, &scale);

	endaddr=atoi(val2);
	if(debug) printf("\narg: -%s-  n=%d a1=%d s1=%x v2=%s/a2=%d s2=%x a3=%d\n",argv[loop], n, addr, s1, val2, endaddr, s2, scale);

	s1&=0xff; s2&=0xff;	// perculiar upper bit swarf
	if(n==1)
	{
		endaddr=addr;
		scale=1;
		s1=0;
	}

	if(n>2)
	{
		switch (s1)
		{
			case '-':		// range of addresses
				if(endaddr<addr)
				{
					printf("Invalid range:%d-%d\n",addr,endaddr);
					exit(0);
				}
				if(n>4)
				{
					switch(s2)	// scale?
					{
						case '/':	// divide
						case '*':	// multiply
						case '>':	// shift right
						case '%':	// modulo
						case 'f':	// convert C to F
							s1=s2;	// remember the operator
							break;
						default:
							printf("Invalid scale cmd %c\n",s2);
							exit(0);
					}
				} else scale=1;
				break;

			case '/':		// scale (divide)
			case '*':		// multiply
			case '>':		// shift right
			case '%':		// modulo
			case '=':		// assign
			case 'f':		// convert to C to F
				scale=endaddr;
				endaddr=addr;
				break;
			default:
				printf("No case!\n");
		}
	}

	while(addr <= endaddr)
	{
		n=modbus_register(addr);
		switch (s1)
		{
			case '/':		// divide
				printf("%d %.1f\n", addr, (float)n/scale);
				break;
			case '%':		// modulo
				printf("%d %d\n", addr, n%scale);
				break;
			case '*':		// multiply
				printf("%d %ld\n", addr, n*scale);
				break;
			case '>':		// shift right
				printf("%d %d\n", addr, n>>scale);
				break;
			case 'f':		// scale and convert deg C to F
				printf("%d %.1f\n", addr, (float)n/scale*1.8+32.0);
				break;
			case '=':		// set to decimal number. CAUTION!
				if(scale==0 && ((val2[0]&0xff) != '0'))
				{
					if(debug) printf("%d+ assigned %d byte string argument %s\n",addr,strlen(val2),val2);
					for(n=0; n<=((strlen(val2)>>1)<<1)+1; n++) id[13+(n^1)]=val2[n]&0xff;
				} else {
					if(debug) printf("%d assigned %d\n",addr,scale);
					id[13]=scale>>8;
					id[14]=scale&0xff;
					n=2;
				}
				modbus_write_registers(n, addr);
				break;
			default:
				printf("%d %d (0x%X)\n", addr, n, n);
		}
	addr++;
	}
	}

	if(showleds)
	{
		n=modbus_register(4165);	// AUX

	}

	if(!(mode & 8))
	{
		if(debug) printf("Closing connection to classic\n");
		close(s);
		s=0;
	}


	// mode=:xxxx1 write data
	// mode=:xxx1x continuous mode (loop after writing)
	// mode=:xx1xx data written
	// mode=:x1xxx keep connection open
	if(writeremote && (mode & 1))		/* Experimental code to write data */
	{
		if(debug) printf("Enter logging code\n");
		mode|=4;		// flag we have written data
		mode&=0xfe;		// remove write flag
		for(loop=0; loop<8; loop++) id[loop]=buf[(4210-4101)*2+loop^1];	// read ID string
		id[8]=0;						// force termination in case it wasn't already
		for(addr=4115; addr<=4118; addr++)
			sprintf(id+strlen(id),"&%d=%.1f", addr, (float)modbus_register(addr)/10);

		sprintf(id+strlen(id),"&%d=%d", addr, modbus_register(addr));

		addr=4120; sprintf(id+strlen(id),"&%d=%d", addr, modbus_register(addr)>>8);

		for(addr=4121; addr<=4122; addr++)
			sprintf(id+strlen(id),"&%d=%.1f", addr, (float)modbus_register(addr)/10);

		addr=4125; sprintf(id+strlen(id),"&%d=%d", addr, modbus_register(addr));

		for(addr=4132; addr<=4134; addr++)
			sprintf(id+strlen(id),"&%d=%.1f", addr, (float)modbus_register(addr)/10);

		addr=4275; sprintf(id+strlen(id),"&%d=%d", addr, modbus_register(addr));

		for(addr=4130; addr<=4131; addr++)
			sprintf(id+strlen(id),"&%d=0x%04x", addr, modbus_register(addr));
		for(addr=4341; addr<=4344; addr++)
			sprintf(id+strlen(id),"&%d=0x%04x", addr, modbus_register(addr));

		if(debug>1) printf("Assembled logdata\n");


		if(writeremote&1)
		{
			//if((hp = gethostbyname("ranges.albury.net.au")) == NULL)
			if((hp = gethostbyname(remoteloghost)) == NULL)
			{
				fprintf(stderr,"%s: no such host?\n", remoteloghost);
				//exit(1);	// do not exit on error, just try again later
			}
			bcopy((char *)hp->h_addr, (char *)&rangesa.sin_addr, hp->h_length);
			rangesa.sin_family = hp->h_addrtype;

			rangesa.sin_port=80>>8 | (80<<8 & 0xffff);

			if((ranges = socket(hp->h_addrtype, SOCK_STREAM,0)) < 0)
			{
				perror("socket");
				//exit(1);	// do not exit on error, just try again later
			}

			if(connect(ranges, &rangesa, sizeof(rangesa)) < 0)
			{
				perror("connect");
				//exit(1);	// do not exit on error, just try again later
			}
			if(debug>1) printf("Opened connection to Ross\n");



		  	//sprintf(buf,"GET /midnight/ranges-submit.cgi?u=%d&ver=%s&ID=%s http/1.0\nhost: ranges.albury.net.au\n\n",serialno,Version,id);
		  	sprintf(buf,"GET %s?u=%d&ver=%s&ID=%s http/1.0\nhost: %s\n\n",remotelogpath,serialno,Version,id,remoteloghost);
		  	if(addr=write(ranges,&buf, strlen(buf)) != strlen(buf))
			{
				perror("Failed");
				fprintf(stderr, "Failed write %d bytes to Ross (%d in buffer)\n==>%s\n",addr,strlen(buf),buf);
				//exit(1);	// do not exit on error, just try again later
		  	}



			len=read(ranges, buf, 500);	// read response as advised
			buf[len]=0;
			replyptr=strstr(buf,"\r\n\r\n");
			if(replyptr>0) replyptr+=4;
			if(debug)
			{
				printf("Read %d (data) bytes http reply", len);

				if(debug>1)
				{
					for(n=0; n<len; n++)
					{
						if(n%25 ==0) printf("\n%03d:",addr+n);
						printf(" %02x", buf[n]&0xff);
					}
				}
				printf("\nheader ends at byte %d\n",replyptr-buf);
			}
			if((replyptr-buf)<len) printf("%s\n",replyptr);

			close(ranges);
		}

		if(writeremote&2)
		{
			sprintf(buf,"%s%d.log",log_path,serialno);
			if(debug) printf("Opening Local logfile %s\n",log_path);
			if((logfile=fopen(buf,"a")) == NULL)
		        	printf("Failed to open local log %s!\n",buf);
			else if(debug) printf("Opened local log %s\n",buf);

		  	fprintf(logfile,"%02d:%02d:%02d %02d/%02d/%04d u=%d&ID=%s\n",
				systemtime->tm_hour, systemtime->tm_min, systemtime->tm_sec,
				systemtime->tm_mday, systemtime->tm_mon+1, systemtime->tm_year+1900,
				serialno,id);
			fclose(logfile);
		}


	}

	if(mode & 2) sleep(1);
	} while(mode & 2);	// while in continuous mode


}



