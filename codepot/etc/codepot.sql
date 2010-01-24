
USE codepot;

CREATE TABLE project (
	id          VARCHAR(32)  PRIMARY KEY,
	name        VARCHAR(255) UNIQUE NOT NULL,
	summary     VARCHAR(255) NOT NULL,
	description TEXT NOT NULL,

	createdon   DATETIME,
	updatedon   DATETIME,
	createdby   VARCHAR(32),
	updatedby   VARCHAR(32)

) charset=utf8 engine=InnoDB;

CREATE TABLE project_membership (
	projectid VARCHAR(32) NOT NULL,
	userid    VARCHAR(32) NOT NULL,
	priority  INTEGER     NOT NULL,
	UNIQUE KEY membership (projectid, userid),
	CONSTRAINT membership_projectid FOREIGN KEY (projectid) REFERENCES project(id) 
		ON DELETE CASCADE ON UPDATE CASCADE
) charset=utf8 engine=InnoDB;

CREATE TABLE wiki (
	projectid  VARCHAR(32)   NOT NULL,
	name       VARCHAR(255)  NOT NULL,
	text	   TEXT          NOT NULL,

	createdon  DATETIME,
	updatedon  DATETIME,
	createdby  VARCHAR(32),
	updatedby  VARCHAR(32),

	UNIQUE KEY wiki_id (projectid, name),

	CONSTRAINT wiki_projectid FOREIGN KEY (projectid) REFERENCES project(id)
		ON DELETE RESTRICT ON UPDATE CASCADE
) charset=utf8 engine=InnoDB;	


CREATE TABLE file (
	projectid   VARCHAR(32)   NOT NULL,
	name        VARCHAR(255)  NOT NULL,
	encname     VARCHAR(255)  NOT NULL,
	tag         VARCHAR(54)   NOT NULL,
	summary     VARCHAR(255)  NOT NULL,
	md5sum      CHAR(32)      NOT NULL,
	description TEXT          NOT NULL,

	createdon  DATETIME,
	updatedon  DATETIME, 
	createdby  VARCHAR(32),
	updatedby  VARCHAR(32),

	UNIQUE KEY file_id (projectid, name),
	UNIQUE KEY (encname),
	INDEX tagged_file_id (projectid, tag, name),

	CONSTRAINT file_projectid FOREIGN KEY (projectid) REFERENCES project(id) 
		ON DELETE RESTRICT ON UPDATE CASCADE
) charset=utf8 engine=InnoDB;	


