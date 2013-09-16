<?php


function  getpostdate($dname) {
	$minute=  getpost($dname.'_minute') ? zeroleftpad((int)getpost($dname.'_minute'),2)  : '00';
	$hour=    getpost($dname.'_hour')   ? zeroleftpad((int)getpost($dname.'_hour'),2)  : '12';
	$day=   getpost($dname.'_day')   ? zeroleftpad((int)getpost($dname.'_day'),2)  : 1;
	$month= getpost($dname.'_month') ? zeroleftpad((int)getpost($dname.'_month'),2): 1;
	$year=  getpost($dname.'_year')  ? (int)getpost($dname.'_year') : date("Y");
	return ("$year-$month-$day $hour:$minute");
}
function zeroleftpad($wk,$n){
	return  (str_pad($wk, $n, "0", STR_PAD_LEFT));
}


class Form {
	
	var $errors= array();
	
	function error($key) {
		if (array_key_exists($key, $this->errors))	return "<span class='form-error'>{$this->errors[$key]}</span>";
		else return '';
	}
	
	function seterror($key,$val) {
		$this->errors[$key]=$val;
	}
	

	function make_spambot_fields() {
		$out='';
		
		$ip= $_SERVER['REMOTE_ADDR'];
		$valival= md5($_SERVER['DOCUMENT_ROOT'].$_SERVER['PHP_SELF'].$_SERVER['SERVER_NAME'].$_SERVER['HTTP_USER_AGENT'].date("YmdH"));

		$out.="<input type='hidden' name='ip' value='$ip' />\n";
		$out.="<input type='hidden' name='valival' value='$valival' />\n";
		$out.="<input type='hidden' name='valival2' value='' />\n";
		$out.="<input type='text' name='url' value='' class='vhp' />\n";
		
		//set session var
		$_SESSION['valival3']= 'SessionValid';
		
		return $out;
	}

	function spambot_check() {
		
		$spam_errs=array();
		$intotal= implode($_POST).implode($_GET);
		
		//start with maxlength violation
		if (strlen($intotal)>10000)  $spam_errs[]= 1; 

		//urls and stray nasties
		if(preg_match("/bcc:|cc:|multipart|<script|<iframe|\[url|Content-Type:/i", $intotal)) $spam_errs[]= 2;
		
		if(preg_match("~\b((http|https|ftp|gopher)://[a-zA-Z0-9]+)|([a-zA-Z][a-zA-Z0-9]+\.[a-zA-Z0-9]+\.[a-zA-Z]{2,4}(\.[a-zA-Z]{2,4})?)\b~",$intotal)) $spam_errs[]= 3;
		elseif(preg_match("~\b(http|https|ftp|gopher)://[a-z0-9.]+\.[a-z]{2,6}\b~i",$intotal)) $spam_errs[]= 3;
		elseif(preg_match("~\bwww\.[a-z0-9.]+\.[a-z]{2,6}\b~i",$intotal)) $spam_errs[]=3;

		//ip
		$ip= $_SERVER['REMOTE_ADDR'];
		if (!$this->isgoodipaddress($ip))   $spam_errs[]= 4;

		//enhanced valival/timestamp combo, md5 is valid for between 1 and 2 hours
		$valival=  $_POST['valival'];
		$valival2= $_POST['valival2'];
		$url=      $_POST['url'];
		$code1=   md5($_SERVER['DOCUMENT_ROOT'].$_SERVER['PHP_SELF'].$_SERVER['SERVER_NAME'].$_SERVER['HTTP_USER_AGENT'].date("YmdH"));
		$code2=   md5($_SERVER['DOCUMENT_ROOT'].$_SERVER['PHP_SELF'].$_SERVER['SERVER_NAME'].$_SERVER['HTTP_USER_AGENT'].date("YmdH",strtotime('now -1 hour')));
		if    (($valival <> $code1) && ($valival <> $code2))  $spam_errs[]= 5;		
		
		//js set
		if    (($valival2<>"Valid"))   $spam_errs[]= 6;	

		//session
		if (!isset($_SESSION['valival3']) or $_SESSION['valival3']<>'SessionValid') $spam_errs[]= 7;
		
		//referer FWIW
		if(!(isset($_SERVER['HTTP_REFERER']) && stristr($_SERVER['HTTP_REFERER'],$_SERVER['HTTP_HOST'])))  $spam_errs[]= 8;
		
		//honey pot
		$url= $_POST['url'];
		if ($url) $spam_errs[]= 9;
		
		return $spam_errs;
	}
	

	function makeradiogroup($radio) {
		$attrs=$out='';
		
		if (!isset($radio['selected']))  $radio['selected'] ='';
		
		$name =        $radio['name'];            unset($radio['name']);
		$selected =    $radio['selected'];        unset($radio['selected']);
		$options =     $radio['options'];         unset($radio['options']);
		
		//make attrs
		if (count($radio)) {
			foreach($radio as $k=>$v) {
				if (!$v) continue;
				$v=htmlfriendly($v); 
				$attrs.=" $k='$v'";
			}
		}
		
		//make radios
		if (count($options)) {
			foreach($options as $k=>$o){
				$v= $o['value'];
				$d= (isset($o['disabled']) and $o['disabled']) ? true : false;;
				$dis= $d ? ' disabled="disabled"' : '';
				$sel= (!$d && $k==$selected) ? ' checked="checked"' : '';
				$v= htmlfriendly($v);
				$c= $d ? " class='greyed'" : '';
				$out.= "
					<div class='radiogroup'>
						<label class='within'>
							<input type='radio'$dis name='$name' value='$k'$sel />
							<span$c>$v</span>
						</label>
					</div>
				";
			}
		}
		
		//all good
		return $out;
	}

	function makecheckboxgroup($checkbox) {
		$attrs=$out='';
	
		$name =        $checkbox['name'];            unset( $checkbox['name']);
		$options =     $checkbox['options'];         unset( $checkbox['options']);
		
		//make attrs
		if (count($checkbox)) {
			foreach($checkbox as $k=>$v) {
				if (!$v) continue;
				$v=htmlfriendly($v); 
				$attrs.=" $k='$v'";
			}
		}
		
		//make checkboxes
		if (count($options)) {
			foreach($options as $k=>$o){
				$v= $o['value'];
				$d= (isset($o['disabled']) and $o['disabled']) ? true : false;;
				$c= (isset($o['checked']) and $o['checked']) ? true : false;;
				$dis= $d ? ' disabled="disabled"' : '';
				$sel= $k==$c ? ' checked="checked"' : '';
				$v= htmlfriendly($v);
				$out.= "
					<div class='checkboxgroup'>
						<label>
							<input type='checkbox'$dis name='{$name}_$k' value='$k'$sel />
							<span>$v</span>
						</label>
					</div>
				";
			}
		}
		
		//all good
		return $out;
	}	
	
	/**
	* MAKESELECT
	* @arg (array) 
	*   'name'=> 'myselect', 
	*   'selected'=> '0', 
	*   'options'=>array('0'=>'Myopt1','1'=>'Myopt2'), 
	*   'class'=> 'myselects',
	*   'onchange'=> 'myfunction()',
	* 
	* @return (string) html
	**/
	function makeselect($select) {
		$oa=$optionsins='';
		
		if (!isset($select['selected']))  $select['selected'] ='';
		
		$name =        $select['name'];            unset($select['name']);
		$selected=     $select['selected'];        unset($select['selected']);
		$options =     $select['options'];         unset($select['options']);
		$emptyoption = $select['emptyoption'];     unset($select['emptyoption']);
		
		//make options
		if($emptyoption) {
			$v= htmlfriendly($emptyoption);
			$optionsins.="<option value=''>$v</option>\n";
		}
		if (count($options)) {
			foreach($options as $k=>$o){
				$v= $o['value'];
				$d= (isset($o['disabled']) and $o['disabled']) ? true : false;;
				$dis= $d ? ' disabled="disabled"' : '';
				$sel= $k==$selected ? ' selected="selected"' : '';
				$v= htmlfriendly($v);
				$optionsins.= "<option$dis value='$k'$sel>$v</option>\n";
			}
		}
		//make attrs
		if (count($select)) {
			foreach($select as $k=>$v) {
				if (!$v) continue;
				$v=htmlfriendly($v); 
				$oa.=" $k='$v'";
			}
		}
		//make select
		return "<select name='$name'$oa>\n$optionsins</select>\n";
	}


	//MAKEDATESELECT
	//@arg (string) selectname : name attribute 
	//@arg (array) todos : php date component letters in order of appearance eg: Array('Y','m','d');
	//@arg (string) selected : the option to set as selected by value
	//@return (string) html

	function makedateselect($select) {
		
		if (!isset($select['selected']))   $select['selected'] ='';
		
		$select_name = $select['name'];            unset($select['name']);
		$date_selected =    $select['selected'];   unset($select['selected']);
		$spec=         $select['spec'];           unset($select['spec']);

		$todos= str_split(preg_replace("/[^dmFYHis]/", '', $spec));
		if ($date_selected=='') $date_selected= date("Y-m-d H:i:s");

		$attrs='';
		if (count($select)) {
			foreach($select as $k=>$v) {
				if (!$v) continue;
				$v=htmlfriendly($v); 
				$attrs.=" $k='$v'";
			}
		}

		$out='';
		foreach ($todos as $todo) {
			//day of month
			if ($todo=='d'){
				$out.="<select name='{$select_name}_day'$attrs>\n";
				$selday= date("d",strtotime($date_selected));
				for ($i=1; $i<=31; $i++){
					$i2= zeroleftpad($i,2);
					$sel= ($i==$selday)?(' selected="selected"'):('');
					$out.= "<option value='$i'$sel>$i2</option>\n";
				}
				$out.= "</select>\n";
			}
			//month
			elseif ($todo=='m'){
				$out.="<select name='{$select_name}_month'$attrs>\n";
				$selmonth= date("m",strtotime($date_selected));
				for ($i=1; $i<=12; $i++){
					$i2= zeroleftpad($i,2);
					$sel= ($i==$selmonth) ? (' selected="selected"'):('');
					$out.= "<option value='$i'$sel>$i2</option>\n";
				}
				$out.= "</select>\n";
			}
			elseif ($todo=='F'){ //text month
				$out.="<select name='{$select_name}_month'$attrs>\n";
				$selmonth= date("m",strtotime($date_selected));
				for ($i=1; $i<=12; $i++){
					$i2= date("F", mktime(0, 0, 0, $i, 1, 0));
					$sel= ($i==$selmonth) ? (' selected="selected"'):('');
					$out.= "<option value='$i'$sel>$i2</option>\n";
				}
				$out.= "</select>\n";
			}
			//year
			elseif ($todo=='Y'){
				$out.="<select name='{$select_name}_year'$attrs>\n";
				$nowyear= date("Y"); 
				$selyear= date("Y",strtotime($date_selected));
				$startyear = min($nowyear, $selyear);
				$endyear =   max($nowyear+5, $selyear+5);
				for ($i=$startyear; $i<=$endyear; $i++){
					$i2=$i;
					$sel= ($i==$selyear) ? (' selected="selected"'):('');
					$out.= "<option value='$i'$sel>$i2</option>\n";
				}
				$out.= "</select>\n";
			}
			//minute
			elseif ($todo=='i'){
				$out.="<select name='{$select_name}_minute'$attrs>\n";
				$selminute= date("i",strtotime($date_selected));
				for ($i=0; $i<60; $i++){
					$i2= zeroleftpad($i,2);
					$sel= ($i==$selminute) ? (' selected="selected"'):('');
					$out.= "<option value='$i'$sel>$i2</option>\n";
				}
				$out.= "</select>\n";
			}
			//hour
			elseif ($todo=='H'){
				$out.="<select name='{$select_name}_hour'$attrs>\n";
				$selhour= date("H",strtotime($date_selected));
				for ($i=0; $i<24; $i++){
					$i2= zeroleftpad($i,2);
					$sel= ($i==$selhour) ? (' selected="selected"'):('');
					$out.= "<option value='$i'$sel>$i2</option>\n";
				}
				$out.= "</select>\n";
			}
		}
		return $out;
	}


	function isgoodipaddress($ip) {
		$parts=explode('.',$ip);
		if (count($parts) <>4) return false;
		if ($parts[0]>256 or  $parts[0]<0) return false;
		if ($parts[1]>256 or  $parts[1]<0) return false;
		if ($parts[2]>256 or  $parts[2]<0) return false;
		if ($parts[3]>256 or  $parts[3]<0) return false;
		return true;
	}


}



?>