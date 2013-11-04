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

$SQL_TYPE=     "MYSQL";
$SQL_HOST=  	"localhost";            
$SQL_DB=    	"databasename";      
$SQL_USER=  	"dbuser";           
$SQL_PASS=  	"dbpass";              


## Global settings

//this is the time interval in minutes that the devices are sampled
//as low as 1 for x86 computers is ok, but recommend no lower than 5 for ARM.
//this is becasue of a networking bug in the classic do do with not properly closing connections.

$SETTINGS['sample_interval']= 5;



#------------------ END CONFIGURATION----------------------#



?>