<?php

/** 
 *  INSOLATION-MODEL
 *
 *  Thsi module calcualtes theoretical values for insolation and solar related computed datapoints. 
 *  All care no responsibility, use at your own risk. 
 * 
 *  @revision $Rev$
 *  @author:  Peter 2013
 *  @license: GPLv3. 
 *
 **/ 



require("lib-solcalc.php");



class insolation_model extends Module  {
	
	protected $defns;
	
	/**
	 * DEFINE_DATAPOINTS
	 * datapoint definitions
	 * 
	 * @args nil 
	 * @return nil
	 *
	 **/
	 
	protected function define_datapoints() {
		
		$defns= array();
		$order=1;
		
		//time and date, have to go somewhere may as well be here
		$defns['date']= array(
			'name'=>       'Date',
			'type'=>       'derived',
			'store'=>      false,
			'interval'=>   'day',
			'method'=>     'get_date',
			'argument'=>   'Y-m-d', 
			'comment'=>    'Current date',
			'priority'=>   3,
			'unit'=>       '',
			'order'=>      $order++,
		);
		
		$defns['time']= array(
			'name'=>       'Time',
			'type'=>       'derived',
			'store'=>      false,
			'interval'=>   'periodic',
			'method'=>     'get_date',
			'argument'=>   'H:i', 
			'comment'=>    'Current local time',
			'priority'=>   3,
			'unit'=>       'hrs',
			'order'=>      $order++,
		);
		
		//points that change only daily
		$defns['solnoon']= array(
			'name'=>       'Solar Noon',
			'type'=>       'computed',
			'store'=>      false,
			'interval'=>   'day',
			'method'=>     'get_sun',
			'argument'=>   'noon', 
			'comment'=>    'Local solar noon, depends mainly on where you are in your timezone',
			'priority'=>   3,
			'unit'=>       'hrs',
			'order'=>      $order++,
		);

		$defns['sunrise']= array(
			'name'=>       'Sunrise',
			'type'=>       'computed',
			'store'=>      false,
			'interval'=>   'day',
			'method'=>     'get_sun',
			'argument'=>   'rise', 
			'comment'=>    'Civil sunrise, local time',
			'priority'=>   3,
			'unit'=>       '',
			'order'=>      $order++,
		);
		
		$defns['sunset']= array(
			'name'=>       'Sunset',
			'type'=>       'computed',
			'store'=>      false,
			'interval'=>   'day',
			'method'=>     'get_sun',
			'argument'=>   'set', 
			'comment'=>    'Civil sunset, local time',
			'priority'=>   3,
			'unit'=>       'hrs',
			'order'=>      $order++,
		);
		
		//base solar stats
		$defns['azimuth']= array(
			'name'=>       'Solar Azimuth',
			'type'=>       'computed',
			'store'=>      false,
			'interval'=>   'periodic',
			'method'=>     'get_sun',
			'argument'=>   'az', 
			'comment'=>    'current sun angle from 0 degree true north',
			'priority'=>   3,
			'unit'=>       '&deg;',
			'order'=>      $order++,
		);
		
		$defns['zenith']= array(
			'name'=>       'Solar Zenith',
			'type'=>       'computed',
			'store'=>      false,
			'interval'=>   'periodic',
			'method'=>     'get_sun',
			'argument'=>   'ze', 
			'comment'=>    'current sun angle from vertical',
			'priority'=>   3,
			'unit'=>       '&deg;',
			'order'=>      $order++,
		);

		//models
		$defns['power1']= array(
			'name'=>       'Model Power 1',
			'type'=>       'computed',
			'store'=>      false,
			'interval'=>   'periodic',
			'method'=>     'get_sun',
			'argument'=>   'power1', 
			'comment'=>    'Model array energy. Calculated via noa and pv education algos',
			'priority'=>   3,
			'unit'=>       'W',
			'order'=>      $order++,
		);
		
		$defns['power2']= array(
			'name'=>       'Model Power 2',
			'type'=>       'computed',
			'store'=>      false,
			'interval'=>   'periodic',
			'method'=>     'get_sun',
			'argument'=>   'power2', 
			'comment'=>    'Model array energy. Calculated via pv-lib',
			'priority'=>   3,
			'unit'=>       'W',
			'order'=>      $order++,
		);
		
		//factors
		$defns['am']= array(
			'name'=>       'Air mass',
			'type'=>       'computed',
			'store'=>      false,
			'interval'=>   'periodic',
			'method'=>     'get_sun',
			'argument'=>   'am', 
			'comment'=>    'Measure of how the atmospheric thickness reduces insolation depending on sun angle',
			'priority'=>   3,
			'unit'=>       '',
			'order'=>      $order++,
		);
		
		$defns['amf']= array(
			'name'=>       'Airmass factor',
			'type'=>       'computed',
			'store'=>      false,
			'interval'=>   'periodic',
			'method'=>     'get_sun',
			'argument'=>   'amf', 
			'comment'=>    'Percentage of solar energy reaching earth based on airmass',
			'priority'=>   3,
			'unit'=>       '%',
			'order'=>      $order++,
		);
		
		//computed wrt array
		$defns['aoi']= array(
			'name'=>       'Angle of Incidence',
			'type'=>       'computed',
			'store'=>      false,
			'interval'=>   'periodic',
			'method'=>     'get_sun',
			'argument'=>   'aoi', 
			'comment'=>    'The single vector angle that the sun hits the array',
			'priority'=>   3,
			'unit'=>       '&deg;',
			'order'=>      $order++,
		);	
		
		$defns['aoif']= array(
			'name'=>       'Angle of Incidence Factor',
			'type'=>       'computed',
			'store'=>      false,
			'interval'=>   'periodic',
			'method'=>     'get_sun',
			'argument'=>   'aoif', 
			'comment'=>    'The percentage solar energy is reduced by aio',
			'priority'=>   3,
			'unit'=>       '%',
			'order'=>      $order++,
		);	
		
		$defns['eef']= array(
			'name'=>       'Eccentricity factor',
			'type'=>       'computed',
			'store'=>      false,
			'interval'=>   'day',
			'method'=>     'get_sun',
			'argument'=>   'eef', 
			'comment'=>    'Percentage of light reaching earth based on the eccentricity of earth orbit',
			'priority'=>   3,
			'unit'=>       '%',
			'order'=>      $order++,
		);
		
		//add settings as dps too, static but possibly interesting to someone
		foreach($this->settings as $k=>$v) {
			if (substr($k,0,3)<>"pv_") continue;
			$defns[$k]= array(
				'name'=>       $k,
				'type'=>       'derived',
				'store'=>      false,
				'interval'=>   'day',
				'method'=>     'get_pv_setting',
				'argument'=>   $k, 
				'comment'=>    'module setting',
				'priority'=>   3,
				'unit'=>       '', //???
				'order'=>      $order++,
			);
		}
		
		$this->defns= $defns;//so compute can use them

		return $defns;
	}


	/**
	 * GET_PV_SETTING
	 * converts a setting to dp value, maybe add a fixed type
	 * 
	 * @args (string) key 
	 * @return (string) val
	 *
	 **/
	 
	protected function get_pv_setting($arg) {
		return $this->settings[$arg];
	}


	/**
	 * READ_DEVICE
	 * non device
	 *  
	 * @args    nil 
	 * @return (bool) success
	 *
	 **/
	 
	protected function read_device() {
		return true;
	}

	
	/**
	 * COMPUTE
	 * calc the base dps for all periodic times, run once prior to each dp calc_deriv
	 * temp store in this->stats 
	 *  
	 * @args   nil 
	 * @return (array)  dp data
	 *
	 **/
	
	protected function compute() {
	
		//use the noa lib
		$lat=     $this->settings['pv_latitude'];
		$long=    $this->settings['pv_longtitude'];
		$sun= new Solcalc;
		
		//get day to date for all dps
		$data= array();
		foreach ($this->datetimes['periodic'] as $n=>$dtime) {
			$stats=  $sun->calculate($lat,$long,$dtime);
			$stats2= $this->get_model1($dtime, $stats['az'], $stats['el']);
			$stats= array_merge($stats,$stats2);
			
			foreach ($this->defns as $label=> $defn) {
				if ($defn['method']<>'get_sun') continue;
				$l= $defn['argument'];
				$value= isset($stats[$l]) ? $stats[$l] : '';
				
				if ($defn['interval']=='periodic') $data[$label][$n]= $value;
				else                               $data[$label]= $value;
			}
		}
		
		return $data;
	}
	
	
	
	//insolation model ported from javascript at pveducation.org
	protected function get_model1($dtime, $az, $el) {

		//get settings
		$lat=     $this->settings['pv_latitude'];
		$long=    $this->settings['pv_longtitude'];
		$alt=     $this->settings['pv_altitude'];
		$tz=      $this->settings['pv_timezone'];
		$pv_tilt= $this->settings['pv_tilt'];
		$pv_az=   $this->settings['pv_azimith'];
		$pv_stc=  $this->settings['pv_watts_peak'];

		//calc airmass and airmass reduction factor
		//these formulas are a bit rough
		$am=  1/cos(deg2rad(90-$el));
		$amf= $am>0 ? 1.1*1.353* pow(0.7,pow($am,0.678)): 0; 
		
		//calc angle of incidence, arbitrary angle to arbitrary plane
		$aoif=  cos(deg2rad($el))* sin(deg2rad($pv_tilt))*  cos(deg2rad($az-$pv_az)) + sin(deg2rad($el))* cos(deg2rad($pv_tilt));
		$aoi=   rad2deg(acos($aoif));	
		
		$stats= array();
		$stats['aoi']=   $aoi > 90 ? '90+' : round($aoi);
		$stats['aoif']=  $aoif;
		$stats['am']=    $am>0 ? round($am,1) : '0';
		$stats['amf']=   $amf;
		$stats['eef']=   0;
		$stats['power1']=   (round($pv_stc * $aoif * $amf));
		
		return $stats;
	}
	



	#===================================================================================================================
	#  DERIVATIVES
	#===================================================================================================================

	
	/**
	 * GET_DATE
	 * gets /current/ date as a dp
	 * 
	 * @args (string) key 
	 * @return (string) val
	 *
	 **/
	 
	protected function get_date($arg) {
		return date($arg);
	}

	//class ends
}



/*
	NATIVE PHP FUNCTIONS, they dont handle edge cases well
	$z= 96;      //Civil twilight - Conventionally used to signify twilight
	$z= 102;     //Nautical twilight - the point at which the horizon stops being visible at sea.
	$z= 108;     //Astromical twilight - the point when Sun stops being a source of any illumination.
	$z= 90+5/6;  //Official for true sunrise/sunset
	$sunrise=  date_sunrise(strtotime($dtime), SUNFUNCS_RET_STRING, $lat, $long, $z, date('Z',strtotime($dtime))/3600);
	$sunnset=  date_sunset (strtotime($dtime), SUNFUNCS_RET_STRING, $lat, $long, $z, date('Z',strtotime($dtime))/3600);
	OR
	$sun_info = date_sun_info(strtotime($dtime), $lat, $long);
	$sun_info['sunrise'];
	$sun_info['sunset'];
	$sun_info['transit'];
	$sun_info['civil_twilight_begin'];
	$sun_info['civil_twilight_end'];
	$sun_info['nautical_twilight_begin'];
	$sun_info['nautical_twilight_end'];
	$sun_info['astronomical_twilight_begin'];
	$sun_info['astronomical_twilight_end'];
*/


?>