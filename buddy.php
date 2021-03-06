<?php
/*
	This file is part of PHPwnage ("PwnBuddy" floating help tool)

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

if ($user['level'] > 0) {
// TODO: $showme = "display: none;" when we don't want to display normally.
    print <<<END

<!-- PwnBuddy -->
<script type="text/javascript">
//<![CDATA[
document.onkeydown = KeyCheck
var tempXa = 1
var tempYa = 1
var IE = document.all?true:false
if (!IE) document.captureEvents(Event.MOUSEMOVE)
document.onmousemove = getMouseXYA
var tempX = 0
var tempY = 0
function KeyCheck(e)
{
var keyid = (window.event) ? event.keyCode : e.keyCode
if (keyid == 18)
{
    document.getElementById('buddy').style.left = tempX + 'px'
    document.getElementById('buddy').style.top = tempY + 'px'
}
}
function forceToMouse() {
    document.getElementById('buddy').style.left = tempX + 'px'
    document.getElementById('buddy').style.top = tempY + 'px'
}
function buddyAlert(text) {
    showMe()
    forceToMouse()
    document.getElementById('tab5').innerHTML = text
    changeTab('tab5')
}
function getMouseXYA(e) {
  if (IE) {
    tempX = event.clientX + document.documentElement.scrollLeft;
    tempY = event.clientY + document.documentElement.scrollTop;
  } else {
    tempX = e.pageX
    tempY = e.pageY
  }
try {getMouseXY(e)
}
catch(err) {
window.status = err.message;
}
}
function hideMe() {
    document.getElementById('buddy').style.display = "none";
}
function showMe() {
    document.getElementById('buddy').style.display = "inline";
    document.getElementById('buddy_content').style.display = "inline";
}
function shadeMe() {
    if (document.getElementById('buddy_content').style.display == "none") {
        document.getElementById('buddy_content').style.display = "inline";
    } else {
        document.getElementById('buddy_content').style.display = "none";
    }
}
//]]>
</script>
<div id="buddy" style="width: 300px; border: 0px; position: absolute; top: 10px; left: 600px; $showme">
<table class="borderless_table" width="100%">
<tr><td class="block_ul">&nbsp;</td><td class="block_um"><font class="block_title_text">{$_PWNDATA['buddy']['name']}</font></td><td class="block_um" align="right"><font class="block_title_text"><a href="javascript:shadeMe()">^</a>&nbsp;<a href="javascript:hideMe()">X</a></font></td><td class="block_ur">&nbsp;</td></tr>
<tr><td class="block_ml">&nbsp;</td><td class="block_body" colspan="2"><div id="buddy_content">
END;
    // PwnBuddy content
    // Welcome message
    $current_time = date("G");
    if (intval($current_time) < 12) {
        print $_PWNDATA['buddy']['morning'] . ", " . $user['name'] . "!";
    } elseif ((intval($current_time) > 11) and (intval($current_time) < 20)) {
        print $_PWNDATA['buddy']['afternoon'] . ", " . $user['name'] . "!";
    } elseif (intval($current_time) > 19) {
        print $_PWNDATA['buddy']['evening'] . ", " . $user['name'] . "!";
    }
    print "<br />";
// Tabs, make use of custom visibilty javascript.
    print <<<END
<script type="text/javascript">
//<![CDATA[
function changeTab(tabname) {
document.getElementById('tab1').style.display = "none"
document.getElementById('sel_tab1').className = "tab_head_off"
document.getElementById('tab2').style.display = "none"
document.getElementById('sel_tab2').className = "tab_head_off"
document.getElementById('tab3').style.display = "none"
document.getElementById('sel_tab3').className = "tab_head_off"
document.getElementById('tab4').style.display = "none"
document.getElementById('sel_tab4').className = "tab_head_off"
document.getElementById('tab5').style.display = "none"
document.getElementById('sel_tab5').className = "tab_head_off"
document.getElementById(tabname).style.display = "block"
document.getElementById('sel_' + tabname).className = "tab_head_on"
}
//]]>
</script>
<ul class="menu">
<li id = "sel_tab1" class="tab_head_on">
<a onclick="javascript:changeTab('tab1')" href="javascript: void(null);">{$_PWNDATA['buddy']['inbox']}</a></li>
<li id = "sel_tab2" class="tab_head_off">
<a onclick="javascript:changeTab('tab2')" href="javascript: void(null);">{$_PWNDATA['buddy']['forums']}</a></li>
<li id = "sel_tab3" class="tab_head_off">
<a onclick="javascript:changeTab('tab3')" href="javascript: void(null);">{$_PWNDATA['buddy']['calendar']}</a></li>
<li id = "sel_tab4" class="tab_head_off">
<a onclick="javascript:changeTab('tab4')" href="javascript: void(null);">{$_PWNDATA['buddy']['help']}</a></li>
<li id = "sel_tab5" class="tab_head_off">
<a onclick="javascript:changeTab('tab5')" href="javascript: void(null);">{$_PWNDATA['buddy']['messages']}</a></li>
</ul>
<div id="tab1" class="tab_contents">
END;
    $userid = $user['id'];
    $unread_temp = override_sql_query("SELECT COUNT(`read`) FROM `{$_PREFIX}pms` WHERE `to`=$userid AND `read`=0 GROUP BY `read` ");
    $num_unread_t = mysql_fetch_array($unread_temp);
    $num_unread = $num_unread_t['COUNT(`read`)'];
    if ($num_unread == 0) {
        print "<a href=\"forum.php?do=pmbox\">{$_PWNDATA['pm']['you_have']}{$_PWNDATA['pm']['no_new']}</a>";
    } elseif ($num_unread == 1){
        print "<a href=\"forum.php?do=pmbox\">{$_PWNDATA['pm']['you_have']}$num_unread {$_PWNDATA['pm']['one_new']}</a>";
    } else {
        print "<a href=\"forum.php?do=pmbox\">{$_PWNDATA['pm']['you_have']}$num_unread {$_PWNDATA['pm']['some_new']}</a>";
    }
    print "<br />";
    $pmresult = override_sql_query("SELECT `id`,`from`,`title`,`read` FROM `{$_PREFIX}pms` WHERE `to`=" . $user['id'] . " ORDER BY id DESC LIMIT 10", $db);
    while ($row = mysql_fetch_array($pmresult)) {
        $resultb = override_sql_query("SELECT `id`,`name` FROM `{$_PREFIX}users` WHERE id='" . $row['from'] . "'" , $db);
        $rowb = mysql_fetch_array($resultb);
        $author = $rowb['name'];
        $authid = $rowb['id'];
        if ($row['read'] == 0) {
	        print "{$_PWNDATA['buddy']['new']} ";
	    }
        print "<a href=\"forum.php?do=readpm&amp;id=" . $row['id'] . "\"><b>" . $row['title'] . "</b></a> {$_PWNDATA['buddy']['from']} <a href=\"forum.php?do=viewprofile&amp;id=$authid\">$author</a><br />";
    }
    print <<<END
</div>
<div id="tab2" class="tab_contents" style="display: none;">
<b>{$_PWNDATA['buddy']['recent']}</b><br />
END;
    $post_results = override_sql_query("SELECT `id`,`board`,`title` FROM `{$_PREFIX}topics` ORDER BY `lastpost` DESC LIMIT 5");
    while ($topic = mysql_fetch_array($post_results)) {
        if (isReadable($user['level'],$topic['board'])) {
            if (substr($topic['title'], 0, 20) != $topic['title']) {
                $topicName = substr($topic['title'],0,20) . "...";
            } else {
            $topicName = $topic['title'];
            }
            print "<a href=\"forum.php?do=viewtopic&amp;id=" . $topic['id'] . "\">" . $topicName . "</a> {$_PWNDATA['buddy']['in']} " . getBoardName($topic['board']) . "<br />";
        }
    }
    print <<<END
</div>
<div id="tab3" class="tab_contents" style="display: none;">
<b>{$_PWNDATA['cal']['upcoming']}</b><br />
END;
    print "\n";
    $view_date = time();
    $month = date("m",$view_date);
    $year = date("y",$view_date);
    $day = date("j",$view_date); // The current day of the month.
    $today = getDay(mktime(0,0,0,intval($month),intval($day),intval($year)));
    $tomorrow = getDay(mktime(0,0,0,intval($month),intval($day)+1,intval($year)));
    $dayafter = getDay(mktime(0,0,0,intval($month),intval($day)+2,intval($year)));
    $day_results = override_sql_query("SELECT `id`,`title` FROM `{$_PREFIX}calendar` WHERE `day`='" . $today . "'");
    $events = "";
    while ($query_row = mysql_fetch_array($day_results)) {
        $events = $events . "- " . $query_row['title'] . "<br />\n";
    }
    print "<a href=\"calendar.php?view=date&amp;day=$today\">{$_PWNDATA['cal']['today']}</a>:<br />$events";
    $day_results = override_sql_query("SELECT `id`,`title` FROM `{$_PREFIX}calendar` WHERE `day`='" . $tomorrow . "'");
    $events = "";
    while ($query_row = mysql_fetch_array($day_results)) {
        $events = $events . "- " . $query_row['title'] . "<br />\n";
    }
    print "<a href=\"calendar.php?view=date&amp;day=$tomorrow\">{$_PWNDATA['cal']['tomorrow']}</a>:<br />$events";
    $day_results = override_sql_query("SELECT `id`,`title` FROM `{$_PREFIX}calendar` WHERE `day`='" . $dayafter . "'");
    $events = "";
    while ($query_row = mysql_fetch_array($day_results)) {        $events = $events . "- " . $query_row['title'] . "<br />\n";
    }
    print "<a href=\"calendar.php?view=date&amp;day=$dayafter\">{$_PWNDATA['cal']['day_after']}</a>:<br />$events";
    print <<<END
</div>
<div id="tab4" class="tab_contents" style="display: none;">
{$_PWNDATA['buddy']['help_message']}
END;
    print <<<END
</div>
<div id="tab5" class="tab_contents" style="display:none;">
{$_PWNDATA['buddy']['none']}
</div>
END;
    print <<<END
</div></td><td class="block_mr">&nbsp;</td></tr>
<tr><td class="block_bl"></td><td class="block_bm" colspan="2"></td><td class="block_br"></td></tr>
</table>
</div>
<!-- End PwnBuddy -->


END;
}
?>
