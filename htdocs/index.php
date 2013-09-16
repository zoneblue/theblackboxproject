<?php

/** 
 *  INDEX.PHP
 *  ==
 *  Build and render a power monitor 'view'. 
 * 
 *  @package:  The Blackbox Project.
 *  @author:   Peter 2013
 *  @license:  GPLv3. 
 *  @revision: $Rev$
 *
 **/ 



### Prep


include('init.php');


//additional config
$REFRESH_RATE= 60; //seconds

//create page from view template
$page= new Page('template-view.html');

//get all modules with todays data preloaded
$blackbox= new Blackbox(true);
$modules= $blackbox->modules;


### Build page

//get view
//there will be multiple definable views, i guess
$id_view= 1;

//get elements
$query= "
	select * from blackboxelements
	where id_view=':id_view'
	order by panetag,position
";	
$params= array('id_view'=>$id_view);
$result= $db->query($query,$params) or codeerror('DB error',__FILE__,__LINE__);
while ($row= $db->fetch_row($result)) {
	$id_element=   $row['id_element'];
	$name=         $row['name'];
	$type=         $row['type'];
	$panetag=      $row['panetag'];
	$settings=      unserialize($row['settings']);

	if (!isset($page->tags["Pane::$panetag"])) $page->tags["Pane::$panetag"]= '';
	
	//datapt
	if ($type=='d') {
		$mod=        $settings['module'];
		$dp=         $settings['datapoint'];
		$resolution= $settings['resolution'];
		$style=      $settings['style'];

		if (isset($modules[$mod])) {
			//get current value for dp
			$value= $modules[$mod]->datapoints[$dp]->current_value;
			$unit=  $modules[$mod]->datapoints[$dp]->unit; if (!$unit) $unit='&nbsp;';

			if (preg_match("/^\d+$/",$resolution) and preg_match("/^[\d.]+$/",$value)) $value= number_format($value, (int)$resolution, '.','');

			//pin element to the page
			$page->tags["Pane::$panetag"].= "
				<div class='$style'>
					<span class='h'>$name</span>
					<span class='v'>$value</span>
					<span class='u'>$unit</span>
				</div>
			";
		}
	}
	
	//graph
	else {
		$graph= "<img src='tmp/current-graph-$id_element.png' alt='' />";

		$page->tags["Pane::$panetag"].= "
			<div class='viewgraph'>
				$graph
			</div>
		";
	}
}

//var_dump(memory_get_peak_usage());
//var_dump($db->long_querys);
//var_dump($db->nquerys);
$pdump= $profiler->dump();


### Display page

$page->tags['PageTitle']=     'Power System Monitor';
$page->tags['ExtraHeaders']=  "<meta http-equiv=\"Refresh\" content=\"$REFRESH_RATE; url=index.php\" />\n";
$page->tags['Foot']=          "
	<p><a href='setup.php'>Setup</a></p>
	<p style='margin-top:3em;color:#666;font-size:9px;line-height:15px'>$pdump</p>
";
$page->render();

?>