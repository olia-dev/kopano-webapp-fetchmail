CREATE TABLE tbl_name (
	id int(11) unsigned NOT NULL AUTO_INCREMENT,
	kopano_uid VARCHAR(255) NOT NULL,
	kopano_mail VARCHAR(255) NOT NULL,
	src_server VARCHAR(255) NOT NULL,
	src_port SMALLINT UNSIGNED NOT NULL,
	src_protocol TINYINT UNSIGNED NOT NULL,
	src_polling_type TINYINT UNSIGNED NOT NULL,
	src_user VARCHAR(255) NOT NULL,
	src_password VARCHAR(255) NOT NULL,
	polling_freq TIME NOT NULL,
	last_polling TIMESTAMP NULL,
	last_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	last_status_code INT(4) DEFAULT -1,	
	last_log_message TEXT,
	PRIMARY KEY(id)
) Engine=InnoDB DEFAULT CHARSET=utf8;
	


