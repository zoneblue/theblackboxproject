<?php

/** 
 *  MIDNITE-CLASSIC
 *
 *  A module class to talk to the Midnite Classic charge controller via RossW's linux modbus c utility. 
 *  All care no responsibility, use at your own risk. 
 * 
 *  @revision: $Rev$
 *  @author:   Peter 2013
 *  @license:  GPLv3. 
 *
 **/ 



class midnite_classic extends Module {
	
	protected $registers= array(); //temp store raw registers
	
	
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
		$order= 1;
		
		### THE LESS CHANGEABLE DEVICE STATS
		//these will only change occasionally, when cc is swapped, firmware upgraded etc
		//so we'll store them daily and call it good
		
		$defns['classic']= array(
			'name'=>       "Classic Unit Type",
			'type'=>       'sampled',
			'store'=>      true,
			'interval'=>   'day',
			'method'=>     'get_register',
			'argument'=>   'LSB([4101])', 
			'comment'=>    '(int) Classic 150=150,Classic 200=200, Classic 250=250, Classic 250KS=251',
			'priority'=>   3,
			'unit'=>       '',
			'order'=>      $order++,
		);
		
		$defns['rev']= array(
			'name'=>       "Classic PCB Revision",
			'type'=>       'sampled',
			'store'=>      true,
			'interval'=>   'day',
			'method'=>     'get_register',
			'argument'=>   'MSB([4101])',
			'comment'=>    '(int) 1-3',
			'unit'=>       '',
			'priority'=>   3,
			'order'=>      $order++,
		);
		
		$defns['firmdate']= array(
			'name'=>       "Firmware Date",
			'type'=>       'sampled',
			'store'=>      false,
			'interval'=>   'day',
			'method'=>     'get_register',
			'argument'=>   "'[4102]'.'-'.MSB([4103]).'-'.LSB([4103])",
			'comment'=>    '(isodate) year-month-day',
			'unit'=>       '',
			'priority'=>   3,
			'order'=>      $order++,
		);
		
		$defns['firmver']= array(
			'name'=>       "Firmware Version",
			'type'=>       'sampled',
			'store'=>      true,
			'interval'=>   'day',
			'method'=>     'get_register',
			'argument'=>   "[16387]",    // "BITS([16385],15,12).'.'.BITS([16385],11,8).'.'.BITS([16385],7,4)",
			'comment'=>    '',
			'unit'=>       '',
			'priority'=>   3,
			'order'=>      $order++,
		); 
		
		$defns['plifetime']= array(
			'name'=>       "Lifetime kWh",
			'type'=>       'sampled',
			'store'=>      true,
			'interval'=>   'day',
			'method'=>     'get_register',
			'argument'=>   '(([4127] << 16) + [4126])/ 10',
			'comment'=>    '(decimal) kilowatt hours since new',
			'unit'=>       'kWh',
			'priority'=>   3,
			'order'=>      $order++,
		);
		


		#### REALTIME STATS OF INTEREST
		
		//CHARGE STAGE
		//the register is an integer, but not quite in linear order
		//hence we have 2 derived versions, one in english and one in linear order
		//Raw: 0=Resting,3=Absorb,4=BulkMppt,5=Float,6=FloatMppt,7=Equalize,10=HyperVoc,18=EqMppt
		
		$defns['stageword']= array(
			'name'=>       "Charge Stage",
			'type'=>       'derived',
			'store'=>      false,
			'interval'=>   'day',
			'method'=>     'translate_stage',
			'argument'=>   'word', 
			'comment'=>    '(string) in english',
			'unit'=>       '',
			'priority'=>   1,
			'order'=>      $order++,
		);
		
		$defns['stagelin']= array(
			'name'=>       "Charge Stage Lin",
			'type'=>       'derived',
			'store'=>      false,
			'interval'=>   'periodic',
			'method'=>     'translate_stage',
			'argument'=>   'linear',
			'comment'=>    '(int) in sequence',
			'unit'=>       '',
			'priority'=>   1,
			'order'=>      $order++,
		);
		
		$defns['state']= array(
			'name'=>       "Charge Stage Raw",
			'type'=>       'sampled',
			'store'=>      true,
			'interval'=>   'periodic',
			'method'=>     'get_register',
			'argument'=>   'MSB([4120])',
			'comment'=>    '(int)',
			'unit'=>       '',
			'priority'=>   4,
			'order'=>      $order++,
		);

		
		//FLAGS
		//to save cluttering the db we will store the raw flag (in base10)
		//and include as many derived flag values as we need/want
		$defns['infoflags']= array(
			'name'=>       "Info Flags",
			'type'=>       'sampled',
			'store'=>      true,
			'interval'=>   'periodic',
			'method'=>     'get_register',
			'argument'=>   '(([4131] << 16) + [4130])',
			'comment'=>    '(int) decimal rendition of hex flags',
			'unit'=>       '',
			'priority'=>   4,
			'order'=>      $order++,
		);
		
		
		//TEMPS
		//the most interesting is tbat
		//also present are tfet and tpcb 
		//probably tfet is a good enough proxy for cc temp
		
		$defns['tbat']= array(
			'name'=>       "Battery Temp",
			'type'=>       'sampled',
			'store'=>      true,
			'interval'=>   'periodic',
			'method'=>     'get_register',
			'argument'=>   '[4132]/10',
			'comment'=>    '(decimal)',
			'unit'=>       '&deg;C',
			'priority'=>   2,
			'order'=>      $order++,
		);

		$defns['tcc']= array(
			'name'=>       "FET Temp",
			'type'=>       'sampled',
			'store'=>      true,
			'interval'=>   'periodic',
			'method'=>     'get_register',
			'argument'=>   '[4133]/10',
			'comment'=>    '(decimal) fet',
			'unit'=>       '&deg;C',
			'priority'=>   2,
			'order'=>      $order++,
		);

		$defns['tcc2']= array(
			'name'=>       "PCB Temp",
			'type'=>       'sampled',
			'store'=>      false, //ignored for now
			'interval'=>   'periodic',
			'method'=>     'get_register',
			'argument'=>   '[4134]/10',
			'comment'=>    '(decimal) pcb',
			'priority'=>   2,
			'unit'=>       '&deg;C',
			'order'=>      $order++,
		);


		//VOLTS AND AMPS
		//pv volts and amps, battery volts and amps, and pout
		//theres one level of redundancy there, and, problematically they dont agree
		//but for now we will store them all, until someone can shed some light on this
		//just for kicks well track the pin/pout efficiency 
		
		$defns['pout']= array(
			'name'=>       "Output Power",
			'type'=>       'sampled',
			'store'=>      true,
			'interval'=>   'periodic',
			'method'=>     'get_register',
			'argument'=>   '[4119]',
			'comment'=>    '(int)',
			'unit'=>       'W',
			'priority'=>   2,
			'order'=>      $order++,
		);

		$defns['vout']= array(
			'name'=>       "Output Voltage",
			'type'=>       'sampled',
			'store'=>      true,
			'interval'=>   'periodic',
			'method'=>     'get_register',
			'argument'=>   '[4115]/10',
			'comment'=>    '(decimal) cc output voltage at controller',
			'unit'=>       'V',
			'priority'=>   1,
			'order'=>      $order++,
		);

		$defns['iout']= array(
			'name'=>       "Output Current",
			'type'=>       'sampled',
			'store'=>      true,
			'interval'=>   'periodic',
			'method'=>     'get_register',
			'argument'=>   '[4117]/10',
			'comment'=>    '(decimal) cc output current',
			'unit'=>       'A',
			'priority'=>   2,
			'order'=>      $order++,
		);

		$defns['vpv']= array(
			'name'=>       "PV Voltage",
			'type'=>       'sampled',
			'store'=>      true,
			'interval'=>   'periodic',
			'method'=>     'get_register',
			'argument'=>   '[4116]/10',
			'comment'=>    '(decimal)',
			'unit'=>       'V',
			'priority'=>   2,
			'order'=>      $order++,
		);

		$defns['ipv']= array(
			'name'=>       "PV Current",
			'type'=>       'sampled',
			'store'=>      true,
			'interval'=>   'periodic',
			'method'=>     'get_register',
			'argument'=>   '[4121]/10',
			'comment'=>    '(decimal)',
			'unit'=>       'A',
			'priority'=>   2,
			'order'=>      $order++,
		);
		
		$defns['eff']= array(
			'name'=>       "Efficiency",
			'type'=>       'derived',
			'store'=>      false,
			'interval'=>   'periodic',
			'method'=>     'calc_efficiency',
			'argument'=>   'cc',
			'comment'=>    '(decimal) pin cf pout',
			'unit'=>       '%',
			'priority'=>   2,
			'order'=>      $order++,
		);


		//DAY TO DATE
		//the classic tracks float time, and energy today
		//we will add absorb time, bulk time, our own float time
		//we will also derive kWh in all three states.
		//for our derived versions well use the prefix dur for duration
		
		$defns['ftoday']= array(
			'name'=>       "Float Time Today",
			'type'=>       'sampled',
			'store'=>      true,
			'interval'=>   'day',
			'method'=>     'get_register',
			'argument'=>   '[4138]/3600',
			'comment'=>    '(decimal) register seconds, converted to hours',
			'unit'=>       'hrs',
			'priority'=>   4,
			'order'=>      $order++,
		);
		$defns['ptoday']= array(
			'name'=>       "kWh Today",
			'type'=>       'sampled',
			'store'=>      true,
			'interval'=>   'day',
			'method'=>     'get_register',
			'argument'=>   '[4118]/10',
			'comment'=>    '(decimal) ',
			'unit'=>       'kWh',
			'priority'=>   4,
			'order'=>      $order++,
		);

		$defns['durbulk']= array(
			'name'=>       "Time in bulk",
			'type'=>       'derived',
			'store'=>      true,
			'interval'=>   'day',
			'method'=>     'calc_daily_duration',
			'argument'=>   'bulk',
			'comment'=>    '(decimal) hours',
			'unit'=>       'hrs',
			'priority'=>   2,
			'order'=>      $order++,
		);

		$defns['durabsorb']= array(
			'name'=>       "Time in absorb",
			'type'=>       'derived',
			'store'=>      true,
			'interval'=>   'day',
			'method'=>     'calc_daily_duration',
			'argument'=>   'absorb',
			'comment'=>    '(decimal) hours',
			'unit'=>       'hrs',
			'priority'=>   2,
			'order'=>      $order++,
		);

		$defns['durfloat']= array(
			'name'=>       "Time in float",
			'type'=>       'derived',
			'store'=>      true,
			'interval'=>   'day',
			'method'=>     'calc_daily_duration',
			'argument'=>   'float',
			'comment'=>    '(decimal) hours, computed',
			'unit'=>       'hrs',
			'priority'=>   2,
			'order'=>      $order++,
		);

		$defns['whtotal']= array(
			'name'=>       "Wh total",
			'type'=>       'derived',
			'store'=>      true,
			'interval'=>   'day',
			'method'=>     'calc_daily_sum',
			'argument'=>   'pout/total',
			'comment'=>    '(decimal) ',
			'unit'=>       'Wh',
			'priority'=>   2,
			'order'=>      $order++,
		);
		$defns['whbulk']= array(
			'name'=>       "Wh in bulk",
			'type'=>       'derived',
			'store'=>      false,
			'interval'=>   'day',
			'method'=>     'calc_daily_sum',
			'argument'=>   'pout/bulk',
			'comment'=>    '(decimal) ',
			'unit'=>       'Wh',
			'priority'=>   2,
			'order'=>      $order++,
		);

		$defns['whabsorb']= array(
			'name'=>       "Wh in absorb",
			'type'=>       'derived',
			'store'=>      true,
			'interval'=>   'day',
			'method'=>     'calc_daily_sum',
			'argument'=>   'pout/absorb',
			'comment'=>    '(decimal) ',
			'unit'=>       'Wh',
			'priority'=>   2,
			'order'=>      $order++,
		);

		$defns['whfloat']= array(
			'name'=>       "Wh in float",
			'type'=>       'derived',
			'store'=>      true,
			'interval'=>   'day',
			'method'=>     'calc_daily_sum',
			'argument'=>   'pout/float',
			'comment'=>    '(decimal) ',
			'unit'=>       'Wh',
			'priority'=>   2,
			'order'=>      $order++,
		);


		//DAILY PEAKS AND DIPS
		//primarily we are interested in peak pout, peak iout, vbat high and low.
		//but im sure others will surface
		
		$defns['maxpout']= array(
			'name'=>       "Max power output",
			'type'=>       'derived',
			'store'=>      true,
			'interval'=>   'day',
			'method'=>     'calc_daily_max',
			'argument'=>   'pout',
			'comment'=>    '(decimal)',
			'unit'=>       'W',
			'priority'=>   2,
			'order'=>      $order++,
		);
		
		$defns['maxiout']= array(
			'name'=>       "Max current output",
			'type'=>       'derived',
			'store'=>      true,
			'interval'=>   'day',
			'method'=>     'calc_daily_max',
			'argument'=>   'iout',
			'comment'=>    '(decimal)',
			'unit'=>       'A',
			'priority'=>   2,
			'order'=>      $order++,
		);

		$defns['maxvbat']= array(
			'name'=>       "Max battery voltage",
			'type'=>       'derived',
			'store'=>      true,
			'interval'=>   'day',
			'method'=>     'calc_daily_max',
			'argument'=>   'vout',
			'comment'=>    '(decimal)',
			'unit'=>       'V',
			'priority'=>   2,
			'order'=>      $order++,
		);

		$defns['minvbat']= array(
			'name'=>       "Min battery voltage",
			'type'=>       'derived',
			'store'=>      true,
			'interval'=>   'day',
			'method'=>     'calc_daily_min',
			'argument'=>   'vout',
			'comment'=>    '(decimal)',
			'unit'=>       'V',
			'priority'=>   2,
			'order'=>      $order++,
		);
		
		return $defns;
	}
	
	
	/**
	 * READ_DEVICE
	 * invoke newmodbus binary, and scrape the output 
	 * store all registers in this->regsiters
	 *  
	 * @args    nil 
	 * @return (bool) success
	 *
	 **/
	 
	protected function read_device() {
		
		//get settings
		$dir=    dirname(__FILE__);
		$binary= basename($this->settings['newmodbus_ver']);
		$ip=     trim($this->settings['ip_address']);
		$errors= false;
		
		//invoke the binary and parse the results
		if ($this->debug) print "\nInvoke binary: $dir/$binary $ip";
		exec("$dir/$binary $ip 16385-16390 4101-4340", $lines,$ret);
		
		if ($ret) {
			$this->error= true;
			if ($this->debug) print "\nRead device: FAIL";
			return false;
		}
		else {
			foreach ($lines as $line) {
				if (!preg_match("/^\d\d\d\d\d? /",$line)) continue;
				list($register,$value)= explode(" ",$line);
				$this->registers[$register]= $value; 
			}
			if ($this->debug) print "\nRead registers: ".count($this->registers);
		}
		
		//convert raw registers to decimal datapoints
		$data= array();
		foreach ($this->datapoints as $label=> $datapoint) {
			if ($datapoint->method=='get_register') $data[$label]= $this->get_register($datapoint->argument);
		}	
		
		return $data;
	}

	

	/**
	 * GET_REGISTER
	 * helper to read_device
	 * evaluates the logic to combine registers or extract partial registers 
	 * works on a single dp 
	 * lazy expression parser, fix
	 *  
	 * @args   (string) expresssion
	 * @return (decimal) dp value
	 *
	 **/
	 
	protected function get_register($expression) {
		
		//replace register addresses with values
		$tail=$expression; $expression='';  
		while (preg_match("/^(.*?)\[(\d+)\](.*)$/s",$tail,$m)){	 
			$expression.= $m[1]; $reg= $m[2]; $tail= $m[3];
			$expression.= $this->registers[$reg];
		}
		$expression= '$decimal='.$expression.$tail.';';
		
		//use eval to evaluate the bitwise logic, nasty
		//some sort of expression parser is needed there
		eval($expression);
		
		return $decimal;
	}
	

	//unused
	protected function compute() {}


	#####################################################################################################################
	###
	###  DERIVATIONS
	###
	#####################################################################################################################


	/**
	 * TRANSLATE_STAGE
	 * derived method to map the classic charge stage to more helpful things 
	 * operates on the periodic array
	 *  
	 * @args   (string) dp
	 * @return (array)  values
	 *
	 **/

	protected function translate_stage($arg) {
		
		//map native classic charge states to english
		$state_raw = array(
			0=> 'Sleep',
			3=> 'Absorb',
			4=> 'Bulk',
			5=> 'Float',
			6=> 'Float~', //inaptly named 'float mmpt', ie failing to hold float voltage
			7=> 'EQ',
			18=>'EQ~',
			10=>'HyperVoc'
		);
		//map to linear charge states, ie still an integer but in order, duh!
		$state_map = array(
			0=>  2, //sleep
			4=>  3, //bulk
			3=>  4, //abs
			5=>  5, //float
			6=>  5, //float
			7=>  6, //eq
			18=> 6, //eq
			10=> 7  //voc
		);

		//translate
		foreach ($this->datapoints['state']->data as $n=> $raw) {
			if     ($arg=='word')   $newval= $state_raw[$raw];
			elseif ($arg=='linear') $newval= $state_map[$raw];
			$data[$n]= $newval;
		}
		
		return $data;		
	}
	
	
	/**
	 * CALC_EFFICIENCY
	 * custom method to divide Pin by Pout , pretty shitty, as classic pin is reported to be inaccurate
	 * operates on the periodic array
	 *  
	 * @args   (string) not used
	 * @return (array) values
	 *
	 **/

	protected function calc_efficiency($arg) {
	
		$data= array();
		foreach ($this->datapoints['state']->data as $n=> $v) {
			$pin=  $this->datapoints['ipv']->data[$n]  * $this->datapoints['vpv']->data[$n];
			$pout= $this->datapoints['iout']->data[$n] * $this->datapoints['vout']->data[$n];
			$val= $pout ? $pin/$pout*100 : 0;
			$data[$n]= number_format($val,0);
		}
		return $data;
	}
	
	
	/**
	 * CALC_DAILY_DURATION
	 * derivation for time spent in each stage 
	 * operates on the periodic array
	 *  
	 * @args   (string)  stageword 
	 * @return (string)  value
	 *
	 **/

	protected function calc_daily_duration($arg) {
		//this assumes each dp is one minute
		//will need to fix this when other sample rates are introduced.
		$tally= 0;
		$len=2; //2 minutes
		foreach ($this->datapoints['state']->data as $n=> $state) {
			if ($arg=='bulk'   and $state==4) $tally+= $len; 
			if ($arg=='absorb' and $state==3) $tally+= $len; 
			if ($arg=='float'  and $state<7 and $state >4) $tally+= $len; 
		}
		$tally= $tally/60;
		$tally= round($tally,1);
		
		return $tally;
	}
	
				
	/**
	 * CALC_DAILY_SUM
	 * custom method to derive energy produced in each stage 
	 * operates on the periodic array, returns a single value
	 *  
	 * @args   (string) 'dplabel/stage'  
	 * @return (string) value
	 *
	 **/

	protected function calc_daily_sum($arg) {
		//this assumes each dp is one minute
		//will need to fix this when other sample rates are introduced.
		$tally= 0;
		$len=2; //2 minutes
		foreach ($this->datapoints['state']->data as $n=> $state) {
			$pout= $this->datapoints['pout']->data[$n];
			if ($arg=='pout/bulk'  and $state==4)              $tally+= ($pout*$len); 
			if ($arg=='pout/absorb' and $state==3)             $tally+= ($pout*$len); 
			if ($arg=='pout/float' and $state<7 and $state >4) $tally+= ($pout*$len);  
			if ($arg=='pout/total') $tally+= $pout;  
		}
		$tally= $tally/60;
		$tally= round($tally,0);
		
		return $tally;
	}

	//class ends	
}



//hackish functions for translate_register eval
function lsb($in) {
	return ($in & 0x00ff);
}

function msb($in) {
	return ($in >> 8);
}

function bits($in,$start,$stop=0) {
	$bitmask=0; 
	if (!$stop) $stop=$start;
	for ($i=$start;$i<=$stop;$i++) {
		$bitmask+=pow(2,$start);
	}
	return ($in & $bitmask >> pow(2,$stop));
}


?>