<?php
/*
AUTO GENERATED FILE.
Source:		xml_to_tblSingleton.php
Timestamp:	04-18-2015 7:28 pm
*/
require_once('DB_TABLE2.php');

/*For table resource_types */
class DBTBL_resource_types extends DB_TABLE2{
	private static $_instance = NULL;

	const FLD_RESOURCE_TP_ID = 0;
	const FLD_RESOURCE_TP_TEXT = 1;

	protected function __construct(){
		$this->name='resource_types';
		$this->fields 	= array();
		$this->PK_FIELD_IDX = self::FLD_RESOURCE_TP_ID;

		$this->fields[self::FLD_RESOURCE_TP_ID] = new field('resource_tp_id',DB_APP::DATA_TYPE_INT,null,0,0,1);
		$this->fields[self::FLD_RESOURCE_TP_TEXT] = new field('resource_tp_text',DB_APP::DATA_TYPE_STRING,64,1,0,0);
	}

	public static function inst(){
		if(!self::$_instance)
			self::$_instance = new DBTBL_resource_types();

		return self::$_instance;
	}
	protected function get_field_names(){
		return array('resource_tp_id','resource_tp_text');
	}
	protected function get_field_types(){
		return 'is';
	}
}
/*For table mod_types */
class DBTBL_mod_types extends DB_TABLE2{
	private static $_instance = NULL;

	const FLD_MOD_TYPE_ID = 0;
	const FLD_MOD_TYPE_TEXT = 1;

	protected function __construct(){
		$this->name='mod_types';
		$this->fields 	= array();
		$this->PK_FIELD_IDX = self::FLD_MOD_TYPE_ID;

		$this->fields[self::FLD_MOD_TYPE_ID] = new field('mod_type_id',DB_APP::DATA_TYPE_INT,null,0,0,1);
		$this->fields[self::FLD_MOD_TYPE_TEXT] = new field('mod_type_text',DB_APP::DATA_TYPE_STRING,64,1,0,0);
	}

	public static function inst(){
		if(!self::$_instance)
			self::$_instance = new DBTBL_mod_types();

		return self::$_instance;
	}
	protected function get_field_names(){
		return array('mod_type_id','mod_type_text');
	}
	protected function get_field_types(){
		return 'is';
	}
}
/*For table content_mstr */
class DBTBL_content_mstr extends DB_TABLE2{
	private static $_instance = NULL;

	const FLD_CONTENT_ID = 0;
	const FLD_CREATE_DT = 1;
	const FLD_ACTIVE_DT_ST = 2;
	const FLD_ACTIVE_DT_END = 3;
	const FLD_RESOURCE_TITLE = 4;
	const FLD_RESOURCE_TYPE = 5;
	const FLD_RESOURCE_VAL = 6;
	const FLD_MOD_STATUS = 7;
	const FLD_SUBMITTED_BY = 8;
	const FLD_PUBLISH_TO_FEED = 9;
	const FLD_CONTENT_MODDED_BY = 10;
	const FLD_PUBLISH_DT = 11;
	const FLD_SOFTLOCK = 12;

	protected function __construct(){
		$this->name='content_mstr';
		$this->fields 	= array();
		$this->PK_FIELD_IDX = self::FLD_CONTENT_ID;

		$this->fields[self::FLD_CONTENT_ID] = new field('content_id',DB_APP::DATA_TYPE_INT,null,0,0,1);
		$this->fields[self::FLD_CREATE_DT] = new field('create_dt',DB_APP::DATA_TYPE_DATETIME,null,1,1,0);
		$this->fields[self::FLD_ACTIVE_DT_ST] = new field('active_dt_st',DB_APP::DATA_TYPE_DATE,null,0,0,0);
		$this->fields[self::FLD_ACTIVE_DT_END] = new field('active_dt_end',DB_APP::DATA_TYPE_DATE,null,0,0,0);
		$this->fields[self::FLD_RESOURCE_TITLE] = new field('resource_title',DB_APP::DATA_TYPE_STRING,256,1,0,0);
		$this->fields[self::FLD_RESOURCE_TYPE] = new field('resource_type',DB_APP::DATA_TYPE_INT,null,1,0,0);
		$this->fields[self::FLD_RESOURCE_VAL] = new field('resource_val',DB_APP::DATA_TYPE_STRING,128,1,0,0);
		$this->fields[self::FLD_MOD_STATUS] = new field('mod_status',DB_APP::DATA_TYPE_INT,null,1,0,0);
		$this->fields[self::FLD_SUBMITTED_BY] = new field('submitted_by',DB_APP::DATA_TYPE_STRING,128,1,0,0);
		$this->fields[self::FLD_PUBLISH_TO_FEED] = new field('publish_to_feed',DB_APP::DATA_TYPE_STRING,1,0,0,0);
		$this->fields[self::FLD_CONTENT_MODDED_BY] = new field('content_modded_by',DB_APP::DATA_TYPE_STRING,128,0,0,0);
		$this->fields[self::FLD_PUBLISH_DT] = new field('publish_dt',DB_APP::DATA_TYPE_DATE,null,0,0,0);
		$this->fields[self::FLD_SOFTLOCK] = new field('softlock',DB_APP::DATA_TYPE_INT,null,1,0,0);
	}

	public static function inst(){
		if(!self::$_instance)
			self::$_instance = new DBTBL_content_mstr();

		return self::$_instance;
	}
	protected function get_field_names(){
		return array('content_id','create_dt','active_dt_st','active_dt_end','resource_title','resource_type','resource_val','mod_status','submitted_by','publish_to_feed','content_modded_by','publish_dt','softlock');
	}
	protected function get_field_types(){
		return 'issssisissssi';
	}
}


?>