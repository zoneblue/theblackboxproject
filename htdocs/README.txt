Installation.

1. copy the whole blackbox folder somewhere within your web servers docroot
2. edit /config/main-config.php to give database permissions (requires table create)
3. browse to /setup.php, if you get no initial error then your dbs perms are ok
4. you must then click check db in the sidebar, and 'Proceed', to populate module tables
5. edit module configs in /modules to suit your devices
6. see /cronjobs.php for help to get the cronjob going.