alter table items alter column status set default 'n';
alter table items change column item_id item_id decimal(6,2) not null;
alter table people change column status status varchar(128);
alter table comments change column data_id data_id decimal(6,2) not null;
alter table tags change column data_id data_id decimal(6,2);
#alter table people add column skillz text;
alter table messages add column ts int;
create index messages_ts_idx on messages (ts);
alter table items add column mtime int;
create index items_mtime_idx on items (mtime);
alter table item_things change column item_id item_id decimal(6,2) not null;
alter table item_people change column item_id item_id decimal(6,2) not null;
alter table item_missions change column item_id item_id decimal(6,2) not null;

alter table search_index change column data_id data_id decimal(6,2) not null;
alter table missions add column owner int;
alter table people change column sig sig varchar(30) unique not null;
CREATE TABLE `status` (
  `status_id` int(11) NOT NULL AUTO_INCREMENT,
  `text` varchar(128) DEFAULT NULL,
  `author` int(11) DEFAULT NULL,
  `written` int(11) DEFAULT NULL,
  PRIMARY KEY (`status_id`)
)Stuck ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
alter table items add column due int default 4800;
