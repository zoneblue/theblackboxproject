<?php

/* 
   DB ABSTRACTION MARK 2
   June 2011,PS
   
   Usage:
 
	//connect
	$db = new Database('MYSQL');
	$db->connect('localhost','myuser','mypass','mydb');

	//read
	$query = "SELECT * FROM bla";
	$result= $db->query($query) or codeerror('DB error',__FILE__,__LINE__);
	$nrows= $db->num_rows($result);
	while ($row=$db->fetch_row($result)){
		//etc
	}
	
	//write
	$query = "UPDATE bla set bla=':bla'";
	$params=array('bla', $bla);
	$db->query($query,$params) or codeerror('DB error',__FILE__,__LINE__);
	$id= $db->insert_id();
	$nrows= $db->num_rows();
		
*/


class Database {
	var $DBH;
	var $link;
	var $result;
	var $error;
	var $nquerys;
	var $table_prefix;
	var $db_type;
	var $last_query;
	var $usepdo;
	var $long_querys= array();


	/*
	* DATABASE Constructor
	* @param (string) db type
	* @return nil
	*/

	function Database($db_type,$usepdo=false){
		
		$db_type= strtoupper($db_type);
		if	    ($db_type=='MYSQL')	    {}
		elseif ($db_type=='SQLITE') {}
		//elseif ($db_type=='POSTGRESQL') {}
		else  die("Invalid DB type");
		
		$this->nquerys= 0;
		$this->table_prefix= false;
		$this->error= '';
		$this->last_query= '';
		$this->num_querys= 0;
		$this->db_type= $db_type;
		$this->usepdo= (bool)$usepdo;
	}


	/*
	* CONNECT 
	* @param (strings) $host,$user,$pass,$db
	* @return (resource) on success / (bool) false on fail
	*/
	function connect($host,$user,$pass,$db)   {

		//check its not already open
		if ($this->link) return $this->link;
		
		if ($this->usepdo) {
			try {  
				if ($type=='sqlite') $DBH = new PDO("sqlite:$host");
				else                 $DBH = new PDO("$type:host=$host;dbname=$dbname", $user, $pass);  
				$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );	
			}
			catch(PDOException $e) {  
				$this->error= $e->getMessage();
				return false;
			}
			$this->DBH= $DBH;
			return true;
		}
		
		else {
		
			//MYSQL
			if ($this->db_type=='MYSQL'){
				$link= @mysql_connect($host,$user,$pass);
				if (!$link) {
					$this->error= @mysql_error();
					return false;
				}
				if (@mysql_select_db($db,$link)) {
					$this->link= $link;
					return $link;
				}
				else {
					$this->error= @mysql_error($link);
					return false;
				}
			}

			//SQLITE
			elseif ($this->db_type=='SQLITE') {
				$link= sqlite_open($db, 0666, $sqlite_error);
				if (!$link) {
					$this->error= $sqlite_error;
					return false;
				}
			}
		}
	}


	/*
	* QUERY
	* @param (string) $query
	* @return (resource) result
	*/
	function query($query,$params=array()) {
		
		//add table prefix if necc
		if ($this->table_prefix) {
			$query = str_replace("TABLEPREFIX_", $this->table_prefix, $query);
		}
	
		//store query
		$this->last_query= $query;
		$this->nquerys++;
		$start_time = $this->getmicrotime();
		

		//PDO
		if ($this->usepdo){
			try{
				$DBH= $this->DBH;
				$result= $DBH->prepare($query);
				$result->execute($params);
			}  
			catch(PDOException $e) {  
				$this->error= $e->getMessage();
				return false;
			}
		}
		else {
			
			//insert params
			if ($params) {
				$query= $this->insert_params($query,$params);
				if (!$query) return false;
				$this->last_query= $query;
			}

			//MYSQL
			if ($this->db_type=='MYSQL'){
				$result= @mysql_query($query,$this->link);
				if (!$result) {
					$this->error= @mysql_error($this->link);
					return false;
				}
			}

			//SQLITE
			elseif ($this->db_type=='SQLITE'){
				$result= sqlite_query($this->link, $query, SQLITE_ASSOC, $sqlite_error);
				if (!$result) {
					$this->error= $sqlite_error;
					return false;
				}
			}
		}
		
		//log long querys
		$query_time = $this->getmicrotime() - $start_time;
		if ($query_time > 0.5) {
			$this->long_querys[$query]= max($query_time,isset($this->long_querys[$query])?$this->long_querys[$query]:0);
		}

		//all good
		return $result;
	}


	/*
	* FETCH_ROW
	* @param (resource) $result
	* @return (bool) success
	*/
	
	function fetch_row($result) {
		
		//PDO
		if ($this->usepdo){
			$result->setFetchMode(PDO::FETCH_ASSOC);  
			return $result->fetch();
		}
		else {
		
			//MYSQL
			if ($this->db_type=='MYSQL'){
				return @mysql_fetch_assoc($result);
			}

			//SQLITE
			if ($this->db_type=='SQLITE'){
				return sqlite_fetch_array($result, SQLITE_ASSOC);

			}
		}
	}


	/*
	* NUM_ROWS
	* @param (resource) $result
	* @return (bool) success
	*/
	
	function num_rows($result=true) {

		//PDO
		if ($this->usepdo){
			return $result->rowCount();
		}
		else {
		
			//MYSQL
			if ($this->db_type=='MYSQL'){
				if ($result===true) return @mysql_affected_rows($this->link);
				else				     return @mysql_num_rows($result);
			}

			//SQLITE
			if ($this->db_type=='SQLITE'){
				if ($result===true) return sqlite_changes($this->link);
				else				     return sqlite_num_rows($result);
			}
		}
	}


	/*
	* INSERT_ID
	* @param nil
	* @return (int) id
	*/
	
	function insert_id()	{

		//PDO
		if ($this->usepdo){
			return $this->DBH->lastInsertId();
		}
		else {

			//MYSQL
			if ($this->db_type=='MYSQL'){
				return mysql_insert_id($this->link);
			}

			//SQLITE
			if ($this->db_type=='SQLITE'){
				return sqlite_last_insert_rowid($this->link);
			}
		}
	}


	/*
	* QUOTE
	* @param (string) arbitrary textual
	* @return (string) sql cleaned
	*/

	function quote($str)	{
		
		//PDO
		if ($this->usepdo){
			return $this->DBH->quote($str);
		}
		else {
			if ($this->db_type=='MYSQL'){
				return mysql_real_escape_string($str,$this->link);
			}

			if ($this->db_type=='SQLITE'){
				$DBH= $this->DBH;
			}
		}
	}
	
	
	/*
	* FREE
	* @param (resource) $result
	* @return nil
	*/
	
	function free($result)	{
		
		//PDO
		if ($this->usepdo){
			#???
		}
		else {
			//MYSQL
			if ($this->db_type=='MYSQL'){
				@mysql_free_result($result);
			}
			//SQLITE
			if ($this->db_type=='SQLITE'){
				$result->closeCursor();
			}
		}
	}

	
	/*
	* INSERT_PARAMS
	* for sites that dont want to use pdo
	* @param (string) sql
	* @param (array) params
	* @return (string) clean sql
	*/
	
	function insert_params($query,$params) {
		
		$chars=$perrors=array(); 
		$ch= range('a','z'); $ch[]= '_'; 	
		foreach($ch as $c) {$chars[$c]=1;}

		$i=$m=0; $out=$p='';  
		while ($i<strlen($query)) {
			$l= substr($query,$i,1);
			if ($m) {
				if (isset($chars[$l])) $p.=$l;
				else {
					if ($p and isset($params[$p])) {
						$sql_p= $this->quote($params[$p]);
						$len_r= strlen($sql_p);
						$out.= $sql_p.$l;
					}
					else $out.=":$l$p";
					$m=0;$p='';
				}
			} 
			elseif ($l==':') $m=1;
			else $out.=$l;
			$i++;
		}
		if ($perrors) {
			$this->error= implode(",", $perrors);
			return false;
		}
		
		return $out;
	}
	
	
	/*
	* GETMICROTIME
	* @param nil
	* @return (float) seconds
	*/
	
	function getmicrotime(){ 
		 list($usec, $sec) = explode(" ",microtime()); 
		 return ((float)$usec + (float)$sec); 
	} 
	
	//class ends
}


?>