=INSTALLATION=

Prerequsites
------------
-lamp, webserver, mysql, php5.3, 
-gd
-bzip2


Instructions
------------
To manage system permisions, this is what is easy to do: 
Create a system user, say cubie,
Then add cubie to the www-data group. 
Make your docroot owned by cubie:www-data. 
Run newmodbus and the cronjobs by the user cubie.
That way the two users can share happily.

1. copy the whole blackbox folder somewhere within your web servers docroot
2. make sure the /path/to/doc/root/blackbox/tmp/ is writable by the webserver.
3. edit /path/to/doc/root/blackbox/config/config-main.php (may need to rename from config-main-skel.php) to give database permissions (requires table create).
4. edit module configs in /path/to/doc/root/blackbox/modules to suit your devices. 
5. web browse to http://yourLanIp/blackbox/setup.php, if you get no initial error then your dbs perms are ok
6. you must then click check db in the sidebar, and 'Proceed', to populate module tables
7. Copy the bin folder from the installation, to /opt/blackbox
   
Decide on newmodbus or newmodbusd. Former for 60s calls, the latter for 1s calls.

NEWMODBUS
1. Add cronjob:
* * * * *  /usr/bin/php-cgi -f /home/www-data/html/blackbox/cronjobs.php >/dev/null 2>&1
edit for your file locations, see also top of cronjobs.php for more info

NEWMODBUSD
1. Try the precompiled newmodbusd first, its compiled for ARMv7. Failing that compile from the source gcc -o newmodbusd newmodbusd.c
2. Use (or create) a ramdisk for newmodbusd to write its prolific daily logs to. On cubian this is /var/tmp. 
3. Edit /opt/blackbox/newmodbus.conf to suit your classic.
4. Add cronjobs:
* * * * *  /usr/bin/php-cgi -f /home/www-data/html/blackbox/cronjobs.php >/dev/null 2>&1
15 1 * * * /opt/blackbox/nightly.sh
The fist one does the one minute database writes. The second one does daily newmodbusd log rotation. 
5. Create a folder say /home/data that the daily zips can live permanently. Edit /opt/blackbox/nightly.sh to suit your file locations.
6. Start newmodusd with /opt/blackbox/newmodbusd Then check its running with ps -ef | grep newmodbusd . Check the /var/tmp/blackbox/data.txt file is being updated.

Sorry its so complicated, at the moment.







