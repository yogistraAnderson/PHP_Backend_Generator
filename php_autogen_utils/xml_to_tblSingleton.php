<?php
	/*
		Programmer:	Yogistra Anderson
		Date:		4/23/2010 9:12AM
		Desc:		Reads input schema file and outputs DB_TABLE2 subclass for each input table.
					Output based on DB_TABLE2_Template.php
					
					
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
	
	output_header();
	
	/*gather table collection from input schema file*/
	while(!feof(STDIN)){												//read input file
		$text = fgets(STDIN);										
		xml_parse($parser,$text);										//parse input file
	}
	
	output_table_classes();
	output_footer();
	
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
			if(!(isset($attribs["DO_OBJECT"]) && $attribs["DO_OBJECT"] == '1')){
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
				$field['size']			= isset($attribs['SIZE'])? $attribs['SIZE'] : 'null';
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
	
	function output_table_classes(){
		global $table_col;
		
		foreach($table_col as $table){
			echo "/*For table {$table['name']} */".PHP_EOL;
			echo "class DBTBL_{$table['name']} extends DB_TABLE2{".PHP_EOL;
			echo '	private static $_instance = NULL;'.PHP_EOL.PHP_EOL;
			
			$fld_idx = 0;
			$fld_pk = NULL;
			$fld_idx = 0;
			
			foreach($table['fields'] as $field){
				if($field['pk'])
					$fld_pk = $field['name'];
					
				//echo "	const FLD_{$field['name']} = {$fld_idx};".PHP_EOL;
				echo "	const FLD_".strtoupper($field['name'])." = {$fld_idx};".PHP_EOL;
				++$fld_idx;
			}
			
			echo PHP_EOL;
			
			echo '	protected function __construct(){'.PHP_EOL;
			echo "		\$this->name='{$table['name']}';".PHP_EOL;
			echo "		\$this->fields 	= array();".PHP_EOL;
			
			if($fld_pk)
				echo "		\$this->PK_FIELD_IDX = self::FLD_".strtoupper($fld_pk).";".PHP_EOL;
			
			echo PHP_EOL;
			
			foreach($table['fields'] as $field){								
				//'USER_ID',DB_APP::DATA_TYPE_STRING,12,0,0,0); 
				//echo "		\$this->fields[self::FLD_{$field['name']}] = new field(";						
				echo "		\$this->fields[self::FLD_".strtoupper($field['name'])."] = new field(";						
				echo	"'{$field['name']}',";
				echo	translate_fld_type($field['type'],$field['validation']).',';
				echo	(($field['size'])? $field['size']:get_def_field_sz($field['type'])).',';
				echo	$field['not_null'].',';
				echo	$field['default'].',';
				echo	$field['auto_increte'].');'.PHP_EOL;
			}
			
			echo "	}".PHP_EOL.PHP_EOL;
			
			echo "	public static function inst(){".PHP_EOL;
			echo "		if(!self::\$_instance)".PHP_EOL;
			echo "			self::\$_instance = new DBTBL_{$table['name']}();".PHP_EOL.PHP_EOL;
			echo "		return self::\$_instance;".PHP_EOL;
			echo "	}".PHP_EOL;		
			
			//
			echo '	protected function get_field_names(){'.PHP_EOL;
			echo '		return array(';
			
			$is_first = 1;
			foreach($table['fields'] as $field){
				if($is_first){
					echo "'".strtolower($field['name'])."'";
					$is_first = 0;
				}else
					echo ",'".strtolower($field['name'])."'";
			}
			
			echo ');'.PHP_EOL;
			echo '	}'.PHP_EOL;
			
			//field types function
			echo '	protected function get_field_types(){'.PHP_EOL;			
			
			$fldtpstr = '';
			foreach($table['fields'] as $field){
				switch($field['type']){
					case 'BINARY':
					case 'VARCHAR':
					case 'TEXT':
					case 'CHAR':
					case 'DATETIME':
					case 'TIMESTAMP':
					case 'DATE':
						$fldtpstr .= 's';
					break;
					
					case 'INT':
					case 'INT SIGNED':
					case 'BIGINT':
					case 'BOOLEAN':
					case 'BOOL':
						$fldtpstr .= 'i';
					break;
					
					case 'DOUBLE':
					case 'DECIMAL':
					case 'FLOAT':
						$fldtpstr .= 'd';
					break;
					
					case 'BLOB':
						$fldtpstr .= 'b';
					break;
				}
			}
			
			echo "		return '{$fldtpstr}';".PHP_EOL;
			echo '	}'.PHP_EOL;
			
			echo "}".PHP_EOL;
		}
	}
	
	function output_header(){
		echo("<?php".PHP_EOL);		
		echo("/*\nAUTO GENERATED FILE.".PHP_EOL);
		echo("Source:\t\txml_to_tblSingleton.php".PHP_EOL);		
		echo("Timestamp:\t".date('m-d-Y g:i a',time()).PHP_EOL.'*/'.PHP_EOL);		
		echo("require_once('DB_TABLE2.php');".PHP_EOL.PHP_EOL);
	}
	
	function output_footer(){ echo(PHP_EOL.PHP_EOL.'?>'); }
	
	function translate_fld_type($str_fld_type,$validation_rule){
		switch(strtoupper($str_fld_type)){
			case 'BINARY':
			case 'VARCHAR':
			case 'TEXT':
			case 'CHAR':
			//string types support subtypes	to allow more targeted validation
			if($validation_rule){
				switch($validation_rule){
					case 'STRING_WORD':
						return 'DB_APP::DATA_TYPE_STRING_WORD';
					break;
					
					/*	
					case 'STRING_MULTI_WORD':
						return 'DB_APP::DATA_TYPE_STRING_MULTI_WORD';
					break;
					
					case 'TEXT_W_TAGS':
						return 'DB_APP::DATA_TYPE_TEXT_W_TAGS';
					break;
					
					case 'STRING_XML':
						return 'DB_APP::DATA_TYPE_STRING_XML';
					break;
					*/
					
					default:
						throw new Exception('translate_fld_type: unrecognized validation rule');
				}
			}
			else
				return 'DB_APP::DATA_TYPE_STRING';
			break;
			
			case 'INT':
			case 'INT SIGNED':
			case 'BIGINT':
			//TO DO: translate BIG INT to matching PHP data type 
				return 'DB_APP::DATA_TYPE_INT';
			break;
			
			case 'BOOL':
				return 'DB_APP::DATA_TYPE_BOOL';
			break;			
			
			case 'DECIMAL(3,1)':	//TODO: clean up and sort this out
			case 'DECIMAL(2,1)':	//NOTE: (2,1) used to let code run, look up better solution 
			case 'FLOAT':
				return 'DB_APP::DATA_TYPE_FLOAT';
			break;
			
			case 'DATE':			
				return 'DB_APP::DATA_TYPE_DATE';
			break;
			
			case 'DATETIME':
			case 'TIMESTAMP':
				return 'DB_APP::DATA_TYPE_DATETIME';
			break;
			
			default:
				throw new Exception('translate_fld_type: '.$str_fld_type.' is unsupported field type!');
		}
	}	

?>