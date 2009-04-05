<?php
/*
	This file is part of PHPwnage (Calendar)

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

require_once('includes.php');
require_once('sidebar.php');

if ((isset($_POST['action'])) && $_POST['action'] == "add_event") {
    if ($user['level'] < $site_info['mod_rank']) {
        messageBack($_PWNDATA['cal']['name'],$_PWNDATA['cal']['only_mods']);
    }
    $date = mse($_POST['day']);
    $topic = mse($_POST['subj']);
    $content = mse($_POST['content']);
    $uid = $user['id'];
    $_SQL->query("INSERT INTO `{$_PREFIX}calendar` VALUES(NULL, '$date', '$topic', '$content', $uid)");
    messageRedirect($_PWNDATA['cal']['name'],$_PWNDATA['cal']['new_event'],"calendar.php?view=date&amp;day=$date");
}

if ((isset($_POST['action'])) && $_POST['action'] == "edit_event") {
    if ($user['level'] < $site_info['mod_rank']) {
        messageBack($_PWNDATA['cal']['name'],$_PWNDATA['cal']['only_mods']);
    }
    $date = mse($_POST['date']);
    $eid = intval($_POST['event']);
    $title = mse($_POST['subj']);
    $content = mse($_POST['content']);
    $_SQL->query("UPDATE `{$_PREFIX}calendar` SET `title`='$title' WHERE `id`=$eid");
    $_SQL->query("UPDATE `{$_PREFIX}calendar` SET `content`='$content' WHERE `id`=$eid");
    messageRedirect($_PWNDATA['cal']['name'],$_PWNDATA['cal']['edit_event'],"calendar.php?view=date&amp;day=$date");
}

if ((isset($_GET['view'])) && $_GET['view'] == "del_event") {
    if ($user['level'] < $site_info['mod_rank']) {
        messageBack($_PWNDATA['cal']['name'],$_PWNDATA['cal']['only_mods']);
    }
    $_SQL->query("DELETE FROM `{$_PREFIX}calendar` WHERE `id`=" . $_GET['e']);
    messageRedirect($_PWNDATA['cal']['name'],$_PWNDATA['cal']['delete_event'],"calendar.php?view=date&amp;day=$date");
}

// Handle unset $_GET's nicely.

$mode  = (isset($_GET['view'])) ? $_GET['view'] : "";
$month = (isset($_GET['mon']))  ? $_GET['mon']  : "";
$year  = (isset($_GET['y']))    ? $_GET['y']    : "";

if ($mode == "") {
    // Default to current month.
    $view_date = time();
    $month = date("m",$view_date);
    $year = date("y",$view_date);
    $mode = "viewmonth";
}

if ($mode == "viewmonth") {
    $weeks = array();
    // Calculate parameters required for calendar...
    $time_view = mktime(0,0,0,intval($month),1,intval($year));
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
    $smarty->assign("year",$year);
    $smarty->assign("month_year", date("F, Y",$time_view));
    $smarty->assign("month_int", intval($month));
    $smarty->assign("month_previous", date("F",mktime(0,0,0,intval($month) - 1,1,intval($year))));
    $smarty->assign("month_next", date("F",mktime(0,0,0,intval($month) + 1,1,intval($year))));
    $month_started = 0;
    $days_left = $days_in_month;
    for ( $week = 1; $week  <= 6; $week  += 1) {
        $week_tmp = array();
        for ( $day = 1; $day <= 7; $day += 1) {
            if ($week == 1) {
                if ($first_day == $day - 1) {
                    $month_started = 1;
                }
            }
            $tmp = array();
            if ($month_started == 0) {
                $tmp['content'] = "<div style=\"width: 100%; text-align: center;\"><br /><a href=\"calendar.php?view=viewmonth&amp;mon=" . (intval($month) - 1) . "&amp;y=$year\">" . date("F",mktime(0,0,0,intval($month) - 1,1,intval($year))) . "</a></div>";
                $tmp['number'] = "X";
                $tmp['cell'] = "";
            } else if ($month_started == 2) {
                $tmp['content'] = "<div style=\"width: 100%; text-align: center;\"><br /><a href=\"calendar.php?view=viewmonth&amp;mon=" . (intval($month) + 1) . "&amp;y=$year\">" . date("F",mktime(0,0,0,intval($month) + 1,1,intval($year))) . "</a></div>";
                $tmp['number'] = "X";
                $tmp['cell'] = "";
            } else if ($month_started == 1) {
                $tmp['number'] = $days_in_month - $days_left + 1; // The current day of the month.
                $today = getDay(mktime(0,0,0,intval($month),$tmp['number'],intval($year)));
                $day_results = $_SQL->query("SELECT * FROM `{$_PREFIX}calendar` WHERE `day`='{$today}'");
                $tmp['content'] = "";
                while ($query_row = $day_results->fetch_array()) {
                    $tmp['content'] .= "- " . $query_row['title'] . "<br />\n";
                }
                if ($user['level'] >= $site_info['mod_rank']) {
                    $tmp['cell'] = "<a href=\"calendar.php?view=add&amp;day=$today\">{$_PWNICONS['calendar']['add']}</a> <a href=\"calendar.php?view=date&amp;day=$today\">{$_PWNICONS['calendar']['view']}</a>";
                } else {
                    $tmp['cell'] = "<a href=\"calendar.php?view=date&amp;day=$today\">{$_PWNICONS['calendar']['view']}</a>";
                }
                $days_left = $days_left - 1;
                if ($days_left <= 0) {
                    $month_started = 2;
                }
            }
            $week_tmp[] = $tmp;
        }
        $weeks[] = $week_tmp;
    }
    $smarty->assign('weeks',$weeks);
    $smarty->display('calendar/viewmonth.tpl');
}

if ($mode == "date") {
    $date_info = split(",", $_GET['day']);
    $current_time = mktime(0,0,0,intval($date_info[1]),intval($date_info[0]),intval($date_info[2]));
    $month = intval($date_info[1]);
    $year = intval($date_info[2]);
    $month_name = date("F", $current_time);
    $date_format = date("l, F jS, Y", $current_time);
    $day_code = mse($_GET['day']);
    $smarty->assign('year',$year);
    $smarty->assign('month',$month);
    $smarty->assign('month_name',$month_name);
    $smarty->assign('date_formatted',$date_format);
    $smarty->assign('day_code',$day_code);
    $events = array();
    $users = array();

    $day_results = $_SQL->query("SELECT * FROM `{$_PREFIX}calendar` WHERE `day`='{$day_code}'");
    while ($event = $day_results->fetch_array()) {
        $events[] = $event;
        if (!array_key_exists($event['user'],$users)) {
            $userrow = $_SQL->query("SELECT * FROM users WHERE id={$event['user']}");
            $users[$event['user']] = $userrow->fetch_array();
        }
    }
    $smarty->assign('users',$users);
    $smarty->assign('events',$events);
    $smarty->display('calendar/viewday.tpl');
}

if ($mode == "add") {
    $day = $_GET['day'];
    if ($user['level'] < $site_info['mod_rank']) {
        messageBack($_PWNDATA['cal']['name'],$_PWNDATA['cal']['only_mods']);
    }
    $date_info = split(",", $_GET['day']);
    $current_time = mktime(0,0,0,intval($date_info[1]),intval($date_info[0]),intval($date_info[2]));
    $date_formatted = date("l, F jS, Y", $current_time);
    $smarty->assign('date_formatted',$date_formatted);
    $smarty->assign('date_info',$date_info);
    $smarty->assign('day',$day);
    $smarty->display('calendar/addevent.tpl');
}

if ($mode == "edit") {
    $eid = intval($_GET['e']);
    if ($user['level'] < $site_info['mod_rank']) {
        messageBack($_PWNDATA['cal']['name'],$_PWNDATA['cal']['only_mods']);
    }
    $results = $_SQL->query("SELECT * FROM `{$_PREFIX}calendar` WHERE `id`=$eid");
    $event = $results->fetch_array();
    $smarty->assign('event',$event);
    $smarty->display('calendar/editevent.tpl');
}
