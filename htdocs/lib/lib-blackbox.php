<?php

/** 
 *  LIB // BLACKBOX
 *  
 *  @author:   Peter 2013
 *  @license:  GPLv3. 
 *  @revision: $Rev$
 *  
 *  
 *  Contents: 
 *  ---------
 *  1. Class Blackbox  
 *  2. Class Module
 *  3. Class Datapoint
 * 
 **/



#===================================================================================================================
#   1.
#===================================================================================================================

/** 
 *  CLASS // BLACKBOX 
 *
 *  This class minimally defines the module collection  
 *  But there is a table maintanance method
 * 
 * 
 * 
 **/

class Blackbox {

	public     $modules= array();
	protected  $dbase_has_been_checked= false;
	protected  $db;

	public function __construct($incldata=false) {
		$this->db= $GLOBALS['db'];
		$profiler= $GLOBALS['profiler'];
		
		//search modules folder
		foreach (scandir("modules") as $modname) {
			if ($modname[0]==='.' or $modname[0]==='_') continue;
			
			//invoke the module instance(s)
			if (!file_exists("modules/$modname/$modname.php")) die("Module $modname.php not found");
			require("modules/$modname/$modname.php");
			if (!class_exists($modname)) die("Module class $modname not found in $modname.php");
			$this->modules[$modname]= new $modname;
			$profiler->add('Module configs all read');

			//load the datapoints data if requested
			if ($incldata)  $this->modules[$modname]->load_data();
		}
		
		//order modules
		function modulesort($a,$b) {
			$a1= $a->order;
			$b1= $b->order;
			if ($a1==$b1) return 0;
			else  return ($a1>$b1);
		}
		uasort($this->modules,'modulesort');
		
		$profiler->add('Blackbox constructor ends');
	}
	
	
	/**
	 * PROCESS_MODULES
	 * trigger for process all devices
	 * 
	 * @args   nil
	 * @return (bool) success
	 *
	 **/
	 
	public function process_modules() {
		$profiler= $GLOBALS['profiler'];
		foreach ($this->modules as $mod=>$module) {
			$module->process_device();
			$profiler->add("Module $module->name processed");
		}		
	}
	
	

	
	/**
	 * CHECK_DBASE
	 * checks/fixes dbase tables and fields as per module defns
	 * 
	 * @args   (bool) fix flag
	 * @return (bool) success
	 *
	 **/
	
	public function check_dbase($fix=false) {
		
		if ($this->dbase_has_been_checked)   return false;

		$db= $this->db;
		$errors= array();
		
		//iterate each module
		foreach ($this->modules as $mod=>$module) {
			if (!$module->get_setting('store_in_db')) continue;
			
			$table=   $module->get_setting('store_db_table');
			$daytable=$module->get_setting('store_db_table_day');
			
			//check table - periodic
			$query= "show tables like ':table' ";		
			$params= array('table'=>$table);
			$result= $db->query($query,$params) or codeerror('DB error',__FILE__,__LINE__);
			if (!$db->num_rows($result)) {
				if ($fix) {
					$query= "
						create table `:table` (
							id  int unsigned primary key auto_increment,
							code  tinyint unsigned,
							date_created datetime,
							index date (date_created)
						);
					";
					$params= array('table'=>$table);
					$db->query($query,$params) or codeerror('DB error',__FILE__,__LINE__);
				}
				else $errors[]= "Table: $table missing";
			}
			
			//check table - day
			$query= "show tables like ':daytable' ";		
			$params= array('daytable'=>$daytable);
			$result= $db->query($query,$params) or codeerror('DB error',__FILE__,__LINE__);
			if (!$db->num_rows($result)) {
				if ($fix) {
					$query= "
						create table `:daytable` (
							id  int unsigned primary key auto_increment,
							code  tinyint unsigned,
							date_created date,
							index date (date_created)
						);
					";
					$params= array('daytable'=>$daytable);
					$db->query($query,$params) or codeerror('DB error',__FILE__,__LINE__);
				}
				else $errors[]= "Table: $daytable missing";
			}

			//check columns - periodic
			$query= "
				select column_name from information_schema.columns 
				where table_name= ':table' 
			";		
			$params= array('table'=>$table);
			$result= $db->query($query,$params) or codeerror('DB error',__FILE__,__LINE__);
			$done= array();
			while ($row= $db->fetch_row($result)) {
				$done[$row['column_name']]= true;
			}
			foreach ($module->datapoints as $label=>$datapoint) {
				if (!$datapoint->store) continue;
				if ($datapoint->interval<>'periodic') continue;
				if (!isset($done[$label])) {
					if ($fix) {
						$query= "alter table `:table` add `:label` varchar(12)";		
						$params= array('table'=>$table,'label'=>$label);
						$db->query($query,$params) or codeerror('DB error',__FILE__,__LINE__);
					}
					else $errors[]= "Column: $label ($table) missing";
				}
			}
			
			//check columns - day
			$query= "
				select column_name from information_schema.columns 
				where table_name= ':table' 
			";		
			$params= array('table'=>$daytable);
			$result= $db->query($query,$params) or codeerror('DB error',__FILE__,__LINE__);
			$done= array();
			while ($row= $db->fetch_row($result)) {
				$done[$row['column_name']]= true;
			}
			foreach ($module->datapoints as $label=>$datapoint) {
				if (!$datapoint->store)  continue;
				if ($datapoint->interval<>'day') continue;
				if (!isset($done[$label])) {
					if ($fix) {
						$query= "alter table `:table` add `:label` varchar(12)";		
						$params= array('table'=>$daytable,'label'=>$label);
						$db->query($query,$params) or codeerror('DB error',__FILE__,__LINE__);
					}
					else $errors[]= "Column: $label ($daytable) missing";
				}
			}
		}
		
		//done
		if ($errors) return $errors;
		else {
			$this->dbase_has_been_checked=  true;
			return false;
		}
	}
	
	//class ends
}



#===================================================================================================================
#   2.
#===================================================================================================================

/** 
 *  CLASS // MODULE 
 *
 *  This class defines the abstract for module classes. 
 * 
 *  Of the methods contained here, define_datapoints and read_device are the primary methods required for each module.
 *  The rest are stock methods. If you feel an irrepressable urge to alter the abstract class, most likely 
 *  its because you are wanting to improve it, therefore please commit some code to svn ;)
 *
 *  In general there are three ways to use modules:
 *  1. from cron:        process_device()
 *  2. view current:     load_data()
 *  4. view historic:    load_data(date)
 * 
 *	 Datapoints:
 *  Property datapoints holds an array of datapoint objects.
 *  The datapoint key is the db column name and unique key for the datapoint, herein after refered to as 'dp'
 *  They are defined like the below, then loaded into nominal child dp objects.
 *
 *  $defn['vbat']= array( 
 *    'name'=>     "Battery voltage",
 *    'type'=>     'sampled',            //sampled,derived,computed
 *    'store'=>    true,                 //true or false
 *    'interval'=> 'periodic',           //periodic,day, (minute,week,month,year)
 *    'method'=>   'get_register',       //method name to get values 
 *    'argument'=> 'LSB([4101])',        //method arg to get values
 *    'comment'=>  '(decimal) measured at controller terminals accurate to +/-0.1V',
 *	   'unit'=>     'V',
 *    'priority'=> 1,                    //governs whether/where it shows in the default view
 *    'order'=>    8,
 *	 );
 *
 * 
 *  Public methods, see code for documentation for private methods
 *  ---------------------------------------------------------------
 *    
 *  PROCESS_DEVICE             Usage: $module->process_device()
 *     
 *     This is called from cronjobs to actually sample the device and store the data.
 *     It calls read_dbase, read_device, calc_derived, then write_dbase in sequence. 
 *     It does this so that daily agregations can be calculated and stored.
 *     Nb: this and load_data, could possibly be moved to the blackbox class
 *     Later more frequent periodics will be allowed, and a minute agregation added.
 *     When done, returns silently.
 *
 *   
 *  LOAD_DATA                  Usage: $module->load_data() or $module->load_data('2013-08-01')
 *    
 *     Instructs the module to read the database, and populate the datapoints data incl derived data. 
 *     Works on a single calendar day at a time. Ie: it loads one whole day (to date) of periodic/minute data
 *     plus the year to date of daily agregate data. If you need more than that you can call it multiple times.
 *
 *
 *  GET_SETTING                Usage: $s= $module->get_setting('module_name')
 *    
 *     Get module setting, self explanatory 
 *     Returns a single module setting
 *    
 *    
 *  GET_DATETIMES              Usage: $a= $module->get_datetimes('periodic')
 *
 *     The datapoint sample record keys are integer, and the timestamps for those keys are held at the module level
 *     The timestamps are iso datetime format for current_value and periodic, iso date format for day or greater.
 *
 *
 *  @license:  GPLv3. 
 *  @author:   Peter 2013
 *  @revision: $Rev$
 *
 **/ 


abstract class Module {
	
	public    $name=     '';
	public    $order=     0;
	protected $datetime= '';          //date of dataset presently held
	
	protected $settings=     array();
	protected $datetimes=    array();
	protected $dp_keys=      array(); //array of datapoint labels
	public    $datapoints=   array(); //array of dp child objects
	
	protected $dbase_has_been_checked=  false; 
	protected $device_has_been_read=    false; //flags to prevent multiple calls
	protected $dbase_has_been_read=     false; 

	protected $debug= false;
	protected $code= 0;
	protected $profiler;
	protected $db;

	
	/**
	 * CONSTRUCTOR
	 * define datapoints definitions , get settings from module config
	 * 
	 * @arg    nil  
	 * @return nil
	 *
	 **/
	 
	public function __construct() {
		
		//get db
		$this->db=       $GLOBALS['db'];
		$this->profiler= $GLOBALS['profiler'];
		
		//intialise settings, from the _config file
		$set= array();
		$modlabel= get_class($this);
		$dir= dirname(__FILE__);
		$config= "$dir/../modules/$modlabel/{$modlabel}_config.php";
		if (!file_exists($config)) die("Module $modlabel config not found - $config");
		include($config);
		$this->settings= $set; //need to syntax test these, todo
		$this->name=  $set['module_name'];
		$this->order= $set['module_order'];
		
		//initialise the dataset date keys
		//the datapoints hold an ordinary array,datetimes holds an ordinary array of the date keys for those arrays, make sense?
		//saves lugging around thousands of date keys
		$this->datetimes= array(
			'current_value'=> '',
			'periodic'=> array(), //per N sec intervals exactly as db stored for 1 day
			'day'=>      array(), //1day intervals for one year
		);
		
		//set the definitive time for the new sample
		$this->datetime= date("Y-m-d H:i:s");
		
		//initialise our datapoints
		$defns= $this->define_datapoints();		
		
		foreach ($defns as $label=>$defn) {
			$this->datapoints[$label]= new Datapoint($defn);
			$this->dp_keys[]= $label;		
		}
		
		$this->profiler->add("Module $this->name constructor complete");
		
	}
	
	
	/**
	 * DEFINE_DATAPOINTS
	 * abstract module method, module must define datapoints 
	 * 
	 * @args  nil 
	 * @return (array) defns 
	 *
	 **/
	 
	abstract protected function define_datapoints();
	

	/**
	 * GET_SETTING
	 * std module method
	 * public getter for settings 
	 * 
	 * @args   (string) key 
	 * @return (string) value
	 *
	 **/
	 
	public function get_setting($key) {
		return $this->settings[$key];
	}
	
	
	/**
	 * PROCESS_DEVICE
	 * std module method
	 * for the cron job, effects a device sample, plus post processing 
    * 
	 * @args  nil 
	 * @return (array) dp data
	 *
	 **/
	 
	public function process_device() {

		//read device first, so as to delay anything cpu intensive
		$data= $this->read_device(); 	
		if (!$data) $this->code= 1;
		else {
			//now get the day's periodic data  
			$this->read_dbase();

			//then add the fresh sample to the dp data
			$this->datetimes['current_value']= $this->datetime;
			$this->datetimes['periodic'][]=    $this->datetime;
			if (!in_array(date("Y-m-d"), $this->datetimes['day'])) $this->datetimes['day'][]= date("Y-m-d", strtotime($this->datetime)); 
			foreach ($this->datapoints as $label=> $datapoint) {
				if (isset($data[$label]) and count($data[$label])) $datapoint->append($data[$label]);		
			}

			//finally do the derivations
			$this->calc_derived();
			
			//mark done
			$this->device_has_been_read= true;
		}
		
		//store all permanently
		$this->write_dbase();
		
		return !$this->code;
	}


	/**
	 * READ_DIRECT
	 * std module method
	 * experimental for ajax
	 *    
	 * @args  nil 
	 * @return (bool) success
	 *
	 **/
	public function read_direct() {

		$data= $this->read_device(); 	
		if (!$data) $this->code= 1;
		else {
			//now get the day's periodic data  
			#$this->read_dbase();

			//add the fresh sample to the dp data
			$this->datetimes['current_value']= $this->datetime;
			$this->datetimes['periodic'][]=    $this->datetime;
			if (!in_array(date("Y-m-d"), $this->datetimes['day'])) $this->datetimes['day'][]= date("Y-m-d", strtotime($this->datetime)); 
			foreach ($this->datapoints as $label=> $datapoint) {
				if (isset($data[$label]) and count($data[$label])) $datapoint->append($data[$label]);		
			}

			//do the derivations
			$this->calc_derived();
		}
		
		return !$this->code;
	}




	/**
	 * READ_DBASE
	 * std module method
	 * gets a single days data from dbase, and populates dps
	 * for now theres periodic (Day to date), and day (Year to date) datasets
	 *    
	 * @args  nil 
	 * @return (bool) success
	 *
	 **/
	 
	protected function read_dbase() {

		//a module with at least one stored dp may proceed
		if (!$this->settings['store_in_db']) return true;
		if ($this->dbase_has_been_read)      return true;
		
		$db= $this->db;
		$errors= array();
		$table=    $this->settings['store_db_table'];
		$daytable= $this->settings['store_db_table_day'];
		
		//define date limits of query
		$dtime= $this->datetime; 
		$day=   date("Y-m-d", strtotime($dtime));


		### Periodic series
		
		//prep hash
		$data= array();
		foreach ($this->datapoints as $label=> $datapoint) {
			if ($datapoint->store and $datapoint->interval=='periodic') $data[$label]= array();
		}
		
		//get the periodic data, day to date
		$query= "
			select * from `:table`
			where date_created >= ':day 00:00:00' 
			and   date_created <= ':day 23:59:59'
			and   code= 0
			order by id
		";	
		$params= array('table'=>$table,'day'=>$day,);
		$result= $db->query($query,$params) or codeerror('DB error',__FILE__,__LINE__);
		$npoints= $db->num_rows($result);
		while ($row= $db->fetch_row($result)) {
			//hash
			$this->datetimes['periodic'][]=  $row['date_created'];
			foreach (array_keys($data) as $label) {
				$data[$label][]= isset($row[$label]) ? $row[$label] : '';
			}
		}
		
		//store
		foreach ($this->datapoints as $label=> $datapoint) {
			if (isset($data[$label])) $datapoint->set('periodic',$data[$label]);
		}


		### Day series
		
		//prep hash
		$data= array();
		foreach ($this->datapoints as $label=> $datapoint) {
			if ($datapoint->store and $datapoint->interval=='day')	$data[$label]= array();
		}
		
		//get the day data, year to date, the last 30 days?, or ???
		$query= "
			select * from `:daytable`
			where date_created >= ':ybeg' 
			and   date_created <= ':yend'
			and   code= '0'
			order by id
		";	
		$params= array(
			'daytable'=> $daytable,
			'ybeg'=>     date("Y-01-01"),
			'yend'=>     date("Y-12-31"),
		);
		$result= $db->query($query,$params) or codeerror('DB error',__FILE__,__LINE__);
		$npoints= $db->num_rows($result);
		while ($row= $db->fetch_row($result)) {
			//hash
			$this->datetimes['day'][]= $row['date_created'];
			foreach (array_keys($data) as $label) {
				if (isset($row[$label])) $data[$label][]= $row[$label];
			}
		}
		
		//store in dps
		foreach ($this->datapoints as $label=> $datapoint) {
			if (isset($data[$label])) $datapoint->set('day',$data[$label]);
		}

		//flag done
		$this->dbase_has_been_read=  true;
		
		return true;
	}
	

	/**
	 * READ_DEVICE
	 * std module method
	 * gets defined by module, to populate the dps of type sample 
	 * 
	 * @args  nil 
	 * @return (bool) success
	 *
	 **/
	 
	abstract protected function read_device();
	
	
	/**
	 * CALC_DERIVED
	 * std module method
	 * creates derived dps (from sampled), for the whole day (to date)
	 * this either runs following read device, or be called by get_datapoint/s
	 * 
	 * @args   nil 
	 * @return (bool) success
	 *
	 **/
	 
	protected function calc_derived() {
		
		//iterate datapoints
		foreach ($this->datapoints as $label=> $datapoint) {
			
			if ($datapoint->type<>'derived') continue;

			//get derived value(s) via the specified method
			$data= $this->{$datapoint->method}($datapoint->argument);  
			
			//store
			if (is_array($data)) $datapoint->set('periodic',$data);
			else                 $datapoint->set('day',$data);
		}	
		
		return true;
	}


	/**
	 * WRITE_DBASE
	 * std module method
	 * stores a single sample of all dps to the database, ie one full db row
	 * at presnet the datestamp is the time that read_device completes
	 * code is 0 for successful sample, or pos int for error condition
	 * only code 0 samples are ever read, the others kept to help with device fault finding.
	 * 
	 * @args  nil 
	 * @return (bool) success
	 *
	 **/

	protected function write_dbase() {
		
		$db= $this->db;
		
		//prepare periodic query
		$query_insert=$query_insert_day='';
		if (!$this->code) {
			foreach ($this->datapoints as $label=> $datapoint) {
				if (!$datapoint->store) continue;

				$field= $db->quote($label);
				$value= $db->quote($datapoint->current_value);

				if ($datapoint->interval=='periodic') $query_insert.=     "`$field`= '$value',\n";
				if ($datapoint->interval=='day')      $query_insert_day.= "`$field`= '$value',\n";
			}
		}
		
		//write periodic
		if ($this->code or $query_insert) {

			$request= "
				insert into `:table` set
				$query_insert
				code = ':code', 
				date_created= ':dtime'
			";	
			$params= array(
				'table'=> $this->settings['store_db_table'],
				'code'=>  $this->code,
				'dtime'=> $this->datetime,
			);
			$db->query($request,$params) or codeerror('DB error',__FILE__,__LINE__);
		}
		
		//write day, keep overwriting current day til last one stands
		if ($query_insert_day) {
		
			$day= date("Y-m-d", strtotime($this->datetime));
			
			$request= "
				select id from `:daytable` 
				where date_created= ':day'
			";	
			$params= array(
				'daytable'=> $this->settings['store_db_table_day'],
				'day'=>   $day ,
			);
			$result= $db->query($request,$params) or codeerror('DB error',__FILE__,__LINE__);
			if (!$db->num_rows($result)) {
				$request= "
					insert into `:daytable` set
					date_created= ':day'
				";	
				$params= array(
					'daytable'=> $this->settings['store_db_table_day'],
					'day'=>   $day,
				);
				$db->query($request,$params) or codeerror('DB error',__FILE__,__LINE__);
			}
			$request= "
				update `:daytable` set
				$query_insert_day
				`code`= '0'
				where date_created= ':day'
			";	
			$params= array(
				'daytable'=> $this->settings['store_db_table_day'],
				'day'=>    $day,
			);
			$db->query($request,$params) or codeerror('DB error',__FILE__,__LINE__);
		}

		return true;
	}


	/**
	 * GET_DATETIMES
	 * abstract module method
	 * we store the sample record iso timestamps per module not per dp
	 * 
	 * @args  (string) key  [periodic, current_value, day, month etc]
	 * @return (array) datetimes 
	 *
	 **/
	 
	public function get_datetimes($key) {
		return $this->datetimes[$key];
	}
	

	/**
	 * LOAD_DATA
	 * std module method
	 * triggers the reading in from dbase of all dps, for the specified date  
	 * 
	 * @args (date) iso day (defualt today)
	 * @return nil
	 *
	 **/
	 
	public function load_data($dtime='') {
		
 		#$dtime='2013-11-18 22:00:00'; for demo
 		
 		if ($dtime) $this->datetime= $dtime;
 		
		$this->profiler->add("Module $this->name load data starts");
		
		$this->read_dbase(); 		
		$this->profiler->add("Module $this->name read dbase ends");
		
		$this->calc_derived();		
		$this->profiler->add("Module $this->name calc derived ends");
	}
	
	

	#===================================================================================================================
	#   STD DERIVATIONS
	#===================================================================================================================
	
	// There are two kinds:
	// - deriving periodic data from periodic data              , returns an array
	// - deriving day data from periodic data, ie an agregation , returns a string


	/**
	 * CALC_DAILY_MAX / MIN
	 * std methods to derive daily min/max of a dp 
	 * called by get_derived
	 * operates on single dp, periodic array
	 *  
	 * @arg    (string)  dp dest label 
	 * @arg    (string)  dp source label 
	 * @return (string)  day value
	 *
	 **/

	protected function calc_daily_max($dp) {
		$tally= 0;
		foreach ($this->datapoints[$dp]->data as $n=> $val) {
			if ($val==NULL) continue;
			$tally= max($tally,$val); 
		}
		return $tally;
		
	}
	
	protected function calc_daily_min($dp) {
		$tally= 1e25;
		foreach ($this->datapoints[$dp]->data  as $n=> $val) {
			if ($val==NULL) continue;
			$tally= min($tally,$val); 
		}
		return $tally;
	}
	
	protected function calc_daily_mean($dp) {
		$tally= $num= 0;
		foreach ($this->datapoints[$dp]->data  as $n=> $val) {
			if ($val==NULL) continue;
			$tally+=  $val; $num++;
		}
		$tally= $num ? $tally/$num: 0;
		return $tally;
	}

	//class ends
}




#===================================================================================================================
#   3.
#===================================================================================================================


/** 
 *  CLASS // DATAPOINT 
 *
 *  This class minimally defines a datapoint  
 *  At present its naive as to its parent, and does little but house its data.
 * 
 * 
 * 
 **/

class Datapoint {

	protected $propertys=     array();
	protected $data=          array();
	protected $day_data=      array();
	protected $current_value= '';

	public function __construct($defn) {
		$this->propertys= $defn;
	}
	public function set($type,$data) {
		$npoints= count($data);
		if ($type=='periodic') {
			$this->data= $data;
			$this->current_value= $npoints ? $data[$npoints-1] : '';
		}
		elseif ($type=='day') {
			if (is_array($data)) {
				$this->day_data= $data;
				$this->current_value= $npoints ? $data[$npoints-1] : '';
			}
			else $this->current_value= $data;
		}
		return true;
	}		

	public function append($value) { //periodic only
		$this->data[]= $value;
		$this->current_value= $value;
		return true;
	}
	public function __get($var) {
		if (isset($this->propertys[$var])) return $this->propertys[$var];
		if (isset($this->$var))            return $this->$var;
		return false;
	}

}



?>