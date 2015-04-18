#AUTO GENERATED FILE
#Source:		xml_to_logging.php
#Timestamp:		04-18-2015 7:28 pm

delimiter // ; 
drop table if exists content_mstr_log//
create table content_mstr_log(
	user_id		VARCHAR(16),
	timestmp	TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	table_op	char(1),
	content_id	INT,
	create_dt	TIMESTAMP,
	active_dt_st	DATE,
	active_dt_end	DATE,
	resource_title	VARCHAR(256),
	resource_type	INT,
	resource_val	VARCHAR(128),
	mod_status	INT,
	submitted_by	VARCHAR(128),
	publish_to_feed	CHAR(1),
	content_modded_by	VARCHAR(128),
	publish_dt	DATE,
	softlock	INT
)engine=InnoDB//

drop trigger if exists trig_aftr_updt_content_mstr//
create trigger trig_aftr_updt_content_mstr after update on content_mstr
	for each row begin
		insert into content_mstr_log values(@app_user_id,NULL,'U',new.content_id,new.create_dt,new.active_dt_st,new.active_dt_end,new.resource_title,new.resource_type,new.resource_val,new.mod_status,new.submitted_by,new.publish_to_feed,new.content_modded_by,new.publish_dt,new.softlock);
end//
drop trigger if exists trig_aftr_insrt_content_mstr//
create trigger trig_aftr_insrt_content_mstr after insert on content_mstr
	for each row begin
		insert into content_mstr_log values(@app_user_id,NULL,'I',new.content_id,new.create_dt,new.active_dt_st,new.active_dt_end,new.resource_title,new.resource_type,new.resource_val,new.mod_status,new.submitted_by,new.publish_to_feed,new.content_modded_by,new.publish_dt,new.softlock);
end//
drop trigger if exists trig_aftr_del_content_mstr//
create trigger trig_aftr_del_content_mstr after delete on content_mstr
	for each row begin
		insert into content_mstr_log values(@app_user_id,NULL,'D',old.content_id,old.create_dt,old.active_dt_st,old.active_dt_end,old.resource_title,old.resource_type,old.resource_val,old.mod_status,old.submitted_by,old.publish_to_feed,old.content_modded_by,old.publish_dt,old.softlock);
end//
delimiter ; //
