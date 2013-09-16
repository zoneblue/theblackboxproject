<?php

/**
 * CONFIG
 * Core settings for Insolation Model Input Module
 * this module is entirely computed, no device, no db storage
 * 
 * $Rev$
 *
 **/
 


//====================================================================================//

$set['module_name']=         'Insolation Model';


//STD SETTINGS
$set['sample_device']=        false;
$set['sample_during_hours']= '6-18';
$set['sample_interval']=      60; //s

$set['store_in_db']=         false;
$set['store_interval']=      60; //s
$set['store_db_table']=      'none';



//LOCATION METRICS
//for the system location
//these are all added as datapoints too
$set['pv_latitude']=  -39.3237; //decimal deg
$set['pv_longtitude']=174.2166; //decimal deg
$set['pv_altitude']=       459; //meters asl
$set['pv_timezone']=        12; //hours off gmt +/-12hrs , ignoring DST

//ARRAY METRICS
//angles and power rating etc for the array
//these are all added as datapoints too
$set['pv_tilt']=         39; //deg, from horozontal plane
$set['pv_azimith']=       0; //deg, from true north
$set['pv_watts_peak']= 1800; //W
$set['pv_n_panels']=      6; 
$set['pv_n_strings']=     3; 

$set['pv_area']=      1.936; //m2 incl frame per one panel
$set['pv_voc']=        44.8; //V
$set['pv_isc']=         8.8; //A
$set['pv_vmpp']=       36.1; //V
$set['pv_impp']=       8.32; //A
$set['pv_vtc']=      -0.307; //%/K
$set['pv_itc']=      +0.039; //%/K
$set['pv_ptc']=      -0.423; //%/K


//===================================================================================//


?>
