[Home](http://code.google.com/p/theblackboxproject/)
[Contents](Documentation.md)

## Installation guide ##

This document assumes you have a working linux OS on your board. It will take you through the various steps to get blackbox running. At present the web app installer is minimal, and you will have to do some steps manually. In future we hope to find a way to remove all of this.

### Operating system ###

For the purposes of this document we assume you have a debian-esque version of linux. Alter for other distros/package managers as appropriate.

First you need a webserver, database and PHP. We are going to be using the command line version of PHP, and lighttp with fastcgi, and the mysql database, but you can set it up in many ways.

If you already have LAMP running skip ahead to section 2.

1. ensure correct time, date and timezone. Make sure you have some reliable means of keeping the system time up to date. Almost all ARM boards are bereft of real time clocks (RTC). Use ntp, or ntpdate, or similar if you have a internet connection available. Failing that, ensure a reliable power supply for the board and never reboot it.

root@Cubian:# dpkg-reconfigure tzdata
root@Cubian:# ntpdate nz.pool.ntp.org #or
root@Cubian:# apt-get install ntp

2. make sure the arm board networking is setup using a static ip. And that your RE gear is also static (or, in each case, that your router is set to assign the same ip address to these devices).

root@Cubian:# nano /etc/network/interfaces
{edit as needed}

3. Install a lamp stack. Well give lighty a go, its pretty easy:

root@cubie:# apt-get install mysql-server lighttpd
root@cubie:# apt-get install php5-common php5-cgi php5 php5-mysql php5-gd
root@cubie:# lighty-enable-mod fastcgi-php
root@cubie:# service lighttpd restart

4. Create a database for blackbox

The following will create a database called blackbox, a db user called bbuser, with password secret

root@cubie:# mysql -u root -p
{enter your mysql root pwd}

create database blackbox;
grant ALL privileges on blackbox.**to bbuser@localhost identified by 'secret';
exit;**

5. Set up systems users

In order for the webapp (which runs as www-data), your ftp/scp user, and the cronjobs/binarys/scripts to all share file access harmoniously, you need:
- an unpriviledged user for all scp/ftp/cron/scripting eg myname
- add myname to the www-data group

root@Cubian:# usermod -a -G www-data myname

### Application Software ###

1. Copy the blackbox folder into your webserver document root. If that is /var/www/html, then you should have

/var/www/html/blackbox/index.php    ;renders views
/var/www/html/blackbox/setup.php    ;configures views
/var/www/html/blackbox/cronjobs.php ;called from cron to handle regular device reads
/var/www/html/blackbox/modules/     ;code for particular controllers and inverters
/var/www/html/blackbox/templates/   ;html templates for views
etc

edit config/main-config.php to specify your database permissions as set above.
edit modules/midnite\_classic/midnite\_classic\_config.php to specify the ip address of your classic

$set['ip\_address']= '192.168.0.223';

For now multiple classics are handled using module clones. Make a copy of the classic module folder, renaming midnite\_classic to midnite\_classic\_2 etc, and the files within similarly renamed to match the new module name exactly.

Also check that you have the right architecture for newmodus from the ones available in the module folder. For ARM use:

$set['newmodbus\_ver']= 'newmodbus-1.0.19-ARM';

Leave the other settings for now.

2. Then browse to http://youripaddress/blackbox/setup.php which will automatically create the required view/element db tables. Then click check db, then proceed, so as to create the module db tables. If your db permissions arent right it will say so at this point.


3. Tweaks:

The folder blackbox/tmp/ is for raster graphs and needs to be webserver writable, so depending on your permissions something like:

root@cubie:# chown www-data:www-data /var/www/html/blackbox/tmp/
root@cubie:# chmod 0775 /var/www/html/blackbox/tmp/

/modules/midnite\_classic/newmodbus-1.0.19-ARM needs to be executable, so this:

root@cubie:# chmod 0775 /var/www/html/blackbox/modules/midnite\_classic/newmodbus-1.0.19-ARM

At this point a couple of tests to confirm that things are set up right:

At a myname shell, type (alter the path to doc root, newmodbus version and ip address to match yours):

myname@cubie:# cd /var/www/html/blackbox/modules/midnite\_classic/
myname@cubie:# ./newmodbus-1.0.19-ARM 192.168.0.223 16387 4101-4375

You should see this:

ID CLASSIC
ClassicTime 17:35:10  04/11/2013
16387 1609 (0x649)
...
4368 65535 (0xFFFF)
4369 65516 (0xFFEC)
4370 65535 (0xFFFF)
4371 7 (0x7)
4372 19273 (0x4B49)
4373 10 (0xA)
4374 0 (0x0)
4375 0 (0x0)

Then web browse to /blackbox/cronjobs.php, and no errors there is a good sign too.

4. Add a cronjob.

First depending on your version of php you should try these commands in user shell first to see which one works:

myname@cubie:# /usr/bin/php-cgi -f /home/www-data/html/blackbox/cronjobs.php
myname@cubie:# /usr/bin/wget --quiet http://192.168.0.3/blackbox/cronjobs.php

The former works for debian-esque linuxes with php standalone. On some systems it is called php-cli. Should you find yourself with apache and the apache dso module version of php youll need the wget version. If you go the former route, youll need to ensure that the cron user has the same write privs to blackbox/tmp/ as www-data does.

So then add a userspace cron task like so (alter path to suit):

myname@cubie:# nano ~/mycron

  **/usr/bin/php-cgi -f /var/www/html/blackbox/cronjobs.php >/dev/null 2>&1**

{to save: ctrl o,enter,ctrl x}

myname@cubie:# crontab ~/mycron

You can check that the device processor is working by looking in mysql:

myname@cubie:# mysql -u root -p
use blackbox;
select **from classiclogs;
exit;**

If theres no entrys then processing isnt running at all. Check step 4.
If theres entrys with code=0, then newmodbus is returning a fail code, check its ip address and permissions.

5. View setup

You have to now populate your default views. To do this:
- browse to /blackbox/setup.php
- add view , template1
- click View 1 config
- add datapt
- rinse and repeat, til you have your view how you want it

BTW. If you know how, you can dump the contents of blackbox.sql (from the svn) into the database at the outset to prepopulate the two default views.

Template 1 has a basic top bottom left right pane layout, and three font styles.
Template two has a system map backgound, and lots of predefined mini panes. You should be able to alter this to suit your system, by editing template2.html in a text editor.
Otherwise you can modify or add templates to the templates folder as needed.

