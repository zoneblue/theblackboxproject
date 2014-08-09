BINARY FOLDER INFO
The Blackbox Project
$Rev$



This folder contains c binarys and other scripts that provide services to the web application.
Copy the contents of this folder to something like /opt/blackbox/

Precompiled binarys are included for linux ARMv7 and x86.

Choose either newmodbus and 1+ minute cron invoked samples, or newmodbusd in daemon mode running at 1+ second samples.
In the former case newmodbus is invoked by /blackbox/cronjobs.php. In the latter you will need to start this manually after boot using a non privilidged system user, or as a init startup script.

NEWMODBUS
---------------
Written By Ross, this is the orginal newmodbus. Its a super lightweight, very fast modbus tool designed to connect, read and disconnnect. It does have some other functions including write and http push, see -h.

NEWMODBUSD
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

Copy the bin folder somewhere sensible for example /opt/blackbox 
Choose a temp data location, usually /var/tmp (as ramdisk)
Choose a permanent data location something like /home/blackbox/data/classic
Both folders need to be writable by the newmodbusd user.

Edit newmodbusd.conf. 

Start daemon ./newmodbusd 

Check ps -ef | grep newmodbusd to ensure its running correctly 

Use ./killnewmodbusd to stop it.

You can also temporarily disable newmodbusd by renaming newmodbusd.enable to newmodbusd.disable, and reverse to restart. This may be useful so that other applications may connect to the classic.

