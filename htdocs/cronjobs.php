<?php

/** 
 *  CRONJOBS
 *  =========
 *  there is single call from cron to this file each minute, which farms out tasks to each module
 *  but indiv modules will be able to run at more or less frequent intervals as configured.
 * 
 *  @license:  GPLv3. 
 *  @author:   Peter 2013
 *  @revision  $Rev$
 *
 *  Cron setup, usually something along these lines
 *  * * * * * /usr/bin/php-cgi -f /home/www-data/html/blackbox/cronjobs.php >/dev/null 2>&1
 *  * * * * * /usr/bin/wget --quiet http://192.168.0.3/blackbox/cronjobs.php >/dev/null 2>&1
 *
 **/ 





### Prelim

//php set
ini_set('display_errors', 'on');

require("init.php");

//define graph x axis times (24hr time of day)
$graphset['start']= "00";
$graphset['stop']=  "24";
$interval= $SETTINGS['sample_interval']; //minutes


### Read and process the module devices

if (date('H') < $graphset['start'])  exit;
if (date('H') >= $graphset['stop'])  exit;
if (((int)date('i') % $interval)<>0) exit;

$blackbox= new Blackbox();
$blackbox->process_modules();


### Render graphs

$query= "
	select * from blackboxelements
	where type='g'
	order by panetag,position
";	
$params= array('id_view'=>1);
$result= $db->query($query,$params) or codeerror('DB error',__FILE__,__LINE__);
while ($row= $db->fetch_row($result)) {
	$id_element=   $row['id_element'];
	$settings=      unserialize($row['settings']);
	make_graph($id_element,$settings);
}

//all done
print $profiler->dump();
exit;




//MAKE_GRAPH
function make_graph($id_element,$settings) {
	
	global $blackbox,$graphset;
	
	$day= date("Y-m-d");
	
	//to match our axis to the available data 
	//fish out once per minute datetimes for each module, and hash them
	//to handle multiple points per minute, for now first one rules 
	//missing samples use NULL
	$hash= array();
	foreach($blackbox->modules as $mod=>$module) {
		$records= $blackbox->modules[$mod]->get_datetimes('periodic'); //until minute series is fixed
		if (!$records) continue;
		$hash[$mod]= array();
		foreach ($records as $n=>$datetime) {
			$rtime= date("H:i", strtotime($datetime));
			if (isset($hash[$mod][$rtime])) continue; //first one rules as its closer to the 00 mark
			$hash[$mod][$rtime]= $n;
		}
	}
	
	//get actual data
	$ydata= $xdata= array();
	$stamp= "$day {$graphset['start']}:00:00";
	while ($stamp <= "$day {$graphset['stop']}:00:00") {
		$hr=    date("H", strtotime($stamp)); 
		$mn=    date("i", strtotime($stamp)); 
		$rtime= date("H:i", strtotime($stamp)); 
		
		//set x label
		$xdata[]= $mn=='00' ? "$hr" : '';

		//set y values
		foreach ($settings['datapts'] as $series=>$bla) {
			$mod=	  $settings['datapts'][$series]['module'];
			$dp=    $settings['datapts'][$series]['datapoint'];
			$mult=  $settings['datapts'][$series]['multiplier'];
			if (!isset($ydata[$series])) $ydata[$series]= array();
			
			$val= NULL;
			if (isset($hash[$mod][$rtime])) {
				$val= $blackbox->modules[$mod]->datapoints[$dp]->data[$hash[$mod][$rtime]];
				$val= $mult * str_replace(',','',$val); //some data has commas, duh
			}
			$ydata[$series][]= $val;
		}
		
		//inc
		$stamp= date("Y-m-d H:i:s", strtotime("$stamp +1 min"));
	}	

	//data
	$ymax=$ymin=1e20; $data=array();
	$data[0]= $xdata; //x is data[0]
	foreach ($settings['datapts'] as $series=>$bla) {
		$ymax=  max($ymax,max($ydata[$series]));
		$ymin=  min($ymin,min($ydata[$series]));
		$data[]= array(
			'name'=>        $settings['datapts'][$series]['name'],
			'type'=>      	 'line',
			'color'=>       $settings['datapts'][$series]['linecolor'],
			'linewidth'=>   $settings['linethick'],
			'alpha'=>       80,
			'smooth'=>      (bool)$settings['linesmooth'],
			'joinmethod'=>  'angle',
			'data'=>        $ydata[$series],
		);
	}

	//set x and ymax
	if (!isset($settings['ymax']) or !$settings['ymax']) $settings['ymax']= 'auto';
	if (!isset($settings['ymin']) or !$settings['ymin']) $settings['ymin']= '0';
	$ymax= $settings['ymax']=='auto' ? $ymax : $settings['ymax'];
	$ymin= $settings['ymin']=='auto' ? $ymin : $settings['ymin'];

	//main graph setup
	$params= array(
		//chart
		'title'=>            '',
		'size'=>             array($settings['width'],$settings['height']), //680 wide leaves 60px per hour
		'scale'=>            1,                  //upscales everything for print
		'margins'=>          array(20,20,27,40), //t r b l
		'showborder'=>       false,
		'showlegend'=>       true,
		'legend_pos'=>       array(-120,34), //from tl, or use - for br aligned
		'shownote'=>         false,
		'note_pos'=>         array(50,34),   //from tl, or use - for br aligned
		'note_content'=>     '',
		'fontfolder'=>       dirname(__FILE__).'/templates/fonts/', //trailing slash
		'fontfile'=>         'opensans-semibold-latin.ttf', 
		'fontfilebold'=>     'opensans-bold-latin.ttf', 
		'fontsize'=>         8,
		'fontcolor'=>        '#444',
		'border_color'=>     'rgb(150,150,150)',
		'grid_color_major'=> 'rgb(150,150,150)',
		'grid_color_minor'=> 'rgb(220,220,220)',

		//x axis
		'xaxistitle'=>       '',
		'xmode'=>           'adj',  //betw, adj
		'xusemajorgrid'=>   false,    //if false will show ticks only
		'xuseminorgrid'=>   false,    
		'xintervalmajor'=>  60,   //major grid every N points, default 1
		'xqtyminorgrids'=>  2,    //minor grid every N major grids, default 4, must be divisible into major, use 0 for no minor ticks

		//y axis
		'yaxistitle'=>       '',
		'ymode'=>           'fit',  //auto, fit or exact
		'yextents'=>        array($ymin,$ymax), //required for exact
		'yusemajorgrid'=>   true,    
		'yuseminorgrid'=>   false,    
		'yqtymajorgrids'=>      9,    //no of major grids, if fit, this will be rounded using multiples of 1,2, or 5
		'yqtyminorgrids'=>      4,    //minor grid every N major grids, default 4, must be divisible into major, use 0 for no minor ticks
		'yaxislabelspec'=>  ($ymax-$ymin) > 10 ? 'decimal(0)' : 'decimal(1)',

		//series
		'downsample'=>         $settings['average'],           //down samples overly detailed datasets, average every N points 
		'usedatapoints'=>      false,
		'datapointsize'=>      1.75, //times the line thickness
		'datapointshape'=>     'square',
		'usedatalabels'=>      false, 
		'datalabelinterval'=>  60, //interval of xdata points
		'datalabelspec'=>      'decimal(1)', 
		'linejoinmethod'=>     'round',
	);

	//build graph
	$graph= new Graph($params,$data);
	$gfile= "tmp/current-graph-$id_element.png";
	$graph->savetofile($gfile);

	return true;
}




?>