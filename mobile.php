<?php
/*
	This file is part of PHPwnage (Mobile Format News Reader)

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
require 'config.php';
require 'includes.php';

if (isset($user['name'])) {
    $loginout = "<a href=\"mobile.php?do=logoff\">{$_PWNDATA['forum']['logout']}</a>";
} else {
    $loginout = "<a href=\"mobile.php?do=login\">{$_PWNDATA['forum']['login']}</a>";
}

print <<<END
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
<head>
<title>
END;
print $site_info['name'] . " :: Mobile";
print <<<END
</title>
<meta http-equiv="Content-type" content="text/html;charset=windows-1252" />
<style type="text/css">
body {font-family: sans; font-size: 12px;}
.quote {border: 1px dashed black; padding: 2px;}
</style>
</head>

<body>
<b>{$site_info['name']}</b><br />
<a href="mobile.php?do=news">{$_PWNDATA['news_page_title']}</a> | <a href="mobile.php?do=forum">{$_PWNDATA['forum_page_title']}</a> | $loginout <br /><br />
END;
if (!isset($_GET['do'])) {
    $_GET['do'] = "news";
}
if ($_GET['do'] == "news") {
    $result = mysql_query("SELECT * FROM `{$_PREFIX}news` ORDER BY id DESC LIMIT 5", $db);
    while ($row = mysql_fetch_array($result)) {
        $posted = date("F j, Y (g:ia T)", $row['time_code']) . " " . $_PWNDATA['posted_by'] . " " . $row['user'];
        print <<<END
<b>{$row['title']}</b><br /><i>{$posted}</i><br />
END;
        print BBDecode($row['content'],true);
        print "<br /><br />";
    }
} else if ($_GET['do'] == "forum") {
    $post_results = mysql_query("SELECT * FROM `{$_PREFIX}topics` ORDER BY `lastpost` DESC LIMIT 5");
    while ($topic = mysql_fetch_array($post_results)) {
        if (isReadable($user['level'],$topic['board'])) {
            if (substr($topic['title'], 0, 20) != $topic['title']) {
                $topicName = substr($topic['title'],0,20) . "...";
            } else {
                $topicName = $topic['title'];
            }
            if (!check_read($topic['id'],$user['id'])) {
                print "** ";
            }
            print "<a href=\"mobile.php?do=viewtopic&amp;id=" . $topic['id'] . "\">" . $topicName . "</a> {$_PWNDATA['buddy']['in']} " . getBoardName($topic['board']) . "<br />";
        }
    }
} else if ($_GET['do'] == "login") {
    print <<<END
    <form action="forum.php" method="post">
      <input type="hidden" name="mobile" value="yes" />
      <input type="hidden" name="action" value="login" />
      {$_PWNDATA['profile']['username']}:<br />
      <input type="text" name="uname" size="20" /><br />
      {$_PWNDATA['profile']['password']}:<br />
      <input type="password" name="upass" size="20" /><br />
      <input type="checkbox" name="remember" value="ON" />{$_PWNDATA['forum']['remember_me']}<br />
      <input type="submit" value="{$_PWNDATA['forum']['login']}" name="B1" />
    </form>
END;
} else if ($_GET['do'] == "viewtopic") {
    $result = mysql_query("SELECT * FROM `{$_PREFIX}topics` WHERE id='" . $_GET['id'] . "'", $db);
    $topic = mysql_fetch_array($result);
    $resultb = mysql_query("SELECT * FROM `{$_PREFIX}boards` WHERE id='" . $topic['board'] . "'", $db);
    $board = mysql_fetch_array($resultb);
    if ($board['vis_level'] > $user['level']) {
        messageBackLight($_PWNDATA['forum']['not_permitted_topic']);
    }
    set_read($topic['id'],$user['id']);
    print "<b>" . $topic['title'] . "</b><br />";
    if ((!($board['post_level'] > $user['level'])) and ($islocked == false)) {
        print "<a href=\"mobile.php?do=reply&id={$topic['id']}\">{$_PWNDATA['forum']['add_reply']}</a><br />";
    }
    print "<br />";
    $result = mysql_query("SELECT * FROM `{$_PREFIX}posts` WHERE topicid='" . $topic['id'] . "' ORDER BY `id` DESC LIMIT 5", $db);
    while ($row = mysql_fetch_array($result)) {
        $resultb = mysql_query("SELECT `id`,`name` FROM `{$_PREFIX}users` WHERE id='" .  $row['authorid'] . "'", $db);
        $post_author = mysql_fetch_array($resultb);
        print "<b>" . $post_author['name'] . ":</b><br />";
        print "<i>" . date("F j, Y (g:ia T)", $row['time']) ."</i><br />";
        print bbDecode($row['content']);
        print "<br /><br />";
    }
} else if ($_GET['do'] == "reply") {
    $result = mysql_query("SELECT * FROM `{$_PREFIX}topics` WHERE id='" . $_GET['id'] . "'", $db);
    $topic = mysql_fetch_array($result);
    $result = mysql_query("SELECT * FROM `{$_PREFIX}boards` WHERE id='" . $topic['board'] . "'", $db);
    $board = mysql_fetch_array($result);
    if ($board['post_level'] > $user['level']) {
        messageBackLight($_PWNDATA['forum']['not_permitted_reply']);
    }
    if ($topic['locked'] == 0) {
        $islocked = false;
    } else {
        if ($user['level'] >= $site_info['mod_rank']) {
        $islocked = false;
        } else {
            messageBackLight($_PWNDATA['forum']['not_permitted_reply']);
        }
    }
    print <<<END
<form action="forum.php" method="post" name="form">
<input type="hidden" name="action" value="new_reply" />
<input type="hidden" name="mobile" value="yes" />
<textarea rows="4" name="content" style="width:100%; font-size:10pt" cols="20" ></textarea><br />
<input type="hidden" name="topic" value="{$topic['id']}" />
<input type="hidden" name="user" value="{$user['id']}" />
<input type="submit" value="{$_PWNDATA['forum']['submit_post']}" name="sub" />
</form>
END;
}

print <<<END
<br /><br /><i>{$site_info['copyright']}</i><br />
END;
$mtime = microtime();
$mtime = explode(" ",$mtime);
$mtime = $mtime[1] + $mtime[0];
$endtime = $mtime;
$totaltime = ($endtime - $starttime); 
print "{$_PWNDATA['exec_a']}$totaltime{$_PWNDATA['exec_b']}";
print <<<END
</body>
</html>
END;
die('');
?>
