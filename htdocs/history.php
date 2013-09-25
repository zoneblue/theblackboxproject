<?php

/** 
 *  HISTORY.PHP
 *  =========
 *  View previous periods data.
 *  
 *  @package:  The Blackbox Project.
 *  @author:   Peter 2013
 *  @license:  GPLv3. 
 *  @revision: $Rev$
 *
 **/ 


### Prep

include('init.php');

//new page instance
$page= new Page('template-setup.html');

//set nav throughout
$page->tags['Nav']= "<a href='index.php'>View</a> <a href='history.php'>History</a> <a href='setup.php'>Setup</a>";	



###  VIEW
###
###############################################


//Display page
$page->tags['PageTitle']=  'History';
$page->tags['Body']=	"
	<p>Not implemented yet.</p>
	<input type='button' value='Close'  onClick=\"document.location.href='setup.php'\"  />
";
$page->render();


?>