<?php

/** 
 *  CONFIG-MAIN
 *
 *  black box config 
 * 
 *  @revision $Rev$
 *  @author: peter 2013
 *  @license: GPLv3. Use at your own risk. 
 *
 *
 **/ 


#-------------------MAIN CONFIGURATION--------------------#


### Database config

$SQL_TYPE=  'MYSQL';
$SQL_HOST=  'localhost';            
$SQL_DB=    'blackbox';      
$SQL_USER=  'cubie';           
$SQL_PASS=  'secret';              



### Global settings

//this is the time interval in minutes that the device data is stored in the database
//1-15 minutes is about right, minimum 1 minute

$SETTINGS['sample_interval']= 1; //minutes


//the code stores the current days data in a verbose form
//set where you want the temp data files to be stored
//if you are using sd/flash then this should be on a ramdisk
//with cubian r8, /var/tmp is good to go
//bb adds a folder called blackbox so no need to add that to the path
//ramdisks start empty anyway
//no trailing slash, must exist

$SETTINGS['temp_data_dir']= '/var/tmp'; 


//set where you want the permanent data files to be stored
//these are bziped daily records from yesterday onwards
//uses a /data/modulename/yyyy-mm-dd.csv.bz2 tree format
//no trailing slash, must exist

$SETTINGS['data_dir']= '/home/blackbox'; 


//set where the blackbox bin directory is located
//these are for scripts and binarys that run out of cron, or in the background
//no trailing slash, must exist

$SETTINGS['bin_dir']= '/opt/blackbox'; 



#------------------ END CONFIGURATION----------------------#

?>