NEWMODBUSD

Description
---------------
This is a reworked version of RossW's newmodbus program coded in c.

Once started newmodbusd runs in the background on a continuous basis maintaining a single open modbus connection to the classic. Doing this works around the classics network stack bug, and also allows sample rates as fast as about 100ms, 
(1-15s recomended).

The main differences in the code, is the removal of all command line parameters, http logging, register operations etc.

This version keeps the modbus connection alive, and therefore will prevent access to the classic via the local app. It also trys only once each interval, any failed connect gets ignored until the next interval.

The sample interval timing is such that the code waits til the start of each iunterval to read the classic, regardless of how long the sample takes (typically 15ms). When set to 1000ms intervals it will start the read +/-1ms at the start of each second. 

Outut is in two forms, data.txt (in the working directory), is a complete listing of the main register range, one per line. data/ contains one file per day recording several key regsiters only. This folder could grow to a large size, and its recomended that you post process it using cron jobs to summarise and store hourly or daily agregations.

Note that newmodbusd is in early alpha stage, and needs someone better versed in c to take a look over it. 
Note also it probably requires classic firmware 1609 or better.

The binary here is compiled in Cubieboard (ARM v7 instruction set), should work on Beaglebone, and Rasberry Pi probably.

Installation.
--------------
Copy the binary and the conf file somewhere using for example /opt/newmodbusd, then add an empty folder /opt/newmodbusd/data, chmod newmodbusd 0755, then edit newmodbusd.conf. Run as un unpriviledged user with ./newmodbusd 
the the command ps -ef|grep newmodbusd when runngin correctly will show the process id. Use ./killnewmodbusd to stop it.

