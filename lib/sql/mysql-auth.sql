CREATE TABLE `auth` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `acl` tinyint(1) default 0,
  `uid` varchar(31),
  `otp` varchar(8),
  `webpw` varchar(255),
  `msgpw` varchar(255),
  `sqlpw` varchar(255),
  `dnspw` varchar(255),
  `muid` varchar(255),
  `mgid` varchar(255),
  `mpath` varchar(255),
  `mquota` varchar(15),
  `cookie` varchar(255)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

INSERT INTO `auth` VALUES (null,1,'admin@localhost.lan','','changeme','changeme','','','','','','','');
