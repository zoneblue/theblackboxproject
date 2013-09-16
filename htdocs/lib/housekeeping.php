<?php





// used to protect ids from inject silliness
function isgoodid($wk){
	if (!$wk)                      return 0;	//empty or zero
	if (strlen($wk)>6)             return 0;  //more than 999,999
	if (preg_match("/[^\d]/",$wk)) return 0;  //contains non digits
	return 1;
}
function isbadid($wk){
	if (isgoodid($wk)) return 0;	
	else               return 1;
}

function getpost($nm){
	//get form var
	if     (isset($_POST[$nm]))  $wk= $_POST[$nm];
	elseif (isset($_GET[$nm]))   $wk= $_GET[$nm];
	else                         $wk='';
	
	if (get_magic_quotes_gpc()) $wk=stripslashes($wk);

	// once and for all time rid any dos newlines
	$wk=preg_replace("/\x0d\x0a/","\n",$wk); //dos
	$wk=preg_replace("/\x0d/",    "\n",$wk); //mac
	$wk=preg_replace("/\x0a/",    "\n",$wk); //unix
	# $wk=preg_replace("/\t/","    ",$wk);   # tab /x09, /n is x0A
	
	//ditch balance of ctrl chars
	$wk=preg_replace('~[\x00-\x08]~',"",$wk); //null to tab  
	$wk=preg_replace('~[\x0B-\x1F]~',"",$wk); //tab to space

	//make some judicous char conversions for the sake of sanity

	$search[]="~[\x93\x94]~";  $replace[]=   '"';  # smart double quotes
	$search[]="~[\x91\x92]~";  $replace[]=   '\''; # smart single quotes
	$search[]="~[\x95]~";      $replace[]=   '?';   # bullet
	$search[]="~[\x85]~";      $replace[]=   '...'; # ellipsis
	$search[]="~[\x86\x87]~";  $replace[]=   '--'; # m and n dashes
	$search[]="~[\xA0]~";      $replace[]=   ' '; # nbsp
	$search[]="~\xA9~";        $replace[]=   '(c)';
	$search[]="~\xAE~";        $replace[]=   '(R)';
	$search[]="~\xB0~";        $replace[]=   'degrees';
	$search[]="~\xB2~";        $replace[]=   '2';
	$search[]="~\xB3~";        $replace[]=   '3';
	$search[]="~\xBC~";        $replace[]=   '1/4';
	$search[]="~\xBD~";        $replace[]=   '1/2';
	$search[]="~\xBE~";        $replace[]=   '3/4';
	$wk = preg_replace ($search, $replace, $wk);
      
	//cleans up stray white space
	$wk =  preg_replace("/[ \t]+\n/", "\n", $wk); //trailing ws at end of line
	$wk =  preg_replace("/^[ \t]+$/m", '', $wk);  //noisy line
	$wk=trim($wk);

	//worst case over length
	if (strlen($wk)>100000) bail("Form variable ($nm) exceeds 100K");

	//xss precaution
	$wk= preg_replace("/<\s*appl[^>]+>.+?<\s*\/appl[^>]+>/", "<!--tag stripped-->", $wk); 
	$wk= preg_replace("/<\s*scri[^>]+>.+?<\s*\/scri[^>]+>/", "<!--tag stripped-->", $wk); 
	$wk= preg_replace("/<\s*obje[^>]+>.+?<\s*\/obje[^>]+>/", "<!--tag stripped-->", $wk); 
	$wk= preg_replace("/<\s*styl[^>]+>.+?<\s*\/styl[^>]+>/", "<!--tag stripped-->", $wk); 
	

	return $wk;
}

function getpostarray($wk){
	if (preg_match("/[^\w\-]/",$wk)) return; //protect preg
	$myarray=array();
	foreach (array_merge($_GET,$_POST) as $key=>$value){ 
		if (preg_match("/^$wk(\d+)$/",$key,$m) and $value) $myarray[$m[1]]=getpost($wk.$m[1]);
	}		
	return $myarray;
}


function htmlfriendly($wk) {
	$wk = str_replace("\\\"", "\"", $wk);
	$wk = str_replace("\\'", "'", $wk);
	$wk = str_replace("\\\\", "\\", $wk);	
	$wk=  htmlspecialchars($wk, ENT_QUOTES);
	return ($wk);
}	




// CODEERROR is used for all fatal errors not caused by the user
// msg is what the user will see, 
// if debug is set they will also see the filename and linenumber of the error, and the full error msg
// other wise the latter is emailed to the webmaster email address

function codeerror($msg,$file,$line){

	$db=                 (isset($GLOBALS['db'])) ?               $GLOBALS['db']: false;
	$DEBUGON=            (isset($GLOBALS['DEBUGON'])) ?          $GLOBALS['DEBUGON']: 1;
	$uid=                (isset($GLOBALS['id_person'])) ?        $GLOBALS['id_person']: 0;
	$ROOTUSER=           (isset($GLOBALS['ROOTUSER'])) ?         $GLOBALS['ROOTUSER']: 0;
	$SHORTSERVICENAME=   (isset($GLOBALS['SHORTSERVICENAME'])) ? $GLOBALS['SHORTSERVICENAME']: 'WS';
	$DTEMPLATE=          (isset($GLOBALS['DTEMPLATE'])) ? $GLOBALS['DTEMPLATE']: 'template1.html';

	$WEBMASTEREMAIL='';
	$DEBUGON=1;

	//record get and post
	$cget='';$cpost='';

	if ($_GET) {
		$c=0; 
		foreach ($_GET as $field=>$value)  {
			if ($c++) $cget .= "&";
			$cget .= "$field=$value";
		}
	} 
	else $cget="Nil";
	if ($_POST) {
		foreach ($_POST as $field=>$value) {$cpost .= "$field=$value\n";}
	}
	else $cpost="Nil";
	
	//db error?
	if ($db and $db->error) $cdb="SQL Error: ".$db->error."\n";
	elseif (mysql_error())  $cdb="SQL Error: ".mysql_error()."\n";
	else  $cdb='';
	if ($db->last_query) $cdb2="SQL Query: ".$db->last_query."\n";
	else $cdb2='';
	
	//generate full report
	$report=
		"CODE ERROR\n".
		"__________________________________________________\n".
		"Type: $msg\n".
		"Script: http://{$_SERVER['SERVER_NAME']}{$_SERVER['SCRIPT_NAME']}\n".
		"File: $file (line $line)\n".
		"\n".
		$cdb2.
		$cdb.
		"\n".
		"GET: $cget\n".
		"POST: $cpost\n".
		"UID: $uid";


	// Only include error info if in debug mode
	if ($DEBUGON or $uid==$ROOTUSER) 
		$debugins="
			<p>
				Error type: $msg<br />
				Script: http://{$_SERVER['SERVER_NAME']}{$_SERVER['SCRIPT_NAME']}<br />
				File: $file (line $line)<br />
			</p>
	";
	else $debugins='';

	// Email me
	if ($WEBMASTEREMAIL){
		$to=        "$f";
		$subject=   "[$SHORTSERVICENAME] Code error";
		$headers =  "From: $WEBMASTEREMAIL\n".
		            "Errors-To: $WEBMASTEREMAIL";
		$mailbody=  $report;

		mail($to, $subject, $mailbody, $headers);
	}
	
	// Render page
	if (file_exists("templates/$DTEMPLATE")) {
	
		$page['PageTitle']=   "Error";
		$page['PageHeading']= "Error";
		$page['Body']=  "
				<h2>ERROR</h2>
				<p>
					Unfortunately the server has encountered a technical problem delivering this page. 
					The webmaster has been notified and the issue should be rectified shortly.
				</p>
				<hr />
				$debugins
				<hr />
		";
		tparse($page,$DTEMPLATE);
	}
	else {
		echo "
			<div style='width:600px; margin:0 auto;'>
				<h2>ERROR</h2>
				<p>
					Unfortunately the server has encountered a technical problem delivering this page. 
					The webmaster has been notified and the problem should be rectified shortly.
				</p>
				<hr />
				$debugins
				<hr />
			</div>
		";
		exit;
	}
		
}





?>