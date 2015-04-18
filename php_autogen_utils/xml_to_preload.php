<?php
	/**
		@author:	Yogistra Anderson
		@Date:		23-02-2012
		@Desc:		This is an addition to the code generation scripts, parses an schema file for preload directives and 
					produces the sql to insert the preload data.
					
					Expects a directive in this format:
					
					Preload:(table name)	//do preload for this table
					val1,val2,val3			//CSV formatted values representing a record
					Preload:end				//end of preload data
					
	*/
	
	date_default_timezone_set('America/Halifax');
	
	// if($argc > 1){
		// process_file($argv[1]);
	// }else
		// die('No file specified!');
		
	// function process_file($file_nm){
		// if($h_file = fopen($file_nm,'r')){
			$table_found = false;
			$table_name = null;
			$reg_matches = array();
			
			// while(!feof($h_file)){
				// $line = trim(fgets($h_file));
			while(!feof(STDIN)){
				$line = trim(fgets(STDIN));
				//echo 'reading '.PHP_EOL;
				
				if(!$table_found){
				//look for table
				
					if(preg_match('/^preload:(\w*)$/i',$line,$reg_matches)){
					//echo 'table found '.PHP_EOL;
					//table found, set flag and continue
						$table_found = true;
						$table_name = $reg_matches[1];
					}
				}else{
				//table is being processed
					
					if(preg_match('/^preload:end$/i',$line)){
					//end of table preload data reached, flip flag
						$table_found = false;
					}else{
					//parse preload data
						if(strlen($line) && preg_match('/([\'|"]?\w[\'|"]?)/',$line)){
						//write sql for single record
							echo "insert into $table_name values($line);".PHP_EOL;
						}else{
							echo 'invalid data or data format, exiting!'.PHP_EOL;
							// fclose($h_file);
							exit(0);
						}
					}
				}
			}
			
			// fclose($h_file);
		// }else
			// die('Failed to open schema file!');
	// }
?>