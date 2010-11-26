CREATE TABLE `status` (
  `status_id` int(11) NOT NULL AUTO_INCREMENT,
  `text` varchar(128) DEFAULT NULL,
  `author` int(11) DEFAULT NULL,
  `written` int(11) DEFAULT NULL,
  PRIMARY KEY (`status_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
