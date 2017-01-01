<?php

return array(
'threads' => "
-- --------------------------------------------------------

--
-- Table structure for table 'support_threads'
--

CREATE TABLE IF NOT EXISTS %s (
  `thread_id` bigint(20) NOT NULL auto_increment,
  `hash` char(32) NOT NULL,
  `dt` datetime NOT NULL,
  `email` varchar(150) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `messages` int(11) NOT NULL default '1',
  `state` varchar(30) NOT NULL default 'open',
  `has_attachment` int(1) NOT NULL default '0',
  `priority` int(11) NOT NULL default '1',
  PRIMARY KEY  (`thread_id`),
  KEY `hash` (`hash`,`email`),
  KEY `dt` (`dt`),
  KEY `priority` (`priority`),
  KEY `state` (`state`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;",

'messages' => "
-- --------------------------------------------------------


--
-- Table structure for table `support_messages`
--

CREATE TABLE IF NOT EXISTS %s (
  `message_id` bigint(20) NOT NULL auto_increment,
  `hash` char(32) NOT NULL,
  `thread_id` bigint(20) NOT NULL,
  `dt` datetime NOT NULL,
  `email` varchar(150) NOT NULL,
  `from_user_id` int(10) unsigned NOT NULL,
  `content` longtext NOT NULL,
  `email_to` varchar(150) NOT NULL,
  `message_type` varchar(30) NOT NULL default 'support',
  `uid` bigint(20) NOT NULL default '0',
  PRIMARY KEY  (`message_id`),
  KEY `thread_id` (`thread_id`),
  KEY `from_user_id` (`from_user_id`),
  KEY `dt` (`dt`),
  KEY `email` (`email`),
  UNIQUE KEY `hash` (`hash`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;",

'tags' => "
-- --------------------------------------------------------

--
-- Table structure for table 'support_tags'
--

CREATE TABLE IF NOT EXISTS %s (
  thread_id bigint(20) NOT NULL,
  tag_slug varchar(100) NOT NULL,
  dt datetime NOT NULL,
  KEY thread_id (thread_id,tag_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;",

'predefined_messages' => "
-- --------------------------------------------------------

--
-- Table structure for table 'support_predefined_messages'
--

CREATE TABLE IF NOT EXISTS %s (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(128) NOT NULL,
  `message` text NOT NULL,
  `tag` varchar(128) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `tag` (`tag`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;",

'threadmeta' => "
-- --------------------------------------------------------

--
-- Table structure for table 'support_threadmeta'
--

CREATE TABLE %s (
  `meta_id` bigint(20) unsigned NOT NULL auto_increment,
  `thread_id` bigint(20) unsigned NOT NULL,
  `meta_key` varchar(255) default NULL,
  `meta_value` longtext,
  PRIMARY KEY  (`meta_id`),
  KEY `thread` (`thread_id`),
  KEY `meta_key` (`meta_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
",

'messagemeta' => "
-- --------------------------------------------------------

--
-- Table structure for table 'support_messagemeta'
--

CREATE TABLE %s (
  `meta_id` bigint(20) unsigned NOT NULL auto_increment,
  `message_id` bigint(20) unsigned NOT NULL,
  `meta_key` varchar(255) default NULL,
  `meta_value` longtext,
  PRIMARY KEY  (`meta_id`),
  KEY `message` (`message_id`),
  KEY `meta_key` (`meta_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
",

'attachments' => "
-- --------------------------------------------------------

--
-- Table structure for table 'support_attachments'
--

CREATE TABLE %s (
  `message_id` bigint(20) unsigned NOT NULL,
  `filename` varchar(200) NOT NULL DEFAULT '',
  `file_content` longblob NOT NULL,
  PRIMARY KEY (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
",

);