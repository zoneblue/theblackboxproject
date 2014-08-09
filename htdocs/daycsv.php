<?php

// This only works for daemon mode
// allows you to download the 1s series data for a given iso date
// defaults to today





### Prelim

//php set
ini_set('display_errors', 'on');

require("init.php");


$set['4115']='Vbat';
$set['4116']='Vpv';
$set['4117']='Iout';
$set['4120']='Stage';
$set['4121']='Ipv';
$set['4132']='Tbat';
$set['4133']='Tfat';
$set['4371']='Iwb';
$set['4373']='SOC';


$day= getpost('day');
if (!preg_match("/\d\d\d\d-\d\d-\d\d/",$day)) $day=''; 
if (!$day) $day= date('Y-m-d');

if ( $day==date('Y-m-d')) {
	$fname="/var/tmp/blackbox/$day.txt";
	if (!file_exists($fname)) die("No data file - $fname");
	$lines= file($fname);
}
else {
	$fname="/home/data/daily/$day.txt.bz2";
	if (!file_exists($fname)) die("No data file - $fname");
	exec("bunzip2 -c $fname", $lines,$ret);
	if ($ret) die("Unzip failed");
}

if (!$lines) die("No data - $fname");


/*
   [0] => [20:52:44.000] - 4115:249	4116:165	4117:0	4120:0	4121:0	4132:118	4133:194	
   [1] => 0 - 211 ms 
*/

//log_registers    4115,4116,4117,4120,4121,4132,4133,4371,4373
//#                vbat,vpv, iout,stge,ipv, tbat,tfet,iwb, soc





//headrow
$out='';
$out.="Time,";
foreach($set as $reg=>$label) {
	$out.= "$label,";
}
$out.="Dur\n";

//rows
foreach ($lines as $line) {

	if ($line[0]=='[') {
		$bits= explode('-',$line);
		if (preg_match("/(\d\d:\d\d:\d\d)/", $bits[0], $m)) $ts= $m[1];
		else $ts=0;
		$out.= "$ts,";
		
		$p= explode("\t",trim($bits[1]));
		$pairs= array();
		foreach ($p as $pair) {
			$pair= trim($pair);
			if (!$pair) continue;
			list($k,$v)= explode(':', $pair); 	
			$pairs[$k]= $v;
		}
		
		foreach ($set as $reg=>$label) {

			if (isset($pairs[$reg])) {
				$v= $pairs[$reg];

				if ($reg==4115) $v/=10;
				if ($reg==4116) $v/=10;
				if ($reg==4117) $v/=10;

				if ($reg==4121) $v/=10;
				if ($reg==4132) $v/=10;
				if ($reg==4133) $v/=10;
			
				if ($reg==4371) {$v= ($v >= 32768) ? (65536-$v)/10 : $v/-10 ; $v= -$v; }
			}
			else $v= '';
			
			$out.= "$v,";
		}
	}
	else {
		if (preg_match("/(\d+) ms/",$line,$m)) $dur= $m[1];
		else     $dur='?';
		
		$out.="$dur\n";
	}
}


header("Content-type:text/csv; name=\"$day.csv\"");
header("Content-Transfer-Encoding: 7bit");
header("Content-Disposition: attachment; filename=\"$day.csv\"");

print $out;

?>