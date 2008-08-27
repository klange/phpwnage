<?php
/*
	This file is part of PHPwnage (Fresh Installation Script)

	Copyright 2008 Kevin Lange <klange@oasis-games.com>

	PHPwnage is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	PHPwnage is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with PHPwnage. If not, see <http://www.gnu.org/licenses/>.

*/

// XXX: The retheming of this installer was a spur of the moment thing
//	and isn't quite up to scratch with what I want. The installer
//	works and should be able to utilize any theme (Though who
//	cares enough to switch it?), and that's good enough for now.

if(file_exists("installer.lock")) die("There is a lock on the installer. If you wish to do a reinstall, please delete the file 'installer.lock' and run this script again."); 

print <<<END
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
<head>
<title>PHPwnage Installer V. 4.0</title>
<link rel="stylesheet" type="text/css" href="crystal.css" />
<style type="text/css">
.installer_table {
    border: 1px #000000 solid;
    border-collapse: collapse;
}
.installer_table td {
    border: 1px #000000 solid;
    border-collapse: collapse;
    padding: 4px;
}
</style>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
</head>
<body background="colors/crystal.gif">
<table class="borderless_table" width="100%">
END;

$_PWNVERSION = "1.8";

// Our replacement to file_put_contents so that PHPwnage works with PHP 4.
function file_put_contents_debug($file_name, $content) {
$ourFileHandle = fopen($file_name, 'w') or die("Error creating $file_name! You must create the file manually!");
fwrite($ourFileHandle,$content);
fclose($ourFileHandle);
}

// Return the URL for the current running directory.
function getURL() {
    return "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
}

function DrawBlock($name,$right,$content)
{
$output = <<<END
      <tr>
        <td width="100%">
    <table class="borderless_table" width="100%">
      <tr>
        <td class="pan_ul">&nbsp;</td>
        <td class="pan_um">
        <font class="pan_title_text">
END;
$output = $output . $name;
$output = $output . <<<END
	</font></td>
        <td class="pan_um" align="right"><font class="pan_title_text">
END;
$output = $output . $right;
$output = $output . <<<END
	</font></td>
        <td class="pan_ur">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_ml">&nbsp;</td>
        <td class="pan_body" valign="top" colspan="2">
END;
$output = $output . $content;
$output = $output . <<<END
	</td>
        <td class="pan_mr">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_bl"></td>
        <td class="pan_bm" colspan="2"></td>
        <td class="pan_br"></td>
      </tr>
    </table>
        </td>
      </tr>
END;
print $output;
}

function FileFault($file)
{
print "Could not write file. Printing instead...<br /><br />\n\n";
print "<!-- BEGIN CONFIG.PHP\n";
print $file;
print "\nEND CONFIG.PHP-->\n\n";
print "View the source for this page and save everything between BEGIN CONFIG.PHP and END CONFIG.PHP to a file named 'config.php' and place it in your PHPwnage root directory.\n";
print "When you have uploaded the file to your webserver, continue to the next page by clicking <a href=\"fresh_install.php?do=page3\">here</a>";
die ("\n<br />Breaking installer...");
}

if ($_GET['do'] == '') {
$print_what = <<<END
<table style="border-collapse: collapse" width="100%">
  <tr>
    <td width="100%" valign="top" align="left">
    <p align="center">PHPwnage Version $_PWNVERSION - Installer - 
    Welcome!</p></td>
  </tr>
  <tr>
    <td width="100%" valign="top" align="left">
    Welcome to PHPwnage, the all-in-one news site and 
    forum! This installer will guide you through the process of setting up your 
    new site with PHPwnage! If you have not already done so, please gather the 
    following information about your site:<ul>
      <li>Your SQL server (if you have a CPanel, check 
      under &quot;Manage SQL&quot;)</li>
      <li>Your SQL user name</li>
      <li>Your SQL password</li>
    </ul>
    <p>You may also wish to set up a blank SQL database 
    now. We can not guarantee that the installer will be able to make one for 
    you as creation permissions are often limited by web hosts. Also, you will 
    not be able to undo what you do here until after you have finished. Please 
    keep this in mind.</p>
    <p align="center">&gt; <a href="fresh_install.php?do=page2">
    Continue to the Next Step</a> &lt;</p></td>
  </tr>
  </table>
END;
DrawBlock("Welcome to the PHPwnage Installer!","V. $_PWNVERSION",$print_what);
}

if ($_GET['do'] == 'page2'){
$print_what = <<<END
<form action="fresh_install.php?do=submit" method="post"><input type="hidden" name="do" value="set_config" />
<table width="100%" class="installer_table">
  <tr>
    <td width="100%" valign="top" align="left" colspan="2">
    <p align="center">PHPwnage Version $_PWNVERSION - Installer - Setting Up Your 
    Configuration</p></td>
  </tr>
  <tr>
    <td width="48%" valign="top" align="left">
SQL Database Server Location<br />
    <font size="2">The URL to your SQL server. Ex: localhost OR sql1.phpnet.us</font></td>
    <td width="52%" valign="top" align="right">
  <input type="text" name="sql_server" style="width: 100%" /></td>
  </tr>
  <tr>
    <td width="48%" valign="top" align="left">
SQL User Name <br />
    <font size="2">The user name you use to access your SQL server.</font></td>
    <td width="52%" valign="top" align="right">
  <input type="text" name="sql_user" style="width: 100%" /></td>
  </tr>
  <tr>
    <td width="48%" valign="top" align="left">
    SQL  Password<br />
    <font size="2">The password you use to access your SQL server. cAsE sEnSiTiVe</font></td>
    <td width="52%" valign="top" align="right">
  <input type="text" name="sql_password" style="width: 100%" /></td>
  </tr>
  <tr>
    <td width="48%" valign="top" align="left">
    SQL Database  Name<br />
    <font size="2">The name of the database in which PHPwnage will install. </font></td>
    <td width="52%" valign="top" align="right">
  <input type="text" name="sql_database" style="width: 100%" /></td>
  </tr>
  <tr>
    <td width="48%" valign="top" align="left">
    Administrator Email <br />
    <font size="2">The email address you would like to display if an error is 
    encountered.</font></td>
    <td width="52%" valign="top" align="right">
  <input type="text" name="admin_email" style="width: 100%" /></td>
  </tr>
  <tr>
    <td width="48%" valign="top" align="left">Table Prefix <br />
    <font size="2">ie, &quot;pwn_&quot;</font></td>
    <td width="52%" valign="top" align="right"><input type="text" name="prefix" style="width: 100%" /></td>
  </tr>
  <tr>
    <td width="100%" valign="top" align="center" colspan="2">
    &lt;  
    <input type="submit" value="Continue to the Next Step" name="enter" /> &gt;</td>
  </tr>
  </table>
</form>

END;
DrawBlock("Setting up the Configuration File","V. $_PWNVERSION",$print_what);
}


if ($_POST['do'] == 'set_config'){
// Write data.
print "Writing your configuration file...\n";
$SQL_SERVER = $_POST['sql_server'];
$SQL_USER = $_POST['sql_user'];
$SQL_PASSWORD = $_POST['sql_password'];
$SQL_DATABASE = $_POST['sql_database'];
$ADMIN_EMAIL = $_POST['admin_email'];
$PREFIX = $_POST['prefix'];

$data = "<?php
// PHPwnage Automatically Generated Configuration Page
// This page was automatically generated by fresh_install.php
// and its contents are controlled by the license under which
// fresh_install.php is administered (the GNU General Public
// License, version 3)\n";
$data = $data . "\$conf_server = \"$SQL_SERVER\";
\$conf_user = \"$SQL_USER\";
\$conf_password = \"$SQL_PASSWORD\";
\$conf_database = \"$SQL_DATABASE\";
\$conf_email = \"$ADMIN_EMAIL\";
\$_TRACKER = \"\"; // Add your analytics tracking here
\$_PREFIX = \"$PREFIX\";";
$data = $data . <<<END
// DO NOT EDIT ANYTHING BELOW THIS LINE
// ------------------------------------------------------------------------------------------------------------
   \$mtime = microtime();
   \$mtime = explode(" ",\$mtime);
   \$mtime = \$mtime[1] + \$mtime[0];
   \$starttime = \$mtime; 
   // Meh, calculate the generation time...
\$db_fail = false;
\$db = mysql_connect(\$conf_server,\$conf_user,\$conf_password) or 
die ("<font face=\"Tahoma\">We've experienced an internal error. Please contact " . \$conf_email . ".<br />\nError Code 001: Failed to connect to SQL server.</font>"); 
mysql_select_db(\$conf_database, \$db) or \$db_fail = true; 

putenv("TZ=America/New_York"); // Set the time zone to EST
// IP ban detection
\$banlist = mysql_query("SELECT * FROM banlist");
while (\$ban = mysql_fetch_array(\$banlist)) {
if (\$_SERVER['REMOTE_ADDR'] == \$ban['ip'])	{
die ("<font face=\"Tahoma\">You do not have permission to access this site.</font>");
}
}

?>
END;
// <?
file_put_contents_debug("config.php",$data);
// FileFault($date); // XXX: Create_File_Failed, replace the above with this line.
print "<br />Success! Moving to next page...";
print "\n<meta http-equiv=\"Refresh\" content=\"1;url=fresh_install.php?do=page3\">";
}

if ($_GET['do'] == 'page3')
{
$this_dir = getURL() . "/"; // Current running directory
$print_what = <<<END
<form action="fresh_install.php?do=submit" method="post"><input type="hidden" name="do" value="install" />
<table width="100%" class="installer_table">
  <tr>
    <td width="100%" valign="top" align="left" colspan="2">
    <p align="center">PHPwnage Version $_PWNVERSION - Installer - Setting Up Your Site 
    Information</p></td>
  </tr>
  <tr>
    <td width="50%" valign="top" align="left">
    Forum Title<br />
    <font size="2">A short description for your site. Ex: Oasis-Games.com</font></td>
    <td width="50%" valign="top" align="right">
  <input type="text" name="site_name" style="width: 100%" /></td>
  </tr>
  <tr>
    <td width="50%" valign="top" align="left">
 
    Copyright Line<br />
    <font size="2">A message displayed in the footer. Ex: (C) 2008 Oasis-Games</font></td>
    <td width="50%" valign="top" align="right">
 
  <input type="text" name="site_copyright" style="width: 100%" /></td>
  </tr>
  <tr>
    <td width="50%" valign="top" align="left">
    Installation URL <br />
    <font size="2">The URL for your site (including the /) Ex: http://oasis-games.com/home/</font></td>
    <td width="50%" valign="top" align="right">
 
  <input type="text" value="$this_dir" name="site_url" style="width: 100%" /></td>
  </tr>
  <tr>
    <td width="50%" valign="top" align="left">
    Site Description<br />
    <font size="2">A short piece of text to display in the right of the &quot;sub 
    header&quot;</font></td>
    <td width="50%" valign="top" align="right">
 
  <input type="text" name="site_description" style="width: 100%" /></td>
  </tr>
  <tr>
    <td width="50%" valign="top" align="left">
    Administrator User Name <br />
    <font size="2">The name you would like to use to log in to the admin panel. 
    Ex: Admin</font></td>
    <td width="50%" valign="top" align="right">
 
  <input type="text" name="site_admin_name" style="width: 100%" /></td>
  </tr>
  <tr>
    <td width="50%" valign="top" align="left">
    Administrator Password <br />
    <font size="2">The password you would like to use to log in to the admin 
    panel. cAsE sEnSiTiVe</font></td>
    <td width="50%" valign="top" align="right">
  <input type="password" name="site_admin_pass" style="width: 100%" /></td>
  </tr>
  <tr>
    <td width="100%" valign="top" align="left" colspan="2">
    <p align="center">
    &lt;  
    <input type="submit" value="Continue to the Next Step" name="enter" /> &gt;</p></td>
  </tr>
  </table>
</form>

END;
DrawBlock("Setting up the Site Information","V. $_PWNVERSION",$print_what);
}


if ($_POST['do'] == 'install')
{
require 'config.php';
if ($db_fail) {
// It appears that the database doesn't exist. We will try to make it.
mysql_query("CREATE DATABASE `$conf_database` ;") or print "<font color=#FF0000>Failed to create database, you will have to add it manually and return to this point in the installer.</font>";
mysql_select_db($conf_database, $db);
}
/*
    PHPwnage MySQL Database Table Generation
    This section of the installer is *crucial*, it creates all
    of the tables for your MySQL database. If you are upgrading
    and an upgrade tool is not available for your version, look
    here for more information on what to do with your tables.
*/
$query = <<<END
CREATE TABLE  `{$_PREFIX}banlist` (
  `ip` varchar(50) collate latin1_general_ci NOT NULL default ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
END;
mysql_query($query);
$query = <<<END
CREATE TABLE  `{$_PREFIX}blocks` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(40) collate latin1_general_ci default NULL,
  `content` text collate latin1_general_ci,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
END;
mysql_query($query);
$query = <<<END
CREATE TABLE  `{$_PREFIX}boards` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(100) collate latin1_general_ci NOT NULL default '',
  `desc` text collate latin1_general_ci NOT NULL,
  `orderid` int(11) NOT NULL default '0',
  `catid` int(11) NOT NULL default '0',
  `vis_level` int(11) NOT NULL default '0',
  `top_level` int(11) NOT NULL default '1',
  `post_level` int(11) NOT NULL default '1',
  `link` varchar(200) collate latin1_general_ci default 'NONE',
  PRIMARY KEY `id` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
END;
mysql_query($query);
$query = <<<END
CREATE TABLE  `{$_PREFIX}calendar` (
  `id` int(11) NOT NULL auto_increment,
  `day` varchar(10) collate latin1_general_ci NOT NULL default '',
  `title` varchar(100) collate latin1_general_ci NOT NULL default '',
  `content` text collate latin1_general_ci NOT NULL,
  `user` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
END;
mysql_query($query);
$query = <<<END
CREATE TABLE  `{$_PREFIX}categories` (
  `id` int(11) NOT NULL auto_increment,
  `orderid` int(11) NOT NULL default '0',
  `name` varchar(200) collate latin1_general_ci NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
END;
mysql_query($query);
$query = <<<END
CREATE TABLE  `{$_PREFIX}info` (
  `id` int(11) NOT NULL default '1',
  `name` varchar(40) collate latin1_general_ci default NULL,
  `copyright` varchar(40) collate latin1_general_ci default NULL,
  `right_data` varchar(200) collate latin1_general_ci default NULL,
  `last_updated` varchar(100) collate latin1_general_ci default NULL,
  `url` varchar(120) collate latin1_general_ci NOT NULL default '',
  `pheader` varchar(200) collate latin1_general_ci default NULL,
  `admin_rank` int(11) NOT NULL default '3',
  `mod_rank` int(11) NOT NULL default '2',
  `security_mode` int(11) NOT NULL default '0',
  `recap_pub` varchar(200) collate latin1_general_ci default NULL,
  `recap_priv` varchar(200) collate latin1_general_ci default NULL,
  PRIMARY KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
END;
mysql_query($query);
$query = <<<END
CREATE TABLE  `{$_PREFIX}news` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(200) collate latin1_general_ci default NULL,
  `content` text collate latin1_general_ci,
  `time_code` varchar(100) collate latin1_general_ci default NULL,
  `user` varchar(50) collate latin1_general_ci default NULL,
  `topicid` int(11) default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
END;
mysql_query($query);
$query = <<<END
CREATE TABLE  `{$_PREFIX}pages` (
  `name` varchar(20) collate latin1_general_ci NOT NULL default '',
  `display_name` varchar(100) collate latin1_general_ci NOT NULL default '',
  `content` text collate latin1_general_ci NOT NULL,
  `showsidebar` varchar(10) collate latin1_general_ci NOT NULL default 'false',
  `author` varchar(60) collate latin1_general_ci default NULL,
  `pw` varchar(50) collate latin1_general_ci NOT NULL default '',
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
END;
mysql_query($query);
$query = <<<END
CREATE TABLE  `{$_PREFIX}pms` (
  `id` int(11) NOT NULL auto_increment,
  `to` int(11) NOT NULL default '0',
  `from` int(11) NOT NULL default '0',
  `title` varchar(100) collate latin1_general_ci NOT NULL default '',
  `content` text collate latin1_general_ci NOT NULL,
  `read` int(11) NOT NULL default '0',
  `time` varchar(100) collate latin1_general_ci NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
END;
mysql_query($query);
$query = <<<END
CREATE TABLE  `{$_PREFIX}polls` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `title` varchar(100) NOT NULL default '',
  `op1_name` text,
  `op1_votes` text,
  `voters` text,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC AUTO_INCREMENT=1;
END;
mysql_query($query);
$query = <<<END
CREATE TABLE  `{$_PREFIX}posts` (
  `id` int(11) NOT NULL auto_increment,
  `topicid` int(11) NOT NULL default '0',
  `authorid` int(11) NOT NULL default '0',
  `content` text collate latin1_general_ci NOT NULL,
  `time` varchar(100) collate latin1_general_ci NOT NULL default '',
  `ip` varchar(50) collate latin1_general_ci NOT NULL default '',
  PRIMARY KEY `id` (`id`),
  FULLTEXT KEY `search` (`content`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
END;
mysql_query($query);
$query = <<<END
CREATE TABLE  `{$_PREFIX}ranks` (
  `id` int(11) NOT NULL auto_increment,
  `value` int(11) NOT NULL default '1',
  `name` varchar(40) NOT NULL,
  `posts` int(11) NOT NULL default '-1',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
END;
mysql_query($query);
$query = <<<END
CREATE TABLE  `{$_PREFIX}security` (
  `time` varchar(50) collate latin1_general_ci NOT NULL default '',
  `passused` varchar(50) collate latin1_general_ci NOT NULL default '',
  `where` varchar(50) collate latin1_general_ci NOT NULL default '',
  `ip` varchar(50) collate latin1_general_ci NOT NULL default ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
END;
mysql_query($query);
$query = <<<END
CREATE TABLE  `{$_PREFIX}topics` (
  `id` int(11) NOT NULL auto_increment,
  `authorid` int(11) NOT NULL default '0',
  `board` int(11) NOT NULL default '0',
  `title` varchar(100) collate latin1_general_ci NOT NULL default '',
  `lastpost` int(11) NOT NULL default '0',
  `readby` text collate latin1_general_ci NOT NULL,
  `stick` int(11) NOT NULL default '0',
  `locked` int(11) NOT NULL default '0',
  `has_poll` int(10) NOT NULL default '0',
  `poll_id` int(10) NOT NULL default '0',
  PRIMARY KEY `id` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
END;
mysql_query($query);
$query = <<<END
CREATE TABLE  `{$_PREFIX}users` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(50) collate latin1_general_ci NOT NULL default '',
  `email` varchar(50) collate latin1_general_ci NOT NULL default '',
  `password` varchar(40) collate latin1_general_ci NOT NULL default '',
  `sig` text collate latin1_general_ci NULL,
  `avatar` varchar(100) collate latin1_general_ci NOT NULL default '',
  `msn` varchar(40) collate latin1_general_ci NOT NULL default '',
  `yahoo` varchar(40) collate latin1_general_ci NOT NULL default '',
  `aim` varchar(40) collate latin1_general_ci NOT NULL default '',
  `icq` varchar(40) collate latin1_general_ci NOT NULL default '',
  `xfire` varchar(50) collate latin1_general_ci NOT NULL default '',
  `live` varchar(50) collate latin1_general_ci NOT NULL default '',
  `level` int(11) NOT NULL default '1',
  `sbonforum` int(11) NOT NULL default '1',
  `pand` varchar(50) collate latin1_general_ci NOT NULL default '',
  `color` varchar(50) collate latin1_general_ci NOT NULL default '',
  `theme` varchar(50) collate latin1_general_ci NOT NULL default '',
  PRIMARY KEY `id` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
END;
mysql_query($query);
$query = <<<END
CREATE TABLE  `{$_PREFIX}smileys` (
  `id` int(11) NOT NULL auto_increment,
  `code` varchar(20) NOT NULL,
  `image` varchar(100) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
END;
mysql_query($query);
$query = <<<END
INSERT INTO `{$_PREFIX}smileys` VALUES  
 (1,'[:)]','happy.png'),
 (2,'[:(]','sad.png'),
 (3,'[:P]','tongue.png'),
 (4,'[;)]','wink.png'),
 (5,'[XD]','deadlaugh.png'),
 (6,'[:|]','blank.png'),
 (7,'[:O]','ohmy.png'),
 (8,'[shades]','mc.png');
END;
mysql_query($query);
$query = <<<END
CREATE TABLE  `{$_PREFIX}sessions` (
  `id` int(11) NOT NULL default '0' COMMENT 'Session ID',
  `user` int(11) NOT NULL default '0' COMMENT 'userid',
  `last` int(11) NOT NULL default '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
END;
mysql_query($query);
$query = <<<END
CREATE TABLE `{$_PREFIX}galleries` (
    `id`    int(11)         NOT NULL    auto_increment,
    `name`  varchar(100)    NOT NULL    DEFAULT '',
    `desc`  text,
    `view`  int(11)         NOT NULL    DEFAULT 0,
    `upload` int(11)        NOT NULL    DEFAULT 1,
    `thumb` int(11)         NOT NULL    DEFAULT 0,
    PRIMARY KEY `id` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
END;
mysql_query($query);
$query = <<<END
CREATE TABLE `{$_PREFIX}images` (
    `id`    int(11)         NOT NULL    auto_increment,
    `name`  varchar(100)    NOT NULL    DEFAULT '',
    `desc`  text,
    `uid`   int(11)         NOT NULL    DEFAULT 1,
    `fname` varchar(200)    NOT NULL    DEFAULT '',
    `gid`   int(11)         NOT NULL    DEFAULT 0,
    `size`  int(11)         NOT NULL    DEFAULT 0,
    `type`  varchar(50)     NOT NULL    DEFAULT '',
    `publ`  int(11)         NOT NULL    DEFAULT 1,
    `data`  mediumblob      NULL,
    `thumb` mediumblob      NULL,
    PRIMARY KEY `id` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
END;
mysql_query($query);
print "Completed database setup. Moving on to data...<br />\n";
$info = "INSERT INTO `{$_PREFIX}info` VALUES (1, '" . $_POST['site_name'] . "', '" . $_POST['site_copyright'] . "', '" . $_POST['site_description'] . "', '" . time() . "', '" . $_POST['site_url'] . "', '', 3, 2, 0, NULL, NULL);";
$result = mysql_query($info);
print "Primary site information added! Moving on to generic set up...<br />\n";
print "Adding administrator forum user...<br />\n";
$adminname = $_POST['site_admin_name'];
$adminpass = md5($_POST['site_admin_pass']);
mysql_query("INSERT INTO `{$_PREFIX}users` (`name`, `password`, `level`, `email`)  VALUES ('$adminname', '$adminpass', 3, '$conf_email');");
print "Adding generic news item...<br />\n";
$time = time();
mysql_query("INSERT INTO `{$_PREFIX}news` VALUES (null, 'Welcome to PHPwnage!', 'Welcome to your new PHPwnage site! Thank you for choosing PHPwnage for your CMS needs. If you have any problems or questions, stop on over at [url=http://oasis-games.com/]our home page[/url]. We are ready to assist anyone who needs help with PHPwnage.', '$time', 'PHPwnage', 0);");
print "Adding navigation block...<br />\n";
mysql_query("INSERT INTO `{$_PREFIX}blocks` VALUES (null, 'Navigation', '<a href=\"index.php\">Home</a><br />\n<a href=\"admin.php\">Admin</a><br />\n<a href=\"mobile.php\">Mobile</a><br />\n<a href=\"rss.php\">RSS</a><br />\n<a href=\"forum.php\">Forum</a><br />\n<a href=\"calendar.php\">Calendar</a><br />\n<a href=\"modules.php?m=members\">Member List</a><br />\n<a href=\"gallery.php\">Image Gallery</a>');");
print "Completed! Moving to next page...\n";
print "\n<meta http-equiv=\"Refresh\" content=\"1;url=fresh_install.php?do=page4\">";
// Now that the core of the installation has completed, grab the $_POST data...
}
// XXX: This tutorial is *very* old and needs to be updated.
if ($_GET['do'] == 'page4') {
file_put_contents_debug("installer.lock","Installer is locked");
$print_what = <<<END
<table width="100%" class="installer_table">
  <tr>
    <td width="100%" valign="top" align="left" dir="ltr">
    <p align="center">PHPwnage Version $_PWNVERSION - Installer - Setting Up Your Site 
    Information</p></td>
  </tr>
  <tr>
    <td width="100%" valign="top" align="left" dir="ltr">
    Thank you for installing PHPwnage!<br />
    You can access your site now, or continue with this tutorial which will show 
    you how to work the <a href="admin.php">administration panel</a>.<br />
    To access your site <a href="index.php">Click Here</a><br />
    To read the tutorial, click the button.
    <div>
	<input value="Show Tutorial" onclick="if (this.parentNode.parentNode.getElementsByTagName('div')[1].getElementsByTagName('div')[0].style.display != '') { this.parentNode.parentNode.getElementsByTagName('div')[1].getElementsByTagName('div')[0].style.display = '';this.innerText = ''; this.value = 'Hide Tutorial'; } else { this.parentNode.parentNode.getElementsByTagName('div')[1].getElementsByTagName('div')[0].style.display = 'none'; this.innerText = ''; this.value = 'Show Tutorial'; }" type="button" /></div>
	<div class="alt2" style="border: 1px inset ; margin: 0px; padding: 6px;">
	<div style="display: none;">
    <p align="center" dir="ltr">
    <b>-- Welcome to the PHPwnage Administration Tutorial --</b><br />
    This tutorial will guide you through adding new news topics, creating 
    blocks, adding user pages, designing and installing modules, and using the 
    various site features, such as themes and background colors.<br />
    <b>Part 1<br />
    </b>The Admin Panel<br />
    The main place to administer your new site is the 'Admin Panel'. You must 
    login before accessing the admin panel. You can log in from the forum. 
    Afterwards, you 
    can access the Admin Panel <a href="admin.php">here</a>. From the Admin Panel, we will be able to 
    add blocks, news topics, forums, user pages, and even review a security log. 
    Moderators can also view the Admin Panel, but have limited capabilities to 
    prevent them from overpowering administrators.<br />
    As the root user, you can promote yourself in rank however you wish. No other
    users will have this ability.<br />
    <b>Part 2</b><br />
    News Topics<br />
    With news topics, your site can be anything from a blog to a respectable 
    world news headquarters. To add a new news article, find the &quot;News&quot; section 
    on the Admin Panel. You will be directed to a page where you can add a new 
    article and edit previous articles. When adding a new article, content 
    should be formatted in bbCode, but you can also use HTML. Be warned, however,
    as all line breaks will be replaced with &lt;br /&gt;, so if you have sensitve
    multi-line code, edit it to fit on a single line.<br />
    <b>Part 3</b><br />
    Custom Pages<br />
    Custom Pages, or also referred to as User Pages, are places where site 
    members can create new HTML pages within your website. They are edited by 
    the users using a password entered into the address bar after the page URL. 
    To add a new user page, fill in the appropriate information. The link name 
    refers to the name that is used to access the page (ie, pages.php?page=your_new_user_page) 
    The display name is a longer name displayed in the page's header. Default 
    content need not be filled in if you are planning on giving a page to a 
    member, however, if you wish to provided some generic content, you can use 
    HTML code in this small box. The next input is the Author's name. This piece 
    of information is displayed on the right side of the sub header when the 
    page is being viewed. Following this is &quot;Show Sidebar?&quot; This should be 
    either `true` or `false`. When set to true, the blocks on the left are 
    visible. When set to false, they are hidden. Moderators and administrators 
    have the ability to edit these pages and will be shown the HTML code for 
    them when they are logged in.<br />
    <b>Part 4</b><br />
    Site Information<br />
    Here you can change the information for your site, which you entered during 
    installation.<br />
    <b>Part 5</b><br />
    Security<br />
    Shown here is every failed attempt to access the administration panel, along 
    with the password that was tried and the IP address of the perpetrator. You 
    can clear this log by pressing &quot;Clear Security Log&quot;<br />
    You can also set the CAPTCHA mode used for registration on the forum.
    PHPwnage integrates ReCAPTCHA and we recommend that you use it.<br />
    Finally, you can apply site-wide IP bans from the security panel.<br />
    <b>Part 6</b><br />
    Blocks<br />
    Here you can add new blocks to the side bar. As with news articles, these 
    use HTML code. If you wish to edit a block, find it on the left and edit it 
    appropriately. Note that HTML can also be used in the title. You can also 
    move and delete existing blocks as well.<br />
    A listing of your currently used dynamic blocks is also shown. Dynamic blocks
    are stored in the &quot;blocks&quot; directory as PHP scripts. You can use
    the Calendar block as a starting point for making your own dynamic blocks.<br />
    <b>Part 7</b><br />
    Forums<br />
    PHPwnage's forum system is divided into categories, and categories are 
    divided into boards. From the forum section, you can add new categories, put 
    boards in them, edit existing boards and categories, and reorder the boards. 
    Note that if you wish to move a board to another category, you must also 
    reorder it after moving it. This is currently just a technical problem that 
    will be addressed in the future, just know that we are working on it. When 
    adding boards, you are asked for three values that are defaulted to 0, 1 and 
    1 respectively. These are the permission levels. The first is the ability to 
    see and read the board. This should be set to 0, which means 'guest'. Second 
    is the `new topic` level. Set this to the lowest level you want to be able 
    to post new topics. Last is the `new reply` level. Set this to be the lowest 
    you want new posts to be at. A news forum, for example, can be set to 0, 2, 
    1, allowing guests to read the news, moderators and administrators to post 
    new topics, and members to post comments.<br />
    <b>Part 8</b><br />
    The Forum<br />
    The Forum is a place where members can join your site for discussion on 
    relevant topics. New users can join from a link in the sub header of the 
    forum. New in PHPwnage 1.5 is the ability to see what topics have new posts. 
    The status indicator is a colored stack of papers for a forum, and a single 
    sheet for a topic. If the forum or topic has new posts, the indicator will 
    appear yellow. If not, it will appear grey. Clicking a status indicator in 
    the forum viewer will set all topics to &quot;read&quot;. Also new in 1.5 are private 
    messages. Each users is given a mailbox for messages that they can send to 
    other users.<br />
    PHPwnage 1.8 has added a significant number new features to the forum that
    are listed in the commit log on Launchpad.<br /> 
    When logged in as a moderator, you will be able to edit and delete 
    posts on the forum. Regular users are given the option of editing their own 
    posts as well. Guests have no permissions for security reasons. Update: New 
    in PHPwnage $_PWNVERSION are categories and extended bbCode. Categories allow you to 
    divide your boards by relevance. The new bbCode insertion system is also 
    present in $_PWNVERSION. Also new is the ability to hide boards to certain user 
    levels. This allows private boards to be added.<br />
    <b>Part 9</b><br />
    RSS and the Mobile Page<br />
    A key feature in PHPwnage is RSS. RSS, or &quot;Really Simple Syndication&quot;, 
    allows users to read news articles from other programs. The Mobile site has 
    been engineered specifically for use on mobile phones and the Sony 
    PlayStation Portable (hence its filename). Both of these items can 
    be accessed by links found at the bottom of the page.<br />
	<b>Part 10</b><br />
    Themes and Colors<br />
    Background colors have been in PHPwnage since version 1.3, but themes are a 
    new thing. With themes, you can define your own set up for the look of your 
    site. This installation comes with two themes: Aeolus and Crystal.<br />
    As of PHPwnage 1.8, theme settings are stored per-user in your profile.<br />    <br /><br />
    If you have any concerns with PHPwnage $_PWNVERSION, please contact
    <a href="mailto:klange@oasis-games.com">klange@oasis-games.com</a> for more information.<br />
    Thank you for choosing PHPwnage as your CMS. For more information, please 
    visit our official site at <a href="http://oasis-games.com/home">http://oasis-games.com/</a>
    and be sure to check out the <a href="https://launchpad.net/phpwnage">Launchpad project page</a>.
    </p>
	</div>
	</div>
    </td>
  </tr>
  </table>
END;
DrawBlock("Thank you for choosing PHPwnage!","V. $_PWNVERSION",$print_what);
}

print "</table></body></html>";
?>
