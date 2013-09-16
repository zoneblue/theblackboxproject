<?php

/** 
 *  SETUP.PHP
 *  =========
 *  User configuration of views.  Various form incarnations, edit, del, datapts and graphs
 *  
 *  @package:  The Blackbox Project.
 *  @author:   Peter 2013
 *  @license:  GPLv3. 
 *  @revision: $Rev$
 *
 **/ 


### Prep

include('init.php');

//defaults
//we could get these from the view template to make it more flexible
$panetags= array('Top','Left','Right','Bottom');
$styles=   array('small','medium','large');

//new page instance
$page= new Page('template-setup.html');
$form= new Form;

//set nav throughout
$page->tags['Nav']= "<a href='index.php'>View</a> <a href='history.php'>History</a> <a href='setup.php'>Setup</a>";	

//get module/dp definitions
$blackbox= new Blackbox();
$modules= $blackbox->modules;

//get incoming
$do=         getpost('do');
$id_element= getpost('id_element');
$series=     getpost('series');
$name=       getpost('name');
$panetag=    getpost('panetag');
$position=   getpost('position');

$datapt=     getpost('datapt');
$resolution= getpost('resolution');
$style=      getpost('style');

$width=      getpost('width');
$height=     getpost('height');
$ymax=       getpost('ymax');
$ymin=       getpost('ymin');
$average=    getpost('average');
$linethick=  getpost('linethick');
$linesmooth= getpost('linesmooth');

$linecolor=  getpost('linecolor');
$multiplier= getpost('multiplier');



###  TABLE MAINTENANCE
###
###############################################

//check table - elements
$query= "show tables like 'blackboxelements'";		
$result= $db->query($query) or codeerror('DB error',__FILE__,__LINE__);
if (!$db->num_rows($result)) {
	$query= "
		create table blackboxelements (
			id_element   int unsigned primary key auto_increment,
			id_view      tinyint unsigned not null,
			name         varchar(255) not null,
			type         char(1) not null,
			panetag      varchar(255) not null,
			settings     text not null,
			position     tinyint unsigned not null
		);
	";
	if ($db->query($query))  {}
	else {
		//Display page
		$page->tags['PageTitle'] =  'Error';
		$page->tags['Body']=	 "Our attempt to add the elements table failed. That means your database/permissions are not set right.";
		$page->render();
	}
}
			
//db fix
if ($do=='fixdb') {
	$blackbox->check_dbase(true);
	$backto= "setup.php";
		
	//Display page
	$page->tags['PageTitle'] =  'Setup';
	$page->tags['Body']=	 "
		<h2>Database setup</h2>
		<p>All good.</p>
		<div class='buttons'>
			<input type='button' value='Proceed'  onClick=\"document.location.href='$backto';\"  />
		</div>
	";
	$page->render();
}

//db check
if ($do=='checkdb') {
	$errors= $blackbox->check_dbase();
	if ($errors) {
		$errors= implode("<br/>",$errors);
		$fwdto= "setup.php?do=fixdb";
		$bodyinsert="
			<p>Your database needs to be setup to work with new blackbox modules.</p>
			<div style='margin:10px 0; background:#ddd;'>
				<p>$errors</p>
			</div>
			<p>Assuming that your database permissions are sufficient, the necessary changes will be made by clicking Proceed below.
			<div class='buttons right'>
				<input type='button' value='Proceed'  onClick=\"document.location.href='$fwdto';\"  />
			</div>
		";
	}
	else {
		$backto= "setup.php";
		$bodyinsert="
			<p>Database check shows that all module tables are OK.</p>
			<div class='buttons left'>
				<input type='button' value='Close'  onClick=\"document.location.href='$backto';\"  />
			</div>
		";
	}
	
	//Display page
	$page->tags['PageTitle'] =  'Setup';
	$page->tags['Body']=	 $bodyinsert;
	$page->render();
}


###  DEL ELEMENT
###
###############################################

if ($do=='delelement') {
	
	if (!isgoodid($id_element)) die('Invalid id '.__LINE__);
	$query= "delete from blackboxelements where id_element= ':id_element' ";	
	$params= array('id_element'=>$id_element);
	$db->query($query,$params) or codeerror('DB error',__FILE__,__LINE__);
}



###  EDIT DATAPT 2
###
###############################################

if ($do=='editdatapt2') {

	//clean and val
	if (!$datapt)     $form->errors['datapt']= "field empty";
	if (!$style)      $form->errors['style']=  "field empty";
	if (!$panetag)    $form->errors['panetag']="field empty";
	if ($resolution) $resolution=(int)$resolution;
	
	//if valid proceed
	if ($form->errors) $do='editdatapt';
	else {
	
		//insert if new
		if (!isgoodid($id_element)) {
			$query= "
				insert into blackboxelements set
				type= 'd',
				id_view=1
			";	
			$db->query($query) or codeerror('DB error',__FILE__,__LINE__);
			$id_element= $db->insert_id();
		}
	
		//pack settings
		$settings=array(); 
		list($mod,$dp)= explode('::',$datapt);
		$settings['module']=    $mod;
		$settings['datapoint']= $dp;
		$settings['resolution']= $resolution;
		$settings['style']=     $style;

		//write 
		$query= "
			update blackboxelements set
			name=     ':name',
			settings= ':settings',
			panetag=  ':panetag',
			position= ':position'
			where id_element= ':id_element'
		";	
		$params= array(
			'name'=>       $name,
			'settings'=>   serialize($settings),
			'panetag'=>    $panetag,
			'position'=>   $position,
			'id_element'=> $id_element,
		);
		$db->query($query,$params) or codeerror('DB error',__FILE__,__LINE__);
	}
}



###  EDIT DATAPT
###
###############################################

if ($do=='editdatapt') {

	//preload form
	if ($form->errors) {}
	elseif (isgoodid($id_element)) {
		$query= "select * from blackboxelements where id_element=':id_element' ";	
		$params= array('id_element'=>$id_element);
		$result= $db->query($query,$params) or codeerror('DB error',__FILE__,__LINE__);
		if ($row= $db->fetch_row($result)) {
			$name=      $row['name'];
			$settings=  $row['settings'];
			$panetag=   $row['panetag'];
			$position=  $row['position'];
			
			$settings= unserialize($settings);
			$datapt=     $settings['module'].'::'.$settings['datapoint'];
			$resolution= $settings['resolution'];
			$style=      $settings['style'];
		}
		else die('Invalid id '.__LINE__);
	}
	else {
		$name=      '';
		$panetag=   '';
		$style=     '';
		$position=   0;
		$resolution= '';
	}

	//make datapt select
	//roll all together for now, cascade select later
	//maybe heading/seperator type elements as well
	$options= array();
	foreach ($modules	as $mod=> $module) {
		foreach ($module->datapoints as $dp=> $datapoint) {
			$label= "$mod::$dp";
			$options[$label]= array('value'=>"$module->name - $datapoint->name");
		}
	}
	$select= array(
		'name'=>     'datapt',
		'selected'=> $datapt,
		'options'=>  $options,
		'emptyoption'=>  '--',
		'class'=> '',
	);
	$datapt_select= $form->makeselect($select);

	//make pane select
	$options= array();
	foreach ($panetags as $label) {
		$options[$label]= array('value'=>$label);
	}	
	$select= array(
		'name'=>     'panetag',
		'selected'=> $panetag,
		'options'=>  $options,
		'emptyoption'=>  '--',
		'class'=> '',
	);
	$panetag_select= $form->makeselect($select);
		
	//make style select
	$options= array();
	foreach ($styles as $label) {
		$options[$label]= array('value'=>$label);
	}	
	$select= array(
		'name'=>     'style',
		'selected'=> $style,
		'options'=>  $options,
		'emptyoption'=>  '--',
		'class'=> '',
	);
	$style_select= $form->makeselect($select);

	//prep
	$do=     'editdatapt2';
	$backto= 'setup.php';
	
	//assemble form
	$bodyinsert="
		<form action='setup.php' method='post'>
			<fieldset>
				<legend>Edit datapt element</legend>
				<div class='row'>
					<label>Datapoint:</label>
					$datapt_select
					{$form->error('datapt')}
				</div>
				<div class='row'>
					<label>Label:</label>
					<input type='text' name='name' value='$name' /> 
					{$form->error('name')}
				</div>
				<div class='row'>
					<label>Pane:</label>
					$panetag_select
					{$form->error('panetag')}
				</div>
				<div class='row'>
					<label>Style:</label>
					$style_select
					{$form->error('style')}
				</div>
				<div class='row'>
					<label>Decimals:</label>
					<input type='text' name='resolution' value='$resolution' /> 
					{$form->error('resolution')}
				</div>
				<div class='row'>
					<label>Order:</label>
					<input type='text' name='position' value='$position' /> 
					{$form->error('position')}
				</div>
				<div class='buttons'>
					<input type='hidden' name='do' value='$do' />
					<input type='hidden' name='id_element' value='$id_element' />
					<input type='button' value='Cancel'  onClick=\"document.location.href='$backto';\"  />
					<input type='submit' value='OK' />
				</div>
			</fieldset>
		</form>
	";

	//Display page
	$page->tags['PageTitle'] =  'Setup';
	$page->tags['Body']=	 $bodyinsert;
	$page->render();
}



###  EDIT GRAPH SERIES 2
###
###############################################

if ($do=='editseries2' or $do=='delseries') {

	if (!$id_element) die('Invalid id '.__LINE__);
	if ($do=='delseries' and !isgoodid($series))  die('Invalid id '.__LINE__);
	
	//clean and val
	if ($do=='editseries2'){
		if (!$datapt)     $form->errors['datapt']= "field empty";
		if (!$name)       $form->errors['name']=  "field empty";
		if (!$linecolor)  $form->errors['linecolor']=  "field empty";
		if (!$multiplier) $form->errors['multiplier']=  "field empty";
	}
	
	//if valid proceed
	if ($form->errors) $do='editseries';
	else {
	
		//get
		$query= "select * from blackboxelements where id_element=':id_element' ";	
		$params= array('id_element'=>$id_element);
		$result= $db->query($query,$params) or codeerror('DB error',__FILE__,__LINE__);
		if ($row= $db->fetch_row($result)) {
			$settings=  $row['settings'];
			$settings= unserialize($settings);
		}
		else die('Invalid id '.__LINE__);
		
		//add new series
		if (!$series) {
			$series= isset($settings['datapts']) ? (count($settings['datapts'])+1) : 1;
			if (!isset($settings['datapts'])) $settings['datapts']=array();
		}
		
		//merge in settings
		if ($do=='editseries2'){
			list($mod,$dp)= explode('::',$datapt);
			$settings['datapts'][$series]['module']=    $mod;
			$settings['datapts'][$series]['datapoint']= $dp;
			$settings['datapts'][$series]['linecolor']= $linecolor;
			$settings['datapts'][$series]['multiplier']= round($multiplier,1);
			$settings['datapts'][$series]['name']=      $name;
		}
		else 	unset($settings['datapts'][$series]);

		//write back
		$query= "
			update blackboxelements set
			settings= ':settings'
			where id_element= ':id_element'
		";	
		$params= array(
			'settings'=>   serialize($settings),
			'id_element'=> $id_element,
		);
		$db->query($query,$params) or codeerror('DB error',__FILE__,__LINE__);
		
		$do='graph';
	}
}



###  EDIT GRAPH SERIES
###
###############################################

if ($do=='editseries') {

	if (!$id_element) die('Invalid id '.__LINE__);
	
	//preload form
	if ($form->errors) {}
	elseif (isgoodid($series)) {
		$query= "select * from blackboxelements where id_element=':id_element' ";	
		$params= array('id_element'=>$id_element);
		$result= $db->query($query,$params) or codeerror('DB error',__FILE__,__LINE__);
		if ($row= $db->fetch_row($result)) {
			$settings=  $row['settings'];
			$settings= unserialize($settings);
			$datapt=     $settings['datapts'][$series]['module'].'::'.$settings['datapts'][$series]['datapoint'];
			$name=       $settings['datapts'][$series]['name'];
			$linecolor=  $settings['datapts'][$series]['linecolor'];
			$multiplier=  $settings['datapts'][$series]['multiplier'];
		}
		else die('Invalid id '.__LINE__);
	}
	else {
		$datapt=    '';
		$linecolor= '';
		$multiplier= 1;
		$name= '';
	}

	//make datapt select
	//roll all together for now, cascade select later
	//maybe heading/seperator type elements as well
	$options= array();
	foreach ($modules	as $mod=> $module) {
		foreach ($module->datapoints as $dp=> $datapoint) {
			$label= "$mod::$dp";
			$options[$label]= array('value'=>"$module->name - $datapoint->name");
		}
	}
	$select= array(
		'name'=>     'datapt',
		'selected'=> $datapt,
		'options'=>  $options,
		'emptyoption'=>  '--',
		'class'=> '',
	);
	$datapt_select= $form->makeselect($select);

	//prep
	$do=     'editseries2';
	$backto= "setup.php?do=graph&amp;id_element=$id_element";
	
	//assemble form
	$bodyinsert="
		<form action='setup.php' method='post'>
			<fieldset>
				<legend>Edit graph series</legend>
				<div class='row'>
					<label>Datapoint:</label>
					$datapt_select
					{$form->error('datapt')}
				</div>
				<div class='row'>
					<label>Label:</label>
					<input type='text' name='name' value='$name' /> 
					{$form->error('name')}
				</div>
				<div class='row'>
					<label>Line color:</label>
					<input type='text' name='linecolor' value='$linecolor' /> 
					{$form->error('linecolor')}
				</div>
				<div class='row'>
					<label>Multiplier:</label>
					<input type='text' name='multiplier' value='$multiplier' /> 
					{$form->error('multiplier')}
				</div>
				<div class='buttons'>
					<input type='hidden' name='do' value='$do' />
					<input type='hidden' name='id_element' value='$id_element' />
					<input type='hidden' name='series'     value='$series' />
					<input type='button' value='Cancel'  onClick=\"document.location.href='$backto';\"  />
					<input type='submit' value='OK' />
				</div>
			</fieldset>
		</form>
	";

	//Display page
	$page->tags['PageTitle'] =  'Setup';
	$page->tags['Body']=	 $bodyinsert;
	$page->render();
}



###  EDIT GRAPH 2
###
###############################################

if ($do=='editgraph2') {

	//clean
	$width=      (int)$width;
	$height=     (int)$height;
	$ymax=       (int)$ymax;
	$ymin=       (int)$ymin;
	$average=    (int)$average;
	$linethick=  (int)$linethick;
	$linesmooth= (int)(bool)$linesmooth;
	$position=   (int)$position;
	
	//validate
	if (!$name)       $form->errors['name']=   "field empty";
	if (!$panetag)    $form->errors['panetag']= "field empty";
	
	if ($width <100 or $width >1000)  $form->errors['width']=     "must be 100-1000";
	if ($height<100 or $height>1000)  $form->errors['height']=    "must be 100-1000";
	if (!$width)      $form->errors['width']=     "field empty";
	if (!$height)     $form->errors['height']=     "field empty";

	if ($linethick>20) $form->errors['linethick']=     "must be 1-20";
	if (!$linethick)   $form->errors['linethick']=     "field empty";

	
	//if valid proceed
	if ($form->errors) $do='editgraph';
	else {
	
		//insert if new
		if (!isgoodid($id_element)) {
			$query= "
				insert into blackboxelements set
				id_view=1,
				type='g'
			";	
			$db->query($query) or codeerror('DB error',__FILE__,__LINE__);
			$id_element= $db->insert_id();
			$settings= array(); 
		}
		else {
			$query= "select * from blackboxelements where id_element=':id_element' ";	
			$params= array('id_element'=>$id_element);
			$result= $db->query($query,$params) or codeerror('DB error',__FILE__,__LINE__);
			$row= $db->fetch_row($result) or die('Invalid id '.__LINE__);
			$settings=  $row['settings'];
			$settings= unserialize($settings);
		}
	
		//merge settings
		$settings['width']=  $width;
		$settings['height']= $height;
		$settings['ymax']=    $ymax;
		$settings['ymin']=    $ymin;
		$settings['average']= $average;
		$settings['linethick']=  $linethick;
		$settings['linesmooth']= $linesmooth;

		//write 
		$query= "
			update blackboxelements set
			name=     ':name',
			settings= ':settings',
			panetag=  ':panetag',
			position= ':position'
			where id_element= ':id_element'
		";	
		$params= array(
			'name'=>       $name,
			'settings'=>   serialize($settings),
			'panetag'=>    $panetag,
			'position'=>   $position,
			'id_element'=> $id_element,
		);
		$db->query($query,$params) or codeerror('DB error',__FILE__,__LINE__);
		
		$do='graph';
	}
}



###  EDIT GRAPH
###
###############################################

if ($do=='editgraph') {

	//preload form
	if ($form->errors) {}
	elseif (isgoodid($id_element)) {
		$query= "select * from blackboxelements where id_element=':id_element' ";	
		$params= array('id_element'=>$id_element);
		$result= $db->query($query,$params) or codeerror('DB error',__FILE__,__LINE__);
		if ($row= $db->fetch_row($result)) {
			$name=      $row['name'];
			$settings=  $row['settings'];
			$panetag=   $row['panetag'];
			$position=  $row['position'];
			
			$settings= unserialize($settings);
			$width=      $settings['width'];
			$height=     $settings['height'];
			$ymax=       $settings['ymax'];
			$ymin=       $settings['ymin'];
			$average=    $settings['average'];
			$linethick=  $settings['linethick'];
			$linesmooth= $settings['linesmooth'];
		}
		else die('Invalid id '.__LINE__);
	}
	else {
		$name=      '';
		$panetag=   '';
		$width=   680;
		$height=  230;
		$ymax= '';
		$ymin= 0;
		$average= 1;
		$linethick= 2;
		$linesmooth= 1;
	}

	//make pane select
	$options= array();
	foreach ($panetags as $label) {
		$options[$label]= array('value'=>$label);
	}	
	$select= array(
		'name'=>     'panetag',
		'selected'=> $panetag,
		'options'=>  $options,
		'emptyoption'=>  '--',
		'class'=> '',
	);
	$panetag_select= $form->makeselect($select);
	
	$chk_linesmooth = $linesmooth ? " checked='checked'" : '';
	
	//prep
	$do=     'editgraph2';
	$backto= $id_element ? "setup.php?do=graph&amp;id_element=$id_element" : 'setup.php';
	
	//assemble form
	$bodyinsert="
		<form action='setup.php' method='post'>
			<fieldset>
				<legend>Edit graph element</legend>
				<div class='row'>
					<label>Label:</label>
					<input type='text' name='name' value='$name' /> 
					{$form->error('name')}
				</div>
				<div class='row'>
					<label>Pane:</label>
					$panetag_select
					{$form->error('panetag')}
				</div>
				<div class='row'>
					<label>Width:</label>
					<input type='text' name='width' value='$width' /> 
					{$form->error('width')}
				</div>
				<div class='row'>
					<label>Height:</label>
					<input type='text' name='height' value='$height' /> 
					{$form->error('height')}
				</div>
				<div class='row'>
					<label>Y max:</label>
					<input type='text' name='ymax' value='$ymax' /> 
					{$form->error('ymax')}
				</div>
				<div class='row'>
					<label>Y min:</label>
					<input type='text' name='ymin' value='$ymin' /> 
					{$form->error('ymin')}
				</div>
				<div class='row'>
					<label>Moving average (mins):</label>
					<input type='text' name='average' value='$average' /> 
					{$form->error('average')}
				</div>
				<div class='row'>
					<label>Line thickness:</label>
					<input type='text' name='linethick' value='$linethick' /> 
					{$form->error('linethick')}
				</div>
				<div class='row'>
					<label>Line smoothing:</label>
					<input type='checkbox' name='linesmooth' value='1' $chk_linesmooth /> 
					{$form->error('linesmooth')}
				</div>
				<div class='row'>
					<label>Order:</label>
					<input type='text' name='position' value='$position' /> 
					{$form->error('position')}
				</div>
				<div class='buttons'>
					<input type='hidden' name='do' value='$do' />
					<input type='hidden' name='id_element' value='$id_element' />
					<input type='button' value='Cancel'  onClick=\"document.location.href='$backto';\"  />
					<input type='submit' value='OK' />
				</div>
			</fieldset>
		</form>
	";

	//Display page
	$page->tags['PageTitle'] =  'Setup';
	$page->tags['Body']=	 $bodyinsert;
	$page->render();
}



###  GRAPH
###
###############################################

if ($do=='graph') {

	//get element
	$query= "
		select * from blackboxelements
		where id_element=':id_element'
	";	
	$params= array('id_element'=>$id_element);
	$result= $db->query($query,$params) or codeerror('DB error',__FILE__,__LINE__);
	$series_insert='';
	if ($row= $db->fetch_row($result)) {
		$id_element=  $row['id_element'];
		$name=        $row['name'];
		$settings=    $row['settings'];
		$settings= unserialize($settings);
	}
	else die('Invalid id '.__LINE__);

	//graph
	$name=       htmlfriendly($name);
	$width=      htmlfriendly($settings['width']);
	$height=     htmlfriendly($settings['height']);
	$ymax=       htmlfriendly($settings['ymax']);
	$ymin=       htmlfriendly($settings['ymin']);
	$average=    htmlfriendly($settings['average']);
	$linethick=  htmlfriendly($settings['linethick']);
	$linesmooth= htmlfriendly($settings['linesmooth']);
		
	//and series
	if (isset($settings['datapts'])){	
		foreach ($settings['datapts'] as $series=> $s) {
			$sname=      htmlfriendly($s['name']);
			$linecolor= htmlfriendly($s['linecolor']);
			$multiplier= htmlfriendly($s['multiplier']);
			$series_insert.= "
				<tr>
					<td>$series.</td>
					<td>$sname</td>
					<td><i>$linecolor</i></td>
					<td><i>$multiplier</i></td>
					<td>
						<a href='setup.php?do=editseries&amp;id_element=$id_element&amp;series=$series'>Edit</a> 
						<a href='setup.php?do=delseries&amp;id_element=$id_element&amp;series=$series'>Del</a>
					</td>
				</tr>
			";
		}		
	}
	else $series_insert= 'None';

	//build output
	$backto='setup.php';
	
	$output="
		<h3 class='section'>Graph - <a href='setup.php?do=editgraph&amp;id_element=$id_element'>Edit</a> 
					         <a href='setup.php?do=delelement&amp;id_element=$id_element'>Del</a>
		</h3>
		<table class='padright'>
			<tr><td>Label:</td>          <td><b>$name</b></td></tr>
			<tr><td>Width:</td>          <td>$width px</td></tr>
			<tr><td>Height:</td>         <td>$height px</td></tr>
			<tr><td>Y max:</td>          <td>$ymax</td></tr>
			<tr><td>Y min:</td>          <td>$ymin</td></tr>
			<tr><td>Average:</td>        <td>$average minutes</td></tr>
			<tr><td>Line thickness:</td> <td>$linethick px</td></tr>
			<tr><td>Line smoothing:</td> <td>$linesmooth</td></tr>
		<table>
	
		<h3 class='section'>Series - <a href='setup.php?do=editseries&amp;id_element=$id_element'>Add</a></h3>
		<table class='padright'>
			$series_insert
		</table>
		
		<div style='margin-top:2em; text-align:right'>
			<input type='button' value='Close'  onClick=\"document.location.href='$backto';\"  />
		</div>
	";

	//Display page
	$page->tags['PageTitle'] = 'Setup';
	$page->tags['Body'] =	   $output;
	$page->render();
}


###  VIEW
###
###############################################

$id_view= 1;

//get view element names
$panes=$panelines= array();
$query= "
	select * from blackboxelements
	where id_view=':id_view'
	order by panetag,position
";	
$params= array('id_view'=>$id_view);
$result= $db->query($query,$params) or codeerror('DB error',__FILE__,__LINE__);
while ($row= $db->fetch_row($result)) {
	$id_element=  $row['id_element'];
	$name=        $row['name'];
	$panetag=     $row['panetag'];
	$type=        $row['type'];
	$settings=    $row['settings'];
	
	//hash lines per pane 
	if (!isset($panelines[$panetag])) $panelines[$panetag]='';
	
	//datapts
	if ($type=='d') {
		$panelines[$panetag].= "
			<div class='datapt'>
				$name -
				<span class='small'>
					<a href='setup.php?do=editdatapt&amp;id_element=$id_element'>Edit</a> 
					<a href='setup.php?do=delelement&amp;id_element=$id_element'>Del</a>
				</span>
			</div>
		";
	}
	//graphs
	else {
		$settings= unserialize($settings);
		$series_insert='';
		if (isset($settings['datapts'])){	
			foreach ($settings['datapts'] as $series=> $bla) {
				$series_insert.= "&nbsp&nbsp;".$settings['datapts'][$series]['name']."<br/>\n";
			}		
		}
		$panelines[$panetag].= "
			<div class='graph'>
				$name - <a class='small' href='setup.php?do=graph&amp;id_element=$id_element'>Configure</a>
				<br/>
				<span class='small'>$series_insert</span>
			</div>
		";
	}
}

//build element columns by pane
$head=$tbody='';
foreach ($panetags as $panetag) {
	if (!isset($panelines[$panetag])) continue; 
	$lines= isset($panelines[$panetag]) ? $panelines[$panetag] : '';
	$head.= "<th><h3>$panetag</h3></th>";
	$tbody.="<td>$lines</td>";
}

//Display page
$page->tags['PageTitle']=  'Setup';
$page->tags['LeftSidebar']=  "
	<p style='font-style:italic;'>
		Place datapoint and/or graph elements into the View template panes. 
	</p>
	<a href='setup.php?do=checkdb'>Check db</a>
";
$page->tags['Body']=	"
	<table style='clear:right;margin-bottom:5px' class='wborder autohide'>
		<tr>$head</tr>
		<tr>$tbody</tr>
	</table>
	<input type='button' value='Add datapt'  onClick=\"document.location.href='setup.php?do=editdatapt';\"  />
	<input type='button' value='Add graph'  onClick=\"document.location.href='setup.php?do=editgraph';\"  />
";
$page->render();


?>