-- CREATE USER 'cgeadmin' IDENTIFIED BY 'Qrpey10';
-- CREATE USER 'cgeclient' IDENTIFIED BY 'www';
-- CREATE USER 'www' IDENTIFIED BY '';
--
-- GRANT ALL ON * TO cgeadmin;
-- GRANT ALL ON * TO cgeclient;

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL auto_increment,
  `usr` varchar(255) NOT NULL,
  `pwd` varchar(40) NOT NULL,
  `email` varchar(255) NOT NULL,
  `session_id` varchar(40) default NULL,
  `last_login` varchar(20) default NULL,
  `ip` varchar(100) default NULL,
  `status` varchar(50) default NULL,
  `tmp` varchar(40) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `user` (`usr`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `users` VALUES (5,
    'user2',
    '306ce545e313d7ae4e35ca2882fe54d023d13c75',
    'bellod.cisneros5@gmail.com',
    '',
    '20-11-2015 09:31:01',
    '10.57.17.226',
    'ACCEPTED',
    NULL);
