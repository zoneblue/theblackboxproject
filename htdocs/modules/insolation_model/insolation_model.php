<?php

/** 
 *  INSOLATION-MODEL
 *
 *  Thsi module calculates theoretical values for insolation and sun related datapoints. 
 *  All care no responsibility, use at your own risk. 
 * 
 *  @revision $Rev$
 *  @author:  Peter 2013
 *  @license: GPLv3. 
 *
 **/ 



require("lib-solcalc.php");



class insolation_model extends Module  {
	
	
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
		
		//time and date, have to go somewhere, may as well be here
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
			'unit'=>       '',
			'order'=>      $order++,
		);
		
		//points that change only daily
		$defns['solnoon']= array(
			'name'=>       'Solar Noon',
			'type'=>       'sampled',
			'store'=>      true,
			'interval'=>   'day',
			'method'=>     'get_solcalc',
			'argument'=>   'noon', 
			'comment'=>    'Local solar noon, depends mainly on where you are in your timezone',
			'priority'=>   3,
			'unit'=>       'hrs',
			'order'=>      $order++,
		);

		$defns['sunrise']= array(
			'name'=>       'Sunrise',
			'type'=>       'sampled',
			'store'=>      true,
			'interval'=>   'day',
			'method'=>     'get_solcalc',
			'argument'=>   'rise', 
			'comment'=>    'Civil sunrise, local time',
			'priority'=>   3,
			'unit'=>       '',
			'order'=>      $order++,
		);
		
		$defns['sunset']= array(
			'name'=>       'Sunset',
			'type'=>       'sampled',
			'store'=>      true,
			'interval'=>   'day',
			'method'=>     'get_solcalc',
			'argument'=>   'set', 
			'comment'=>    'Civil sunset, local time',
			'priority'=>   3,
			'unit'=>       'hrs',
			'order'=>      $order++,
		);
		
		//base solar stats
		$defns['az']= array(
			'name'=>       'Solar Azimuth',
			'type'=>       'sampled',
			'store'=>      true,
			'interval'=>   'periodic',
			'method'=>     'get_solcalc',
			'argument'=>   'az', 
			'comment'=>    'current sun angle from 0 degree true north',
			'priority'=>   3,
			'unit'=>       '&deg;',
			'order'=>      $order++,
		);
		
		$defns['ze']= array(
			'name'=>       'Solar Zenith',
			'type'=>       'sampled',
			'store'=>      true,
			'interval'=>   'periodic',
			'method'=>     'get_solcalc',
			'argument'=>   'ze', 
			'comment'=>    'current sun angle from vertical',
			'priority'=>   3,
			'unit'=>       '&deg;',
			'order'=>      $order++,
		);

		//models
		$defns['power1']= array(
			'name'=>       'Model Power 1',
			'type'=>       'derived',
			'store'=>      true, 
			'interval'=>   'periodic',
			'method'=>     'get_sun',
			'argument'=>   'power1', 
			'comment'=>    'Model array energy. Calculated via noaa and pveducation algos',
			'priority'=>   3,
			'unit'=>       'W',
			'order'=>      $order++,
		);
		
		$defns['power2']= array(
			'name'=>       'Model Power 2',
			'type'=>       'derived',
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
			'type'=>       'sampled',
			'store'=>      true,
			'interval'=>   'periodic',
			'method'=>     'get_solcalc',
			'argument'=>   'am', 
			'comment'=>    'Measure of how the atmospheric thickness reduces insolation depending on sun elevation',
			'priority'=>   3,
			'unit'=>       '',
			'order'=>      $order++,
		);
		
		$defns['amf']= array(
			'name'=>       'Airmass factor',
			'type'=>       'derived',
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
			'type'=>       'sampled',
			'store'=>      true,
			'interval'=>   'periodic',
			'method'=>     'get_solcalc',
			'argument'=>   'aoi', 
			'comment'=>    'The single vector angle that the sun hits the array',
			'priority'=>   3,
			'unit'=>       '&deg;',
			'order'=>      $order++,
		);	
		
		$defns['aoif']= array(
			'name'=>       'Angle of Incidence Factor',
			'type'=>       'derived',
			'store'=>      false,
			'interval'=>   'periodic',
			'method'=>     'get_sun',
			'argument'=>   'aoif', 
			'comment'=>    'The percentage solar energy is reduced by AOI',
			'priority'=>   3,
			'unit'=>       '%',
			'order'=>      $order++,
		);	
		
		$defns['eef']= array(
			'name'=>       'Eccentricity factor',
			'type'=>       'derived',
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
				'name'=>       ucwords(str_replace('_',' ',$k)),
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

		return $defns;
	}



	/**
	 * READ_DEVICE
	 * non device
	 * calc the base solar stats, temporily store in this->stats 
	 * the diff betw sampled and derived is a bit arbitrary 
	 *  
	 * @args    nil 
	 * @return (bool) success
	 *
	 **/
	 
	protected function read_device() {

		$dtime= $this->datetime;
		
		//use the noaa lib to get core solar position and solar times
		$lat=     $this->settings['pv_latitude'];
		$long=    $this->settings['pv_longtitude'];
		$alt=     $this->settings['pv_altitude'];
		$pv_tilt= $this->settings['pv_tilt'];
		$pv_az=   $this->settings['pv_azimith'];
		
		$sun= new Solcalc;
		$stats=  $sun->calculate($lat,$long,$dtime);

		$ze= $stats['ze'];
		$az= $stats['az'];
		
		//calc airmass
		$stats['am']=  1/cos(deg2rad($ze));
		$stats['am']=  $stats['am']>0 ? number_format($stats['am'],2) : 999; //algo goes breifly negative below the horizon
		
		//calc angle of incidence, arbitrary angle to arbitrary plane
		$stats['aoi']=  rad2deg(acos( cos(deg2rad(90-$ze))*sin(deg2rad($pv_tilt))*cos(deg2rad($az-$pv_az))+sin(deg2rad(90-$ze))*cos(deg2rad($pv_tilt)) ));
		$stats['aoi']=  $stats['aoi']<90 ? number_format($stats['aoi'],2) : 90; //sun behind panel
		
		return $stats;
	}
	


	#===================================================================================================================
	#  DERIVATIVES
	#===================================================================================================================

	
	/**
	 * GET_SUN
	 * gets sun dp
	 * 
	 * @args (string) key 
	 * @return (string) val
	 *
	 **/
	 
	protected function get_sun($arg) {
		
		$alt=     $this->settings['pv_altitude'];
		$tz=      $this->settings['pv_timezone'];
		$pv_stc=  $this->settings['pv_watts_peak'];
		
		//use insolation model ported from javascript at pveducation.org
		$data= array();
		foreach ($this->datetimes['periodic'] as $n=> $dtime) {
			
			$az=  (float)$this->datapoints['az']->data[$n];
			$ze=  (float)$this->datapoints['ze']->data[$n];
			$am=  (float)$this->datapoints['am']->data[$n];
			$aoi= (float)$this->datapoints['aoi']->data[$n];

			//calc airmass reduction factor, a bit rough
			//aoif is ok, except doesnt factor in reflectance
			if ($arg=='amf'  or $arg=='power1') $amf=  1.1*1.353*pow(0.7,pow($am,0.678));
			if ($arg=='aoif' or $arg=='power1') $aoif= cos(deg2rad($aoi));
			
			if     ($arg=='eef')     $data[$n]= 0;
			elseif ($arg=='power2')  $data[$n]= 0;
			elseif ($arg=='amf')     $data[$n]= round($amf,3); 
			elseif ($arg=='aoif')    $data[$n]= round($aoif,3);
			elseif ($arg=='power1')  $data[$n]= round($pv_stc * $aoif * $amf,0);
		}
		
		return $data;
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