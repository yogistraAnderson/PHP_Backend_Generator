<?php
	/*common functions*/
	
	//returns a default field size for certain fields
	function get_def_field_sz($fld_type){
		switch(strtoupper($fld_type)){
			case 'VARCHAR':
				return 20;
							
			//case 'INT':
			//	return 8;
				
			default:
				return null; 
		}
	}
?>