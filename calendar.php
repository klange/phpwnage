<?php
/*
	This file is part of PHPwnage (Calendar)

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
require 'config.php';
require 'includes.php';

standardHeaders($site_info['name'] . " :: " . $_PWNDATA['cal']['name'],true);

if ($_POST['action'] == "add_event") // If a new event is being added
{
if ($user['level'] < $site_info['mod_rank']) {
messageBack($_PWNDATA['cal']['name'],$_PWNDATA['cal']['only_mods']);
}
$date = $_POST['day'];
$topic = $_POST['subj'];
$content = $_POST['content'];
$uid = $_POST['user'];
mysql_query("INSERT INTO `calendar` VALUES(NULL, '$date', '$topic', '$content', $uid)");
messageRedirect($_PWNDATA['cal']['name'],$_PWNDATA['cal']['new_event'],"calendar.php?view=date&day=$date");
}
if ($_POST['action'] == "edit_event") // If an event is being edited
{
if ($user['level'] < $site_info['mod_rank']) {
messageBack($_PWNDATA['cal']['name'],$_PWNDATA['cal']['only_mods']);
}
$date = $_POST['date'];
$eid = $_POST['event'];
$title = $_POST['subj'];
$content = $_POST['content'];
mysql_query("UPDATE `calendar` SET `title`='$title' WHERE `id`=$eid");
mysql_query("UPDATE `calendar` SET `content`='$content' WHERE `id`=$eid");
messageRedirect($_PWNDATA['cal']['name'],$_PWNDATA['cal']['edit_event'],"calendar.php?view=date&day=$date");
}
if ($_GET['view'] == "del_event") {
if ($user['level'] < $site_info['mod_rank']) {
messageBack($_PWNDATA['cal']['name'],$_PWNDATA['cal']['only_mods']);
}
mysql_query("DELETE FROM `calendar` WHERE `id`=" . $_GET['e']);
messageRedirect($_PWNDATA['cal']['name'],$_PWNDATA['cal']['delete_event'],"calendar.php?view=date&day=$date");
}

function printDay($day, $content, $upper) {
print <<<END
<td border="1" bordercolor="#000000">
    <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse; border-width: 0" bordercolor="#111111" width="100%" height="100">
      <tr>
        <td width="76%" height="24" style="border-style: none; border-width: medium">
        <p align="center"><font class="forum_body"><font size="2">$upper</font></font></td>
        <td width="24%" height="24" style="border-style: none; border-width: medium" bgcolor="#C0C0C0">
        <p align="center"><i><font class="forum_body"><font size="5">$day</font></font></i></td>
      </tr>
      <tr>
        <td width="100%" colspan="2" height="59" style="border-style: none; border-width: medium; padding: 1px 1px 1px 1px" valign="top"><font class="forum_body">$content</font></td>
      </tr>
    </table>
</td>
END;
}

drawSubbar("<a href=\"index.php\">" . $site_info['name'] . "</a> > {$_PWNDATA['cal']['name']}","<a href=\"calendar.php\">{$_PWNDATA['cal']['name']}</a>");

require 'sidebar.php';

print <<<END
<td valign="top">
<table class="borderless_table" width="100%">
      <tr>
        <td width="100%">
    <table class="borderless_table" width="100%">
      <tr>
        <td class="pan_ul">&nbsp;</td>
        <td class="pan_um">
        <font class="pan_title_text">{$_PWNDATA['cal']['name']}</font></td>
        <td class="pan_um">
        <p align="right"><font class="pan_title_text">
	</font></td>
        <td class="pan_ur">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_ml">&nbsp;</td>
        <td class="pan_body" valign="top" colspan="2">
        <font class="pan_body_text">

END;
print "<a href=\"calendar.php\">{$_PWNDATA['cal']['month_view']}</a><br>";

$mode = $_GET['view'];
$month = $_GET['mon'];
$year = $_GET['y'];


if ($mode == "") {
$view_date = time();
$month = date("m",$view_date);
$year = date("y",$view_date);
$mode = "viewmonth";
}

if ($mode == "viewmonth")
{
$time_view = mktime(0,0,0,intval($month),1,intval($year));
// Determine stuff about the month
$days_in_month = date("t",$time_view);
$first_day = date("w",$time_view);
if ($month < 1) {
$month = 12 + $month;
$year = $year - 1;
}
if ($month > 12) {
$month = $month - 12;
$year = $year + 1;
}
if ($year > 99) {
$year = $year - 100;
}
print "<p align=\"center\"><font size=\"4\">" . date("F, Y",$time_view) . "</font><br><font size=\"2\"><a href=\"calendar.php?view=viewmonth&mon=" . (intval($month) - 1) . "&y=$year\">" . date("F",mktime(0,0,0,intval($month) - 1,1,intval($year))) . "</a> | <a href=\"calendar.php?view=viewmonth&mon=" . (intval($month) + 1) . "&y=$year\">" . date("F",mktime(0,0,0,intval($month) + 1,1,intval($year))) . "</a></font></p>\n";
print "<table border=\"1\" style=\"border-collapse: collapse; border-width: 1; table-layout: fixed\" width=\"100%\" bordercolor=\"#000000\">\n";
print "<tr><td align=\"center\"><font class=\"forum_body\">{$_PWNDATA['cal']['sunday']}</font></td><td align=\"center\"><font class=\"forum_body\">{$_PWNDATA['cal']['monday']}</font></td><td align=\"center\"><font class=\"forum_body\">{$_PWNDATA['cal']['tuesday']}</font></td><td align=\"center\"><font class=\"forum_body\">{$_PWNDATA['cal']['wednesday']}</font></td><td align=\"center\"><font class=\"forum_body\">{$_PWNDATA['cal']['thursday']}</font></td><td align=\"center\"><font class=\"forum_body\">{$_PWNDATA['cal']['friday']}</font></td><td align=\"center\"><font class=\"forum_body\">{$_PWNDATA['cal']['saturday']}</font></td></tr>";
$month_started = 0;
$days_left = $days_in_month;
for ( $week = 1; $week  <= 6; $week  += 1) {
print "<tr>";
for ( $day = 1; $day <= 7; $day += 1) {
if ($week == 1) {
if ($first_day == $day - 1) {
$month_started = 1;
}
}
$zing = "<a href=\"calendar.php?view=viewmonth&mon=" . (intval($month) - 1) . "&y=$year\">" . date("F",mktime(0,0,0,intval($month) - 1,1,intval($year))) . "</a>";
$zinga = "X";
$top_cell = "";
if ($month_started == 2) {
$zing = "<a href=\"calendar.php?view=viewmonth&mon=" . (intval($month) + 1) . "&y=$year\">" . date("F",mktime(0,0,0,intval($month) + 1,1,intval($year))) . "</a>";
}
if ($month_started == 1) {
$zinga = $days_in_month - $days_left + 1; // The current day of the month.
$today = getDay(mktime(0,0,0,intval($month),$zinga,intval($year)));
$day_results = mysql_query("SELECT * FROM `calendar` WHERE `day`='" . $today . "'");
$zing = "";
while ($query_row = mysql_fetch_array($day_results))
{
	$zing = $zing . "- " . $query_row['title'] . "<br>\n";
}
if ($user['level'] >= $site_info['mod_rank']) {
$top_cell = "<a href=\"calendar.php?view=add&day=$today\"><img src=\"smiles/cal_add.png\"></a> <a href=\"calendar.php?view=date&day=$today\"><img src=\"smiles/cal_view.png\"></a>";
} else {
$top_cell = "<a href=\"calendar.php?view=date&day=$today\"><img src=\"smiles/cal_view.png\"></a>";
}
$days_left = $days_left - 1;
if ($days_left <= 0) {
$month_started = 2;
}
}
printDay($zinga, $zing, $top_cell);
}
print "</tr>";
}
print "</table>";
}

if ($mode == "date") // View a particular day
{
$date_info = split(",", $_GET['day']);
$current_time = mktime(0,0,0,intval($date_info[1]),intval($date_info[0]),intval($date_info[2]));
print "<font size=\"4\">" . date("l, F jS, Y", $current_time) . "</font><br><br>\n"; 
$day_results = mysql_query("SELECT * FROM `calendar` WHERE `day`='" . $_GET['day'] . "'");
$day_stuff = $_GET['day'];
$zing = "";
while ($query_row = mysql_fetch_array($day_results))
{
$resultb = mysql_query("SELECT * FROM users WHERE id=" .  $query_row['user']);
$post_author = mysql_fetch_array($resultb);
$uid = $query_row['user'];
$uname = $post_author['name'];
$zing = $zing . "<b>" .  $query_row['title'] . "</b> posted by <a href=\"forum.php?do=viewprofile&id=$uid\">$uname</a><br>\n--- " . bbDecode($query_row['content']);
if ($user['level'] >= $site_info['mod_rank']) {
	$zing = $zing . " [<a href=\"calendar.php?view=edit&e=" . $query_row['id'] . "\">{$_PWNDATA['admin']['forms']['edit']}</a>] [<a href=\"calendar.php?view=del_event&e=" . $query_row['id'] . "\">{$_PWNDATA['admin']['forms']['delete']}</a>]";
}
$zing = $zing . "<br>\n\n";
}
if ($zing == "") { $zing = "{$_PWNDATA['cal']['no_events']}<a href=\"calendar.php?view=add&day=$day_stuff\">{$_PWNDATA['cal']['add_one']}</a>"; }
print $zing;
print "<br><br><br><a href=\"calendar.php?view=add&day=$day_stuff\">{$_PWNDATA['cal']['event_add']}</a>";
}

if ($mode == "add") {
$day = $_GET['day'];
if ($user['level'] < $site_info['mod_rank']) { messageBack($_PWNDATA['cal']['name'],$_PWNDATA['cal']['only_mods']); }
$userid = $user['id'];
print <<<END
<form method="post" action="calendar.php" name="form">
<input type="hidden" name="action" value="add_event">
<input type="hidden" name="day" value="$day">
<input type="hidden" name="user" value="$userid">
{$_PWNDATA['cal']['event_name']}: <br>
<input type="text" name="subj" size="51" style="width:100%"><br>
{$_PWNDATA['cal']['event_desc']}:<br>
END;
print printPoster("content");
print <<<END
<textarea rows="11" name="content" id="content" style="width:100%; font-family:Tahoma; font-size:10pt" cols="20"></textarea><br>
<input type="submit" value="{$_PWNDATA['cal']['event_add']}" name="sub">
</form>
END;
}
if ($mode == "edit") {
$eid = $_GET['e'];
if ($user['level'] < $site_info['mod_rank']) { messageBack($_PWNDATA['cal']['name'],$_PWNDATA['cal']['only_mods']); }
$userid = $user['id'];
$results = mysql_query("SELECT * FROM `calendar` WHERE `id`=$eid");
$event = mysql_fetch_array($results);
$title = $event['title'];
$content = $event['content'];
$date = $event['day'];
print <<<END
<form method="post" action="calendar.php" name="form">
<input type="hidden" name="action" value="edit_event">
<input type="hidden" name="event" value="$eid">
<input type="hidden" name="date" value="$date">
{$_PWNDATA['cal']['event_name']}: <br>
<input type="text" name="subj" value="$title" size="51" style="width:100%"><br>
{$_PWNDATA['cal']['event_desc']}:<br>
END;
print printPoster("content");
print <<<END
<textarea rows="11" name="content" id="content" style="width:100%; font-family:Tahoma; font-size:10pt" cols="20">$content</textarea><br>
<input type="submit" value="{$_PWNDATA['cal']['event_save']}" name="sub">
</form>
END;
}
print <<<END
	</font></td>
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
print <<<END
	</table>
        </td>
  </tr>
</table>
END;require "footer.php";
?>
