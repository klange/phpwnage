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

standardHeaders($site_info['name'] . " :: " . $_PWNDATA['cal']['name'],true);

if ($_POST['action'] == "add_event") {
    if ($user['level'] < $site_info['mod_rank']) {
        messageBack($_PWNDATA['cal']['name'],$_PWNDATA['cal']['only_mods']);
    }
    $date = $_POST['day'];
    $topic = $_POST['subj'];
    $content = $_POST['content'];
    $uid = $_POST['user'];
    mysql_query("INSERT INTO `{$_PREFIX}calendar` VALUES(NULL, '$date', '$topic', '$content', $uid)");
    messageRedirect($_PWNDATA['cal']['name'],$_PWNDATA['cal']['new_event'],"calendar.php?view=date&amp;day=$date");
}

if ($_POST['action'] == "edit_event") {
    if ($user['level'] < $site_info['mod_rank']) {
        messageBack($_PWNDATA['cal']['name'],$_PWNDATA['cal']['only_mods']);
    }
    $date = $_POST['date'];
    $eid = $_POST['event'];
    $title = $_POST['subj'];
    $content = $_POST['content'];
    mysql_query("UPDATE `{$_PREFIX}calendar` SET `title`='$title' WHERE `id`=$eid");
    mysql_query("UPDATE `{$_PREFIX}calendar` SET `content`='$content' WHERE `id`=$eid");
    messageRedirect($_PWNDATA['cal']['name'],$_PWNDATA['cal']['edit_event'],"calendar.php?view=date&amp;day=$date");
}

if ($_GET['view'] == "del_event") {
    if ($user['level'] < $site_info['mod_rank']) {
        messageBack($_PWNDATA['cal']['name'],$_PWNDATA['cal']['only_mods']);
    }
    mysql_query("DELETE FROM `{$_PREFIX}calendar` WHERE `id`=" . $_GET['e']);
    messageRedirect($_PWNDATA['cal']['name'],$_PWNDATA['cal']['delete_event'],"calendar.php?view=date&amp;day=$date");
}

function printDay($day, $content, $upper) {
    return <<<END
<td class="forum_topic_content">
    <table class="borderless_table" width="100%">
      <tr>
        <td width="75%" height="24" style="border-style: none; border-width: 0px;" align="center">$upper</td>
        <td width="25%" height="24" class="calendar_day" align="center">$day</td>
      </tr>
      <tr>
        <td width="100%" colspan="2" height="60" style="border-style: none; border-width: medium; padding: 1px 1px 1px 1px" valign="top">$content</td>
      </tr>
    </table>
</td>
END;
}

drawSubbar("<a href=\"index.php\">" . $site_info['name'] . "</a> > {$_PWNDATA['cal']['name']}","<a href=\"calendar.php\">{$_PWNDATA['cal']['name']}</a>");

require 'sidebar.php';
$pane_title = "";
$content = "";

$mode = $_GET['view'];
$month = $_GET['mon'];
$year = $_GET['y'];


if ($mode == "") {
    $view_date = time();
    $month = date("m",$view_date);
    $year = date("y",$view_date);
    $mode = "viewmonth";
}

if ($mode == "viewmonth") {
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
    $content = $content . "<p align=\"center\"><font size=\"4\">" . date("F, Y",$time_view) . "</font><br /><font size=\"2\"><a href=\"calendar.php?view=viewmonth&amp;mon=" . (intval($month) - 1) . "&amp;y=$year\">" . date("F",mktime(0,0,0,intval($month) - 1,1,intval($year))) . "</a> | <a href=\"calendar.php?view=viewmonth&amp;mon=" . (intval($month) + 1) . "&amp;y=$year\">" . date("F",mktime(0,0,0,intval($month) + 1,1,intval($year))) . "</a></font></p>\n";
    $content = $content . "<table border=\"1\" style=\"border-collapse: collapse; border-width: 1; table-layout: fixed; border-color: #000000;\" width=\"100%\">\n";
    $content = $content . "<tr><td class=\"forum_thread_title\" align=\"center\">{$_PWNDATA['cal']['sunday']}</td><td class=\"forum_thread_title\" align=\"center\">{$_PWNDATA['cal']['monday']}</td><td class=\"forum_thread_title\" align=\"center\">{$_PWNDATA['cal']['tuesday']}</td><td class=\"forum_thread_title\" align=\"center\">{$_PWNDATA['cal']['wednesday']}</td><td class=\"forum_thread_title\" align=\"center\">{$_PWNDATA['cal']['thursday']}</td><td class=\"forum_thread_title\" align=\"center\">{$_PWNDATA['cal']['friday']}</td><td class=\"forum_thread_title\" align=\"center\">{$_PWNDATA['cal']['saturday']}</td></tr>";
    $month_started = 0;
    $days_left = $days_in_month;
    for ( $week = 1; $week  <= 6; $week  += 1) {
        $content = $content . "<tr>";
        for ( $day = 1; $day <= 7; $day += 1) {
            if ($week == 1) {
                if ($first_day == $day - 1) {
                    $month_started = 1;
                }
            }
            if ($month_started == 0) {
                $day_content = "<div style=\"width: 100%; text-align: center;\"><br /><a href=\"calendar.php?view=viewmonth&amp;mon=" . (intval($month) - 1) . "&amp;y=$year\">" . date("F",mktime(0,0,0,intval($month) - 1,1,intval($year))) . "</a></div>";
                $dayloop = "X";
                $top_cell = "";
            } else if ($month_started == 2) {
                $day_content = "<div style=\"width: 100%; text-align: center;\"><br /><a href=\"calendar.php?view=viewmonth&amp;mon=" . (intval($month) + 1) . "&amp;y=$year\">" . date("F",mktime(0,0,0,intval($month) + 1,1,intval($year))) . "</a></div>";
                $dayloop = "X";
                $top_cell = "";
            } else if ($month_started == 1) {
                $dayloop = $days_in_month - $days_left + 1; // The current day of the month.
                $today = getDay(mktime(0,0,0,intval($month),$dayloop,intval($year)));
                $day_results = mysql_query("SELECT * FROM `{$_PREFIX}calendar` WHERE `day`='" . $today . "'");
                $day_content = "";
                while ($query_row = mysql_fetch_array($day_results)) {
                    $day_content .= "- " . $query_row['title'] . "<br />\n";
                }
                if ($user['level'] >= $site_info['mod_rank']) {
                    $top_cell = "<a href=\"calendar.php?view=add&amp;day=$today\">{$_PWNICONS['calendar']['add']}</a> <a href=\"calendar.php?view=date&amp;day=$today\">{$_PWNICONS['calendar']['view']}</a>";
                } else {
                    $top_cell = "<a href=\"calendar.php?view=date&amp;day=$today\">{$_PWNICONS['calendar']['view']}</a>";
                }
                $days_left = $days_left - 1;
                if ($days_left <= 0) {
                    $month_started = 2;
                }
            }
            $content .= printDay($dayloop, $day_content, $top_cell);
        }
        $content .= "</tr>";
    }
    $content .= "</table>";
}

if ($mode == "date") {
    $date_info = split(",", $_GET['day']);
    $current_time = mktime(0,0,0,intval($date_info[1]),intval($date_info[0]),intval($date_info[2]));
    $month = intval($date_info[1]);
    $year = intval($date_info[2]);
    $month_name = date("F", $current_time);
    $panel_title = $panel_title . " &gt; " . date("l, F jS, Y", $current_time);
    $content .= "<a href=\"calendar.php?view=viewmonth&amp;mon=$month&amp;y=$year\">$month_name</a><br />";
    $content .= "<font size=\"4\">" . date("l, F jS, Y", $current_time) . "</font><br /><br />\n"; 
    $day_results = mysql_query("SELECT * FROM `{$_PREFIX}calendar` WHERE `day`='" . $_GET['day'] . "'");
    $day_stuff = $_GET['day'];
    $day_content = "<table class=\"forum_base\" width=\"100%\">";
    $num_results = 0;
    while ($query_row = mysql_fetch_array($day_results)) {
        $num_results++;
        $resultb = mysql_query("SELECT * FROM users WHERE id=" .  $query_row['user']);
        $post_author = mysql_fetch_array($resultb);
        $uid = $query_row['user'];
        $uname = $post_author['name'];
        $day_content .= "<tr><td colspan=\"2\" class=\"forum_thread_title\">";
        $day_content .= "<b>" .  $query_row['title'] . "</b> posted by <a href=\"forum.php?do=viewprofile&amp;id=$uid\">$uname</a></td></tr><tr><td class=\"forum_topic_content\">" . bbDecode($query_row['content']) . "</td><td class=\"forum_topic_content\" width=\"200\">";
        if ($user['level'] >= $site_info['mod_rank']) {
            $day_content = $day_content . " [<a href=\"calendar.php?view=edit&amp;e=" . $query_row['id'] . "\">{$_PWNDATA['admin']['forms']['edit']}</a>] [<a href=\"calendar.php?view=del_event&amp;e=" . $query_row['id'] . "\">{$_PWNDATA['admin']['forms']['delete']}</a>]";
        }
        $day_content .= "</td></tr>\n\n";
    }
    if ($num_results == 0) {
        $day_content = "<table class=\"forum_base\" width=\"100%\"><tr><td class=\"forum_topic_content\">{$_PWNDATA['cal']['no_events']}<a href=\"calendar.php?view=add&amp;day=$day_stuff\">{$_PWNDATA['cal']['add_one']}</a></td></tr>";
    }
    $content .= $day_content . "</table>";
    $content .= "<br /><a href=\"calendar.php?view=add&amp;day=$day_stuff\">{$_PWNDATA['cal']['event_add']}</a>";
}

if ($mode == "add") {
    $day = $_GET['day'];
    if ($user['level'] < $site_info['mod_rank']) {
        messageBack($_PWNDATA['cal']['name'],$_PWNDATA['cal']['only_mods']);
    }
    $userid = $user['id'];
    $date_info = split(",", $_GET['day']);
    $current_time = mktime(0,0,0,intval($date_info[1]),intval($date_info[0]),intval($date_info[2]));
    $panel_title = $panel_title . " &gt; " . date("l, F jS, Y", $current_time);
    $content .= <<<END
<form method="post" action="calendar.php" name="form">
<input type="hidden" name="action" value="add_event" />
<input type="hidden" name="day" value="$day" />
<input type="hidden" name="user" value="$userid" />
<table class="forum_base" width="100%">
<tr><td class="forum_topic_content">{$_PWNDATA['cal']['event_name']}</td>
<td class="forum_topic_content"><input type="text" name="subj" size="51" style="width:100%" /></td></tr>
<tr><td class="forum_topic_sig" colspan="2">{$_PWNDATA['cal']['event_desc']}</td></tr>
<tr><td class="forum_topic_sig" colspan="2">
END;
    $content .= printPoster("content");
    $content .= <<<END
<textarea rows="11" name="content" id="content" style="width:100%;" cols="20" class="content_editor"></textarea></td></tr>
<tr><td class="forum_topic_sig" colspan="2">
<input type="submit" value="{$_PWNDATA['cal']['event_add']}" name="sub" /></td></tr>
</table>
</form>
END;
}

if ($mode == "edit") {
    $eid = $_GET['e'];
    if ($user['level'] < $site_info['mod_rank']) {
        messageBack($_PWNDATA['cal']['name'],$_PWNDATA['cal']['only_mods']);
    }
    $userid = $user['id'];
    $results = mysql_query("SELECT * FROM `{$_PREFIX}calendar` WHERE `id`=$eid");
    $event = mysql_fetch_array($results);
    $title = $event['title'];
    $event_content = $event['content'];
    $date = $event['day'];
    $content .= <<<END
<form method="post" action="calendar.php" name="form">
<input type="hidden" name="action" value="edit_event" />
<input type="hidden" name="event" value="$eid" />
<input type="hidden" name="date" value="$date" />
<table class="forum_base" width="100%">
<tr><td class="forum_topic_content">{$_PWNDATA['cal']['event_name']}</td>
<td class="forum_topic_content"><input type="text" name="subj" value="$title" size="51" style="width:100%" /></td></tr>
<tr><td class="forum_topic_sig" colspan="2">{$_PWNDATA['cal']['event_desc']}</td></tr>
<tr><td class="forum_topic_sig" colspan="2">
END;
    $content .= printPoster("content");
    $content .= <<<END
<textarea rows="11" name="content" id="content" style="width:100%;" cols="20" class="content_editor">$event_content</textarea></td></tr>
<tr><td class="forum_topic_sig" colspan="2">
<input type="submit" value="{$_PWNDATA['cal']['event_save']}" name="sub" /></td></tr>
</table>
</form>
END;
}

print <<<END
<td valign="top">
<table class="borderless_table" width="100%">
END;

drawBlock($_PWNDATA['cal']['name'] . $panel_title,"",$content);

print <<<END
	</table>
        </td>
  </tr>
</table>
END;
require "footer.php";
?>
