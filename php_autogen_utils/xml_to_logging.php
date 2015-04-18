<?php
	/*
		Programmer:	Yogistra Anderson
		Date:		4/23/2010 9:12AM
		Desc:		Reads input schema file and outputs logging.
					
					
		Note: when a new field type is added, corresponding support must be added to DB_APP					
	*/
	//require_once('shared.php');
	//require_once('DB_APP.php');	
	
	date_default_timezone_set('America/Halifax');
	
	$cur_tbl_nm = '';
	$do_process = true;
	$table_col = array();
	
	$parser = xml_parser_create();										//create parser
	xml_set_element_handler($parser,"start_element","end_element");		//set handlers	
	
	/*gather table collection from input schema file*/
	while(!feof(STDIN)){												//read input file
		$text = fgets(STDIN);										
		xml_parse($parser,$text);										//parse input file
	}
	
	output_table_logging();
		
	xml_parser_free($parser);											//free parser	
	
	//parse start tag handler
	function start_element($parser,$name,$attribs){	
		global $cur_tbl_nm;
		global $do_process;	
		global $table_col;	
		
		if($name == "TABLE"){
			//$cur_tbl_nm = strtoupper($attribs['NAME']);
			$cur_tbl_nm = $attribs['NAME'];
			$do_process = true;		//reset flag
				
			//if object not specified, exit
			if(!(isset($attribs["DO_LOGGING"]) && $attribs["DO_LOGGING"] == '1')){
				$do_process = false;	//set flag
				return;
			}			
			
			$table = array();
			$table['name'] = $cur_tbl_nm;
			$table['fields'] = array();			
			
			$table_col []= $table;
		}
		elseif($name == "FIELD"){
			if($do_process){
				$field = array();
				//$field['name']			= strtoupper($attribs['NAME']);
				$field['name']			= $attribs['NAME'];
				$field['type']			= $attribs['TYPE'];		
				$field['size']			= isset($attribs['SIZE'])? $attribs['SIZE'] : null;
				$field['pk']			= (isset($attribs['PRIMARY_KEY']) && ($attribs['PRIMARY_KEY'] == '1')) ? 1 : 0;
				$field['not_null']		= (isset($attribs['NOT_NULL']) && ($attribs['NOT_NULL'] == '1')) ? 1 : 0;
				$field['auto_increte']	= (isset($attribs['AUTO_INCRETE']) && ($attribs['AUTO_INCRETE'] == '1')) ? 1 : 0;
				$field['default'] 		= isset($attribs['DEFAULT']) ? 1 : 0;
				$field['validation']	= isset($attribs['VALIDATE_AS'])? $attribs['VALIDATE_AS'] : null;
				
				$table_col[count($table_col) - 1]['fields'] []= $field;
			}
		}		
	}
	
	//element end tag handler
	function end_element($parser,$name){
		//if($name == "TABLE") echo("\n");
		/*
		global $cur_tbl_nm;
		global $do_process;		
		
		if($name == "TABLE"){			
			if($do_process){
				echo("TBL_$cur_tbl_nm::set_desc(\$desc_$cur_tbl_nm); \n\n");
			}
		}
		*/
	}
	
	/*NOTE:change this when using different method to assert the userid for the logging trigger*/
	function get_code_for_userid(){ 
		//return '(select user_id from current_user_logging)';
		return '@app_user_id';
	}
	
	function output_table_logging(){
		global $table_col;
		
		echo('#AUTO GENERATED FILE'.PHP_EOL);
		echo("#Source:\t\txml_to_logging.php".PHP_EOL);		
		echo("#Timestamp:\t\t".date('m-d-Y g:i a',time()).PHP_EOL.PHP_EOL);
		echo 'delimiter // ; '.PHP_EOL;
		
		foreach($table_col as $table){			
			echo "drop table if exists {$table['name']}_log//".PHP_EOL;
			echo "create table {$table['name']}_log(".PHP_EOL;
			echo "	user_id		VARCHAR(16),".PHP_EOL;
			echo "	timestmp	TIMESTAMP DEFAULT CURRENT_TIMESTAMP,".PHP_EOL;
			echo "	table_op	char(1),".PHP_EOL;
			
			$fld_idx = 0;
			$fld_count = count($table['fields']);
			
			foreach($table['fields'] as $field){
				$fld_idx++;
				$fld_delimiter = ($fld_idx < $fld_count)?',':'';
				$fld_delimiter.= PHP_EOL;
				
				if($field['size'])
					echo "	{$field['name']}\t{$field['type']}({$field['size']})".$fld_delimiter;
				else
					echo "	{$field['name']}\t{$field['type']}".$fld_delimiter;
			}
			
			echo ")engine=InnoDB//".PHP_EOL;
			echo PHP_EOL;
			
			//update trigger
			echo "drop trigger if exists trig_aftr_updt_{$table['name']}//".PHP_EOL;
			echo "create trigger trig_aftr_updt_{$table['name']} after update on {$table['name']}".PHP_EOL;
			echo "	for each row begin".PHP_EOL;
			echo "		insert into {$table['name']}_log values(".get_code_for_userid().",NULL,'U',";
			
			output_val_list_NEW($table);
			
			echo ");".PHP_EOL;
			echo "end//".PHP_EOL;
			
			//insert trigger
			echo "drop trigger if exists trig_aftr_insrt_{$table['name']}//".PHP_EOL;
			echo "create trigger trig_aftr_insrt_{$table['name']} after insert on {$table['name']}".PHP_EOL;
			echo "	for each row begin".PHP_EOL;
			echo "		insert into {$table['name']}_log values(".get_code_for_userid().",NULL,'I',";
			
			output_val_list_NEW($table);
			
			echo ");".PHP_EOL;
			echo "end//".PHP_EOL;
			
			//delete trigger
			echo "drop trigger if exists trig_aftr_del_{$table['name']}//".PHP_EOL;
			echo "create trigger trig_aftr_del_{$table['name']} after delete on {$table['name']}".PHP_EOL;
			echo "	for each row begin".PHP_EOL;
			echo "		insert into {$table['name']}_log values(".get_code_for_userid().",NULL,'D',";
			
			output_val_list_OLD($table);
			
			echo ");".PHP_EOL;
			echo "end//".PHP_EOL;
		}
		
		echo 'delimiter ; //'.PHP_EOL;
	}
	
	/**
		returns value list for the log insert
	*/
	function output_val_list_NEW($table){
		$fld_idx = 0;
		$fld_count = count($table['fields']);
		
		foreach($table['fields'] as $field){
			$fld_idx++;
			$fld_delimiter = ($fld_idx < $fld_count)?',':'';
			
			echo "new.{$field['name']}$fld_delimiter";
		}
	}
	
	/**
		returns value list for the log insert
	*/
	function output_val_list_OLD($table){
		$fld_idx = 0;
		$fld_count = count($table['fields']);
			
		foreach($table['fields'] as $field){
			$fld_idx++;
			$fld_delimiter = ($fld_idx < $fld_count)?',':'';
			
			echo "old.{$field['name']}$fld_delimiter";
		}
	}
?>