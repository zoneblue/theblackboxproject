<?php

/** 
 *  INIT.PHP
 *
 *  Holds code common to UI pages. 
 * 
 *  The Blackbox Project
 *  --
 *  @author:   Peter 2013
 *  @license:  GPLv3. 
 *  @revision: $Rev$
 *
 **/ 


//php set
ini_set('display_errors', 'on');
error_reporting(E_ALL);

//includes
require('config/config-main.php');
require('lib/housekeeping.php');
require('lib/lib-db.php');
require('lib/lib-form.php');
require('lib/lib-page.php');
require('lib/lib-draw.php');
require('lib/lib-graph.php');
require('lib/lib-blackbox.php');

//connect to db
$db = new Database($SQL_TYPE);
$db->connect($SQL_HOST, $SQL_USER, $SQL_PASS, $SQL_DB) or codeerror("DB connect failed");

//sanity check sample interval
$SETTINGS['sample_interval']= (int)$SETTINGS['sample_interval']>=1 ? (int)$SETTINGS['sample_interval']: 1;

$profiler= new Profiler;


?>