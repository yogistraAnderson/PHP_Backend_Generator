<?php
	//make sure these includes are in your include path
	require_once('DB_APP.php');
	require_once('DB_TABLE2.php');
	require_once('myapp_db_objs.php');
	
	if(!DB_APP::is_connected())
		DB_APP::open_conn2( array('localhost','root','admin','app_test') );
	
	//create records
	DBTBL_content_mstr::inst()->new_record2(Array(
		NULL,			//auto increted sequence
		NULL,			//defaulted CURRENT_TIMESTAMP
		NULL,			//active date start
		NULL,			//active date end
		'my_picture',		//title
		1,					//content type
		'myimage.jpg',		//resource val
		0,					//mod status
		'yogistra',			//submitted by
		'Y',			//publish to feed
		NULL,			//content modded by
		NULL,			//publish date
		1				//softlock
	));
	
	$content_id = 0;
	$mod_val = 2;
	
	//update record
	DBTBL_content_mstr::inst()->update_record2($content_id,array(
		DBTBL_content_mstr::FLD_MOD_STATUS => $mod_val,
		DBTBL_content_mstr::FLD_PUBLISH_DT => date("Y-m-d H:i:s")
	));	
			
	//filter records
	$recs = DBTBL_content_mstr::inst()->filter_records2(array(DBTBL_content_mstr::FLD_CONTENT_ID => $content_id));
	
	//output
	print_r($recs);
?>