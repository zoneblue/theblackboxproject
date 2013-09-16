	function modelpower($pvtilt,$pvaz,$alt=0){
	
		$az= $this->vars['az'];
		$el= $this->vars['el'];
		
		if ($el<0) $el=0;
		
		$ze= 90-$el;
		$sc= 1367;   //solar constant
		$pve= 15.75; //efficiency of array (%)
		$pva= 1.873; //area of array (m2)
		
		//day number
		$dn= date('z');
		
		//earths eliptical orbit around sun (eef)
		$eef= 1 + (0.033*cos(deg2rad(360*$dn/365)));

		//air mass at zenith angle
		$am = 1/(cos(deg2rad($ze))+ 0.50572*(pow((6.07995+(90-$ze)),-1.6364))); //kastenyoung1989
		
		//pressure correct
		$atmp= 100 * pow((44331.514 - $alt)/11880.516,(1/0.1902632));
		$amc= $am * $atmp/101325;
	
		//reduction for aoi, both az and el angles merged into one angle, then acos
		$aoi = cos(deg2rad($ze))*cos(deg2rad($pvtilt)) + sin(deg2rad($pvtilt))*sin(deg2rad($ze))*cos(deg2rad($az-$pvaz));
		
		##direct irradiance at sun normal (W/m2)
		
		//e1= aoi only
		$this->vars['e1']= 1000 * $aoi;
		
		//e2= am only
		$this->vars['e2']= $sc*pow(0.7,pow($am, 0.678));
		
		//e3 = +aoi
		$this->vars['e3']= $sc*pow(0.7,pow($am, 0.678)) * $aoi;
		
		//dni2= +press
		$this->vars['e4']= $sc*pow(0.7,pow($amc,0.678)) * $aoi;
		
		//dn3=  +eccentricity
		$this->vars['e5']= $sc*pow(0.7,pow($am, 0.678)) * $aoi * $eef;

		return ($this->vars['e5']);
	}


/*
Maximum Rated Power Pm (Watt): The maximum power output from a PV panel at STC which is usually labeled on the panel nameplate. The actual power output can be estimated by
Preal = Pm * S / 1000 * [1 - ?(Tcell - 25)]
Tcell = Tambient + S / 800 * (TNOCT - 20)
where S - the solar radiation on the panel surface, Tambient - the ambient temperature, TNOCT - the Nominal Operating Cell Temperature, and ? - Maximum Power Temperature Coefficient. 
*/
