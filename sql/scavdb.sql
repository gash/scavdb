drop table if exists  search_index;
create table search_index (
    datatype  varchar(10) not null,
    data_id   decimal(6,2) not null,
    data      text,
    primary key search_index_idx (datatype,data_id),
    FULLTEXT (data)
) TYPE=MyISAM;


drop table if exists  comments;
create table comments (
    comment_id   int auto_increment not null,
    datatype  varchar(16) not null,
    data_id   decimal(6,2) not null,
    commenter int,
    comment   text,
    parent_id int,
    ts        int,
    ctype     char,
    primary key comment_id (comment_id),
    key parent_idx (datatype,data_id),
    key ts (ts),
    key ctype (ctype)
) TYPE=MyISAM;


drop table if exists  tags;
create table tags (
    datatype  varchar(16) not null,
    data_id   decimal(6,2) not null,
    tag       varchar(32) not null,
    primary key tags_idx (datatype,data_id,tag)
) TYPE=MyISAM;
create index tag_rev_idx on tags (tag,datatype,data_id);


drop table if exists  things;
create table things (
    thing_id  int auto_increment not null,
    cost      int,
    thing     text,
    descrption text,
    status    char,
    requester varchar(64),
    owner     varchar(64),
    primary key thing_id (thing_id)
) TYPE=MyISAM;


drop table if exists  people;
create table people (
    person_id int auto_increment not null,
    name      varchar(128) not null,
    nickname  varchar(64) unique not null,
    sig       varchar(30) unique not null,
    email     varchar(128) unique not null,
    cell      varchar(16),
    status    varchar(128),
    skillz    text,
    role      char,
    image     text,
    primary key person_id (person_id),
    FULLTEXT (name,nickname)
) TYPE=MyISAM;


drop table if exists  page_captains;
create table page_captains (
    person_id int,
    page      int,
    primary key page_captains (person_id,page)
) TYPE=MyISAM;


drop table if exists  missions;
create table missions (
    mission_id  int auto_increment not null,
    ts          int not null,
    time_str    varchar(128),
    duration    int,
    priority    char,
    place       text,
    description text,
    owner       int,
    primary key mission_id (mission_id),
    key ts (ts)
) TYPE=MyISAM;


drop table if exists  mission_participants;
create table mission_participants(
    mission_id int not null,
    person_id  int not null,
    primary key mission_particpants_idx (mission_id,person_id)
) TYPE=MyISAM;


drop table if exists  messages;
create table messages (
    mesg_id   int auto_increment not null,
    sender    int not null,
    recipient int not null,
    tag       varchar(32),
    title     text,
    message   text,
    flag      char,
    ts        int,
    primary key mesg_id (mesg_id),
    key sender (sender),
    key recipient (recipient),
    key tag (tag),
    key flag (flag),
    key ts (ts)
) TYPE=MyISAM;


drop table if exists items;
create table items (
    item_id   decimal(6,2) not null,
    page      int,
    status    char default 'n',
    max_pt    int,
    points    text,
    description text,
    mtime     int,
    due       int default 4800,
    primary key item_id (item_id),
    key page (page),
    key mtime (mtime)
) TYPE=MyISAM;


drop table if exists  item_things;
create table item_things (
    item_id   decimal(6,2),
    thing_id  int,
    primary key item_things_idx (item_id,thing_id)
) TYPE=MyISAM;


drop table if exists item_people;
create table item_people (
    item_id   decimal(6,2),
    person_id int,
    status    char,
    primary key item_people_idx (item_id, person_id)
) TYPE=MyISAM;


drop table if exists item_missions;
create table item_missions (
    item_id   decimal(6,2),
    mission_id int,
    primary key item_missions_idx (item_id,mission_id)
) TYPE=MyISAM;


drop table if exists `status`;
CREATE TABLE `status` (
  `status_id` int(11) NOT NULL AUTO_INCREMENT,
  `text` varchar(128) DEFAULT NULL,
  `author` int(11) DEFAULT NULL,
  `written` int(11) DEFAULT NULL,
  PRIMARY KEY (`status_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
