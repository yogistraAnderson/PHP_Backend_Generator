#AUTO GENERATED FILE
#Source:		xml_to_sql.php
#Timestamp:		04-18-2015 7:28 pm

SET FOREIGN_KEY_CHECKS = 0; 
DROP TABLE IF EXISTS guyanalive.resource_types;
CREATE TABLE guyanalive.resource_types(
	resource_tp_id	INT	 AUTO_INCREMENT	 PRIMARY KEY,
	resource_tp_text	VARCHAR(64)	 NOT NULL
) engine=InnoDB;

DROP TABLE IF EXISTS guyanalive.mod_types;
CREATE TABLE guyanalive.mod_types(
	mod_type_id	INT	 AUTO_INCREMENT	 PRIMARY KEY,
	mod_type_text	VARCHAR(64)	 NOT NULL
) engine=InnoDB;

DROP TABLE IF EXISTS guyanalive.content_mstr;
CREATE TABLE guyanalive.content_mstr(
	content_id	INT	 AUTO_INCREMENT	 PRIMARY KEY,
	create_dt	TIMESTAMP	 NOT NULL	 DEFAULT CURRENT_TIMESTAMP,
	active_dt_st	DATE,
	active_dt_end	DATE,
	resource_title	VARCHAR(256)	 NOT NULL,
	resource_type	INT	 NOT NULL,
	resource_val	VARCHAR(128)	 NOT NULL,
	mod_status	INT	 NOT NULL,
	submitted_by	VARCHAR(128)	 NOT NULL,
	publish_to_feed	CHAR(1),
	content_modded_by	VARCHAR(128),
	publish_dt	DATE,
	softlock	INT	 NOT NULL
) engine=InnoDB;

SET FOREIGN_KEY_CHECKS = 1; 
