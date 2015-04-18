<?php
//Created by: Yogistra Anderson
//Create Dt: 10/23/2009 11:04PM
//
//Purpose: (Requires an xml file as input parameter)
//Generates an SQL output to reproduce the Entities and Relations described in the XML schema input file
//NOTE: The SQL output file can be modified to fine tune any option not considered

require_once('shared.php');

error_reporting(E_ALL ^ E_NOTICE);
date_default_timezone_set('America/Halifax');

$dbase = NULL;
$tables = array();
$cnstrnts = array();

/*gets the schema defination and stores it(tables and constraints) in arrays*/
function start_element($parser,$name,$attribs){	
	global $tables;
	global $cnstrnts;
	global $dbase;
	
	if(strtoupper($name) == 'SCHEMA'){
		$dbase = $attribs['NAME'];
	}
	elseif($name == "TABLE"){		
		$table = array();
		
		$table['name']=$attribs['NAME'];
		$table['fields'] = array();
		
		$tables []= $table;		
	}
	elseif($name == "FIELD"){
		$field = array();
		
		$field['name']			= $attribs['NAME'];
		$field['type']			= $attribs['TYPE'];		
		$field['size']			= isset($attribs['SIZE'])? $attribs['SIZE'] : get_def_field_sz($attribs['TYPE']);
		$field['pk']			= ($attribs['PRIMARY_KEY'] == "1") ? 1 : 0;
		$field['not_null']		= ($attribs['NOT_NULL'] == "1")? 1 : 0;
		$field['auto_increte']	= ($attribs['AUTO_INCRETE'])? 1 : 0;
		$field['default'] 		= isset($attribs['DEFAULT'])? $attribs['DEFAULT']: null;
		
		if($attribs['TYPE'] == 'TEXT')
			$field['size'] = $attribs['SIZE'];
			
		$tables[count($tables) - 1]['fields'] []= $field;			
	}
	elseif($name == 'FLD_CNSTRNT'){
		$cnstrnt = array();
		
		$cnstrnt['fld_nm'] = $attribs['FLD_NM'];
		$cnstrnt['type'] = $attribs['TYPE'];
		$cnstrnt['table'] = $attribs['TABLE'];		
		
		//each type of constraint has different required fields
		switch($attribs['TYPE']){
			case 'foreign_key':
				$cnstrnt['ref_table'] = $attribs['REF_TABLE'];
				$cnstrnt['ref_field'] = $attribs['REF_FIELD'];
				$cnstrnt['on_delete'] = $attribs['ON_DELETE'];
				break;
			
			case 'composite_key':
				$cnstrnt['field1'] = $attribs['FIELD1'];
				$cnstrnt['field2'] = $attribs['FIELD2'];
				break;
			
			default:
				echo("Error: Constraint type not supported \n");
				break;				
		}
		
		$cnstrnts []= $cnstrnt;
	}
}

//element end tag handler
function end_element($parser,$name){}


$parser = xml_parser_create();
xml_set_element_handler($parser,"start_element","end_element");

while(!feof(STDIN)){
	$text = fgets(STDIN);
	xml_parse($parser,$text);
}

xml_parser_free($parser);


echo('#AUTO GENERATED FILE'.PHP_EOL);
echo("#Source:\t\txml_to_sql.php".PHP_EOL);		
echo("#Timestamp:\t\t".date('m-d-Y g:i a',time()).PHP_EOL.PHP_EOL);
	
//set so that key constraints doesnt prevent dropping the table
echo "SET FOREIGN_KEY_CHECKS = 0; \n";

foreach($tables as $table){
	$fld_count = count($table['fields']);
	$fld_idx = 1;
	
	echo 'DROP TABLE IF EXISTS ',($dbase.'.'),$table['name'],";\n";
	echo 'CREATE TABLE ',($dbase.'.'),$table['name'],'(',"\n";		
	
	foreach($table['fields'] as $field){
		echo "\t",$field['name'],"\t",$field['type'],($field['size'])?'('.$field['size'].')':null;
		echo ($field['not_null'])?"\t NOT NULL":null;
		echo ($field['auto_increte'])?"\t AUTO_INCREMENT":null;
		echo ($field['pk'])?"\t PRIMARY KEY":null;
		echo ($field['default'])? ("\t DEFAULT ".$field['default']) :null;
		echo ($fld_count != $fld_idx)?",\n":"\n";
		$fld_idx++;
	}
	
	echo ") engine=InnoDB;\n\n";	//default table engine is InnoDB
}

foreach($cnstrnts as $cnstrnt){
	switch($cnstrnt['type']){
		case 'foreign_key':
			echo 'ALTER TABLE ',$cnstrnt['table']," \n";
			echo '	ADD CONSTRAINT FOREIGN KEY ','FK_',$cnstrnt['table'],'_',$cnstrnt['fld_nm'],'(',$cnstrnt['fld_nm'],") \n";
			echo '	REFERENCES ',$cnstrnt['ref_table'],'(',$cnstrnt['ref_field'],") \n";
			echo '	ON DELETE ',$cnstrnt['on_delete'], "; \n";
			break;
			
		case 'composite_key':
			echo 'ALTER TABLE ',$cnstrnt['table']," \n";
			echo '	ADD CONSTRAINT PRIMARY KEY ','CK_',$cnstrnt['table']," \n";
			echo '	(',$cnstrnt['field1'],',',$cnstrnt['field2'],"); \n";
			break;
			
		default:
			echo("Error:constraint type not supported \n");
			break;
	}
}

//restore foreign key checks
echo "SET FOREIGN_KEY_CHECKS = 1; \n";

//ob_flush();
//$sql = $ob_get_clean();

?>