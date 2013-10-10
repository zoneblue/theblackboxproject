<?php

/**
 * CONFIG
 * Core settings for Insolation Model Input Module
 * this module is entirely derived
 * 
 * $Rev$
 *
 **/
 


//====================================================================================//

$set['module_name']=         'Insolation Model';
$set['module_order']=        2;


//STD SETTINGS
$set['sample_device']=        true;
$set['sample_during_hours']= '6-20';
$set['sample_interval']=      120; //s

$set['store_in_db']=         true;
$set['store_interval']=      120; //s
$set['store_db_table']=      'insolationlogs';
$set['store_db_table_day']=  'insolationdaylogs';



//LOCATION METRICS
//for the system location
//these are all added as datapoints too
$set['pv_latitude']=   -39.323; //decimal deg
$set['pv_longtitude']= 174.229; //decimal deg
$set['pv_altitude']=       459; //meters asl
$set['pv_timezone']=        12; //hours off gmt +/-12hrs , ignoring DST


//storage
$set['pv_battery_ah']=   400; 
$set['pv_battery_v']=     24; 


//ARRAY METRICS
//angles and power rating etc for the array
//these are all added as datapoints too
$set['pv_tilt']=         39; //deg, from horozontal plane
$set['pv_azimith']=       0; //deg, from true north
$set['pv_watts_peak']= 1800; //W
$set['pv_n_panels']=      6; 
$set['pv_n_strings']=     3; 

//per panel specs
$set['pv_area']=      1.936; //m2 incl frame 
$set['pv_voc']=        44.8; //V
$set['pv_isc']=         8.8; //A
$set['pv_vmp']=        36.1; //V
$set['pv_imp']=        8.32; //A
$set['pv_vtc']=      -0.307; //%/K
$set['pv_itc']=      +0.039; //%/K
$set['pv_ptc']=      -0.423; //%/K


//===================================================================================//


?>