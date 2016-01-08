-- ------------------------------------------------------------
-- This file is the Codepot database schema file for PostreSQL.
-- Note this file doesn't mandate which database to use.
--
-- Assumining "local all all password" in /var/lib/pgsql/data/pg_hba.conf
--
-- $ sudo -u postgres psql
-- postgres=# CREATE USER codepot WITH PASSWORD 'codepot';
-- postgres=# \du
-- postgres=# CREATE DATABASE codepot;
-- postgres=# \l
-- postgres=# ALTER DATABASE "codepot" OWNER TO codepot;
-- postgres=# \l
-- postgres=# \q
--
-- $ psql -U codepot -W codepot
-- postgres=# \i codepot.pgsql
-- postgres=# \dt
-- postgres=# \q
-- ------------------------------------------------------------

CREATE TABLE site (
	id          VARCHAR(32)  PRIMARY KEY,
	name        VARCHAR(128) NOT NULL,
	summary     VARCHAR(255) NOT NULL,
	text        TEXT         NOT NULL,

	createdon   TIMESTAMP    NOT NULL,
	updatedon   TIMESTAMP    NOT NULL,
	createdby   VARCHAR(32)  NOT NULL,
	updatedby   VARCHAR(32)  NOT NULL
);

CREATE TABLE project (
	id          VARCHAR(32)  PRIMARY KEY,
	name        VARCHAR(255) UNIQUE NOT NULL,
	summary     VARCHAR(255) NOT NULL,
	description TEXT NOT NULL,
	commitable  CHAR(1)      NOT NULL DEFAULT 'Y',
	public      CHAR(1)      NOT NULL DEFAULT 'Y',
	codecharset VARCHAR(32),

	createdon   TIMESTAMP    NOT NULL,
	updatedon   TIMESTAMP    NOT NULL,
	createdby   VARCHAR(32)  NOT NULL,
	updatedby   VARCHAR(32)  NOT NULL

);

CREATE TABLE project_membership (
	projectid VARCHAR(32) NOT NULL,
	userid    VARCHAR(32) NOT NULL,
	priority  INTEGER     NOT NULL,
	UNIQUE (projectid, userid),
	CONSTRAINT membership_projectid FOREIGN KEY (projectid) REFERENCES project(id) 
		ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE wiki (
	projectid  VARCHAR(32)   NOT NULL,
	name       VARCHAR(255)  NOT NULL,
	text       TEXT          NOT NULL,
	columns    INT           NOT NULL DEFAULT 1,

	createdon  TIMESTAMP     NOT NULL,
	updatedon  TIMESTAMP     NOT NULL,
	createdby  VARCHAR(32)   NOT NULL,
	updatedby  VARCHAR(32)   NOT NULL,

	UNIQUE (projectid, name),

	CONSTRAINT wiki_projectid FOREIGN KEY (projectid) REFERENCES project(id)
		ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE wiki_attachment (
	projectid  VARCHAR(32)   NOT NULL,
	wikiname   VARCHAR(255)  NOT NULL,
	name       VARCHAR(255)  NOT NULL,
	encname    VARCHAR(255)  NOT NULL,

	createdon  TIMESTAMP     NOT NULL,
	createdby  VARCHAR(32)   NOT NULL,

	UNIQUE (projectid, wikiname, name),

	CONSTRAINT wiki_attachment_projectid FOREIGN KEY (projectid) REFERENCES project(id)
		ON DELETE RESTRICT ON UPDATE CASCADE,

	CONSTRAINT wiki_attachment_wikiid FOREIGN KEY (projectid,wikiname) REFERENCES wiki(projectid,name)
		ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE issue (
	projectid     VARCHAR(32)   NOT NULL,
	id            BIGINT        NOT NULL,
	summary       VARCHAR(255)  NOT NULL,
	description   TEXT          NOT NULL,

	type          VARCHAR(32)   NOT NULL,
	status        VARCHAR(32)   NOT NULL,
	owner         VARCHAR(255)  NOT NULL,
	priority      VARCHAR(32)   NOT NULL,

	createdon     TIMESTAMP     NOT NULL,
	updatedon     TIMESTAMP     NOT NULL,
	createdby     VARCHAR(32)   NOT NULL,
	updatedby     VARCHAR(32)   NOT NULL,

	PRIMARY KEY (projectid, id),

	CONSTRAINT issue_projectid FOREIGN KEY (projectid) REFERENCES project(id)
		ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE INDEX issue_index_1 ON issue(projectid, status, type, summary);

CREATE INDEX issue_index_2 ON issue(projectid, summary);

CREATE TABLE issue_attachment (
	projectid  VARCHAR(32)   NOT NULL,
	issueid    BIGINT        NOT NULL,
	name       VARCHAR(255)  NOT NULL,
	encname    VARCHAR(255)  NOT NULL,

	createdon  TIMESTAMP     NOT NULL,
	createdby  VARCHAR(32)   NOT NULL,

	UNIQUE (projectid, issueid, name),

	CONSTRAINT issue_attachment_projectid FOREIGN KEY (projectid) REFERENCES project(id)
		ON DELETE RESTRICT ON UPDATE CASCADE,

	CONSTRAINT issue_attachment_issueid FOREIGN KEY (projectid,issueid) REFERENCES issue(projectid,id)
		ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE issue_file_list (
	projectid   VARCHAR(32)   NOT NULL,
	issueid     BIGINT        NOT NULL,
	filename    VARCHAR(255)  NOT NULL,
	encname     VARCHAR(255)  NOT NULL,
	md5sum      CHAR(32)      NOT NULL,
	description VARCHAR(255)  NOT NULL,

	createdon   DATETIME      NOT NULL,
	createdby   VARCHAR(32)   NOT NULL,

	UNIQUE (projectid, issueid, filename),
	UNIQUE (encname),

	CONSTRAINT issue_file_list_projectid FOREIGN KEY (projectid) REFERENCES project(id)
		ON DELETE RESTRICT ON UPDATE CASCADE,

	CONSTRAINT issue_file_list_issueid FOREIGN KEY (projectid,issueid) REFERENCES issue(projectid,id)
		ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE issue_change (
	projectid VARCHAR(32)  NOT NULL,
	id        BIGINT       NOT NULL,
	sno       BIGINT       NOT NULL,

	type      VARCHAR(32)  NOT NULL,
	status    VARCHAR(32)  NOT NULL,
	owner     VARCHAR(255) NOT NULL,
	priority  VARCHAR(32)  NOT NULL,
	comment   TEXT         NOT NULL,

	updatedon TIMESTAMP    NOT NULL,
	updatedby VARCHAR(32)  NOT NULL,

	PRIMARY KEY (projectid, id, sno),

	CONSTRAINT issue_update_id FOREIGN KEY (projectid,id) REFERENCES issue(projectid,id)
		ON DELETE RESTRICT ON UPDATE CASCADE

);

CREATE INDEX issue_change_index_1 ON issue_change(projectid, id, updatedon);

CREATE TABLE issue_change_attachment (
	projectid  VARCHAR(32)   NOT NULL,
	issueid    BIGINT        NOT NULL,
	issuesno   BIGINT        NOT NULL,
	name       VARCHAR(255)  NOT NULL,
	encname    VARCHAR(255)  NOT NULL,

	createdon  TIMESTAMP     NOT NULL,
	createdby  VARCHAR(32)   NOT NULL,

	UNIQUE (projectid, issueid, name),

	CONSTRAINT issue_change_attachment_projectid FOREIGN KEY (projectid) REFERENCES project(id)
		ON DELETE RESTRICT ON UPDATE CASCADE,

	CONSTRAINT issue_change_attachment_issueidsno FOREIGN KEY (projectid,issueid,issuesno) REFERENCES issue_change(projectid,id,sno)
		ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE file (
	projectid   VARCHAR(32)   NOT NULL,
	name        VARCHAR(255)  NOT NULL,
	tag         VARCHAR(54)   NOT NULL,
	description TEXT          NOT NULL,

	createdon  TIMESTAMP      NOT NULL,
	updatedon  TIMESTAMP      NOT NULL, 
	createdby  VARCHAR(32)    NOT NULL,
	updatedby  VARCHAR(32)    NOT NULL,

	UNIQUE (projectid, name),

	CONSTRAINT file_projectid FOREIGN KEY (projectid) REFERENCES project(id)
		ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE INDEX file_index_1 ON file(projectid, tag, name);

CREATE TABLE file_list (
	projectid   VARCHAR(32)   NOT NULL,
	name        VARCHAR(255)  NOT NULL,
	filename    VARCHAR(255)  NOT NULL,
	encname     VARCHAR(255)  NOT NULL,
	md5sum      CHAR(32)      NOT NULL,
	description VARCHAR(255)  NOT NULL,

	createdon  TIMESTAMP      NOT NULL,
	updatedon  TIMESTAMP      NOT NULL, 
	createdby  VARCHAR(32)    NOT NULL,
	updatedby  VARCHAR(32)    NOT NULL,

	UNIQUE (projectid, filename),
	UNIQUE (encname),

	CONSTRAINT file_list_projectid FOREIGN KEY (projectid,name) REFERENCES file(projectid,name)
		ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE INDEX file_list_index_1 ON file(projectid, name);

CREATE TABLE code_review (
	projectid VARCHAR(32)   NOT NULL,
	rev       BIGINT        NOT NULL,
	sno       BIGINT        NOT NULL,
	comment   TEXT          NOT NULL,

	createdon TIMESTAMP     NOT NULL,
	createdby VARCHAR(32)   NOT NULL,

	updatedon TIMESTAMP     NOT NULL,
	updatedby VARCHAR(32)   NOT NULL,

	UNIQUE (projectid, rev, sno),

	CONSTRAINT code_review_projectid FOREIGN KEY (projectid) REFERENCES project(id)
		ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE log  (
	id         BIGSERIAL PRIMARY KEY,
	projectid  VARCHAR(32)  NOT NULL,
	type       VARCHAR(16)  NOT NULL,
	action     VARCHAR(16)  NOT NULL,
	userid     VARCHAR(32)  NOT NULL,
	message    TEXT         NOT NULL,
	createdon  TIMESTAMP    NOT NULL
);

CREATE INDEX log_index_1 ON log(createdon, projectid, type, action);

CREATE TABLE user_settings (
	userid              VARCHAR(32) PRIMARY KEY,
	code_hide_line_num  CHAR(1) NOT NULL,
	code_hide_metadata  CHAR(1) NOT NULL,
	icon_name           VARCHAR(255) UNIQUE NULL
);

CREATE TABLE user_account (
	userid     VARCHAR(32)  PRIMARY KEY,
	passwd     VARCHAR(255) NOT NULL,
	email      VARCHAR(255),
	enabled    CHAR(1)      NOT NULL DEFAULT 'N' CHECK(enabled in ('Y', 'N'))
);
