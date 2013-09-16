<?php

/**
 * LIB GRAPH 4
 *
 * a cut down raster graphing library
 * this extarcted version contains only line graphs
 * The lib farms out line drawing to lib-draw.php
 *
 * usage 
 *   include ('lib/lib-draw.php');
 *   include ('lib/lib-graph.php');
 *   
 *   $data[0]= array(0,1,2,3,4,5,6,7,8,9);
 *   $data[1]= array(
 *   	'name'=>       'Test',
 *   	'type'=>      	'line',
 *   	'color'=>      'blue',
 *   	'linewidth'=>   4,
 *   	'alpha'=>       80,
 *   	'smooth'=>      true,
 *   	'data'=>        array(1,5,6,2,2,2,2,3,8,4);
 *   );
 *   
 *   $params= array(
 *   	//chart
 *   	'title'=>            '',
 *   	'size'=>             array(420,175),     //w h  780 leaves 60px per hour!
 *   	'scale'=>            1,                  //upscales everything for print
 *   	'margins'=>          array(20,20,27,40), //t r b l
 *   	'showborder'=>       false,
 *   	'showlegend'=>       false,
 *   	'legend_pos'=>       array(-120,34), //from top/left, or use minus for bottom/right aligned
 *   	'shownote'=>         false,
 *   	'note_pos'=>         array(50,34),   //from top/left, or use minus for bottom/right aligned
 *   	'note_content'=>     '',
 *   	'fontfolder'=>       '/home/www-data/html/lib/', //trailing slash
 *   	'fontfile'=>         'SegoeSb.ttf',//calibri.ttf
 *   	'fontfilebold'=>     'calibrib.ttf', //nb:calibri wont smooth or rotate below 13pt
 *   	'fontsize'=>          8,
 *   	'fontcolor'=>        '#444',
 *   	'border_color'=>     'rgb(150,150,150)',
 *   	'grid_color_major'=> 'rgb(150,150,150)',
 *   	'grid_color_minor'=> 'rgb(220,220,220)',
 *   
 *   	//x axis
 *   	'xaxistitle'=>       '',
 *   	'xmode'=>           'adj',    //betw, adj
 *   	'xusemajorgrid'=>   false,    //if false will show ticks only
 *   	'xuseminorgrid'=>   false,    
 *   	'xintervalmajor'=>  2,    //major grid every N points, default 1
 *   	'xqtyminorgrids'=>  2,    //minor grid every N major grids, default 4, must be divisible into major, use 0 for no minor ticks
 *   
 *   	//y axis
 *   	'yaxistitle'=>       '',
 *   	'ymode'=>           'fit',  //auto, fit or exact
 *   	'yextents'=>        array(0,$pmax), //required for exact
 *   	'yusemajorgrid'=>   true,    
 *   	'yuseminorgrid'=>   false,    
 *   	'yqtymajorgrids'=>      9,    //no of major grids, if fit, this will be rounded using multiples of 1,2, or 5
 *   	'yqtyminorgrids'=>      4,    //minor grid every N major grids, default 4, must be divisible into major, use 0 for no minor ticks
 *   	'yaxislabelspec'=>  'decimal(0)',
 *   
 *   	//series
 *   	'downsample'=>         1,        //down samples overly detailed datasets, averages every N points 
 *   	'usedatapoints'=>      false,
 *   	'datapointsize'=>      1.75, //times the line thickness
 *   	'datapointshape'=>     'square',
 *   	'usedatalabels'=>      false, 
 *   	'datalabelinterval'=>  1, //interval of xdata points
 *   	'datalabelspec'=>      'decimal(1)', 
 *   	'linejoinmethod'=>     'angle', //angle or round
 *   );
 *   
 *   //make test graph
 *   $graph= new Graph($params,$data);
 *   $graph->savetofile("tmp/test.png");
 *   $graphlink1= "<img src='$gfile' alt='' />";
 *   print $graphlink1;  
 *
 */


class Graph {

	public    $errorlog;
	protected $img;
	protected $settings;
	protected $legend;
	protected $colors;
	protected $xdata;
	protected $ydata;
	protected $vars;
	protected $xsf;
	protected $draw;
	
	function __construct($params,$data){
		
		//define
		$this->settings=array();
		$this->legend=  array();
		$this->colors=  array();
		$this->errorlog=  '';
		
		$this->colors= array( 
			'blue'=>   '#6197F2', 'darkblue'=>  '#476EB1',	 'red'=>    '#F47771', 'darkred'=>   '#BF5D58',	 
			'green'=>  '#5DCC4E', 'darkgreen'=> '#489E3C',	 'tan'=>    '#DBD384', 'darktan'=>   '#C5BA42',
			'gold'=>   '#E4D74C', 'darkgold'=>  '#C8BD43',	 'brown'=>  '#C5BB7C', 'darkbrown'=> '#A39A66',
			'olive'=>  '#B0CC63', 'darkolive'=> '#8EA550',   'teal'=>   '#6BB8B3', 'darkteal'=>   '#589894',
			'sage'=>   '#6FB982', 'darksage'=>  '#5B986B',   'orange'=> '#E9B53A', 'darkorange'=> '#BF8B0F',
			'mauve'=>  '#BB93BB', 'darkmauve'=> '#9C7A9C',   'crimson'=>'#D37EAA', 'darkcrimson'=> '#B57194',
			'midblue'=> '#1763B7','darkmidblue'=>'#1763B7',         'midgreen'=> '#4C9413','darkmidgreen'=>'#4C9413',  
			'midbrown'=> '#C56619','darkmidbrown'=>'#C56619',       'lightred'=>'#FFA19C','darklightred'=>  '#FFA19C',
			'lightorange'=>'#F9DA7C','darklightorange'=> '#F9DA7C', 'grey'=>'#999','darkgrey'=>  '#777',
			'earth'=>'#8A312F','darkearth'=>  '#5A4A38',
		);	
		
		
		//parse and check parameters
		$this->check_params($params);

		//create raster canvas
		//checkdata needs draw
		$this->draw= new Draw($this->settings['canvas_width'], $this->settings['canvas_height']);

		$this->check_data($data);
		
		//errors
		if ($this->errorlog)	{		
			print "<h2>Graph config error</h2>";
			print nl2br($this->errorlog);
			exit;
		}
		
		//draw graph
		$this->prep();
		
		//plot
		foreach($this->yoptions as $n=>$a) {
			if ($a['type']=='line') $this->drawlineplot($n);		
		}
		
		//legend, note
		$this->finish();
	}



	//PREP
	function prep() {
		
		
		
		### CHART MATH
		
		//find Y grid
		if ($this->settings['ymode']=='fit') {
		
			//adjust ymax so that the grid is a nice multiple of 1,2 or 5
			$this->settings['ymin']= 0;
			$rawygrid= ($this->settings['ymax']-$this->settings['ymin'])/$this->settings['yqtymajorgrids'];//raw
			$yexp=  log10(abs($rawygrid)); //ie.how many zeros
			$osig=$sig= $rawygrid/POW(10,floor($yexp));//the sigfigs
			if     ($sig <1.5 ) $sig=1;
			elseif ($sig <3.5 ) $sig=2;
			elseif ($sig <7.5 ) $sig=5;
			else                $sig=10;
			$this->settings['ygrid']= $ygrid= $sig*POW(10,floor($yexp));//adjusted now
			$this->debug("YSig:$osig");
			$this->debug("YExp:$yexp");
			$this->debug("YGrd:$ygrid");
		}
		else {
			$this->settings['ygrid']= $ygrid= ($this->settings['ymax']-$this->settings['ymin']) / $this->settings['yqtymajorgrids'];
		}
		
		//calc y scale factor
		$numygrids=   ceil(($this->settings['ymax']-$this->settings['ymin'])/$this->settings['ygrid']);//now adjusted
		$this->settings['ymax'] = $this->settings['ymin']+ $this->settings['ygrid']*($numygrids); //now adjusted
		$this->settings['ysf']= ($this->settings['canvas_height']-$this->settings['bottommargin']-$this->settings['topmargin']) / ($this->settings['ymax']-$this->settings['ymin']);

		//calc X scale factor
		$divisor=   $this->settings['xmode']=='adj' ? $this->settings['npoints']-1 : $this->settings['npoints']; 
		$this->xsf= ($this->settings['canvas_width']- $this->settings['leftmargin'] - $this->settings['rightmargin']) / $divisor;


		

		#### DRAW BG AND LABELS

		$scale= $this->settings['scale'];
		
		//canvas border
		if ($this->settings['showborder']) {
			$x1= 0;	
			$y1= 0;
			$x2= $this->settings['canvas_width']- 1;	//starts at 0 therefore 1 less
			$y2= $this->settings['canvas_height']- 1;
			$opts = array('color'=>$this->settings['border_color'],'alpha'=>100);
			$this->draw->rectangle($x1,$y1,$x2,$y2,$opts); 
		}
		
		//margin background color
		$bsize= round(1 * $scale); //1px
		$x1= $bsize;	
		$y1= $bsize;
		$x2= round($this->settings['canvas_width']- $bsize-1);	
		$y2= round($this->settings['canvas_height']- $bsize-1);
		$opts = array('color'=>$this->settings['margin_color'],'alpha'=>100);
		$this->draw->rectangle($x1,$y1,$x2,$y2,$opts); 					

		//graph background color 
		$x1= $this->settings['leftmargin'];	
		$y1= $this->settings['topmargin'];
		$x2= $this->settings['canvas_width']-$this->settings['rightmargin'];	
		$y2= $this->settings['canvas_height']-$this->settings['bottommargin'];
		$opts = array('color'=>$this->settings['background_color'],'alpha'=>100);
		$this->draw->rectangle($x1,$y1,$x2,$y2,$opts); 					

		//draw threshold bands
		if ($this->settings['ybands']){
			foreach ($this->settings['ybands'] as $b) {
				$y1=$this->settings['canvas_height']-$this->settings['bottommargin']-(($b['y1band']-$this->settings['ymin']) *$this->settings['ysf']);
				$y2=$this->settings['canvas_height']-$this->settings['bottommargin']-(($b['y2band']-$this->settings['ymin']) *$this->settings['ysf']);
				$x1= $this->settings['leftmargin'];	
				$x2= $this->settings['canvas_width']-$this->settings['rightmargin'];	
				$opts= array(
					'color'=> $b['bandcolor'],
					'alpha'=> 90,
				);	
				$this->draw->rectangle($x1,$y1,$x2,$y2,$opts); 					
			}
		}


		### Draw the 3 main titles
		
		//draw chart title
		if ($this->settings['title']) {
			$opts= array(
				'text'=>     $this->settings['title'],
				'x'=>        $this->settings['canvas_width']/2,
				'y'=>        $this->settings['topmargin']/2,
				'align'=>    'middlemiddle',
				'fontname'=> $this->settings['fontbold'],
				'fontsize'=> $this->settings['fontsize'] * 1.35,
				'color'=>    $this->settings['fontcolor'],
				'angle'=>    0,
			);
			$this->draw->text($opts);
		}
		
		//draw X axis title
		if ($this->settings['xaxistitle']) {
			$opts= array(
				'text'=>     $this->settings['xaxistitle'],
				'x'=>        $this->settings['canvas_width']/2,
				'y'=>        $this->settings['canvas_height']-$this->settings['bottommargin']*0.33,
				'align'=>    'middlemiddle',
				'fontname'=> $this->settings['fontbold'],
				'fontsize'=> $this->settings['fontsize'] * 1.44,
				'color'=>    $this->settings['fontcolor'],
				'angle'=>    0,
			);
			$this->draw->text($opts);
		}
		
		//draw Y title
		if ($this->settings['yaxistitle']) {
			$opts= array(
				'text'=>     $this->settings['yaxistitle'],
				'x'=>        $this->settings['leftmargin']*0.33,
				'y'=>        $this->settings['canvas_height']/2,
				'align'=>    'middlemiddle',
				'fontname'=> $this->settings['fontbold'],
				'fontsize'=> $this->settings['fontsize'] * 1.3,
				'color'=>    $this->settings['fontcolor'],
				'angle'=>    90,
			);
			$this->draw->text($opts);
		}
		
		
		$ticksize=  round(5*$scale);
		$tickwidth= round(1*$scale);
		
		
		### X axis
		
		//draw x axis line
		$x1= $this->settings['leftmargin'];
		$y1= $this->settings['canvas_height']-$this->settings['bottommargin'];
		$x2= $this->settings['canvas_width']-$this->settings['rightmargin'];
		$y2= $y1;
		$color= $this->settings['grid_color_major']; 
		$opts = array('color'=>$color,'linewidth'=>$tickwidth,'smooth'=>false);
		$this->draw->line($x1,$y1,$x2,$y2,$opts);

		//draw x ticks
		$max= $this->settings['xmode']=='adj' ? $this->settings['npoints'] : $this->settings['npoints']+1;
		for ($i=0; $i<$max; $i++) {
			$x1= $this->settings['leftmargin'] + ($i * $this->xsf);
			$y1= $this->settings['canvas_height']-$this->settings['bottommargin'] + (0.5*$scale); //1px below the axis
			$x2= $x1;
			$y2= $y1 + $ticksize;
			if ($i % $this->settings['xintervalmajor']==0) {
				$opts = array('color'=>$this->settings['grid_color_major'],'linewidth'=>$tickwidth,'smooth'=>false);
				$this->draw->line($x1,$y1,$x2,$y2,$opts);
			}
			elseif ($this->settings['xuseminorgrid']) {
				$mi= round($this->settings['xintervalmajor']/$this->settings['xqtyminorgrids']);
				if ($i % $mi==0) {
					$opts = array('color'=>$this->settings['grid_color_minor'],'linewidth'=>$tickwidth,'smooth'=>false);
					$this->draw->line($x1,$y1,$x2,$y2,$opts);
				}
			}
		}

		//draw x axis labels
		$offset= $this->settings['xmode']=='adj' ? 0 : $this->xsf/2; 
		for ($i=0; $i<$this->settings['npoints']; $i++){
			if ($i % $this->settings['xintervalmajor']==0) {
				$opts= array(
					'text'=>     $this->xdata[$i],
					'x'=>        $this->settings['leftmargin'] + ($i * $this->xsf) + $offset,
					'y'=>        $this->settings['canvas_height']-$this->settings['bottommargin']+ (8*$scale), //10px below axis
					'align'=>    'topmiddle',
					'fontname'=> $this->settings['font'],
					'fontsize'=> $this->settings['fontsize'] * 1.0,
					'color'=>    $this->settings['fontcolor'],
					'angle'=>    0,
				);
				$this->draw->text($opts);
			}
		}
		
		//draw x grids (vertical)
		if ($this->settings['xusemajorgrid']) {
			for ($i=1; $i<$this->settings['npoints']; $i++) {
				$x1= $this->settings['leftmargin']+ ($i * $this->xsf);
				$y1= $this->settings['topmargin'];
				$x2= $x1;
				$y2= $this->settings['canvas_height'] - $this->settings['bottommargin'];
				if ($i % $this->settings['xintervalmajor']==0) {
					$opts = array('color'=>$this->settings['grid_color_major'],'linewidth'=>$tickwidth,'smooth'=>false);
					$this->draw->line($x1,$y1,$x2,$y2,$opts);
				}					
				elseif ($this->settings['xuseminorgrid']) {
					$mi= round($this->settings['xintervalmajor']/$this->settings['xqtyminorgrids']);
					if ($i % $mi==0) {
						$opts = array('color'=>$this->settings['grid_color_minor'],'linewidth'=>$tickwidth,'smooth'=>false);
						$this->draw->line($x1,$y1,$x2,$y2,$opts);
					}
				}					
			}
		}
		
		
		### Y AXIS
		
		//draw y axis
		$x1=$this->settings['leftmargin'];
		$y1=$this->settings['topmargin'];
		$x2=$x1;
		$y2=$this->settings['canvas_height']-$this->settings['bottommargin'];
		$color= $this->settings['grid_color_major']; 
		$opts = array('color'=>$color,'linewidth'=>$tickwidth,'smooth'=>false);
		$this->draw->line($x1,$y1,$x2,$y2,$opts);

		//draw y ticks
		$vdivision= ($this->settings['canvas_height']-$this->settings['topmargin']-$this->settings['bottommargin']) / ($numygrids); //ysf?
		for ($i=0; $i<$numygrids+1; $i++)	{
			$x1= $this->settings['leftmargin'] - $ticksize;
			$y1= $this->settings['canvas_height']-$this->settings['bottommargin']-($vdivision*$i);
			$x2= $this->settings['leftmargin']- (0.5*$scale);
			$y2= $y1;
			if ($i % $this->settings['yaxislabelfreq']==0) $color= $this->settings['grid_color_major']; 
			else                                           $color= $this->settings['grid_color_minor'];
			$opts = array('color'=>$color,'linewidth'=>$tickwidth,'smooth'=>false);
			$this->draw->line($x1,$y1,$x2,$y2,$opts);
		}

		//draw y axis labels
		for ($i=0; $i<$numygrids+1; $i++)	{
			if ($i % $this->settings['yaxislabelfreq']==0) {
				$label= ($i*$this->settings['ygrid']) + $this->settings['ymin'];
				$opts= array(
					'text'=>     $this->convertlabel($label,$this->settings['yaxislabelspec']),
					'x'=>        $this->settings['leftmargin']- (8*$scale), //8px to the left of the axis
					'y'=>        ($this->settings['canvas_height']-$this->settings['bottommargin'])-($vdivision*$i),
					'align'=>    'middleright',
					'fontname'=> $this->settings['font'],
					'fontsize'=> $this->settings['fontsize'] * 1.0,
					'color'=>    $this->settings['fontcolor'],
					'angle'=>    0,
				);
				$this->draw->text($opts);
			}
		}

		//draw y grid lines (horizontal)
		if ($this->settings['yusemajorgrid']) {
			for ($i=1; $i<$numygrids+1; $i++)	{

				$x1=$this->settings['leftmargin'];
				$y1=$this->settings['canvas_height']-$this->settings['bottommargin']-($vdivision*$i);
				$x2=$this->settings['canvas_width']-$this->settings['rightmargin'];
				$y2=$y1;
				if ($i % $this->settings['yaxislabelfreq']==0) {
					$color= $this->settings['grid_color_major']; 
					$opts = array('color'=>$color,'linewidth'=>$tickwidth,'smooth'=>false);
					$this->draw->line($x1,$y1,$x2,$y2,$opts);
				}
				elseif ($this->settings['yuseminorgrid']) {
					$color= $settings['grid_color_minor']; 
					$opts = array('color'=>$color,'linewidth'=>$tickwidth,'smooth'=>false);
					$this->draw->line($x1,$y1,$x2,$y2,$opts);
				}
			}
		}

	
		//method ends 
		return 1;
	}
	

	function drawlineplot($n) {

		//get data
		$xdata= $this->xdata;
		$ydata=$ydata2= $this->ydata[$n];
	
		//get options
		$yo= $this->yoptions[$n]; 
		$line_color=   $yo['color'];
		$scale= $this->settings['scale'];
		$linewidth=  round($yo['linewidth']*$scale);
		
		//downsample dataset
		$downsample= isset($yo['downsample']) ? (int)$yo['downsample'] : $this->settings['downsample'];
		
		if ($downsample>1) {
			$flag=$t=$n=$c=0;
			foreach($ydata2 as $k=>$v) {
				if ($flag and (($k+1) % $downsample==0)) {
					$ydata2[$k]= $c ? ($t/$c) : NULL;
					$t=$n=$c=0;
				}
				else {
					$ydata2[$k]= NULL;
					if ($v!=NULL) $n=1;
				}
				$flag=1;
				if ($v!=NULL) {$t+=$v; $c++;}
			}
		}

		//first look ahead 
		$aheads= array();
		$lastgood=NULL;
		for ($i=count($ydata2)-1; $i>=0;$i--) {
			$v= $ydata2[$i];
			if ($v==NULL) continue;
			if ($lastgood<>NULL) $aheads[$i]= $lastgood; 
			$lastgood= $v;
		}
		
		//then look behind to prune flat areas
		$lastgood= NULL;
		foreach($ydata2 as $k=>$v) {
			if ($v==NULL) continue;
			if ($lastgood<>NULL and $v==$lastgood and isset($aheads[$k]) and $v==$aheads[$k]) $ydata2[$k]= NULL;
			$lastgood= $v;
		}


		//remove the voids
		$points= array(); 	
		$offset= $this->settings['xmode']=='adj' ? 0 : $this->xsf/2; 
		for ($i=0; $i<count($xdata); $i++)	{
			if (!isset($ydata2[$i])) continue;
			$x= $this->settings['leftmargin']+ ($i * $this->xsf) + $offset;
			$y= $this->settings['canvas_height']-$this->settings['bottommargin']-(($ydata2[$i]-$this->settings['ymin'])*$this->settings['ysf']) ;
			$x=round($x); 
			$y=round($y);
			$points[]= array($x,$y);
		}	

		//print_r($points);
		
		//plot the line
		if ($points) {
			$opts = array('color'=>$yo['color'],'alpha'=>$yo['alpha'],'linewidth'=>$linewidth,'smooth'=>$yo['smooth'],'joinmethod'=>$yo['joinmethod']);
			$this->draw->polyline($points,$opts);
		}

		//draw data points
		if ($this->settings['usedatapoints']){
			for ($i=0; $i<count($xdata); $i++)	{
				if (!isset($ydata[$i])) continue;//skip if invalid or null
				$x2= $this->settings['leftmargin']+(($xdata[$i]-$this->settings['xmin'])*$this->xsf) + $offset;
				$y2= $this->settings['canvas_height']-$this->settings['bottommargin']-(($ydata[$i]-$this->settings['ymin'])*$this->settings['ysf']) ;
				$size= $this->settings['datapointsize']* $linewidth;
				
				if ($this->settings['datapointshape']=='circle') {
					$opts = array('color'=>$yo['color'],'alpha'=>100-(100-$yo['alpha'])/2);
					$this->draw->circle($x2,$y2,$size,$size,$opts);
				}
				elseif ($this->settings['datapointshape']=='square') {
					$opts = array('color'=>$yo['color'],'alpha'=>100-(100-$yo['alpha'])/2);
					$this->draw->rectangle($x2-$size/2,$y2-$size/2,$x2+$size/2,$y2+$size/2,$opts); 					
				}
			}
		}

		//y data point labels 
		if ($this->settings['usedatalabels']) {
			for ($i=0; $i<count($xdata); $i++)	{
				if ($i % $this->settings['datalabelinterval']==0) {
					if (!isset($ydata[$i])) continue;//skip if invalid or null
					$opts= array(
						'text'=>     $this->convertlabel($ydata[$i],$this->settings['ydatalabelspec']),
						'x'=>        $this->settings['leftmargin']+(($xdata[$i]-$this->settings['xmin'])*$this->xsf) + $offset,
						'y'=>        $this->settings['canvas_height']-$this->settings['bottommargin']-(($ydata[$i]-$this->settings['ymin'])*$this->settings['ysf']) -(6*$scale),//rests 6px above
						'align'=>    'bottommiddle',
						'fontname'=> $this->settings['font'],
						'fontsize'=> $this->settings['fontsize'] * 1.0,
						'color'=>    $this->settings['fontcolor'],
						'angle'=>    0,
					);
					$this->draw->text($opts);
				}
			}
		}

		//end
		return;
	}
	
	
	function finish() {
		
		if ($this->settings['showlegend']) {
			
			$scale= $this->settings['scale'];
		
			$boxwidth=    10 *$scale; //10px
			$boxheight=   8 * $scale; //8px
			$boxinterval= 16 * $scale; 

			$legends= array();

			if ($this->settings['ybands']){
				foreach ($this->settings['ybands'] as $b) {
					$legends[]= array(
						'text'=>  $b['bandname'],
						'color'=> $b['bandcolor'],
						'alpha'=> 100,
					);	
				}
			}

			if ($this->yoptions) {
				foreach ($this->yoptions as $i=>$yo) {
					$legends[]= array(
						'text'=>  $yo['name'],
						'color'=> $yo['color'],
						'alpha'=> $yo['alpha'],
					);	
				}
			}

			//legend
			foreach ($legends as $i=>$l) {

				//plot color 'icon'
				$x= $this->settings['legend_posx'];
				$y= $this->settings['legend_posy']+ $i* $boxinterval;

				//same alpha as the plot
				$x2= $x+$boxwidth;	
				$y2= $y+$boxheight;
				$opts = array('color'=>$l['color'],'alpha'=>$l['alpha']);
				$this->draw->rectangle($x,$y,$x2,$y2,$opts); 					

				$opts= array(
					'text'=>     $l['text'],
					'x'=>        $x + ($boxwidth *1.3), //10px to the right of the 'box'
					'y'=>        $y - ($boxheight*0.2),
					'align'=>    'topleft',
					'fontname'=> $this->settings['font'],
					'fontsize'=> $this->settings['fontsize'] * 1.0,
					'color'=>    $this->settings['fontcolor'],
					'angle'=>    0,
				);
				$this->draw->text($opts);
			}
		}

		
		//note box
		if ($this->settings['shownote']){

			foreach (explode("\n",$this->settings['note_content']) as $i=>$ln) {
				$x= $this->settings['note_posx'];
				$y= $this->settings['note_posy'] + $i* $boxinterval;
				$opts= array(
					'text'=>     $ln,
					'x'=>        $x,
					'y'=>        $y,
					'align'=>    'middleleft',
					'fontname'=> $this->settings['font'],
					'fontsize'=> $this->settings['fontsize'] * 1.0,
					'color'=>    $this->settings['fontcolor'],
					'angle'=>    0,
				);
				$this->draw->text($opts);
			}
		}
		
		
	}
		
	function render(){	
		header('Content-type: image/png');
		imagepng($this->draw->img);
		imagedestroy($this->draw->img);
		exit;
	}

	function savetofile($file){
		imagepng($this->draw->img,$file);
		imagedestroy($this->draw->img);
	}




	function debug($msg) {
		$this->errorlog.= "$msg\n";
	}


	//test html color spec 
	function testcolorgood($wk){
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

	
	function convertlabel($wk,$spec){
		if (stristr($spec,'date')){
			$spec=preg_replace('/"/',"'",$spec);
			if (preg_match("/\('(.+)'\)/",$spec,$m)) $wk=date($m[1],$wk);
		}
		elseif (stristr($spec,'round')){
			if (preg_match("/(\d+)/",$spec,$m)) $wk=round($wk,$m[1]);
		}
		elseif (stristr($spec,'number_format')){
			if (preg_match("/(\d+)/",$spec,$m)) $wk=number_format($wk,$m[1]);
		}
		elseif (stristr($spec,'decimal')){
			if (preg_match("/(\d+)/",$spec,$m)) $wk=number_format($wk,$m[1],'.', '' );
		}
		return $wk;
	}

	function check_data($data) {

		$errors= array();
		$xdata=  array();
		$ydata=  array();
		$yoptions=  array();
		$npoints= 0; 
		
		//xdata
		if     (!is_array($data))                         $errors[]="Data missing";
		elseif (!isset($data[0]) or !is_array($data[0]))  $errors[]="X data missing";
		elseif (count($data[0])<1)                        $errors[]="X data empty";
		else  {
			#if (!$this->errorlog and $this->settings['xintervalmajor'] > $npoints)    $errors[]="X xintervalmajor invalid";
			$xdata= array_shift($data);
			$npoints= count($xdata);
		}
		
		//ydata
		if     (!isset($data) or !is_array($data)) $errors[]="Y data missing";
		elseif (count($data)<1)                    $errors[]="Y data missing";
		elseif ($npoints) {
			$ymax=0; $ymin=1E20;
			$autocolors= $this->colors;
			
			foreach($data as $k=>$a) {
				//required options
				if     (!isset($a['name']) or !isset($a['color']) or !isset($a['linewidth']))       $errors[]="Y data $k incomplete";
				elseif (!isset($a['type']) or !in_array($a['type'],array('line','stepline','bar'))) $errors[]="Y data $k type bad";
				elseif (!isset($a['linewidth']) or (int)$a['linewidth']<1)                          $errors[]="Y data $k line bad";
				elseif (!isset($a['color']) or !trim($a['color']))                                  $errors[]="Y data $k incomplete";
				elseif (!isset($a['data']) or !is_array($a['data']))                                $errors[]="Y data $k missing";
				elseif (count($a['data'])<>$npoints)                                                $errors[]="Y data $k invalid";
				else {
				
					//convert any named colors
					if ($a['color']=='auto')                              {$color= array_shift($autocolors);array_shift($autocolors);}
					elseif (isset($this->colors[trim($a['color'])]))      $color= $this->colors[trim($a['color'])];
					elseif ($this->draw->convertcolor(trim($a['color']))) $color= trim($a['color']);
					else $errors[]="Y data $k color bad";
				
					//store options and data
					$ydata[$k]= $a['data'];
					$yoptions[$k]['name']=  trim($a['name']);
					$yoptions[$k]['type']=  trim($a['type']);
					$yoptions[$k]['color']= $color;
					$yoptions[$k]['alpha']=	(int)$a['alpha'];
					$yoptions[$k]['linewidth']=	(int)$a['linewidth'];
					$yoptions[$k]['smooth']=	   (int)$a['smooth'];
					$yoptions[$k]['joinmethod']=	trim($a['joinmethod']);
					$yoptions[$k]['downsample']=	   isset($a['downsample']) ?   (int)$a['downsample'] : NULL;
					$yoptions[$k]['usedatalabels']=	isset($a['usedatalabels']) ? (bool)$a['usedatalabels'] : NULL;
					
					$ymax= max($ymax,max($a['data']));
					$ymin= min($ymin,min($a['data']));
				}
			}
		}
		
		//handle errors
		if ($errors) {
			$this->errorlog.= implode("\n",$errors)."\n";
			return false;
		}

		//save
		$this->xdata=    $xdata;
		$this->ydata=    $ydata;
		$this->yoptions= $yoptions;
		
		$this->settings['xmin']=     0;
		$this->settings['xmax']=      $npoints;//not used
		$this->settings['yminreal']=     $ymin;
		$this->settings['xmaxreal']=     $ymax;
		$this->settings['npoints']=   $npoints;
	
	}


	function check_params($params) {
		
		$errors= array();
		$sizeok=$xok=$yok= false;
		$npoints=0;
		

		//title and size
		if     (!isset($params['title']))  $errors[]="Graph title missing";
		if     (!isset($params['scale']))  $errors[]="Graph scale array empty";
		elseif ((float)$params['scale']<1 or (float)$params['scale']>10)     $errors[]="Graph scale array invalid";
		if     (!isset($params['downsample']))                                    $errors[]="Graph downsample missing";
		elseif ((int)$params['downsample']<1 or (int)$params['downsample']>1000)  $errors[]="Graph downsample invalid";
		
		if     (!isset($params['size']))   $errors[]="Graph size array empty";
		elseif (!isset($params['size'][0]) or !isset($params['size'][1]))     $errors[]="Graph size array incomplete";
		elseif ((int)$params['size'][0]<10 or (int)$params['size'][1]<10)     $errors[]="Graph size too small";
		elseif ((int)$params['size'][0]>2000 or (int)$params['size'][1]>2000) $errors[]="Graph size too large";
		elseif (((int)$params['size'][0]*(int)$params['size'][1]*pow((float)$params['scale'],2)) >= 8000000) $errors[]="Graph size way too large";
		else $sizeok= true;
		
		//margins
		if (!isset($params['margins'])) $errors[]="Margins array missing";
		elseif (!isset($params['margins'][0]) or !isset($params['margins'][1])) $errors[]="Margins array incomplete";
		elseif (!isset($params['margins'][2]) or !isset($params['margins'][3])) $errors[]="Margins array incomplete";
		elseif ((int)$params['margins'][0]<10   or (int)$params['margins'][1]<10)    $errors[]="Margins too small";
		elseif ((int)$params['margins'][0]>2000 or (int)$params['margins'][1]>2000)  $errors[]="Margins too large";
		elseif (!$sizeok) {} //skip in bounds if size borked
		elseif (((int)$params['margins'][0]+(int)$params['margins'][2]) > (int)$params['size'][1]) $errors[]="Margins too large for canvas";
		elseif (((int)$params['margins'][1]+(int)$params['margins'][3]) > (int)$params['size'][0]) $errors[]="Margins too large for canvas";
		
		//legend
		if     (!isset($params['showlegend']))   $errors[]="Show legend missing";
		elseif (!$params['showlegend'])   {}
		elseif (!isset($params['legend_pos']))   $errors[]="Legend array incomplete";
		elseif (!isset($params['legend_pos'][0]) or !isset($params['legend_pos'][1])) $errors[]="Legend pos array incomplete";
		elseif (!$sizeok) {}
		elseif (abs((int)$params['legend_pos'][0])> (int)$params['size'][0]) $errors[]="Legend pos too large for canvas";
		elseif (abs((int)$params['legend_pos'][1])> (int)$params['size'][1]) $errors[]="Legend pos too large for canvas";

		//note
		if     (!isset($params['shownote']))   $errors[]="Show note missing";
		elseif (!$params['shownote'])   {}
		elseif (!isset($params['note_pos']))   $errors[]="note array incomplete";
		elseif (!isset($params['note_pos'][0]) or !isset($params['note_pos'][1])) $errors[]="note pos array incomplete";
		elseif (!$sizeok) {}
		elseif (abs((int)$params['note_pos'][0])> (int)$params['size'][0]) $errors[]="note pos too large for canvas";
		elseif (abs((int)$params['note_pos'][1])> (int)$params['size'][1]) $errors[]="note pos too large for canvas";

		//fonts
		if     (!isset($params['fontfolder']))  $errors[]="Font folder missing";
		elseif (!isset($params['fontcolor']))   $errors[]="Font color missing";
		elseif (!isset($params['fontsize']))    $errors[]="Font size missing";
		elseif (!isset($params['fontfile']))    $errors[]="Font file missing";
		elseif (!file_exists((string)$params['fontfolder']))                             $errors[]="Font folder not found";
		elseif (!file_exists((string)$params['fontfolder'].(string)$params['fontfile'])) $errors[]="Font file not found";
		elseif (!isset($params['fontfilebold'])) {}
		elseif (!file_exists((string)$params['fontfolder'].(string)$params['fontfilebold'])) $errors[]="Bold Font file not found";

		//canvas colors
		if     (!isset($params['border_color']))    $errors[]="Border color missing";
		elseif (!isset($this->colors[(string)$params['border_color']]) and !$this->testcolorgood((string)$params['border_color']))    $errors[]="Border color bad";
		if (!isset($params['grid_color_minor']))     $errors[]="Major grid color missing";
		elseif (!isset($this->colors[(string)$params['grid_color_minor']]) and !$this->testcolorgood((string)$params['grid_color_minor'])) $errors[]="Major grid color bad";
		if (!isset($this->colors[(string)$params['grid_color_major']]) and !$this->testcolorgood((string)$params['grid_color_major'])) $errors[]="Minor grid color bad";
		elseif (!isset($params['grid_color_major'])) $errors[]="Minor grid color missing";
		
		//x params
		if     (!isset($params['xaxistitle']))       $errors[]="X xaxistitle missing";
		if     (!isset($params['xmode']))            $errors[]="X xmode missing";
		elseif (!in_array($params['xmode'], array('betw','adj'))) $errors[]="X xmode invalid";
		if     (!isset($params['xusemajorgrid']))    $errors[]="X usemajorgrid missing";
		if     (!isset($params['xintervalmajor']))   $errors[]="X xintervalmajor missing";
		elseif ((int)$params['xintervalmajor']<1)    $errors[]="X xintervalmajor invalid";
		if     (!isset($params['xuseminorgrid']))    $errors[]="X xuseminorgrid missing";
		elseif ($params['xuseminorgrid']) {
			if     (!isset($params['xqtyminorgrids']))                             $errors[]="X xqtyminorgrids missing";
			elseif ((int)$params['xqtyminorgrids']<2)                              $errors[]="X xqtyminorgrids invalid";
			elseif ((int)$params['xqtyminorgrids']>(int)$params['xintervalmajor']) $errors[]="X xqtyminorgrids invalid";
		}
		
		//y params
		if     (!isset($params['yaxistitle']))        $errors[]="Y yaxistitle missing";
		if     (!isset($params['yusemajorgrid']))    $errors[]="Y yusemajorgrid missing";
		if     (!isset($params['ymode']))    $errors[]="Y ymode missing";
		elseif (!in_array($params['ymode'], array('fit','exact','auto'))) $errors[]="Y ymode invalid";
		elseif ($params['ymode']<>'auto') {
			if     (!isset($params['yextents']) or !is_array($params['yextents']))    $errors[]="Y yextents missing";
			elseif (!isset($params['yextents'][0]) or !isset($params['yextents'][1])) $errors[]="Y yextents incomplete";
		}
		if     (!isset($params['yusemajorgrid']))    $errors[]="Y yusemajorgrid missing";
		if     (!isset($params['yqtymajorgrids']))   $errors[]="Y yqtymajorgrids missing";
		elseif ((int)$params['yqtymajorgrids']<1)    $errors[]="Y yqtymajorgrids invalid";
		
		if     (!isset($params['yuseminorgrid']))    $errors[]="Y yuseminorgrid missing";
		elseif ((int)$params['yqtyminorgrids']<2)    $errors[]="Y yqtyminorgrids invalid";
		if (!preg_match("/^decimal\(\d\)$/", trim($params['yaxislabelspec'])))  $errors[]="Y yaxislabelspec invalid";
	
		//series params
		if     (!isset($params['usedatapoints']))    $errors[]="Series usedatapoints missing";
		elseif ($params['usedatapoints']) {
			if     (!isset($params['datapointshape'])) $errors[]="Series datapointshape missing";
			elseif (!isset($params['datapointsize']))  $errors[]="Series datapointsize missing";
		}
		if     (!isset($params['usedatalabels']))     $errors[]="Series usedatalabels missing";
		elseif ($params['usedatalabels']) {
			if (!isset($params['datalabelinterval'])) $errors[]="Series datalabelinterval missing";
			elseif (!isset($params['datalabelspec']))    $errors[]="Series datalabelspec missing";
			elseif (!preg_match("/^decimal\(\d\)$/", trim($params['datalabelspec'])))  $errors[]="Y datalabelspec invalid";
		}
		if     (!isset($params['linejoinmethod']))    $errors[]="Series linejoinmethod missing";
		elseif (!in_array($params['linejoinmethod'], array('round','angle','square','none'))) $errors[]="Y linejoinmethod invalid";


		//handle errors
		if ($errors) {
			$this->errorlog.= implode("\n",$errors)."\n";
			return false;
		}
		
		//save main
		$scale=  round($params['scale'],1);
		if ((int)$params['legend_pos'][0]<0) $params['legend_pos'][0]= (int)$params['size'][0] + (int)$params['legend_pos'][0];
		if ((int)$params['legend_pos'][1]<0) $params['legend_pos'][1]= (int)$params['size'][1] + (int)$params['legend_pos'][0];
		if ((int)$params['note_pos'][0]<0) $params['note_pos'][0]= (int)$params['size'][0] + (int)$params['note_pos'][0];
		if ((int)$params['note_pos'][1]<0) $params['note_pos'][1]= (int)$params['size'][1] + (int)$params['note_pos'][0];
		
		$this->settings['title']=         trim($params['title']);
		$this->settings['scale']=         $scale;
		$this->settings['downsample']=    (int)$params['downsample'];
		$this->settings['canvas_width']=  round((int)$params['size'][0]*$scale);
		$this->settings['canvas_height']= round((int)$params['size'][1]*$scale);
		$this->settings['showborder']=    (bool)$params['showborder'];
		$this->settings['showlegend']=    (bool)$params['showlegend'];
		$this->settings['legend_posx']=   round((int)$params['legend_pos'][0]*$scale);
		$this->settings['legend_posy']=   round((int)$params['legend_pos'][1]*$scale);
		$this->settings['shownote']=    (bool)$params['shownote'];
		$this->settings['note_posx']=   round((int)$params['note_pos'][0]*$scale);
		$this->settings['note_posy']=   round((int)$params['note_pos'][1]*$scale);
		$this->settings['note_content']=   (string)$params['note_content'];
		
		$this->settings['topmargin']=     round((int)$params['margins'][0]*$scale);
		$this->settings['rightmargin']=   round((int)$params['margins'][1]*$scale);
		$this->settings['bottommargin']=  round((int)$params['margins'][2]*$scale);
		$this->settings['leftmargin']=    round((int)$params['margins'][3]*$scale);
		$this->settings['fontfolder']=    trim($params['fontfolder']);
		$this->settings['fontfile']=      trim($params['fontfile']);
		$this->settings['fontfilebold']=  trim($params['fontfilebold']);
		$this->settings['font']=          $params['fontfolder'].$params['fontfile'];
		$this->settings['fontbold']=      $params['fontfolder'].$params['fontfilebold'];
		$this->settings['fontsize']=      round($params['fontsize']*$scale,1);
		$this->settings['fontcolor']=      trim($params['fontcolor']);
		
		$this->settings['border_color']=     $params['border_color'];
		$this->settings['grid_color_minor']= $params['grid_color_minor'];
		$this->settings['grid_color_major']= $params['grid_color_major'];

		$this->settings['margin_color']=     "rgb(255,255,255)"; //bg
		$this->settings['background_color']= "rgb(255,255,255)"; //graph
		$this->settings['band_color']=       "#AEDA99"; //optimum band
		
		//save x
		$this->settings['xaxistitle']=     trim($params['xaxistitle']);
		$this->settings['xmode']=          $params['xmode'];		
		$this->settings['xusemajorgrid']=  (bool)$params['xusemajorgrid'];
		$this->settings['xuseminorgrid']=  (bool)$params['xuseminorgrid'];
		$this->settings['xintervalmajor']= (int)$params['xintervalmajor'];
		$this->settings['xqtyminorgrids']= (int)$params['xqtyminorgrids'];

		//save y
		$this->settings['yaxistitle']=  $params['yaxistitle'];
		$this->settings['ymode']=       $params['ymode'];		
		$this->settings['ymin']=        $params['yextents'][0];
		$this->settings['ymax']=        $params['yextents'][1];
		$this->settings['yusemajorgrid'] =   $params['yusemajorgrid']; //fix
		$this->settings['yuseminorgrid'] =   $params['yuseminorgrid']; //fix
		$this->settings['yqtymajorgrids']= (int)$params['yqtymajorgrids'];        
		$this->settings['yaxislabelfreq']=  1; //fix  now yqtyminorgrids
		$this->settings['yaxislabelspec'] =  $params['yaxislabelspec'];
		
		//series
		$this->settings['usedatapoints']=   (bool)$params['usedatapoints'];
		$this->settings['datapointshape']=   trim($params['datapointshape']);
		$this->settings['datapointsize']=   (float)$params['datapointsize'];
		$this->settings['usedatalabels']=  (bool)$params['usedatalabels'];
		$this->settings['datalabelinterval'] =  $params['datalabelinterval'];
		$this->settings['ydatalabelspec'] =  $params['datalabelspec'];
		$this->settings['showxdatalabels']=  false; //deprecated
		$this->settings['linejoinmethod']=  $params['linejoinmethod'];
		
		$this->settings['ybands']=         isset($params['ybands']) ? $params['ybands']: array();
		
		return true;
	}

	//class ends
}


?>