-- LynxHD database schema and sample data.
-- Preserve the connection character-set values before restoring them below.
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- WARNING: importing this file replaces the existing LynxHD database tables.
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `livechat_message`;
DROP TABLE IF EXISTS `livechat_conversation`;
DROP TABLE IF EXISTS `livechat_canned_message`;
DROP TABLE IF EXISTS `livechat_block`;
DROP TABLE IF EXISTS `task`;
DROP TABLE IF EXISTS `module`;
DROP TABLE IF EXISTS `message`;
DROP TABLE IF EXISTS `post`;
DROP TABLE IF EXISTS `privilege`;
DROP TABLE IF EXISTS `reply`;
DROP TABLE IF EXISTS `survey`;
DROP TABLE IF EXISTS `ticket`;
DROP TABLE IF EXISTS `field`;
DROP TABLE IF EXISTS `faq`;
DROP TABLE IF EXISTS `pop`;
DROP TABLE IF EXISTS `test`;
DROP TABLE IF EXISTS `dept`;
DROP TABLE IF EXISTS `options`;
DROP TABLE IF EXISTS `user`;
SET FOREIGN_KEY_CHECKS=1;

-- --------------------------------------------------------

-- Optional live-chat module tables (the module also creates these on first use).
CREATE TABLE IF NOT EXISTS `livechat_conversation` (
  `id` int unsigned NOT NULL auto_increment,
  `visitor_token` char(64) NOT NULL,
  `visitor_name` varchar(100) NOT NULL,
  `visitor_email` varchar(190) NOT NULL default '',
  `dept_id` int NOT NULL default '0',
  `ip_address` varchar(45) NOT NULL default '',
  `status` enum('open','closed') NOT NULL default 'open',
  `created_at` int unsigned NOT NULL,
  `updated_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `visitor_token` (`visitor_token`), KEY `status_updated` (`status`,`updated_at`), KEY `department` (`dept_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `livechat_message` (
  `id` int unsigned NOT NULL auto_increment,
  `conversation_id` int unsigned NOT NULL,
  `sender` enum('visitor','operator') NOT NULL,
  `sender_id` int NOT NULL default '0',
  `body` text NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`), KEY `conversation_messages` (`conversation_id`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `livechat_canned_message` (
  `id` int unsigned NOT NULL auto_increment,
  `title` varchar(120) NOT NULL,
  `body` text NOT NULL,
  `language` varchar(40) NOT NULL default 'English',
  `operator_id` int NOT NULL default '0',
  `created_at` int unsigned NOT NULL,
  `updated_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`), KEY `language_operator` (`language`,`operator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `livechat_block` (
  `id` int unsigned NOT NULL auto_increment,
  `conversation_id` int unsigned NOT NULL default '0',
  `visitor_token` char(64) NOT NULL default '',
  `visitor_email` varchar(190) NOT NULL default '',
  `ip_address` varchar(45) NOT NULL default '',
  `blocked_by` int NOT NULL default '0',
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`), KEY `visitor_token` (`visitor_token`), KEY `visitor_email` (`visitor_email`), KEY `ip_address` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `module` (
  `id` int unsigned NOT NULL auto_increment, `module_dir` varchar(80) NOT NULL, `module_name` varchar(150) NOT NULL,
  `version` varchar(30) NOT NULL default '', `author` varchar(150) NOT NULL default '', `description` text,
  `installed` tinyint(1) NOT NULL default '0', `enabled` tinyint(1) NOT NULL default '0', `updated_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `module_dir` (`module_dir`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `task` (
  `id` int unsigned NOT NULL auto_increment, `title` varchar(255) NOT NULL, `description` text,
  `priority` enum('low','normal','high') NOT NULL default 'normal', `due_date` date default NULL,
  `assigned_to` int NOT NULL default '0', `is_complete` tinyint(1) NOT NULL default '0',
  `created_by` int NOT NULL default '0', `completed_by` int NOT NULL default '0',
  `created_at` int unsigned NOT NULL, `updated_at` int unsigned NOT NULL, `completed_at` int unsigned NOT NULL default '0',
  PRIMARY KEY (`id`), KEY `assigned_to` (`assigned_to`), KEY `due_date` (`due_date`), KEY `is_complete` (`is_complete`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `dept`
--

CREATE TABLE IF NOT EXISTS `dept` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `options` int(11) NOT NULL default '0',
  `sortnum` int(11) NOT NULL default '0',
  `description` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `dept`
--

INSERT INTO `dept` (`id`, `name`, `options`, `sortnum`, `description`) VALUES
(0, 'Global (All Departments)', 0, 0, ''),
(2, 'support', 0, 1, ''),
(3, '', 0, 2, '');

-- --------------------------------------------------------

--
-- Table structure for table `faq`
--

CREATE TABLE IF NOT EXISTS `faq` (
  `id` int(11) NOT NULL auto_increment,
  `kb_number` varchar(16) NOT NULL default '',
  `description` text NOT NULL,
  `symptoms` text NOT NULL,
  `solution` text NOT NULL,
  `category` int(11) NOT NULL default '0',
  `parent` int(11) NOT NULL default '0',
  `date` int(11) NOT NULL default '0',
  `publish_date` date default NULL,
  `expiry_date` date default NULL,
  PRIMARY KEY  (`id`),
  KEY `kb_number` (`kb_number`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `faq`
--

INSERT INTO `faq` (`id`, `description`, `symptoms`, `solution`, `category`, `parent`, `date`) VALUES
(1, 'test', 'test', '', -1, 0, 1315160068),
(2, 'test', 'test', 'test', 0, -1, 1315160136);

-- --------------------------------------------------------

--
-- Table structure for table `field`
--

CREATE TABLE IF NOT EXISTS `field` (
  `id` int(11) NOT NULL auto_increment,
  `dept_id` int(11) NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `required` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `message`
--

CREATE TABLE IF NOT EXISTS `message` (
  `id` int(11) NOT NULL auto_increment,
  `ticket_id` int(11) NOT NULL default '0',
  `user_id` int(11) NOT NULL default '0',
  `viewed` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `options`
--

CREATE TABLE IF NOT EXISTS `options` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  `num` int(11) NOT NULL default '0',
  `text` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=40 ;

--
-- Dumping data for table `options`
--

INSERT INTO `options` (`id`, `name`, `num`, `text`) VALUES
(1, 'background', 0, '#FFFFFF'),
(2, 'outsidebackground', 0, '#94BECE'),
(3, 'border', 0, '#182C5A'),
(4, 'topbar', 0, '#99CCFF'),
(5, 'menu', 0, '#DDDDDD'),
(6, 'styles', 0, 'a\r\n{ \r\n  color: navy;  \r\n  text-decoration: underline;\r\n}\r\na:visited\r\n{ \r\n  color: navy;  \r\n  text-decoration: underline;\r\n}\r\na:active\r\n{\r\n  color: navy;  \r\n  text-decoration: underline;\r\n}\r\na:hover\r\n{\r\n  color: navy;  \r\n  text-decoration: underline;\r\n}\r\n.normal\r\n{\r\n  font: 9pt Verdana, Arial, Helvetica;\r\n}\r\n.title\r\n{\r\n  font: bold 14pt Arial, Helvetica, Verdana;\r\n  color: #182C5A;\r\n}'),
(7, 'tags', 0, 'on'),
(8, 'uploads', 0, 'on'),
(9, 'autosurvey', 0, '0'),
(10, 'repeatsurvey', 0, '0'),
(11, 'survey1', 1, 'Overall Support Rating'),
(12, 'email_ticket_created', 0, '{$data[emailheader]}$name,\n\nYour support ticket has been created and successfully dispatched to the $department\ndepartment.  Here is the information you will need to access your ticket:\n\nTicket ID: $ticket\nEmail: $email\nLink to view ticket: {$GLOBALS[PATH_TO_HELPDESK]}{$GLOBALS[HD_URL_TICKET_VIEW]}?cmd=view&id={$ticket}&email={$email}\n\n$autoreply{$data[title]}\n{$data[url]}{$data[emailfooter]}'),
(13, 'email_ticket_created_subject', 0, '$ticket - New Ticket Created'),
(14, 'email_ticket_notify', 0, '{$data[emailheader]}$name,\n\nYour ticket concerning ''$subject'' has been responded to.  You can view\nthis response (and reply to it if necessary) using the information or link below.\n\nTicket ID: $ticket\nEmail: $email\nLink to ticket: {$GLOBALS[PATH_TO_HELPDESK]}{$GLOBALS[HD_URL_TICKET_VIEW]}?cmd=view&id={$ticket}&email={$email}\n\nHere is the response made to your ticket.  Please do not reply to this email directly, use the link above\nto reply.\n\n$message\n\n{$data[title]}\n{$data[url]}{$data[emailfooter]}'),
(15, 'email_ticket_notify_subject', 0, '$ticket - Reply To Your Ticket'),
(16, 'email_ticket_survey', 0, '{$data[emailheader]}$name,\n\nYour ticket concerning ''$subject'' has been closed.  Please take a moment to complete\na short survey that will help us to serve you better in the future.\n\nLink to survey: {$GLOBALS[PATH_TO_HELPDESK]}{$GLOBALS[HD_URL_TICKET_SURVEY]}?id={$ticket}&email={$email}\n\nWe appreciate your time!\n\n{$data[title]}\n{$data[url]}{$data[emailfooter]}'),
(17, 'email_ticket_survey_subject', 0, '$ticket - Please Survey Our Support'),
(18, 'email_notify_reply', 0, '{$data[title]}\n------------------------------\n\nOne of the tickets in which you have posted has been replied to by the customer.\n\nHere is the ticket information:\n\nTicket ID: {$ticket}\nLink to ticket: {$GLOBALS[PATH_TO_HELPDESK]}{$GLOBALS[HD_URL_ADMINVIEW]}?id={$ticket}\n\nReply contents below:\n\n{$message}'),
(19, 'email_notify_reply_subject', 0, 'Help Desk Notification - New Ticket Reply: $ticket'),
(20, 'email_notify_create', 0, '{$data[title]}\n------------------------------\n\nA new ticket has been created in one of the departments you have been assigned to.\n\nHere is the ticket information:\n\nTicket ID: {$ticket}\nLink to ticket: {$GLOBALS[PATH_TO_HELPDESK]}{$GLOBALS[HD_URL_ADMINVIEW]}?id={$ticket}\n\nTicket contents below:\n\n{$message}'),
(21, 'email_notify_create_subject', 0, 'Help Desk Notification - New Ticket Created: $ticket'),
(22, 'email_notifysms_reply', 0, '{$data[title]}\n------------------------------\n\nOne of the tickets in which you have posted has been replied to by the customer.\n\nHere is the ticket information:\n\nTicket ID: {$ticket}\nLink to ticket: {$GLOBALS[PATH_TO_HELPDESK]}{$GLOBALS[HD_URL_ADMINVIEW]}?id={$ticket}\n\nReply contents below:\n\n{$message}'),
(23, 'email_notifysms_reply_subject', 0, 'Help Desk Notification - New Ticket Reply: $ticket'),
(24, 'email_notifysms_create', 0, '{$data[title]}\n------------------------------\n\nA new ticket has been created in one of the departments you have been assigned to.\n\nHere is the ticket information:\n\nTicket ID: {$ticket}\nLink to ticket: {$GLOBALS[PATH_TO_HELPDESK]}{$GLOBALS[HD_URL_ADMINVIEW]}?id={$ticket}\n\nTicket contents below:\n\n{$message}'),
(25, 'email_notifysms_create_subject', 0, 'Help Desk Notification - New Ticket Created: $ticket'),
(26, 'email_ticket_lookup', 0, '{$data[title]}\n------------------------------\n\nHere are your all of your tickets for the help desk.  The most recent tickets are shown first:\n\n'),
(27, 'email_ticket_lookup_subject', 0, 'Help Desk Notification - Ticket Lookup'),
(28, 'email_ticket_flagged', 0, '{$data[title]}\n------------------------------\n\nA ticket has been flagged to you (and possibly other staff):\n\nTicket ID: {$ticket}\nLink to ticket: {$GLOBALS[PATH_TO_HELPDESK]}{$GLOBALS[HD_URL_ADMINVIEW]}?id={$ticket}\n\n'),
(29, 'email_ticket_flagged_subject', 0, 'Help Desk Notification - Flagged Ticket'),
(30, 'helpdeskurl', 0, 'http://demo.lynxhd.com/'),
(31, 'url', 0, 'http://www.lynxhd.com/'),
(32, 'title', 0, 'LynxHD Demo'),
(33, 'email', 0, 'admin@lynxhd.com'),
(34, 'autoclose', 0, ''),
(35, 'autodelete', 0, ''),
(36, 'banned_emails', 0, ''),
(37, 'banned_ips', 0, ''),
(38, 'floodcontrol', 0, ''),
(39, 'cc', 0, '');

-- --------------------------------------------------------

--
-- Table structure for table `pop`
--

CREATE TABLE IF NOT EXISTS `pop` (
  `id` int(11) NOT NULL auto_increment,
  `dept_id` int(11) NOT NULL default '0',
  `server` varchar(255) NOT NULL default '',
  `port` int(11) NOT NULL default '110',
  `username` varchar(255) NOT NULL default '',
  `password` varchar(255) NOT NULL default '',
  `email` varchar(255) NOT NULL default '',
  `del` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Table structure for table `post`
--

CREATE TABLE IF NOT EXISTS `post` (
  `id` int(11) NOT NULL auto_increment,
  `ticket_id` int(11) NOT NULL default '0',
  `user_id` int(11) NOT NULL default '0',
  `date` int(11) NOT NULL default '0',
  `subject` varchar(255) NOT NULL default '',
  `message` text NOT NULL,
  `ip` varchar(20) NOT NULL default '',
  `private` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=27 ;

--
-- Dumping data for table `post`
--

INSERT INTO `post` (`id`, `ticket_id`, `user_id`, `date`, `subject`, `message`, `ip`, `private`) VALUES
(1, 1, -1, 1311894907, 'testining ', 'dhddhddhdhd', '99.244.118.176', 0),
(2, 1, 1, 1311896127, 'adadasdas', 'adadasdas', '', 0),
(3, 1, -1, 1311896152, 'adasds', 'asdad', '99.244.118.176', 0),
(4, 1, 1, 1311896169, 'sfsdfsd', 'sdfsdfsdffsdf', '', 0),
(5, 2, -1, 1313957085, 'dsfsdafsed', 'sfsfasdfsdfsdf', '99.244.118.176', 0),
(7, 4, -1, 1314196713, 'bguuohou', ';ugygluyfgkuty', '196.210.151.245', 0),
(8, 5, -1, 1314238224, 'test', 'asd', '203.217.176.231', 0),
(9, 6, -1, 1314330044, 'jhhuu', 'hhuuytgyg', '221.224.85.154', 0),
(10, 7, -1, 1314892154, 'vrgv', 'vrvrrv', '90.227.204.195', 0),
(11, 8, -1, 1314946075, 'test subject', 'hello world', '95.167.125.10', 0),
(12, 9, -1, 1315112503, 'printer', 'hp 4250 can''t print. error 49.00123', '212.198.148.15', 0),
(13, 10, -1, 1315160170, 'test', 'test', '99.244.118.176', 0),
(14, 11, -1, 1315380905, 'test', 'test', '213.184.138.218', 0),
(15, 12, -1, 1315499153, 'It is already red', 'I cannot highlight the row in excel because it is already red', '165.138.202.253', 0),
(16, 13, -1, 1316408953, 'PROVA', 'Ma non funziona!', '212.19.97.186', 0),
(17, 14, -1, 1316435565, 'dfdfd', 'dfdf', '66.202.119.114', 0),
(18, 15, -1, 1316669552, 'Test_888', 'Hello!', '82.209.198.93', 0),
(19, 16, -1, 1317119232, 'test test', 'test test', '74.198.87.22', 0),
(20, 17, -1, 1317290411, 'how to', 'message', '217.67.121.227', 0),
(21, 18, -1, 1317592732, 'Helpdesk test', 'I want to see how the system works', '68.72.238.238', 0),
(22, 19, -1, 1318002355, 'billing', 'will call company for billing info', '173.184.248.67', 0),
(23, 20, -1, 1318002459, 'billing', 'will call company for billing info', '173.184.248.67', 0),
(24, 21, -1, 1318227158, 'y', 'sdadsda', '95.26.105.201', 0),
(25, 22, -1, 1318786489, 'test', 'this is a test', '194.46.237.91', 0),
(26, 23, -1, 1318988561, 'fghdfh', 'dhgdh', '202.124.29.7', 0);

-- --------------------------------------------------------

--
-- Table structure for table `privilege`
--

CREATE TABLE IF NOT EXISTS `privilege` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL default '0',
  `dept_id` int(11) NOT NULL default '0',
  `admin` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `privilege`
--

INSERT INTO `privilege` (`id`, `user_id`, `dept_id`, `admin`) VALUES
(1, 1, 0, 1),
(2, 0, 2, 0),
(3, 0, 3, 0);

-- --------------------------------------------------------

--
-- Table structure for table `reply`
--

CREATE TABLE IF NOT EXISTS `reply` (
  `id` int(11) NOT NULL auto_increment,
  `dept_id` int(11) NOT NULL default '0',
  `reply` text NOT NULL,
  `phrase` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `survey`
--

CREATE TABLE IF NOT EXISTS `survey` (
  `id` int(11) NOT NULL auto_increment,
  `ticket_id` int(11) NOT NULL default '0',
  `rating1` tinyint(4) NOT NULL default '0',
  `rating2` tinyint(4) NOT NULL default '0',
  `rating3` tinyint(4) NOT NULL default '0',
  `rating4` tinyint(4) NOT NULL default '0',
  `rating5` tinyint(4) NOT NULL default '0',
  `rating6` tinyint(4) NOT NULL default '0',
  `rating7` tinyint(4) NOT NULL default '0',
  `rating8` tinyint(4) NOT NULL default '0',
  `rating9` tinyint(4) NOT NULL default '0',
  `rating10` tinyint(4) NOT NULL default '0',
  `comments` text NOT NULL,
  `date` int(11) NOT NULL default '0',
  `email` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `test`
--

CREATE TABLE IF NOT EXISTS `test` (
  `id` int(4) NOT NULL auto_increment,
  `rowName` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `ticket`
--

CREATE TABLE IF NOT EXISTS `ticket` (
  `id` int(11) NOT NULL auto_increment,
  `ticket_id` varchar(40) NOT NULL default '',
  `dept_id` int(11) NOT NULL default '0',
  `email` varchar(255) NOT NULL default '',
  `name` varchar(20) NOT NULL default '',
  `subject` varchar(255) NOT NULL default '',
  `date` int(11) NOT NULL default '0',
  `status` int(11) NOT NULL default '0',
  `notify` tinyint(4) NOT NULL default '1',
  `priority` tinyint(4) NOT NULL default '0',
  `custom` text NOT NULL,
  `lastactivity` int(11) NOT NULL default '0',
  `lastpost` int(11) NOT NULL default '-1',
  `flag` int(11) NOT NULL default '-1',
  `private` int(4) NOT NULL default '0',
  `cc` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=24 ;

--
-- Dumping data for table `ticket`
--

INSERT INTO `ticket` (`id`, `ticket_id`, `dept_id`, `email`, `name`, `subject`, `date`, `status`, `notify`, `priority`, `custom`, `lastactivity`, `lastpost`, `flag`, `private`, `cc`) VALUES
(1, '4E31ED7B', 0, 'matin47@gmail.com', 'test', 'testining ', 1311894907, 1, 1, 0, '', 1311896169, 1, -1, 0, ''),
(2, '4E5164DD', 2, 'test@lynxhd.com', 'test', 'dsfsdafsed', 1313957085, 0, 1, 0, '', 1313957085, -1, -1, 0, ''),
(4, '4E550CE9', 2, 'vmgghmv@gjhvghjv.net', 'bnvmnv', 'bguuohou', 1314196713, 0, 1, 0, '', 1314196713, -1, -1, 0, ''),
(5, '4E55AF10', 0, 'jalut78@gmail.com', 'Jamalulkhair', 'test', 1314238224, 0, 1, 0, '', 1314238224, -1, -1, 0, ''),
(6, '4E5715BC', 2, 'dfe@dfe.com', 'fef', 'jhhuu', 1314330044, 1, 1, 0, '', 1314330044, -1, -1, 0, ''),
(7, '4E5FA97A', 0, 'tehae@cecf.se', 'gbde', 'vrgv', 1314892154, 0, 1, 0, '', 1314892154, -1, -1, 0, ''),
(8, '4E607C1B', 2, 'ra@ma.ru', 'ramis', 'test subject', 1314946075, 0, 1, 1, '', 1314946075, -1, -1, 0, ''),
(9, '4E630637', 2, 'testor@test.com', 'testor', 'printer', 1315112503, 0, 0, 2, '', 1315112503, -1, -1, 0, ''),
(10, '4E63C06A', 2, 'sfagsa@gahg.com', 'sfsdafsagsg', 'test', 1315160170, 0, 1, 0, '', 1315160170, -1, -1, 0, ''),
(11, '4E671EA9', 2, 'test@rte.com', 'test', 'test', 1315380905, 0, 1, 0, '', 1315380905, -1, -1, 0, ''),
(12, '4E68EC91', 2, 'bob@bobs.org', 'bob', 'It is already red', 1315499153, 1, 1, 1, '', 1315499949, -1, -1, 0, ''),
(13, '4E76CE79', 0, 'Ciro@Gmain.com', 'Ciro', 'PROVA', 1316408953, 0, 1, 0, '', 1316408953, -1, -1, 0, ''),
(14, '4E77366D', 2, 'fgfg@vtcars.com', 'fgfg', 'dfdfd', 1316435565, 0, 1, 0, '', 1316435565, -1, -1, 0, ''),
(15, '4E7AC870', 2, 'sergeus@gmail.com', 'Peter', 'Test_888', 1316669552, 0, 1, 1, '', 1316669552, -1, -1, 0, ''),
(16, '4E81A500', 2, 'admin@lynxhd.com', 'admin', 'test test', 1317119232, 0, 1, 0, '', 1317119232, -1, -1, 0, ''),
(17, '4E8441AB', 2, 'user@gmail.com', 'user', 'how to', 1317290411, 0, 1, 0, '', 1317290411, -1, -1, 0, ''),
(18, '4E88DE9C', 0, 'joeblow@blowhard.com', 'Joseph Blowhard', 'Helpdesk test', 1317592732, 0, 0, 1, '', 1317592732, -1, -1, 0, ''),
(19, '4E8F1EB3', 3, 'hugh@ne.com', 'joyce red', 'billing', 1318002355, 1, 1, 0, '', 1318002394, -1, -1, 0, ''),
(20, '4E8F1F1B', 3, 'hugh@ne.com', 'joyce red', 'billing', 1318002459, 0, 1, 0, '', 1318002459, -1, -1, 0, ''),
(21, '4E928CD6', 0, 'bealter.org@gmail.com', 'niknik', 'y', 1318227158, 0, 1, 1, '', 1318227194, -1, -1, 0, ''),
(22, '4E9B15B9', 2, 'no@spam.com', 'blah', 'test', 1318786489, 0, 0, 2, '', 1318786489, -1, -1, 0, ''),
(23, '4E9E2B11', 2, 'sgsdfg@fdgd.hj', 'dfgs', 'fghdfh', 1318988561, 0, 1, 0, '', 1318988561, -1, -1, 0, '');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(70) NOT NULL default '',
  `email` varchar(255) NOT NULL default '',
  `sms` varchar(255) NOT NULL default '',
  `signature` text NOT NULL,
  `password` varchar(20) NOT NULL default '',
  `admin` tinyint(4) NOT NULL default '0',
  `date` int(11) NOT NULL default '0',
  `lastlogin` int(11) NOT NULL default '0',
  `notify` int(11) NOT NULL default '0',
  `pwkey` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `name`, `email`, `sms`, `signature`, `password`, `admin`, `date`, `lastlogin`, `notify`, `pwkey`) VALUES
(1, 'admin', 'admin@lynxhd.com', '', '', 'IV0AkDwl89HqA', 1, 1311456691, 1319054589, 0, ''),
(2, 'dfgdsgdfgdf', 'afdsdsh@dahd.com', '', '', 'IVw1jbE5OQci.', 0, 1313957253, 0, 0, ''),
(3, 'werwqre', 'werwer@werwewer.com', '', '', 'IVeUGI.1GY8CQ', 0, 1316321489, 0, 0, '');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
