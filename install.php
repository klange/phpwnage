<?php
/*
	This file is part of PHPwnage (Fresh Installation Script)

	Copyright 2009 Kevin Lange <klange@oasis-games.com>

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

error_reporting(E_ERROR); // Don't print warnings, we handle them ourselves.

print <<<END
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
<head>
<title>PHPwnage Installer</title>
<link rel="stylesheet" type="text/css" href="themes/styles/crystal/theme.css" />
<link rel="icon" type="image/png" href="favicon.ico" />
<style type="text/css">
.installer_table {
    border: 0px #000000 none;
    border-collapse: collapse;
}
.installer_table td {
    border: 0px #000000 none;
    border-collapse: collapse;
    padding: 4px;
}
body {
    font-family: Verdana, Tahoma, sans;
    font-size: 14px;
}
input {
    font-size: 16px;
}
.pan_body {
    font-size: 14px;
}
</style>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
</head>
<body background="themes/backgrounds/crystal.gif">
<div align="center"><img src="logo.png" alt="PHPwnage" /></div>
<table class="borderless_table" width="100%">
END;

$_PWNVERSION = "1.9";

// Our replacement to file_put_contents so that PHPwnage works with PHP 4.
function file_put_contents_debug($file_name, $content) {
$ourFileHandle = fopen($file_name, 'w') or FileFault($content);
fwrite($ourFileHandle,$content);
fclose($ourFileHandle);
}
function lock_installer($file_name, $content) {
$ourFileHandle = fopen($file_name, 'w');
if ($ourFileHandle) {
    fwrite($ourFileHandle,$content);
    fclose($ourFileHandle);
    return 1;
} else {
    return 0;
}
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

function FileFault($file) {
print "<br /><br /><div align=\"center\"><span style=\"font-size: 18px; color: #881111;\">Could Not Write config.php</span><br /><span>(This is not entirely fatal)</span></div><br />";
print "<div align=\"center\" style=\"width: 100%\"><div align=\"center\" style=\"width: 80%;\">An error occurred while trying to create your configuration file, which means that PHP may not have the appropriate permissions to create files. This file is crucial to the operation of PHPwnage, so you'll have to make it yourself. View the source of this web page and copy the text between &quot;&lt;!-- BEGIN CONFIG.PHP&quot; and &quot;END CONFIG.PHP--&gt;&quot; and place it into a file named 'config.php' in your PHPwnage installation directory. When you have uploaded the file to your webserver, continue to the next page by clicking <a href=\"install.php?do=page3\">here</a>.</div></div>";
print "\n\n<!-- BEGIN CONFIG.PHP\n";
print $file;
print "\nEND CONFIG.PHP-->\n\n";
print "";
die ("");
}

if ($_GET['do'] == '') {
$print_what = <<<END
<form action="install.php?do=page1" method="post">
<table style="border-collapse: collapse" width="100%">
  <tr>
    <td width="100%" valign="top" align="left">
    <p align="center"><b>PHPwnage Version $_PWNVERSION - Installer - Welcome!</b></p></td>
  </tr>
  <tr>
    <td width="100%" valign="top" align="center">
    Welcome to PHPwnage, an open-source and completely free forum, calendar, 
    image gallery, and more!<br />
    This installer will guide you through the process of setting up your 
    new site with PHPwnage! <br />
    If you have not already done so, please gather the 
    following information about your site:<ul>
      <li>Your SQL server (if you have a CPanel, check 
      under &quot;Manage SQL&quot;)</li>
      <li>Your SQL user name</li>
      <li>Your SQL password</li>
    </ul>
    <b>Important Notes</b><br />You may wish to set up a blank SQL database 
    now.<br />We can not guarantee that the installer will be able to make one for 
    you as creation permissions are often limited by web hosts.<br />
    If you do not have write access to your PHPwnage
    directory, you will be asked to create a configuration file after the
    first step of the installer.<br />You will 
    not be able to undo what you do here until after you have finished. Please 
    keep this in mind.
    <p align="center"><input type="submit" value="Continue" /></p></td>
  </tr>
  </table>
  </form>
END;
DrawBlock("Welcome to the PHPwnage Installer!","V. $_PWNVERSION",$print_what);
}

if ($_GET['do'] == "page1"){
$print_what = <<<END
<table width="100%" class="installer_table">
  <tr>
    <td width="100%" valign="top" align="left" dir="ltr">
    <p align="center"><b>PHPwnage Version $_PWNVERSION - Installer - License and Terms of Use</b></p></td>
  </tr>
  <tr>
    <td width="100%" valign="top" align="left" dir="ltr"><div align="center">
<form action="install.php?do=page2" method="post">
PHPwnage is licensed under the GNU General Public License version 3.<br />
A copy of this license can be obtained <a href="LICENSE">here</a>.<br />
PHPwnage also contains elements under other licenses which you must agree to:<br /><br />
<b>Tango Icon Set</b><br />
The Tango icon theme included with PHPwnage contains icons from the
<a href="http://tango.freedesktop.org/Tango_Desktop_Project">Tango Desktop Project</a>.<br />
These icons are released under the <a href="http://creativecommons.org/licenses/by-sa/2.5/">Creative Commons Attribution Share-Alike</a> license,<br />
meaning you must attribute all derivative works to the original creator <br />
and all derivatives must be released under the same or a similar license.<br />
All modified icons included with PHPwnage retain their "by-sa" status.<br />
<br />
<b>ReCAPTCHA PHP Client Library</b><br />
PHPwnage comes with the <a href="http://recaptcha.net/">ReCAPTCHA</a> PHP client
library, which is open-source<br />
under a specific license which can be found in the file 'recaptchalib.php'.<br />
This library is provided <i>as-is</i> directly from the ReCAPTCHA web site.<br />
<br />
<b>TineMCE</b><br />
The TinyMCE Javascript WYSIWYG editor is released uner the terms of the Lesser<br />
GNU General Public License. You can find a copy of this license <a href="tiny_mce/license.txt">here</a>.<br />
<br />
By pressing "Continue", you agree to and accept these licenses.
<p align="center"><input type="submit" value="Continue" /></p>
</div></td></tr>
</table>
END;
DrawBlock("License and Terms of Use","V. $_PWNVERSION",$print_what);

}

if ($_GET['do'] == 'page2'){
$print_what = <<<END
<form action="install.php?do=submit" method="post"><input type="hidden" name="do" value="set_config" />
<table width="100%" class="installer_table">
  <tr>
    <td width="100%" valign="top" align="left" colspan="2">
    <p align="center"><b>PHPwnage Version $_PWNVERSION - Installer - Setting Up Your Configuration</b></p></td>
  </tr>
  <tr>
    <td width="50%" valign="middle" align="right"><b>SQL Database Server Location</b><br />
    <font size="2">The URL to your SQL server. Ex: localhost OR sql1.phpnet.us</font></td>
    <td width="50%" valign="middle" align="right"><input type="text" name="sql_server" style="width: 100%" /></td>
  </tr>
  <tr>
    <td width="50%" valign="middle" align="right"><b>SQL User Name</b><br />
    <font size="2">The user name you use to access your SQL server.</font></td>
    <td width="50%" valign="middle" align="right"><input type="text" name="sql_user" style="width: 100%" /></td>
  </tr>
  <tr>
    <td width="50%" valign="middle" align="right"><b>SQL User Password</b><br />
    <font size="2">The password you use to access your SQL server. cAsE sEnSiTiVe</font></td>
    <td width="50%" valign="middle" align="right"><input type="text" name="sql_password" style="width: 100%" /></td>
  </tr>
  <tr>
    <td width="50%" valign="middle" align="right"><b>SQL Database Name</b><br />
    <font size="2">The name of the database in which PHPwnage will install.</font></td>
    <td width="50%" valign="middle" align="right"><input type="text" name="sql_database" style="width: 100%" /></td>
  </tr>
  <tr>
    <td width="50%" valign="middle" align="right"><b>Administrator Email</b><br />
    <font size="2">The email address you would like to display if an error is encountered.</font></td>
    <td width="50%" valign="middle" align="right"><input type="text" name="admin_email" style="width: 100%" /></td>
  </tr>
  <tr>
    <td width="50%" valign="middle" align="right"><b>Table Prefix</b><br />
    <font size="2">ie, &quot;pwn_&quot;</font></td>
    <td width="50%" valign="middle" align="right"><input type="text" name="prefix" style="width: 100%" /></td>
  </tr>
  <tr>
    <td width="100%" valign="top" align="center" colspan="2">  
    <input type="submit" value="Continue" name="enter" /></td>
  </tr>
  </table>
</form>

END;
DrawBlock("Setting up the Configuration File","V. $_PWNVERSION",$print_what);
}


if ($_POST['do'] == 'set_config'){
// Write data.
print "<div align=\"center\">Writing your configuration file...</div>";
$SQL_SERVER = $_POST['sql_server'];
$SQL_USER = $_POST['sql_user'];
$SQL_PASSWORD = $_POST['sql_password'];
$SQL_DATABASE = $_POST['sql_database'];
$ADMIN_EMAIL = $_POST['admin_email'];
$PREFIX = $_POST['prefix'];

$data = "<?php
// PHPwnage Automatically Generated Configuration Page
// This page was automatically generated by install.php
// and its contents are controlled by the license under which
// install.php is administered (the GNU General Public
// License, version 3)\n";
$data = $data . "\$conf_server = \"$SQL_SERVER\";
\$conf_user = \"$SQL_USER\";
\$conf_password = \"$SQL_PASSWORD\";
\$conf_database = \"$SQL_DATABASE\";
\$conf_email = \"$ADMIN_EMAIL\";
\$_TRACKER = \"\"; // Add your analytics tracking here
\$_PREFIX = \"$PREFIX\";";
$data = $data . <<<END

\$_POSTSPERPAGE = 10;    // Number of posts per page. This should move to a User Option!
\$_THREADSPERPAGE = 10;  // Same. Threads per page in viewforum (new in 1.8)
\$_CONFIG_MAIL = false;   // Should we send an email? This is buggy.
\$_IMAGESPERPAGE = 10;  // Images per page on the gallery
\$_DEFAULT_THEME = "crystal"; // Default theme
\$_DEFAULT_ICONS = "tango"; // Icons
\$_DEFAULT_COLOR = "crystal"; // Background
\$_DEFAULT_LANG = "enUS"; // Language;

// DO NOT EDIT ANYTHING BELOW THIS LINE
// ------------------------------------------------------------------------------------------------------------

\$mtime = microtime();
\$mtime = explode(" ",\$mtime);
\$mtime = \$mtime[1] + \$mtime[0];
\$starttime = \$mtime;

\$db_fail = false;
\$db = mysql_connect(\$conf_server,\$conf_user,\$conf_password) or 
die ("<span style=\"font-family: Verdana, Tahoma, sans; color: #EE1111;\">We've experienced an internal error. Please contact " . \$conf_email . ".<br />\n(Failed to connect to SQL server.)</span>"); 
mysql_select_db(\$conf_database, \$db) or \$db_fail = true; 

putenv("TZ=America/New_York");

\$banlist = mysql_query("SELECT * FROM `{\$_PREFIX}banlist`");
while (\$ban = mysql_fetch_array(\$banlist)) {
if (\$_SERVER['REMOTE_ADDR'] == \$ban['ip']) {
die ("<span style=\"font-family: Verdana, Tahoma, sans; color: #EE1111;\">You have been permanently banned from this site.</span>");
}
}

?>
END;
// <?
file_put_contents_debug("config.php",$data);
print "<br /><div align=\"center\">Success! Moving to the next page...</div>";
print "\n<meta http-equiv=\"Refresh\" content=\"1;url=install.php?do=page3\">";
}

if ($_GET['do'] == 'page3')
{
$this_dir = str_replace("http:/","http://",str_replace("//","/", getURL() . "/")); // Current running directory
$print_what = <<<END
<form action="install.php?do=submit" method="post"><input type="hidden" name="do" value="install" />
<table width="100%" class="installer_table">
  <tr>
    <td width="100%" valign="top" align="left" colspan="2">
    <p align="center"><b>PHPwnage Version $_PWNVERSION - Installer - Setting Up Your Site Information</b></p></td>
  </tr>
  <tr>
    <td width="50%" valign="middle" align="right"><b>Forum Title</b><br />
    <font size="2">A short description for your site. Ex: Oasis-Games.com</font></td>
    <td width="50%" valign="middle" align="right"><input type="text" name="site_name" style="width: 100%" /></td>
  </tr>
  <tr>
    <td width="50%" valign="middle" align="right"><b>Copyright Line</b><br />
    <font size="2">A message displayed in the footer. Ex: (C) 2008 Oasis-Games</font></td>
    <td width="50%" valign="middle" align="right"><input type="text" name="site_copyright" style="width: 100%" /></td>
  </tr>
  <tr>
    <td width="50%" valign="middle" align="right"><b>Installation URL</b><br />
    <font size="2">The URL for your site (including the /) Ex: http://oasis-games.com/home/</font></td>
    <td width="50%" valign="middle" align="right"><input type="text" value="$this_dir" name="site_url" style="width: 100%" /></td>
  </tr>
  <tr>
    <td width="50%" valign="middle" align="right"><b>Site Description</b><br />
    <font size="2">A short piece of text to display in the right of the &quot;sub header&quot;</font></td>
    <td width="50%" valign="middle" align="right"><input type="text" name="site_description" style="width: 100%" /></td>
  </tr>
  <tr>
    <td width="50%" valign="middle" align="right"><b>Administrator User Name</b><br />
    <font size="2">The name you would like to use to log in to your account. Ex: Admin</font></td>
    <td width="50%" valign="middle" align="right"><input type="text" name="site_admin_name" style="width: 100%" value="" /></td>
  </tr>
  <tr>
    <td width="50%" valign="middle" align="right"><b>Administrator Password</b><br />
    <font size="2">The password you would like to use to log in to your account. cAsE sEnSiTiVe</font></td>
    <td width="50%" valign="middle" align="right"><input type="password" name="site_admin_pass" style="width: 100%" value="" /></td>
  </tr>
  <tr>
    <td width="100%" valign="top" colspan="2" align="center">
    <input type="submit" value="Continue" name="enter" /></td>
  </tr>
  </table>
</form>

END;
DrawBlock("Setting up the Site Information","V. $_PWNVERSION",$print_what);
}

function databaseFault() {
print "<br /><br /><div align=\"center\"><span style=\"font-size: 18px; color: #881111;\">Database Does Not Exist and Could Not Be Created</span><br /><span>(This is not entirely fatal)</span></div><br />";
print "<div align=\"center\" style=\"width: 100%\"><div align=\"center\" style=\"width: 80%;\">PHPwnage failed to connect to the database you specified and was unable to create the database for you. To fix this problem, create the database manually and then refresh this page or run through the installer again.</div></div>";
die();
}

if ($_POST['do'] == 'install')
{
require 'config.php';
if ($db_fail) {
// It appears that the database doesn't exist. We will try to make it.
mysql_query("CREATE DATABASE `$conf_database` ;") or databaseFault();
mysql_select_db($conf_database, $db);
}
/*
    PHPwnage MySQL Database Table Generation
    This section of the installer is *crucial*, it creates all
    of the tables for your MySQL database. If you are upgrading
    and an upgrade tool is not available for your version, look
    here for more information on what to do with your tables.
*/
print "<div align=\"center\" style=\"width: 100%\"><div align=\"center\" style=\"width: 80%;\">Creating SQL Tables...</div>\n";
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
  `ims` TEXT DEFAULT NULL,
  `ims_title` TEXT DEFAULT NULL,
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
$time = time();
$query = <<<END
CREATE TABLE  `{$_PREFIX}users` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(50) collate latin1_general_ci NOT NULL default '',
  `email` varchar(50) collate latin1_general_ci NOT NULL default '',
  `password` varchar(40) collate latin1_general_ci NOT NULL default '',
  `sig` text collate latin1_general_ci NULL,
  `avatar` varchar(100) collate latin1_general_ci NOT NULL default '',
  `ims` TEXT collate latin1_general_ci NOT NULL default '',
  `level` int(11) NOT NULL default '1',
  `sbonforum` int(11) NOT NULL default '1',
  `color` varchar(50) collate latin1_general_ci NOT NULL default '',
  `theme` varchar(50) collate latin1_general_ci NOT NULL default '',
  `rich_edit` int(11) NOT NULL default '1',
  `time` varchar(100) collate latin1_general_ci NOT NULL default '{$time}',
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
print "<div align=\"center\" style=\"width: 100%\"><div align=\"center\" style=\"width: 80%;\">Table set up complete.</div>\n";
print "<div align=\"center\" style=\"width: 100%\"><div align=\"center\" style=\"width: 80%;\">Loading site infomration...</div>\n";
$info = "INSERT INTO `{$_PREFIX}info` VALUES (1, '" . $_POST['site_name'] . "', '" . $_POST['site_copyright'] . "', '" . $_POST['site_description'] . "', '" . time() . "', '" . $_POST['site_url'] . "', 'logo.png', 3, 2, 0, NULL, NULL, 'msn,yahoo,aim,icq,xfire,live','MSN,Yahoo,AIM,ICQ,xFire,Live');";
$result = mysql_query($info);
print "<div align=\"center\" style=\"width: 100%\"><div align=\"center\" style=\"width: 80%;\">Adding root user...</div>\n";
$adminname = $_POST['site_admin_name'];
$adminpass = md5($_POST['site_admin_pass']);
mysql_query("INSERT INTO `{$_PREFIX}users` (`name`, `password`, `level`, `email`)  VALUES ('$adminname', '$adminpass', 3, '$conf_email');");
print "<div align=\"center\" style=\"width: 100%\"><div align=\"center\" style=\"width: 80%;\">Adding generic news item...</div>\n";
$time = time();
mysql_query("INSERT INTO `{$_PREFIX}news` VALUES (null, 'Welcome to PHPwnage!', 'Welcome to your new PHPwnage site! Thank you for choosing PHPwnage for your CMS needs. If you have any problems or questions, stop on over at [url=http://phpwnage.com/]our home page[/url]. We are ready to assist anyone who needs help with PHPwnage.', '$time', 'PHPwnage', 0);");
print "<div align=\"center\" style=\"width: 100%\"><div align=\"center\" style=\"width: 80%;\">Adding navigation block...</div>\n";
mysql_query("INSERT INTO `{$_PREFIX}blocks` VALUES (null, 'Navigation', '<a href=\"index.php\">Home</a><br />\n<a href=\"admin.php\">Admin</a><br />\n<a href=\"mobile.php\">Mobile</a><br />\n<a href=\"rss.php\">RSS</a><br />\n<a href=\"forum.php\">Forum</a><br />\n<a href=\"calendar.php\">Calendar</a><br />\n<a href=\"modules.php?m=members\">Member List</a><br />\n<a href=\"gallery.php\">Image Gallery</a>');");
print "<div align=\"center\" style=\"width: 100%\"><div align=\"center\" style=\"width: 80%;\">Installation complete! Moving to last page...</div>\n";
print "\n<meta http-equiv=\"Refresh\" content=\"1;url=install.php?do=page4\">";
}

if ($_GET['do'] == 'page4') {
$locked = lock_installer("installer.lock","Installer is locked");
if ($locked == 1) {
    $lock_message = "A lock has been placed on the installer so that your installation can not be tampered with. You should still remove the installer from your site to ensure that nothing happens.";
} else {
    $lock_message = "The installer could not create a lock file, so you <b><i>MUST</i></b> delete 'install.php' to ensure the security of your site.";
}
$print_what = <<<END
<table width="100%" class="installer_table">
  <tr>
    <td width="100%" valign="top" align="left" dir="ltr">
    <p align="center"><b>PHPwnage Version $_PWNVERSION - Installer - Installation Complete</b></p></td>
  </tr>
  <tr>
    <td width="100%" valign="top" align="left" dir="ltr"><div align="center">Thank you for installing PHPwnage!<br />
    You can now access your site and start settings things up how you like.<br /><br />
    You'll want to get on the <a href="admin.php">administration panel</a> and set up your forums,
    change security settings, and set up your image gallery.<br />
    To access your site <a href="index.php">Click Here</a>.<br /><br />
    For more information on PHPwnage, you can visit <a href="http://oasis-games.com">our homepage</a>.<br />
    Be sure to check us out <a href="https://launchpad.net/phpwnage/">on Launchpad</a> for bug reports, updates, and more.
    <br /><br />
    {$lock_message}
    </td>
  </tr>
  </table>
END;
DrawBlock("Thank you for choosing PHPwnage!","V. $_PWNVERSION",$print_what);
}

print "</table></body></html>";
?>
