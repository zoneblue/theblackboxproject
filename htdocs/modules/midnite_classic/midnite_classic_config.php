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

$set['sample_device']=       true;
$set['sample_during_hours']= '06-19';
$set['sample_interval']=     120; //seconds

$set['store_in_db']=         true;
$set['store_interval']=      120; //seconds
$set['store_db_table']=      'classiclogs';
$set['store_db_table_day']=  'classicdaylogs';


//SET IP ADDRESS
//this is the static ip address of the classic
$set['ip_address']=         '192.168.0.223';
$set['modbus_port']=        '502';

//SET NEWMODBUS BINARY
//choose which architecture and version from one of the files in the module folder
//its assumed that the path is the relative to this module folder
$set['newmodbus_ver']=      'newmodbus-1.0.19-ARM';


//===================================================================================//



?>