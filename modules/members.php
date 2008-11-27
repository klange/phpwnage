<?php
/*
	This file is part of PHPwnage (Simple Memberlist module)

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
$mod['title'] = "Member List";
$mod['right'] = "Viewing List";
$mod['right_inner'] = "Members of " . $site_info['name'];
function mod_print()
{
global $_PWNDATA, $_PWNICONS, $_PREFIX;
$content = "<table class=\"forum_base\" width=\"100%\">";
$content = $content . "<tr><td class=\"forum_thread_title\" width=\"20\"><a href=\"modules.php?m=members&o=uid\">#</a></td><td class=\"forum_thread_title\"><a href=\"modules.php?m=members&o=uname\">Username</a></td><td class=\"forum_thread_title\" width=\"100\">PM</td><td class=\"forum_thread_title\" width=\"120\">Messaging</td><td class=\"forum_thread_title\" width=\"30\"><a href=\"modules.php?m=members&o=posts\">Posts</a></td></tr>\n";
$odd = 0;
$result_set = array();
if (!isset($_GET['o']) or $_GET['o'] == "uname") {
$members_result = mysql_query("SELECT * FROM `{$_PREFIX}users` ORDER BY `name`");
while ($temp = mysql_fetch_array($members_result)) {
    array_push(&$result_set, $temp);
}
} else if ($_GET['o'] == "uid") {
$members_result = mysql_query("SELECT * FROM `{$_PREFIX}users` ORDER BY `id`");
while ($temp = mysql_fetch_array($members_result)) {
    array_push(&$result_set, $temp);
}
} else if ($_GET['o'] == "posts") {
$members_result_t = mysql_query("SELECT COUNT(`authorid`), `authorid` FROM `{$_PREFIX}posts` GROUP BY `authorid` ORDER BY COUNT(`authorid`) DESC");
$ignore = "WHERE `id`<>";
while ($memb = mysql_fetch_array($members_result_t)) {
    $members_result = mysql_query("SELECT * FROM `{$_PREFIX}users` WHERE `id`=" . $memb['authorid']);
    $array = mysql_fetch_array($members_result);
    if (isset($array['id'])) {
        array_push(&$result_set, $array);
    }
    $ignore = $ignore . $memb['authorid'] . " AND `id`<>";
}
$members_result = mysql_query("SELECT * FROM `{$_PREFIX}users` $ignore 0 ORDER BY `id`");
while ($temp = mysql_fetch_array($members_result)) {
    array_push(&$result_set, $temp);
}
} else {
$members_result = mysql_query("SELECT * FROM `{$_PREFIX}users` ORDER BY `name`");
while ($temp = mysql_fetch_array($members_result)) {
    array_push(&$result_set, $temp);
}
}

//while ($member = mysql_fetch_array($members_result))
while (list($key,$member) = each(&$result_set))
{
$odd = 1 - $odd;
if ($odd == 1) {
    $back = "class=\"forum_topic_sig\"";
} else {
    $back = "class=\"forum_odd_row\"";
}
$content = $content . "<tr><td $back>" . $member['id'] . "</td><td $back>";
$content = $content . "<a href=\"forum.php?do=viewprofile&amp;id=" . $member['id'] . "\">" . $member['name'] . "</a>";
$post_count = postCount($member['id']);
$post_author = $member;
$has_messenger = false; // then we'll go through the IMs...
$auth_info = "";
$authmsn = "";
$authaim = "";
$authyahoo = "";
$authicq = "";
$authlive = "";
$authxf = "";
$authid = $post_author['id'];
        if ($post_author['msn'] != "") {
            $has_messenger = true;
            $authmsn = $post_author['msn'];
            $auth_info = $auth_info . "<a href=\"forum.php?do=viewprofile&amp;id=$authid\">{$_PWNICONS['protocols']['msn']}</a>";
        }
        if ($post_author['yahoo'] != "") {
            $has_messenger = true;
            $authyahoo = $post_author['yahoo'];
            $auth_info = $auth_info . "<a href=\"forum.php?do=viewprofile&amp;id=$authid\">{$_PWNICONS['protocols']['yahoo']}</a>";
        }
        if ($post_author['aim'] != "") { // AIM we're actually going to do something usefull for...
            $has_messenger = true;
            $authaim = $post_author['aim'];
            $auth_info = $auth_info . "<a href=\"aim:goim?screenname=$authaim&amp;message=Hello+Are+you+there?\">{$_PWNICONS['protocols']['aim']}</a>";
        }
        if ($post_author['icq'] != "") { // ICQ as well...
            $has_messenger = true;
            $authicq = $post_author['icq'];
            $auth_info = $auth_info . "<a href=\"http://wwp.icq.com/scripts/search.dll?to=$authicq\">{$_PWNICONS['protocols']['icq']}</a>";
        }
        if ($post_author['xfire'] != "") { // xfire
            $has_messenger = true;
            $authxf = $post_author['xfire'];
            $auth_info = $auth_info . "<a href=\"http://www.xfire.com/profile/$authxf\">{$_PWNICONS['protocols']['xfire']}</a>";
        }
        if ($post_author['live'] != "") { // xfire
            $has_messenger = true;
            $authlive = str_replace(" ","+",$post_author['live']);
            $auth_info = $auth_info . "<a href=\"http://live.xbox.com/en-US/profile/profile.aspx?pp=0&amp;GamerTag=$authlive\">{$_PWNICONS['protocols']['live']}</a>";
        }
        if ($post_author['pand'] != "") { // Pandemic
            $has_messenger = true;
            $authpand = $post_author['pand']; // We assume we're using the default server from this point on.
            $auth_info = $auth_info . "<a href=\"pandemic://sendmessage.$authpand\">";
            if ($_CHECKPANDEMIC) { // If we're going to look...
	            $sock = socket_create(AF_INET,SOCK_DGRAM,SOL_UDP);
	            socket_connect($sock,"76.189.178.118",60009); // If you run a custom server, change this!
	            socket_send($sock,"10",strlen("10"),0);
	            $return = socket_read($sock,1024);
	            $return = socket_read($sock,1024);
	            $serverInfo = explode("|_|",$return);
	            socket_send($sock,"3|_|" . $authpand,strlen("3|_|" . $authpand),0);
	            $return = socket_read($sock,1024);
	            $return = socket_read($sock,1024);
	            $userInfo = explode("|_|",$return);
	            socket_close($sock);
	            if ($userInfo[1] == "1") {
	            $auth_info = $auth_info . $_PWNICONS['protocols']['pand_on'];
	            } else {
	            $auth_info = $auth_info . $_PWNICONS['protocols']['pand_off'];
	            }
            } else {
                $auth_info = $auth_info . $_PWNICONS['protocols']['pand_on'];
            }
        }
$content = $content . "</td><td $back><a href=\"forum.php?do=newpm&amp;to=" . $member['id'] . "\">Send a PM</a></td><td $back>$auth_info</td><td $back>$post_count</td></tr>";
}
$content = $content . "</table>";
return $content;
}
?>
