<?php
/*
	Created By:	Yogistra Anderson
	Create Dt: 	11/8/2009
	
	DBase abstraction layer 
	Requires PHP mysqli extension
	
/*	=========================	
	Class DB_APP API
	=========================*/
/*
	database static methods
		- bool 	open_conn()
		- bool	open_conn2($ary_conn_params) //expects array with params in order (server,user,pass,dbase)
		- bool  set_ex_context($ary_context) //set execution context(session variables) that may be used by db triggers, procedures etc
		- bool 	close_conn()
		- bool 	exec($sql)
		- bool	multi_query($sql)
		- bool	is_connected()
		- ary	get_records($sql,$start_idx=0,$row_count=100)
		- ary	get_records2($sql,$start_idx=0,$rec_limit=10000,&$recs_retrieved=NULL) //utiltizes LIMIT and OFFSET for paging, should be quicker, need to test
		- int	last_insert_id()
		- int	affected_rows()
		- obj	get_mysqli()
		- str	time_to_datetime_str($i_time)
		- str	time_to_mysql_datetime_str			
		- bool	is_in_trans()
		
	transaction methods
		- void	start_trans();						//begin transaction mode
		- vodi	rollback_trans();					//rollback all since start_trans() called and exit transaction mode
		- void	commit_trans();						//commit all since start_trans() called and exit transaction mode

	validation methods		
		- bool validate_field($val,$type,$size)		//main checking function, used in conjunction with the type table	
		- bool prepare_data($val,$type)				//prepares dbase bound string data (escape special chars etc)
		
	NOTE:
		- to use 'prepare_data', a connection must be established as the underlying function uses the charset of the
		- ..connection to do the escaping.
		
*/

//require_once('MDB2.php');
//require_once('MySQLi.php');

//Exceptions
//================================================================================
class Ex_DB_APP_Invalid_Type extends Exception{}
class Ex_DB_APP_Out_Of_Range extends Exception{}
//class Ex_DB_APP_Unknown_User extends Exception{}
//================================================================================

class DB_APP{			
	const NULL_VALUE		= -9999;
	
	//Data type constants
	const DATA_TYPE_INT 	= 0;
	const DATA_TYPE_STRING 	= 1;
	const DATA_TYPE_BOOL 	= 2;
	const DATA_TYPE_DATE  	= 3;
	const DATA_TYPE_DATETIME = 4;
	const DATA_TYPE_FLOAT	= 9;	
	
	//string subtypes added for more targeted validation rules
	const DATA_TYPE_STRING_WORD		  = 5;
	const DATA_TYPE_STRING_MULTI_WORD = 6;
	const DATA_TYPE_STRING_XML		  = 7;
	const DATA_TYPE_TEXT_W_TAGS		  = 8;	
	
	//static variables
	//static private $conn 	 = null;
	static private $mysqli = null;
	static private $connected = false;
	static private $in_trans = false;		//in transaction flag
	
	//start transaction mode
	public static function start_trans(){		
		if(!self::$connected) throw new Exception('[DB_APP.start_trans]: not connected!');						//sanity checking
		if(self::$in_trans) throw new Exception('[DB_APP.start_trans]: previous transaction still active!');	//sanity checking
			
		self::$mysqli->autocommit(false);
		self::$in_trans = true;
	}
	
	//commit transactions and exit transaction mode
	public static function commit_trans(){
		if(!self::$connected) throw new Exception('[DB_APP.commit_trans]: not connected!');		//sanity checking
		if(!self::$in_trans) throw new Exception('[DB_APP.commit_trans]: not in transaction!');	//sanity checking
	
		self::$mysqli->commit();
		self::$mysqli->autocommit(true);
		self::$in_trans = false;
	}	
	
	//rollback transactions and exit transaction mode
	public static function rollback_trans(){
		if(!self::$connected) throw new Exception('[DB_APP.rollback_trans]: not connected!');		//sanity checking
		if(!self::$in_trans) throw new Exception('[DB_APP.rollback_trans]: not in transaction!');	//sanity checking
		
		self::$mysqli->rollback();
		self::$mysqli->autocommit(true);
		self::$in_trans = false;
	}
	
	//returns the transaction status
	public static function is_in_trans(){ return self::$in_trans; }
	
	/**
		convert from timestamp into DB_APP acceptable datetime formatted string
	*/
	public static function time_to_datetime_str($i_time){ return date('m-d-Y H:i',$i_time); }	
	public static function time_to_mysql_datetime_str($i_time) { return date('Y-m-d H:i:s',$i_time); }
	
	//validate field data
	static public function validate_field($val,$type,$size=null){
		//test size if specified
		//if(($size) && (strlen((string)$val) > $size))
		//	return false;
		if($val == self::NULL_VALUE)
			return true;
			
		switch($type){
			case self::DATA_TYPE_INT:							//MAXIMUM OF MYSQL INTEGER TYPE: 2147483647 (SIGNED),				
				if(!preg_match('/^\d{1,10}$/m',(string)$val))	//checking length of digits only
					return false;					
			break;	
			
			//NOTE:allows up to double precision floats
			//Changed 1/16/2012 to allow triple precision floats
			case self::DATA_TYPE_FLOAT:				
				if(!preg_match('/^\d{1,10}(\.\d{1,3})?$/',(string)$val))
					return false;
			break;		
			
			case self::DATA_TYPE_STRING_XML:
			case self::DATA_TYPE_TEXT_W_TAGS:	//allowed tags <b/> <a/> <i/>
				throw new Exception('[DB_APP:validate_field]: not yet implimented!');
				break;				
					
			case self::DATA_TYPE_STRING_WORD:
				//size parameter required for string types
				if(!$size) throw new Exception('[DB_APP.validate_field]: DATA_TYPE_STRING requires a "$size" parameter!');
				
				//allows word characters only
				if(!preg_match("/^[\w]{0,$size}$/m",$val)) return false;
			break;			
			
			case self::DATA_TYPE_STRING:								
				//size parameter required for string types
				if(!$size) throw new Exception('[DB_APP.validate_field]: DATA_TYPE_STRING requires a "$size" parameter!');
				
				//TO DO: straighten this with character encoding
				if(strlen($val) > $size)
					return false;
				//allows word characters, spaces and some punctuation
				//if(!preg_match("/^[\w\s\.\,\?\!\@\$\%\*\(\)\{\}\[\]\;\:\-\'\"]{0,$size}$/mi",$val)) return false;
			break;
					
			case self::DATA_TYPE_BOOL:						//expect 1 or 0
				if(!preg_match('/^[0|1]$/m',(string)$val))
					return false;
			break;
			
			case self::DATA_TYPE_DATE:						//expect in yyyy-mm-dd format				
				//if(!preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/m',(string)$val))
				if(!preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/m',(string)$val))
					return false;
			break;
			
			case self::DATA_TYPE_DATETIME:
				//if(!preg_match('/^\d{4}-\d{1,2}-\d{1,2} [0-2]\d:[0-5]\d:[0-5]\d$/m',(string)$val))
				//	return false;					
			break;
			
			default:										//unknown type
				throw new Ex_DB_APP_Invalid_Type('[DB_APP.prepare_data]: unknown type['.$type.']!');
		}
		
		return true;
	}
	
	//prepare data parameters to be merged into an SQL statement
	static public function prepare_data($val,$type){
		if($val == self::NULL_VALUE)
			return 'null';
			
		switch($type){
			case self::DATA_TYPE_STRING_MULTI_WORD:
			case self::DATA_TYPE_STRING_WORD:
			case self::DATA_TYPE_STRING_XML:
			case self::DATA_TYPE_TEXT_W_TAGS:			
			case self::DATA_TYPE_STRING:
				return '\''.self::$mysqli->escape_string($val).'\'';
			break;
			
			case self::DATA_TYPE_FLOAT:
			case self::DATA_TYPE_INT:
			case self::DATA_TYPE_BOOL:
				return self::$mysqli->escape_string((string)$val);
			break;
				
			case self::DATA_TYPE_DATE:
				//return 'STR_TO_DATE(\''.self::$mysqli->escape_string($val).'\',\'%m/%d/%Y\')';
				//return 'STR_TO_DATE(\''.self::$mysqli->escape_string($val).'\',\'%d-%m-%Y\')';
				return 'STR_TO_DATE(\''.self::$mysqli->escape_string($val).'\',\'%Y-%m-%d\')';
			break;
			
			case self::DATA_TYPE_DATETIME:
				//return 'STR_TO_DATE(\''.self::$mysqli->escape_string($val).'\',\'%m/%d/%Y %H:%i\')';
				//return 'STR_TO_DATE(\''.self::$mysqli->escape_string($val).'\',\'%m-%d-%Y %H:%i\')';
				return '\''.self::$mysqli->escape_string($val).'\'';
			break;
			
			default:
				throw new Ex_DB_APP_Invalid_Type('[DB_APP.prepare_data]: unknown type['.$type.']!');
		}
	}	
	
	/**
		@param $is_ext is a flag indicating external user
	*/
	//DEPRECATED
	// static public function open_conn($is_ext=0){		
		// if(!self::$connected){
			// try{
				// if($is_ext)
					// self::$mysqli = new mysqli(DSNEXT_SERVER,DSNEXT_USER,DSNEXT_PASSWORD,DSNEXT_DATABASE);
				// else{
					// if( SecureLib::get_user_level() == SecureLib::USER_LEVEL_ADMIN)
						// self::$mysqli = new mysqli(DSNADMIN_SERVER,DSNADMIN_USER,DSNADMIN_PASSWORD,DSNADMIN_DATABASE);
					// else
						// self::$mysqli = new mysqli(DSNSTD_SERVER,DSNSTD_USER,DSNSTD_PASSWORD,DSNSTD_DATABASE);
				// }
					
			// }
			// catch(Exception $ex){ return false; }
			
			// if(self::$mysqli->connect_error){
				// //throw new Exception('[DB_APP.open_conn]:('.self::$mysqli->connect_errno.')'.self::$mysqli->connect_error);
				// return false;
			// }
			// else{
				// self::$connected = true;
				// return true;
			// }
		// }else
			// return true;			
	// }	
	
	/**
		TODO: get entire application to use open_conn2
		New addition 2/17/2012 9:13AM
	*/
		
	//expects array with params in order (server,user,pass,dbase)
	static public function open_conn2($ary_conn_params){
		if(!self::$connected){
			try{			
				self::$mysqli = new mysqli($ary_conn_params[0],$ary_conn_params[1],$ary_conn_params[2],$ary_conn_params[3]);
				//if additional app variables sent, set session variables in the DB
				if( isset($ary_conn_params[4]) && isset($ary_conn_params[5]) ){
					$app_user_id = $ary_conn_params[4];
					$app_user_lvl = $ary_conn_params[5];					
					self::$mysqli->query("set @app_user_id := '$app_user_id', @app_user_lvl := $app_user_lvl");
				}				
			}
			catch(Exception $ex){ return false; }
			
			if(self::$mysqli->connect_error){
				error_log('[DB_APP.open_conn2]:('.self::$mysqli->connect_errno.')'.self::$mysqli->connect_error);
				return false;
			}
			else{
				self::$connected = true;
				return true;
			}
		}else
			return true;			
	}
	
	//sets execution context
	static public function set_ex_context($ary_context){
		if(self::$connected){
			$app_user_id = $ary_context['userid'];
			$app_user_lvl = $ary_context['userlvl'];
			
			self::$mysqli->query("set @app_user_id := '$app_user_id', @app_user_lvl := $app_user_lvl");
			
			if(self::$mysqli->errno)
				throw new Exception('[DB_APP.set_ex_context]:('.self::$mysqli->errno.')'.self::$mysqli->error);
			
			return true;
		}else
			return false;
	}
	
	//close database connection	
	static public function close_conn(){
		if(self::$connected){
			if(self::$in_trans)				//if in transaction
				self::rollback_trans();		//rollback uncommited transactions
			
			self::$mysqli->close();
			self::$mysqli = null;
			self::$connected = false;
		}
	}
	
	//executes sql statement, (intented for insert, update, delete)
	static public function exec($sql){
		//error_log($sql);
		self::$mysqli->query($sql);
		
		if(self::$mysqli->errno)
			throw new Exception('[DB_APP.exec]:('.self::$mysqli->errno.')'.self::$mysqli->error);
		else
			return self::$mysqli->affected_rows;
	}
	
	static public function multi_query($sql){
		self::$mysqli->multi_query($sql);
		
		if(self::$mysqli->errno)
			throw new Exception('[DB_APP.exec]:('.self::$mysqli->errno.')'.self::$mysqli->error);
		else
			return self::$mysqli->affected_rows;
	}
	
	//executes the sql, uses LIMIT and OFFSET for paging, alters the SQL query
	static public function get_records2($sql,$start_idx=0,$rec_limit=10000,&$recs_retrieved=NULL){
		if( !(preg_match('/^\d{1,9}$/',$start_idx) && preg_match('/^\d{1,9}$/',$rec_limit) ) )
			throw new Exception('[DB_APP.get_records2]:Invalid start_idx and/or rec_limit params');
			
		$sql .= " LIMIT {$rec_limit} OFFSET {$start_idx}";				
		$res = self::$mysqli->query($sql,MYSQLI_STORE_RESULT);
		$ary_res = array();
		
		//error_log($sql);
		
		//test for error
		if(self::$mysqli->errno)
			throw new Exception('[DB_APP.get_records2]:('.self::$mysqli->errno.')'.self::$mysqli->error);
			
		$rec_count = 0;
		
		//fetch and store	
		while($rec = $res->fetch_row()){			
			$ary_res []= $rec;			
			$rec_count++;
		}
		
		$res->free();
		
		if(isset($recs_retrieved)) 
			$recs_retrieved = $rec_count;
		
		return $ary_res;
	}
	
	//executes sql and returns records
	static public function get_records($sql,$start_idx=0,$rec_limit=10000,&$recs_retrieved=NULL){
		$res = self::$mysqli->query($sql,MYSQLI_STORE_RESULT);
		$ary_res = array();
		
		//test for error
		if(self::$mysqli->errno)
			throw new Exception('[DB_APP.get_records]:('.self::$mysqli->errno.')'.self::$mysqli->error);
		
		//seek to start idx if specified
		if($start_idx){
			if($res->num_rows > $start_idx)
				$res->data_seek($start_idx);
			else
				throw new Ex_DB_APP_Out_Of_Range('[DB_APP.get_records]: start_idx is out of range!');
		}
		
		$rec_count = 0;
		
		//fetch and store	
		while($rec = $res->fetch_row()){			
			$ary_res []= $rec;			
			$rec_count++;
			if($rec_count >= $rec_limit) break;
		}
		
		$res->free();
		
		if(isset($recs_retrieved)) 
			$recs_retrieved = $rec_count;
		
		return $ary_res;
	}
	
	//returns the id of the last auto increte record
	static public function last_insert_id(){return self::$mysqli->insert_id;}	
	
	//returns the rows affected by the last operation
	static public function affected_rows(){ return self::$mysqli->affected_rows;}
	
	/**
		returns the internal mysqli object for more finegrained control
	*/
	static public function get_mysqli(){ return self::$mysqli; }
	
	static public function is_connected(){ return self::$connected;}
}

//Testing section database functions
//NOTE: Unit testing can be found in the TESTING\BACKEND dir
//===============================================================================
//assert_options(ASSERT_ACTIVE,1);
//
//assert(DB_APP::open_conn());
//assert(count(DB_APP::get_records('select * from CONTENT_TYPES'))==2);
//assert(count(DB_APP::get_records('select * from CONTENT_TYPES',1))==1);
//
////trying for exception, seeking out of range
//try{
//	DB_APP::get_records('select * from CONTENT_TYPES',4);
//	assert(1==2);
//}
//catch(Exception $ex){}	

//DB_APP::exec('insert into content_types(desc_text) values(\'slideshow\')');
//assert(count(DB_APP::get_records('select * from CONTENT_TYPES'))==3);
//DB_APP::exec('delete from content_types where id='.DB_APP::last_insert_id());
//assert(count(DB_APP::get_records('select * from CONTENT_TYPES'))==2);
//DB_APP::close_conn();

//test validation function
//===============================================================================
//assert(!DB_APP::validate_field('',DB_APP::DATA_TYPE_STRING));			//empty, expect fail
//assert(!DB_APP::validate_field('eresdfs',DB_APP::DATA_TYPE_STRING,3));	//too long, expect fail 
//assert(DB_APP::validate_field('ereewre',DB_APP::DATA_TYPE_STRING,20));	//valid
//assert(DB_APP::validate_field(1231,DB_APP::DATA_TYPE_INT));				//valid
//assert(DB_APP::validate_field('1231',DB_APP::DATA_TYPE_INT));			//valid
//assert(!DB_APP::validate_field('123g1',DB_APP::DATA_TYPE_INT));			//invalid
//assert(!DB_APP::validate_field('123g1',DB_APP::DATA_TYPE_BOOL));		//invalid
//assert(DB_APP::validate_field(1,DB_APP::DATA_TYPE_BOOL));				//valid
//assert(DB_APP::validate_field(0,DB_APP::DATA_TYPE_BOOL));				//valid
//assert(DB_APP::validate_field('10/11/2002',DB_APP::DATA_TYPE_DATE));	//valid
//assert(!DB_APP::validate_field('1032/1/2002',DB_APP::DATA_TYPE_DATE));	//invalid

//test prepare function
//===============================================================================
//assert(DB_APP::open_conn());
//assert(DB_APP::prepare_data('10/11/2002',DB_APP::DATA_TYPE_DATE)=='STR_TO_DATE(\'10/11/2002\',\'%m/%d/%Y\')');

//test transaction functions
// assert(DB_APP::open_conn2(array('localhost','root','admin','test')));
// DB_APP::exec('create table test1(id	int,name	varchar(10))');
// DB_APP::start_trans();
// assert(DB_APP::exec('insert into test1 values(1,"andrew")'));
// DB_APP::commit_trans();
// assert(count(DB_APP::get_records('select * from test1 where id=1')) == 1);
// DB_APP::start_trans();
// assert(DB_APP::exec('delete from test1 where id=1'));
// DB_APP::rollback_trans();
// assert(count(DB_APP::get_records('select * from test1 where id=1')) == 1);
// DB_APP::start_trans();
// assert(DB_APP::exec('update test1 set id=2'));
// DB_APP::commit_trans();
// assert(count(DB_APP::get_records('select * from test1 where id=2')) == 1);
// DB_APP::start_trans();
// assert(DB_APP::exec('update test1 set id=3'));
// DB_APP::rollback_trans();
// assert(count(DB_APP::get_records('select * from test1 where id=3')) == 0);
// DB_APP::exec('drop table test1');

?>