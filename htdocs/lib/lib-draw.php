<?php

/*
	DRAW
	a graphics wrapper class to add some functionality to gd. 
	later to provide backend graphics lib flexibility
	ie: adding imagemagick support

	mostly functions borrowed from pchart. (GPL)
	color conversion,polylines, and improved wide line support are original work. peter (GPLv3)
	
	usage: 
	$this->draw= new Draw($width, $height);
	$opts = array('color'=>'#ccc','linewidth'=>3,'smooth'=>true, alpha=>80);
	$this->draw->line($x1,$y1,$x2,$y2,$opts);

*/



class Draw {

	public    $img;
	protected $antialiasquality= 1;	// quality of the antialiasing implementation (0-1) ??
	protected $fontalpha	= 100;			
	protected $xsize;			
	protected $ysize;			

	//CONSTRUCTOR
	//creates a blank gd canvas
	function __construct($xsize,$ysize,$istrans=false) {

		$this->xsize= $xsize;
		$this->ysize= $ysize;
		
		$this->img = imagecreatetruecolor($xsize,$ysize);
		
		if ($istrans) {
			imagealphablending($this->img,false);
			imagefilledrectangle($this->img, 0,0,$xsize,$ysize,imagecolorallocatealpha($this->img,255,255,255,127));
			imagealphablending($this->img,true);
			imagesavealpha($this->img,true); 
		}
		else {
			imagefilledrectangle($this->img,0,0,$xsize,$ysize,imagecolorallocate($this->img,255,255,255));
		}
	}	
	


	// POLYLINE
	// Draws multi-segment lines
	// @arg: (array) of point arrays (x,y)
	// @arg: (array) of options
	
	function polyline($points,$opts) {
		$color=		 isset($opts["color"]) ? $opts["color"] : '#999';
		$alpha=		 isset($opts["alpha"]) ? $opts["alpha"] : 100;
		$linewidth=  isset($opts["linewidth"]) ? $opts["linewidth"] : 1;
		$smooth=		 isset($opts["smooth"]) ? $opts["smooth"] : true;
		$joinmethod= isset($opts["joinmethod"]) ? $opts["joinmethod"] : 'none';
		
		$bopts=$opts;
		
		$joinmethod = $linewidth>3 ? $joinmethod : 'none';
		
		//Where each segment is drawn alone
		if ((count($points)==2) or $joinmethod=='round' or $joinmethod=='none') {
			foreach ($points as $n=> $point)	{
				if (!$n) continue;
				
				list($x1,$y1)= $points[$n-1];
				list($x2,$y2)= $point;
				$this->line($x1,$y1,$x2,$y2,$opts);

				//for now draw a lazy intersection point, will look crap with alpha
				if ($joinmethod=='round') {
					imagesetthickness($this->img,1);
					$gd_color = $this->allocatecolor($color,$alpha);
					# size dep,use point, rect or circle #
					# smooth and alpha to fix            #
					# todo                               #
					imagefilledellipse($this->img,$x1,$y1,$linewidth,$linewidth,$gd_color);
				}
			}
			return true;
		}
		
		//Where intersection method must take into account the other segments
		elseif ($joinmethod=='angle') {
			
			//we work on set of three points
			//p0 skip
			//p1 first
			//p2 mid
			//p3 mid
			//p4 last
			//p5 skip
			//npoints is the array for the new line component arrays
			//npoints[n][0] is the inner polygon
			//npoints[n][1-2] are the outer 1px wide lines
			//npoints[n][3+] are any inner 1px lines 
			
			$npoints= array(); 
			$first= 1; 
			$last= count($points)-2;
		
			foreach ($points as $n=> $point)	{
				
				//skip first point
				if ($n==0) continue;
				
				//the left end of line segment 1 is a square butt
				if ($n==$first) {

					list($x1,$y1)= $points[$n-1]; //p1
					list($x2,$y2)= $points[$n];   //p2
					$angle= $this->getangle($x1,$y1,$x2,$y2);
					
					$top_x = cos(deg2rad($angle-90)) * $linewidth/2 + $x1; 
					$top_y=  sin(deg2rad($angle-90)) * $linewidth/2 + $y1;
					$bot_x = cos(deg2rad($angle+90)) * $linewidth/2 + $x1; 
					$bot_y = sin(deg2rad($angle+90)) * $linewidth/2 + $y1;
					
					$npoints[$n-1][1]= array($top_x,$top_y);
					$npoints[$n-1][2]= array($bot_x,$bot_y);
				}
				
				//mid segments
				list($ax,$ay)= $points[$n-1]; //p1 line a start
				list($x,$y)=   $points[$n];   //p2 intersect
				list($bx,$by)= $points[$n+1]; //p3 line b end
				
				$angle_a=	$this->getangle2($ax,$ay,$x,$y);
				$angle_b=	$this->getangle2($x,$y,$bx,$by);
				
				$omega= ($angle_b-$angle_a)/2; //trig to get the intersection of offset of the two lines
				$hyp= $linewidth/(2*cos(deg2rad($omega)));
				$dx=  $hyp * sin(deg2rad($angle_a+$omega));
				$dy=  $hyp * cos(deg2rad($angle_a+$omega));
				
				list($top_x,$top_y)= array($x+$dx, $y-$dy); 
				list($bot_x,$bot_y)= array($x-$dx, $y+$dy); 
		
				$npoints[$n][1]= array($top_x,$top_y);
				$npoints[$n][2]= array($bot_x,$bot_y);

				//the right of the final line segment is a square butt
				if ($n==$last) {
				
					list($x1,$y1)= $points[$n];    //p1
					list($x2,$y2)= $points[$n+1];  //p2
					$angle= $this->getangle($x1,$y1,$x2,$y2);
					
					$top_x = cos(deg2rad($angle-90)) * $linewidth/2 + $x2; 
					$top_y=  sin(deg2rad($angle-90)) * $linewidth/2 + $y2;
					$bot_x = cos(deg2rad($angle+90)) * $linewidth/2 + $x2; 
					$bot_y = sin(deg2rad($angle+90)) * $linewidth/2 + $y2;
					
					$npoints[$n+1][1]= array($top_x,$top_y);
					$npoints[$n+1][2]= array($bot_x,$bot_y);

					break; //skip last point
				}
			}
			
			//draw merged thick line as a single polygon
			$poly_points= array();
			
			foreach ($npoints as $n) {
				$poly_points[]= $n[1][0]; $poly_points[]= $n[1][1];
			}
			foreach (array_reverse($npoints) as $n) {
				$poly_points[]= $n[2][0]; $poly_points[]= $n[2][1];
			}
			$opts = array ('color'=>$color,'alpha'=>$alpha);
			$this->polygon($poly_points,$opts);
		}
	}

	//placeholder functions
	function rectangle($x1,$y1,$x2,$y2,$opts) {
		$color=		isset($opts["color"]) ? $opts["color"] : '#999';
		$alpha=		isset($opts["alpha"]) ? $opts["alpha"] : 100;
		$linewidth= isset($opts["linewidth"]) ? $opts["linewidth"] : 1;
		$smooth=		isset($opts["smooth"]) ? $opts["smooth"] : true;
	
		$gd_color = $this->allocatecolor($color,$alpha);
		ImageFilledRectangle($this->img, $x1,$y1,$x2,$y2, $gd_color);
	}
	function cicrle($x1,$y1,$x2,$y2,$opts) {
		$color=		isset($opts["color"]) ? $opts["color"] : '#999';
		$alpha=		isset($opts["alpha"]) ? $opts["alpha"] : 100;
		$linewidth= isset($opts["linewidth"]) ? $opts["linewidth"] : 1;
		$smooth=		isset($opts["smooth"]) ? $opts["smooth"] : true;
	
		$gd_color = $this->allocatecolor($color,$alpha);
		imagefilledellipse($this->img, $x1,$y1,$x2,$y2, $gd_color);
	}
	
	
	//LINE
	//main line method, which calls various submeths
	function line($x1,$y1,$x2,$y2,$opts) {
		$color=		isset($opts["color"]) ? $opts["color"] : '#999';
		$alpha=		isset($opts["alpha"]) ? $opts["alpha"] : 100;
		$linewidth= isset($opts["linewidth"]) ? $opts["linewidth"] : 1;
		$smooth=		isset($opts["smooth"]) ? $opts["smooth"] : true;
		
		//cleanup , maybe should leave this til later. 
		$x1= round($x1);
		$x2= round($x2);
		$y1= round($y1);
		$y2= round($y2);
		$linewidth= round($linewidth);
		
		$angular= ($x1==$x2 or $y1==$y2) ? false : true;
		$fat=     ($linewidth>3) ? true : false;
		$skinny=  ($linewidth==1) ? true:false;
		
		if ($x1==$x2 and $y1==$y2) return false;
		
		//gd default method, plain non-aa alpha line
		//this is here to provide some faster method 
		//when smoothing or fussy line ends isnt required
		if (!$smooth) {
			$this->simpleline($x1,$y1,$x2,$y2,$opts); 
		}
		
		//for single px wide lines use pcharts antialias function
		//call it twice to build up a bit of color density
		elseif ($skinny) {
			$this->singleline($x1,$y1,$x2,$y2,$opts); 
			$this->singleline($x1,$y1,$x2,$y2,$opts); 
		}
		//for 2 or 3px wide lines use a few lines offset slightly
		//for now uses a stackoverflow hack with paler offset lines behind, works!
		elseif (!$fat) {
			$this->multiline($x1,$y1,$x2,$y2,$opts); 
			//$this->easysmoothline($x1,$y1,$x2,$y2,$opts); 
		}
		
		//for really wide lines use the pchart polygon square butt method
		//its ok, but sucks if theres alpha, fix border overlap
		elseif ($fat) {
			$this->drawlinepchart($x1,$y1,$x2,$y2,$opts); 
		}
		
	}

	function singleline($x1,$y1,$x2,$y2,$opts) {
		$color=		isset($opts["color"]) ? $opts["color"] : '#999';
		$alpha=		isset($opts["alpha"]) ? $opts["alpha"] : 100;
		
		$opts = array("color"=>$color,"alpha"=>$alpha);
		$distance = sqrt(($x2-$x1)*($x2-$x1)+($y2-$y1)*($y2-$y1));	
		$xstep = ($x2-$x1) / $distance;
		$ystep = ($y2-$y1) / $distance;
		
		for ($i=0;$i<=$distance;$i++) {
			$x = $i * $xstep + $x1;
			$y = $i * $ystep + $y1;
			$this->drawantialiaspixel($x,$y,$opts);
		}
	}

	function multiline($x1,$y1,$x2,$y2,$opts) {
		$color=		isset($opts["color"]) ? $opts["color"] : '#999';
		$alpha=		isset($opts["alpha"]) ? $opts["alpha"] : 100;
		$linewidth= isset($opts["linewidth"]) ? $opts["linewidth"] : 1;
		$smooth=		isset($opts["smooth"]) ? $opts["smooth"] : true;
		
		//3px singlelines works if linewidth set to 2
		if ($linewidth==3) {

			$offset= ($linewidth-1)/2;

			$angle= $this->getangle2($x1,$y1,$x2,$y2);
			$ax1 = cos(deg2rad($angle-90)) * $offset + $x1; $ay1 = sin(deg2rad($angle-90)) * $offset + $y1;
			$ax2 = cos(deg2rad($angle-90)) * $offset + $x2; $ay2 = sin(deg2rad($angle-90)) * $offset + $y2;
			$this->singleline($ax1,$ay1,$ax2,$ay2,$opts);		

			$ax1 = cos(deg2rad($angle+90)) * $offset + $x2; $ay1 = sin(deg2rad($angle+90)) * $offset + $y2;
			$ax2 = cos(deg2rad($angle+90)) * $offset + $x1; $ay2 = sin(deg2rad($angle+90)) * $offset + $y1;
			$this->singleline($ax1,$ay1,$ax2,$ay2,$opts);	

			$this->singleline($x1,$y1,$x2,$y2,$opts);
		}
		if ($linewidth==2) {
		
			$offset= ($linewidth-1)/2;

			$angle= $this->getangle2($x1,$y1,$x2,$y2);
			$ax1 = cos(deg2rad($angle-90)) * $offset + $x1; $ay1 = sin(deg2rad($angle-90)) * $offset + $y1;
			$ax2 = cos(deg2rad($angle-90)) * $offset + $x2; $ay2 = sin(deg2rad($angle-90)) * $offset + $y2;
			$this->singleline($ax1,$ay1,$ax2,$ay2,$opts);		
			$this->singleline($ax1,$ay1,$ax2,$ay2,$opts);		

			$ax1 = cos(deg2rad($angle+90)) * $offset + $x2; $ay1 = sin(deg2rad($angle+90)) * $offset + $y2;
			$ax2 = cos(deg2rad($angle+90)) * $offset + $x1; $ay2 = sin(deg2rad($angle+90)) * $offset + $y1;
			$this->singleline($ax1,$ay1,$ax2,$ay2,$opts);	
			$this->singleline($ax1,$ay1,$ax2,$ay2,$opts);	

			//$this->singleline($x1,$y1,$x2,$y2,$opts);
	
		}
		

		#$acolor = $this->allocatecolor('#f00',100);
		#imagesetpixel($this->img,$x1,$y1,$acolor);
		#imagesetpixel($this->img,$x2,$y2,$acolor);
		
		return true;
	}
	
	function drawlinepchart($x1,$y1,$x2,$y2,$opts) {
		$color=		isset($opts["color"]) ? $opts["color"] : '#999';
		$alpha=		isset($opts["alpha"]) ? $opts["alpha"] : 100;
		$linewidth= isset($opts["linewidth"]) ? $opts["linewidth"] : 1;
		$smooth=		isset($opts["smooth"]) ? $opts["smooth"] : true;
		
		$angle= $this->getangle($x1,$y1,$x2,$y2);
		$points= array(); 
		$points[] = cos(deg2rad($angle-90)) * $linewidth/2 + $x1; $points[] = sin(deg2rad($angle-90)) * $linewidth/2 + $y1;
		$points[] = cos(deg2rad($angle+90)) * $linewidth/2 + $x1; $points[] = sin(deg2rad($angle+90)) * $linewidth/2 + $y1;
		$points[] = cos(deg2rad($angle+90)) * $linewidth/2 + $x2; $points[] = sin(deg2rad($angle+90)) * $linewidth/2 + $y2;
		$points[] = cos(deg2rad($angle-90)) * $linewidth/2 + $x2; $points[] = sin(deg2rad($angle-90)) * $linewidth/2 + $y2;
		
		$opts = array ('color'=>$color,'alpha'=>$alpha);
		$this->polygon($points,$opts);
		
		return true;
	}
		
	function polygon($points,$opts) {
		$color=		  isset($opts["color"]) ? $opts["color"] : '#000';
		$alpha=		  isset($opts["alpha"]) ? $opts["alpha"] : 100;
		$bordercolor= isset($opts["bordercolor"]) ? $opts["bordercolor"] : $color;
		$borderalpha= isset($opts["borderalpha"]) ? $opts["borderalpha"] : 100;
		$nofill=      isset($opts["nofill"]) ? $opts["nofill"] : false;

		if (count($points) < 6 ) return false;
		$backup = $points;

		//fill the inside 		
		$gd_fcolor = $this->allocatecolor($color,$alpha);
		imagefilledpolygon($this->img,$points,count($points)/2,$gd_fcolor); 

		//hand draw the border - to get the smoothing we need
		$points = $backup;
		$opts = array("color"=>$color,"alpha"=>$borderalpha);
		
		for ($i=0;$i<=count($points)-1;$i=$i+2) {
			if (isset($points[$i+2]))  $this->singleline($points[$i],$points[$i+1],$points[$i+2],$points[$i+3],$opts);
			else	                     $this->singleline($points[$i],$points[$i+1],$points[0],$points[1],$opts);
		}
		
		return true;
	}


	//TEXT
	//makes a text line, with alignment goodness
	function text($opts) {
		$text=      isset($opts["text"]) ? $opts["text"] : "Empty";
		$x=			isset($opts["x"]) ? $opts["x"] : 0;
		$y=			isset($opts["y"]) ? $opts["y"] : 0;
		$color=		isset($opts["color"]) ? $opts["color"] : '#000';
		$angle=		isset($opts["angle"]) ? $opts["angle"] : 0;
		$align=		isset($opts["align"]) ? $opts["align"] : 'topleft';
		$alpha=		isset($opts["alpha"]) ? $opts["alpha"] : $this->fontalpha;
		$fontname=	isset($opts["fontname"]) ? $opts["fontname"] : '';
		$fontsize=	isset($opts["fontsize"]) ? $opts["fontsize"] : 10;

		#$x= round($x);
		#$y= round($y);


		$txtpos = $this->gettextbox($x,$y,$fontname,$fontsize,$angle,$text);

		$x = $x - $txtpos[$align]["x"] + $x;
		$y = $y - $txtpos[$align]["y"] + $y;

		$gd_textcolor = $this->allocatecolor($color,$alpha);
		imagettftext($this->img,$fontsize,$angle,$x,$y,$gd_textcolor,$fontname,$text);

		return ($txtpos);
	}
		
	/* Return the surrounding box of text area */
	function getTextBox($x,$y,$fontname,$fontsize,$angle,$text) {
		$coords = imagettfbbox($fontsize, 0, $fontname, $text);

		$a = deg2rad($angle); $ca = cos($a); $sa = sin($a); $realpos = array();
		for($i = 0; $i < 7; $i += 2)	{
			$realpos[$i/2]["x"] = $x + round($coords[$i] * $ca + $coords[$i+1] * $sa);
			$realpos[$i/2]["y"] = $y + round($coords[$i+1] * $ca - $coords[$i] * $sa);
		}

		$realpos['bottomleft']["x"]	= $realpos[0]["x"];		
		$realpos['bottomleft']["y"]	= $realpos[0]["y"];
		$realpos['bottomright']["x"]	= $realpos[1]["x"]; 
		$realpos['bottomright']["y"]	= $realpos[1]["y"];
		$realpos['topleft']["x"]		= $realpos[3]["x"];		
		$realpos['topleft']["y"]			= $realpos[3]["y"];
		$realpos['topright']["x"]		= $realpos[2]["x"];							
		$realpos['topright']["y"]			= $realpos[2]["y"];
		$realpos['bottommiddle']["x"]	= ($realpos[1]["x"]-$realpos[0]["x"]) /2 + $realpos[0]["x"];	
		$realpos['bottommiddle']["y"]	= ($realpos[0]["y"]-$realpos[1]["y"]) /2 + $realpos[1]["y"];
		$realpos['topmiddle']["x"]			= ($realpos[2]["x"]-$realpos[3]["x"]) /2 + $realpos[3]["x"];		
		$realpos['topmiddle']["y"]		= ($realpos[3]["y"]-$realpos[2]["y"]) /2 + $realpos[2]["y"];
		$realpos['middleleft']["x"]	= ($realpos[0]["x"]-$realpos[3]["x"]) /2 + $realpos[3]["x"];		
		$realpos['middleleft']["y"]	= ($realpos[0]["y"]-$realpos[3]["y"]) /2 + $realpos[3]["y"];
		$realpos['middleright']["x"]	= ($realpos[1]["x"]-$realpos[2]["x"]) /2 + $realpos[2]["x"];	
		$realpos['middleright']["y"]	= ($realpos[1]["y"]-$realpos[2]["y"]) /2 + $realpos[2]["y"];
		$realpos['middlemiddle']["x"]	= ($realpos[1]["x"]-$realpos[3]["x"]) /2 + $realpos[3]["x"];	
		$realpos['middlemiddle']["y"]	= ($realpos[0]["y"]-$realpos[2]["y"]) /2 + $realpos[2]["y"];

		return($realpos);
	}
		
		
	/* draw an aliased pixel */
	function drawantialiaspixel($x,$y,$opts) {
		$color=	isset($opts["color"]) ? $opts["color"] : '#f00';
		$alpha = isset($opts["alpha"]) ? $opts["alpha"] : 100;

		if ( $x < 0 || $y < 0 || $x >= $this->xsize || $y >= $this->ysize ) return(-1);

		$xi= floor($x); //floor
		$yi= floor($y);

		//whole pixels
		if ( $xi == $x && $yi == $y) 	$this->drawalphapixel($x,$y,$color,$alpha);
		
		//a 4px square
		else 		{
			$alpha1 = (((1 - ($x - floor($x))) * (1 - ($y - floor($y))) * 100) / 100) * $alpha;
			if ( $alpha1 > $this->antialiasquality ) $this->drawalphapixel($xi,$yi,$color,$alpha1);

			$alpha2 = ((($x - floor($x)) * (1 - ($y - floor($y))) * 100) / 100) * $alpha;
			if ( $alpha2 > $this->antialiasquality ) $this->drawalphapixel($xi+1,$yi,$color,$alpha2); 

			$alpha3 = (((1 - ($x - floor($x))) * ($y - floor($y)) * 100) / 100) * $alpha;
			if ( $alpha3 > $this->antialiasquality ) $this->drawalphapixel($xi,$yi+1,$color,$alpha3);

			$alpha4 = ((($x - floor($x)) * ($y - floor($y)) * 100) / 100) * $alpha;
			if ( $alpha4 > $this->antialiasquality ) $this->drawalphapixel($xi+1,$yi+1,$color,$alpha4); 
		}
	}

	/* draw a semi-transparent pixel */
	function drawalphapixel($x,$y,$color,$alpha) {

		if ($x < 0 || $y < 0 ) return(-1);
		if ($x >= $this->xsize || $y >= $this->ysize ) return(-1);

		$acolor = $this->allocatecolor($color,$alpha);
		imagesetpixel($this->img,$x,$y,$acolor);
	}
		

	/* allocate a color with transparency */
	function allocatecolor($color,$alpha=100) {

		//convert html color to rgb
		list($r,$g,$b)= $this->convertcolor($color);
		
		/* convert alpha to base 10 */
		if ( $alpha < 0 )	{ $alpha = 0; }
		if ( $alpha > 100) { $alpha = 100; }
		$alpha = (127/100)*(100-$alpha); 

		
		$gd_col= imagecolorallocatealpha($this->img,$r,$g,$b,$alpha);
		return $gd_col;
	}
		
	//test html color spec 
	function convertcolor($wk){
		//rgb spec
		$wk=strtoupper($wk);
		if (preg_match("/^rgb\((\d{1,3}),(\d{1,3}),(\d{1,3})\)$/i",trim($wk),$m)){
			$r=$m[1]; $g=$m[2]; $b=$m[3];
		}
		//hex spec
		elseif (preg_match("/^#([a-f0-9][a-f0-9])([a-f0-9][a-f0-9])([a-f0-9][a-f0-9])$/i",trim($wk),$m)){
			$r=$m[1]; $g=$m[2]; $b=$m[3];
			$r=hexdec($r);$g=hexdec($g);$b=hexdec($b);
		}
		//short hex
		elseif (preg_match("/^#([a-f0-9])([a-f0-9])([a-f0-9])$/i",trim($wk),$m)){
			$r=$m[1].$m[1]; $g=$m[2].$m[2]; $b=$m[3].$m[3];
			$r=hexdec($r);$g=hexdec($g);$b=hexdec($b);
		}
		else return false; 

		return array($r,$g,$b);
	}
	
	/* return the orientation of a line */
	function getangle($x1,$y1='',$x2='',$y2='') {
		if (is_array($x1)) list($x1,$y1,$x2,$y2)= $x1;	
		$opposite = $y2 - $y1; 
		$adjacent = $x2 - $x1;
		$angle = rad2deg(atan2($opposite,$adjacent));
		if ($angle > 0) { return($angle); } 
		else				{ return(360-abs($angle)); }
	}
	function getangle2($x1,$y1,$x2,$y2) {
		$opposite = $y2 - $y1; 
		$adjacent = $x2 - $x1;
		$angle = rad2deg(atan2($opposite,$adjacent));
		return($angle); 
	}


	function simpleline ($x1, $y1, $x2, $y2, $opts) {
		$color=		isset($opts["color"]) ? $opts["color"] : '#999';
		$alpha=		isset($opts["alpha"]) ? $opts["alpha"] : 100;
		$linewidth= isset($opts["linewidth"]) ? $opts["linewidth"] : 1;
		$smooth=		isset($opts["smooth"]) ? $opts["smooth"] : true;
		
		$fullcol = $this->allocatecolor($color,$alpha);
		imagesetthickness($this->img, $linewidth);
		imageline($this->img,$x1,$y1,$x2,$y2,$fullcol);
	}
	
	//draws lighter cols behind
	function easysmoothline ($x1, $y1, $x2, $y2, $opts) {
		$color=		isset($opts["color"]) ? $opts["color"] : '#999';
		$alpha=		isset($opts["alpha"]) ? $opts["alpha"] : 100;
		$linewidth= isset($opts["linewidth"]) ? $opts["linewidth"] : 1;
		$smooth=		isset($opts["smooth"]) ? $opts["smooth"] : true;

		$usemeth= 1;
		
		if ($usemeth==1) {
			$fullcol = $this->allocatecolor($color,$alpha);
			$halfcol = $this->allocatecolor($color,$alpha*0.4);
			imagesetthickness($this->img, $linewidth);
			imageline($this->img,$x1+1,$y1,$x2,$y2+1,$halfcol);
			imageline($this->img,$x1,$y1-1,$x2+1,$y2,$halfcol);
			imageline($this->img,$x1-1,$y1,$x2,$y2-1,$halfcol);
			imageline($this->img,$x1,$y1+1,$x2-1,$y2,$halfcol);
			imageline($this->img,$x1,$y1,$x2,$y2,$fullcol);
		}
		elseif ($usemeth==2) {
			$fullcol = $this->allocatecolor($color,$alpha);
			$halfcol = $this->allocatecolor($color,$alpha*0.6);
			imagesetthickness($this->img, $linewidth+2);
			imageline($this->img,$x1,$y1+1,$x2-1,$y2,$halfcol);
			imagesetthickness($this->img, $linewidth);
			imageline($this->img,$x1,$y1,$x2,$y2,$fullcol);
		}
	}	
	function imagelinethick($x1, $y1, $x2, $y2, $opts)	{
		$color=		isset($opts["color"]) ? $opts["color"] : '#999';
		$alpha=		isset($opts["alpha"]) ? $opts["alpha"] : 100;
		$linewidth= isset($opts["linewidth"]) ? $opts["linewidth"] : 1;
		$smooth=		isset($opts["smooth"]) ? $opts["smooth"] : true;

		$gd_color = $this->allocatecolor($color,$alpha);
		
		if ($linewidth == 1) {
		  return imageline($this->img, $x1, $y1, $x2, $y2, $gd_color);
		}
		$t = $linewidth / 2 - 0.5;
		if ($x1 == $x2 || $y1 == $y2) {
		  return imagefilledrectangle($this->img, round(min($x1, $x2) - $t), round(min($y1, $y2) - $t), round(max($x1, $x2) + $t), round(max($y1, $y2) + $t), $gd_color);
		}
		$k = ($y2 - $y1) / ($x2 - $x1); //y = kx + q
		$a = $t / sqrt(1 + pow($k, 2));
		$points = array(
		  round($x1 - (1+$k)*$a), round($y1 + (1-$k)*$a),
		  round($x1 - (1-$k)*$a), round($y1 - (1+$k)*$a),
		  round($x2 + (1+$k)*$a), round($y2 - (1-$k)*$a),
		  round($x2 + (1-$k)*$a), round($y2 + (1+$k)*$a),
		);
		imagefilledpolygon($this->img, $points, 4, $gd_color);
		imagepolygon($this->img, $points, 4, $gd_color);
		
		return true;
	}
	
	/**
	* function imageSmoothAlphaLine() - version 1.0
	* Draws a smooth line with alpha-functionality
	*
	* @param		ident		the image to draw on
	* @param		integer	x1
	* @param		integer	y1
	* @param		integer	x2
	* @param		integer	y2
	* @param		integer	red (0 to 255)
	* @param		integer	green (0 to 255)
	* @param		integer	blue (0 to 255)
	* @param		integer	alpha (0 to 127)
	*
	* @access	public
	*
	* @author	DASPRiD <d@sprid.de>
	*/
	function imageSmoothAlphaLine ($image, $color, $alpha) {
		
		//convert html color to rgb
		list($r,$g,$b)= $this->convertcolor($color);
		
		$icr = $r;
		$icg = $g;
		$icb = $b;
		
		
		$dcol = imagecolorallocatealpha($image, $icr, $icg, $icb, $alpha);

		if ($y1 == $y2 || $x1 == $x2) imageline($image, $x1, $y2, $x1, $y2, $dcol);
		else {
			$m = ($y2 - $y1) / ($x2 - $x1);
			$b = $y1 - $m * $x1;

			if (abs ($m) <2) {
				$x = min($x1, $x2);
				$endx = max($x1, $x2) + 1;

				while ($x < $endx) {
					$y = $m * $x + $b;
					$ya = ($y == floor($y) ? 1: $y - floor($y));
					$yb = ceil($y) - $y;

					$trgb = ImageColorAt($image, $x, floor($y));
					$tcr = ($trgb >> 16) & 0xFF;
					$tcg = ($trgb >> 8) & 0xFF;
					$tcb = $trgb & 0xFF;
					imagesetpixel($image, $x, floor($y), imagecolorallocatealpha($image, ($tcr * $ya + $icr * $yb), ($tcg * $ya + $icg * $yb), ($tcb * $ya + $icb * $yb), $alpha));

					$trgb = ImageColorAt($image, $x, ceil($y));
					$tcr = ($trgb >> 16) & 0xFF;
					$tcg = ($trgb >> 8) & 0xFF;
					$tcb = $trgb & 0xFF;
					imagesetpixel($image, $x, ceil($y), imagecolorallocatealpha($image, ($tcr * $yb + $icr * $ya), ($tcg * $yb + $icg * $ya), ($tcb * $yb + $icb * $ya), $alpha));

					$x++;
				}
			}
			
			else {
				$y = min($y1, $y2);
				$endy = max($y1, $y2) + 1;

				while ($y < $endy) {
					$x = ($y - $b) / $m;
					$xa = ($x == floor($x) ? 1: $x - floor($x));
					$xb = ceil($x) - $x;

					$trgb = ImageColorAt($image, floor($x), $y);
					$tcr = ($trgb >> 16) & 0xFF;
					$tcg = ($trgb >> 8) & 0xFF;
					$tcb = $trgb & 0xFF;
					imagesetpixel($image, floor($x), $y, imagecolorallocatealpha($image, ($tcr * $xa + $icr * $xb), ($tcg * $xa + $icg * $xb), ($tcb * $xa + $icb * $xb), $alpha));

					$trgb = ImageColorAt($image, ceil($x), $y);
					$tcr = ($trgb >> 16) & 0xFF;
					$tcg = ($trgb >> 8) & 0xFF;
					$tcb = $trgb & 0xFF;
					imagesetpixel ($image, ceil($x), $y, imagecolorallocatealpha($image, ($tcr * $xb + $icr * $xa), ($tcg * $xb + $icg * $xa), ($tcb * $xb + $icb * $xa), $alpha));

					$y ++;
				}
			}
		}
	}

	//end class
}

?>