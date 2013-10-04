=INSTALLATION=

Prerequsites
------------
-webserver, mysql, php5.3
-gd


Instructions
------------
1. copy the whole blackbox folder somewhere within your web servers docroot
2. make sure the /tmp folder is writable by the webserver.
3. edit /config/config-main.php (may need to rename from config-main-skel.php) to give database permissions (requires table create).
4. edit module configs in /modules to suit your devices. Make sure sampletime=cronjob interval
5. web browse to http://path/to/blackbox/setup.php, if you get no initial error then your dbs perms are ok
6. you must then click check db in the sidebar, and 'Proceed', to populate module tables
7. see comments top of /cronjobs.php for help to get the cronjob going.