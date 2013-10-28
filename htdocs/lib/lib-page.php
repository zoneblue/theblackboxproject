<?php

class Page {

	public    $tags=array();
	protected $page=array();
	protected $template;
	protected $templ_dir;

	public function __construct ($template) {
		$template=basename($template);
		if (!$template)  $template= 'template1.html';
		$this->templ_dir= dirname(__FILE__).'/../templates';
		$this->template= $template;
	}
	
	public function set_template($template) {
		$this->template= basename($template);
	}
	
	public function find_tags($template=''){
		if (!$template) $template= $this->template;
		
		//get template html
		$templatefile= $this->templ_dir.'/'.$template;
		if ($this->template and file_exists($templatefile)) {
			$templatehtml= join('',file($templatefile));
			
			//preg to get tags
			if (preg_match_all("/\{([a-z]+(?:\:\:[a-z0-9]+)?)\}/i",$templatehtml,$m)){
				return $m[1];
			}
			else return false;
		}		
		else   return false;
	}
	
	
	
	// RENDER is the fn used to render final html pages
	// it basically merges the template html file with {YourTags}
	// $page is a hash containing tag name and value pairs
	// template is the filename sans path of the template html file
	// tags may only contain \w characters and are case sensitive

	public function render($page=array(),$template=''){

		$page= array_merge($this->tags,$page);
		if ($template) $this->template= $template;
		
		
		//get template html
		$template= basename($this->template);
		if ($template and file_exists("{$this->templ_dir}/$template")) $templatehtml= join('',file("{$this->templ_dir}/$template"));
		else                                                           $templatehtml= "{Body}";
			

		// PARSE
		// insert $page hash varables into template
		// BION you can parse in one line, as indeed this fn did for almost a decade
		// print preg_replace ("/(?<!\!)\{([\w:]+)\}/e", '$page["$1"]', $templatehtml);

		$finalhtml='';	$tail="  $templatehtml  "; //needs 2 chars either side
		while (preg_match("/^(.+?[^!])\{([\w:]+)\}(.+)$/s",$tail,$m)){	 
			$head=$m[1]; $tag=$m[2]; $tail=$m[3];
			//check tag
			//replace tag
			if (preg_match("/^\w+::\w+$/",$tag) or preg_match("/^\w+$/",$tag)){
				if    (isset($page[$tag]))  {
					$finalhtml.=$head.$page[$tag];
				}
				elseif(isset($legacypagemap[$tag]) and isset($page[$legacypagemap[$tag]]) ) {
					$finalhtml.=$head.$page[$legacypagemap[$tag]];
				}
				else  $finalhtml.=$head."<!--Unknown tag: $tag-->";
			}
			else	$finalhtml.=$head.$tag; //might have been a style! {color:blue}
			//continue walking through bal of file
		}

		//render html and kill script
		echo $this->htmltidy($finalhtml.$tail);
		exit;
	}


	// HTMLTIDY
	// does a token job of re indenting xhtml, without being a full blown tree parser
	// it wont break tags on one line

	protected function htmltidy($wk){
		//avoid borking newlines in form fields 
		$wk= $this->fixtextareanewlines($wk);

		$oldlines =explode("\n",$wk);
		$newlines='';	$tabcount=0;
		foreach($oldlines as $line){
			$line=trim($line); //rid old indents
			if (!$line) continue;

			// a one line open and close
			if     (preg_match("%^<(\w+)[^>]*>.*</\\1>$%",$line)) $inc='0'; 
			elseif (preg_match("%^<\w[^>]+ />$%",$line))          $inc='0'; 
			//one open or close
			elseif (preg_match("%^<\w[^>]*>$%",$line))            $inc='+';  
			elseif (preg_match("%^</\w[^>]*>$%",$line))           $inc='-'; 
			//one opener or closer + content
			elseif (preg_match("%^<(\w[^>]+)>%",$line))           $inc='+'; 
			elseif (preg_match("%^</(\w[^>]+)>%",$line))          $inc='-'; 
			//doctype
			elseif (preg_match("%^<!(\w[^>]+)>%",$line))          $inc='0'; 
			else                                                  $inc='0'; 

			if ($inc=='-') $tabcount--;
			if ($tabcount>0) $line = str_repeat("\t",$tabcount).$line;// tab would be better except for FF's crazily huge indent
			$newlines.=$line."\n";
			if ($inc=='+') $tabcount++;
		}
		return ($newlines);
	}

	// used to fix the tabulation that html tidy would impart on textarea content
	protected function textareafriendly($wk){
		$wk= preg_replace("/\x0d\x0a/","&#10;",$wk);
		$wk= preg_replace("/\x0d/","&#10;",$wk);
		$wk= preg_replace("/\x0a/","&#10;",$wk);
		return ($wk);
	}

	//converts \n within a textarea value into &#10 to maintain htmltidy integrity
	protected function fixtextareanewlines($wk){
		$tail=" $wk "; //needs 1 char either side
		$wk='';
		while (preg_match("/^(.+?)(< *textarea.+?textarea *>)(.+)$/s",$tail,$m)){	 
			$head=$m[1]; $tag=$m[2]; $tail=$m[3];
			//clean textarea
			$tag=preg_replace("|<\s+textarea|i","<textarea",$tag);
			$tag=preg_replace("|<\s+/textarea|i","</textarea",$tag);
			$tag=preg_replace("|</\s+textarea|i","</textarea",$tag);
			$tag=preg_replace("|/textarea +>|i","textarea>",$tag);
			//clean textarea value
			if (preg_match("|(<textarea[^>]+>)(.+?)</textarea>|si",$tag,$m)){
				$textareahead= $m[1];
				$textareavalue=$m[2];
				$textareavalue=preg_replace("/\x0d\x0a/","&#10;",$textareavalue);
				$textareavalue=preg_replace("/\x0d/","&#10;",$textareavalue);
				$textareavalue=preg_replace("/\x0a/","&#10;",$textareavalue);
				$tag=$textareahead.$textareavalue.'</textarea>';
			}
			$wk.=$head.$tag;
		}
		return ($wk.$tail);
	}


	//class ends
}


?>