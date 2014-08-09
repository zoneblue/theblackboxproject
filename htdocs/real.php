<?php


/** 
 * Wireframe for realtime calls via ajax
 * Only works with daemon mode
 *
 *
 *
 *
 **/
 
$id_view= 1;


### Prelim

//php set
ini_set('display_errors', 'on');

require("init.php");

$blackbox= new Blackbox();
$modules= $blackbox->modules;
	
foreach ($modules as $mod=>$module) {
	$module->read_direct();
	$profiler->add("Module $module->name read direct");
}		


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

			print"$name|$value|$unit\n"; //json todo
		}
	}
	
}

#print $profiler->dump();


?>