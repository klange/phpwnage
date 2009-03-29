<?php
/*
	This file is part of PHPwnage (Calendar Block)

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

$block_title = "Calendar";
$block_content = "<b>Upcoming Events</b>:<br />";
$view_date = time();
$month = date("m",$view_date);
$year = date("y",$view_date);
$day = date("j",$view_date); // The current day of the month.
$today = getDay(mktime(0,0,0,intval($month),intval($day),intval($year)));
$tomorrow = getDay(mktime(0,0,0,intval($month),intval($day)+1,intval($year)));
$dayafter = getDay(mktime(0,0,0,intval($month),intval($day)+2,intval($year)));
$dname = date("l", mktime(0,0,0,intval($month),intval($day)+2,intval($year)));
$dayafter2 = getDay(mktime(0,0,0,intval($month),intval($day)+3,intval($year)));
$d2name = date("l", mktime(0,0,0,intval($month),intval($day)+3,intval($year)));
$dayafter3 = getDay(mktime(0,0,0,intval($month),intval($day)+4,intval($year)));
$d3name = date("l", mktime(0,0,0,intval($month),intval($day)+4,intval($year)));
$day_results = $_SQL->query("SELECT * FROM `{$_PREFIX}calendar` WHERE `day`='" . $today . "'");
$events = "";
while ($query_row = $day_results->fetch_array())
{
	$events = $events . "- " . $query_row['title'] . "<br />\n";
}
$block_content = $block_content . "<a href=\"calendar.php?view=date&amp;day=$today\">Today</a>:<br />$events";
$day_results = $_SQL->query("SELECT * FROM `{$_PREFIX}calendar` WHERE `day`='" . $tomorrow . "'");
$events = "";
while ($query_row = $day_results->fetch_array())
{
	$events = $events . "- " . $query_row['title'] . "<br />\n";
}
$block_content = $block_content . "<a href=\"calendar.php?view=date&amp;day=$tomorrow\">Tomorrow</a>:<br />$events";
$day_results = $_SQL->query("SELECT * FROM `{$_PREFIX}calendar` WHERE `day`='" . $dayafter . "'");
$events = "";
while ($query_row = $day_results->fetch_array())
{
	$events = $events . "- " . $query_row['title'] . "<br />\n";
}
$block_content = $block_content . "<a href=\"calendar.php?view=date&amp;day=$dayafter\">" . $dname . "</a>:<br />$events";
$day_results = $_SQL->query("SELECT * FROM `{$_PREFIX}calendar` WHERE `day`='" . $dayafter2 . "'");
$events = "";
while ($query_row = $day_results->fetch_array())
{
	$events = $events . "- " . $query_row['title'] . "<br />\n";
}
$block_content = $block_content . "<a href=\"calendar.php?view=date&amp;day=$dayafter\">" . $d2name . "</a>:<br />$events";
$day_results = $_SQL->query("SELECT * FROM `{$_PREFIX}calendar` WHERE `day`='" . $dayafter3 . "'");
$events = "";
while ($query_row = $day_results->fetch_array())
{
	$events = $events . "- " . $query_row['title'] . "<br />\n";
}
$block_content = $block_content . "<a href=\"calendar.php?view=date&amp;day=$dayafter\">" . $d3name . "</a>:<br />$events";
?>
