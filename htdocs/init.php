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



class Profiler {
	protected $start;
	protected $record;
	function __construct() {
		$this->start= $this->getmtime();
		$this->add('Profiler start');
	}
	function getmtime(){ 
		list($usec, $sec) = explode(" ",microtime()); 
		return ((float)$usec + (float)$sec); 
	}
	function add($l){ 
		$this->record[]= array($l,number_format($this->getmtime()-$this->start,3));		
	}
	function dump() {
		$this->add('Profiler end');
		$o='';
		foreach($this->record as $r){
			$o.= $r[1].' '.$r[0]."<br>\n";
		}
		return $o;
	}
}
$profiler= new Profiler;


?>