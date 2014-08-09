<?php

/**
 * CONFIG
 * Module: Midnite Classic 
 * 
 * Revision: $Rev$
 *
 **/
 
 
 
//====================================================================================//

//STD 
$set['module_name']=         'Midnite Classic';
$set['module_order']=        1;

$set['sample_device']=       true;  //unused?
$set['sample_during_hours']= '06-19'; //unused?
$set['sample_interval']=     $GLOBALS['SETTINGS']['sample_interval'] * 60;

$set['store_in_db']=         true;
$set['store_interval']=      $GLOBALS['SETTINGS']['sample_interval'] * 60; 
$set['store_db_table']=      'classiclogs';
$set['store_db_table_day']=  'classicdaylogs';


//SET IP ADDRESS
//this is the static ip address of the classic
$set['ip_address']=         '192.168.0.223';
$set['modbus_port']=        '502';

//SET NEWMODBUS BINARY
//for newmodbus, choose which architecture and version from one of the files in the bin folder
//both newmodbus and newmodbusd must be chmod 0775 
//for help deciding which to run see the main readme.
$set['newmodbus_mode']=   'daemon'; // normal|daemon
$set['newmodbus_ver']=    '/opt/blackbox/newmodbus-1.0.19-ARM'; // required for non daemon mode
$set['newmodbusd_log']=   '/var/tmp/blackbox/data.txt';         // required only for daemon mode


//===================================================================================//



?>