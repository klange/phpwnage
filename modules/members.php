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
global $_PWNDATA, $_PWNICONS, $_CHECKPANDEMIC, $_PREFIX, $site_info;
$content = "<table class=\"forum_base\" width=\"100%\">";
$content = $content . "<tr><td class=\"forum_thread_title\" width=\"20\"><a href=\"modules.php?m=members&o=uid\">#</a></td><td class=\"forum_thread_title\"><a href=\"modules.php?m=members&o=uname\">Username</a></td><td class=\"forum_thread_title\" width=\"100\">PM</td><td class=\"forum_thread_title\" width=\"120\">Messaging</td><td class=\"forum_thread_title\" width=\"30\"><a href=\"modules.php?m=members&o=posts\">Posts</a></td></tr>\n";
$odd = 0;
$result_set = array();
if (!isset($_GET['o']) or $_GET['o'] == "uname") {
$members_result = mysql_query("SELECT id,name,ims FROM `{$_PREFIX}users` ORDER BY `name`");
while ($temp = mysql_fetch_array($members_result)) {
    array_push(&$result_set, $temp);
}
} else if ($_GET['o'] == "uid") {
$members_result = mysql_query("SELECT id,name,ims FROM `{$_PREFIX}users` ORDER BY `id`");
while ($temp = mysql_fetch_array($members_result)) {
    array_push(&$result_set, $temp);
}
} else if ($_GET['o'] == "posts") {
$members_result_t = mysql_query("SELECT COUNT(`authorid`), `authorid` FROM `{$_PREFIX}posts` GROUP BY `authorid` ORDER BY COUNT(`authorid`) DESC");
$ignore = "WHERE `id`<>";
while ($memb = mysql_fetch_array($members_result_t)) {
    $members_result = mysql_query("SELECT id,name,ims FROM `{$_PREFIX}users` WHERE `id`=" . $memb['authorid']);
    $array = mysql_fetch_array($members_result);
    if (isset($array['id'])) {
        array_push(&$result_set, $array);
    }
    $ignore = $ignore . $memb['authorid'] . " AND `id`<>";
}
$members_result = mysql_query("SELECT id,name,ims FROM `{$_PREFIX}users` $ignore 0 ORDER BY `id`");
while ($temp = mysql_fetch_array($members_result)) {
    array_push(&$result_set, $temp);
}
} else {
$members_result = mysql_query("SELECT id,name,ims FROM `{$_PREFIX}users` ORDER BY `name`");
while ($temp = mysql_fetch_array($members_result)) {
    array_push(&$result_set, $temp);
}
}
$im_names = explode(",",$site_info['ims']);
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
$auth_info = "";
$im_vals = explode(",",$member['ims']);
if (count($im_vals) == count($im_names)) {
$ims = array_combine($im_names,$im_vals);
foreach ($ims as $name => $value) {
	if (strlen($value) > 0) {
		$auth_info .= $_PWNICONS['protocols'][$name];
	}
}
}
$content = $content . "</td><td $back><a href=\"forum.php?do=newpm&amp;to=" . $member['id'] . "\">Send a PM</a></td><td $back>$auth_info</td><td $back>$post_count</td></tr>";
}
$content = $content . "</table>";
return $content;
}
?>
