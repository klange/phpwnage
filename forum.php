<?php
/*
	This file is part of PHPwnage (Forum Module)

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

// error_reporting(6143); // Error reporting for debugging purposes.
require 'config.php';
$_POSTSPERPAGE = 10;    // Number of posts per page. This should move to a User Option!
$_THREADSPERPAGE = 10;  // Same. Threads per page in viewforum (new in 1.8)
$_CHECKPANDEMIC = true; // Check Pandemic status?
$CONFIG_MAIL = false;   // Should we send an email? This is buggy.
require 'includes.php';

function check_read($id,$userid) {
    $temp_res = mysql_query("SELECT * FROM `topics` WHERE id=$id");
    $topic = mysql_fetch_array($temp_res);
    $read_list = $topic['readby'];
    $split_list = explode(",",$read_list);
    if (in_array($userid, $split_list)) {
        $is_read = true;
    } else {
        $is_read = false;
    }
    return $is_read;
}

function check_read_forum($id,$userid) {
    $temp_res = mysql_query("SELECT * FROM `topics` WHERE board=$id");
    $was_read = true;
    while ($topic = mysql_fetch_array($temp_res)) {
        if (!check_read($topic['id'],$userid)) { $was_read = false; }
    }
    return $was_read;
}

function set_read($id,$userid) {
    $temp_res = mysql_query("SELECT * FROM `topics` WHERE id=$id");
    $topic = mysql_fetch_array($temp_res);
    $read_list = $topic['readby'];
    $split_list = explode(",",$read_list);
    if (!in_array($userid, $split_list)) {
        $read_list = $read_list . ",$userid";
        mysql_query("UPDATE `topics` SET `readby` = '" . mse($read_list) . "' WHERE `topics`.`id` =" . $id);
    }
}

function set_unread($id) {
    mysql_query("UPDATE `topics` SET `readby` = '' WHERE `topics`.`id` =" . $id);
}

function check_voted($id,$userid) {
    $temp_res = mysql_query("SELECT * FROM `polls` WHERE id=$id");
    $topic = mysql_fetch_array($temp_res);
    $read_list = $topic['voters'];
    $split_list = explode(",",$read_list);
    if (in_array($userid, $split_list)) {
        $is_read = true;
    } else {
        $is_read = false;
    }
    return $is_read;
}

function set_voted($id,$userid) {
    $temp_res = mysql_query("SELECT * FROM `polls` WHERE id=$id");
    $topic = mysql_fetch_array($temp_res);
    $read_list = $topic['voters'];
    $split_list = explode(",",$read_list);
    if (!in_array($userid, $split_list)) {
        $read_list = $read_list . ",$userid";
        mysql_query("UPDATE `polls` SET `voters` = '" . mse($read_list) . "' WHERE `polls`.`id` =" . $id);
    }
}

if ($_POST['action'] == "login") {
    $userresult = mysql_query("SELECT * FROM users WHERE UCASE(name)=UCASE('" . $_POST['uname'] . "')", $db);
    $tempuser = mysql_fetch_array($userresult);
    if ((strtoupper($_POST['uname']) == strtoupper($tempuser['name'])) and (md5($_POST['upass']) == $tempuser['password'])) {
        $_SESSION['user_name'] = $tempuser['name'];
        $_SESSION['user_pass'] = md5($_POST['upass']);
        $_SESSION['sess_id'] = time();
        $_SESSION['last_on'] = time();
        if ($_POST['remember'] == "ON") {
            setcookie("rem_user", $tempuser['name'], time()+60*60*24*365*10); // Hehehe, a cookie that'll expire in 10 years!
            setcookie("rem_pass", md5($_POST['upass']), time()+60*60*24*365*10); // lol
            setcookie("rem_yes", "yes", time()+60*60*24*365*10);
        } else {
            setcookie("rem_user", "", time()+60*60*24*365*10); // Hehehe, a cookie that'll expire in 10 years!
            setcookie("rem_pass", "", time()+60*60*24*365*10); // lol
            setcookie("rem_yes", "no", time()+60*60*24*365*10);
        }
        $result = mysql_query("SELECT * FROM users WHERE UCASE(name)=UCASE('" . $_SESSION['user_name'] . "')", $db);
        $user = mysql_fetch_array($result);
        mysql_query("DELETE FROM `sessions` WHERE `user`=" . $user['id'] . "");
        mysql_query("INSERT INTO `sessions` VALUES (" . $_SESSION['sess_id'] . ", " . $user['id'] . ", " . $_SESSION['last_on'] . ");");
        if ($_POST['admin']) {
            messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['signedin'],"admin.php");
        } else {
            messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['signedin'],"forum.php");
        }
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
        $name = $_POST['uname'];
        mysql_query("INSERT INTO `security` ( `time` , `passused`, `where`, `ip` ) VALUES ( '" . time() . "', '" . $_POST['upass'] . "', 'Forum, $name', '" . $ip . "' );");
        messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['failed_login']);
    }
}

// If a new topic is being posted
if ($_POST['action'] == "new_topic") {
    $content = $_POST['content'];
    $results =  mysql_query("SELECT * FROM `boards` WHERE `id`=" . $_POST['board']);
    if (!$board = mysql_fetch_array($results)) {
        messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['invalid_board']);
    }
    if (!isWriteableTopic($user['level'],$_POST['board'])) {
        messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['improper_permissions']);
    }
    if (strlen($_POST['subj']) < 3) {
        messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['topic_too_short']);
    }
    if ($_POST['add_poll'] == "on") {
	    $has_poll = "1";
	    // Add the poll.
	    mysql_query("INSERT INTO `polls` ( `id`, `title`, `op1_name`) VALUES (NULL, '" . mse($_POST['p_name']) . "', '" . mse($_POST['op1']) . "');");
	    $testresult = mysql_query("SELECT * FROM `polls` ORDER BY `id` DESC LIMIT 1");
	    $poll = mysql_fetch_array($testresult);
	    $poll_id = $poll['id'];
    } else {
	    $has_poll = "0";
	    $poll_id = "0";
    }
    mysql_query("INSERT INTO `topics` ( `id` , `authorid` , `board` , `title`, `has_poll`, `poll_id` ) VALUES (NULL , " . $_POST['user'] . ", " . $_POST['board'] . ", '" . mse($_POST['subj']) . "', " . $has_poll . ", " . $poll_id . " );");
    $result = mysql_query("SELECT * FROM `topics` ORDER BY `id` DESC LIMIT 1");
    $topic = mysql_fetch_array($result);
    $ip=$_SERVER['REMOTE_ADDR'];
    mysql_query("INSERT INTO `posts` ( `id` , `topicid` , `authorid` , `content`, `time`, `ip`) VALUES ( NULL , " . $topic['id'] . " , " . $_POST['user'] . " , '" . mse($content) . "' , " . time() . " , '" . $ip . "');");
    $result = mysql_query("SELECT * FROM `posts` ORDER BY `id` DESC LIMIT 1");
    $reply = mysql_fetch_array($result);
    mysql_query("UPDATE `topics` SET `lastpost` = " . $reply['id'] . " WHERE `topics`.`id` =" . $topic['id']);
    mysql_query("ALTER TABLE `posts`  ORDER BY `id`");
    mysql_query("ALTER TABLE `topics`  ORDER BY `id`");
    messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['new_topic_added'],"forum.php?do=viewtopic&amp;id=" . $topic['id']);
}

// If a new PM is being sent
if ($_POST['action'] == "new_pm") {
    if (strlen($_POST['subj']) < 3) {
        messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['pm_too_short']);
    }
    $pmdate = time();
    $toname = $_POST['toline'];
    $result = mysql_query("SELECT * FROM `users` WHERE `name`='$toname'");
    $temp_user = mysql_fetch_array($result);
    $pmto = $temp_user['id'];
    $pmtitle = $_POST['subj'];
    $pmcontent = $_POST['content'];
    $pmfrom = $user['id'];
    mysql_query("INSERT INTO `pms` ( `id` , `to` , `from` , `title` , `content` , `read` , `time` )
VALUES (
NULL , '$pmto', '$pmfrom', '$pmtitle', '$pmcontent', '0', '$pmdate'
);");
    messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['pm_sent'],"forum.php?do=pmbox");
}

// If a new topic is being posted
if ($_POST['action'] == "new_reply") {
    $content = $_POST['content'];
    $topic = $_POST['topic'];
    $ip=$_SERVER['REMOTE_ADDR'];
    $topic_sql = mysql_query("SELECT * FROM `topics` WHERE `id`=$topic");
    if ($this_topic = mysql_fetch_array($topic_sql)) {
        if (!isWriteable($user['level'],$this_topic['board'])) {
            messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['improper_permission']);
        }
        if ($this_topic['locked'] == 1) {
            if ($user['level'] < $site_info['mod_rank']) {
                messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['locked_topic_post']);
            }
        }
    } else {
        messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['invalid_topic']);
    }

    set_unread($topic);
    mysql_query("INSERT INTO `posts` ( `id` , `topicid` , `authorid` , `content` , `time` , `ip` ) VALUES ( NULL , '" . $topic . "', '" . $user['id'] . "', '" . mse($content) . "' , '" . time() . "', '" . $ip . "' );");
    $result = mysql_query("SELECT * FROM `posts` ORDER BY `id` DESC LIMIT 1");
    $reply = mysql_fetch_array($result);
    mysql_query("UPDATE `topics` SET `lastpost` = '" . $reply['id'] . "' WHERE `topics`.`id` =" . $topic);
    mysql_query("ALTER TABLE `posts`  ORDER BY `id`");
    messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['new_reply_added'],"forum.php?do=viewtopic&amp;last=1&amp;id=" . $topic);
}

if ($_POST['action'] == "vote_poll") {
	$vote = $_POST['poll'];
	$pid = $_POST['pid'];
	$tid = $_POST['tid'];
	$uid = $user['id'];
	if (check_voted($pid, $uid)) {
		messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['poll_already_voted']);
	}
	$topic_sql = mysql_query("SELECT * FROM `topics` WHERE `id`=$tid");
	$this_topic = mysql_fetch_array($topic_sql);
	if ($this_topic['locked'] == 1) {
		if ($user['level'] < 2) {
		messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['locked_topic_poll']);
		}
	}
	set_voted($pid,$user['id']);
	$poll_sql = mysql_query("SELECT * FROM `polls` WHERE `id`=$pid");
	$poll = mysql_fetch_array($poll_sql);
	$poll_ops = split(",",$poll['op1_name']);
	$poll_votes = split(",",$poll['op1_votes']);
	$poll_count = count($poll_ops);
	$stri = "";
	for ($i=0;$i<$poll_count;$i++) {
		if (!isset($poll_votes[$i])) {
			$poll_votes[$i] = 0;
		}
		$a = $poll_votes[$i];
		if ($i == $vote) {
			$a = $a + 1;
		}
		$stri = $a . ",";
	}
	$stri = substr($stri,0,strlen($stri)-1);
	mysql_query("UPDATE `polls` SET `op1_votes`=" . $stri . " WHERE `id`=$pid");
	messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['vote_cast'],"forum.php?do=viewtopic&amp;last=1&amp;id=" . $tid);
}

// If an old post is being edited
if ($_POST['action'] == "edit_reply") {
    $content = $_POST['content'];
    mysql_query("UPDATE `posts` SET `content` = '" . mse($content) . "' WHERE `posts`.`id` =" . $_POST['id'] . ";");
    messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['post_edited'],"forum.php?do=viewtopic&amp;id=" . $_POST['topic']);
}

// If a topic title is being changed
if ($_POST['action'] == "edit_title") {
    $title = $_POST['title'];
    mysql_query("UPDATE `topics` SET `title` = '" . mse($title) . "' WHERE `topics`.`id` =" . $_POST['topicid'] . ";");
    messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['topic_title_edited'],"forum.php");
}

// If a topic is being moved
if ($_POST['action'] == "move_topic") {
    $board = $_POST['board'];
    mysql_query("UPDATE `topics` SET `board` = $board WHERE `topics`.`id` =" . $_POST['topid'] . ";");
    messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['topic_moved'],"forum.php?do=viewtopic&amp;id=" . $_POST['topid']);
}

// If an old post is being edited
if ($_POST['action'] == "edit_profile") {
    $userid = $user['id'];
    if ($_POST['adm'] == "true") {
        $userid = $_POST['id'];
    }
    mysql_query("UPDATE `users` SET `name` = '" . mse($_POST['name']) . "' WHERE `users`.`id` =" . $userid);
    mysql_query("UPDATE `users` SET `sig` = '" . mse($_POST['sig']) . "' WHERE `users`.`id` =" . $userid);
    mysql_query("UPDATE `users` SET `avatar` = '" . mse($_POST['avatar']) . "' WHERE `users`.`id` =" . $userid);
    mysql_query("UPDATE `users` SET `email` = '" . mse($_POST['email']) . "' WHERE `users`.`id` =" . $userid);
    mysql_query("UPDATE `users` SET `aim` = '" . mse($_POST['aim']) . "' WHERE `users`.`id` =" . $userid);
    mysql_query("UPDATE `users` SET `msn` = '" . mse($_POST['msn']) . "' WHERE `users`.`id` =" . $userid);
    mysql_query("UPDATE `users` SET `yahoo` = '" . mse($_POST['yah']) . "' WHERE `users`.`id` =" . $userid);
    mysql_query("UPDATE `users` SET `icq` = '" . mse($_POST['icq']) . "' WHERE `users`.`id` =" . $userid);
    mysql_query("UPDATE `users` SET `live` = '" . mse($_POST['live']) . "' WHERE `users`.`id` =" . $userid);
    mysql_query("UPDATE `users` SET `xfire` = '" . mse($_POST['xfire']) . "' WHERE `users`.`id` =" . $userid);
    mysql_query("UPDATE `users` SET `pand` = '" . mse($_POST['pand']) . "' WHERE `users`.`id` =" . $userid);
    mysql_query("UPDATE `users` SET `theme` = '" . mse($_POST['theme']) . "' WHERE `users`.`id` =" . $userid);
    mysql_query("UPDATE `users` SET `color` = '" . mse($_POST['color']) . "' WHERE `users`.`id` =" . $userid);
    if ($_POST['sbonforum'] == "on") {
        $sbon = 1;
    } else {
        $sbon = 0;
    }
    mysql_query("UPDATE `users` SET `sbonforum` = " . $sbon . " WHERE `users`.`id` =" . $userid);
    if ($_POST['apass'] != "") {
	    if ($_POST['apass'] == $_POST['cpass']) {
	        mysql_query("UPDATE `users` SET `password` = '" . md5($_POST['apass']) . "' WHERE `users`.`id` =" . $userid);
	    }
    }
    messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['profile_edited'],"forum.php");
}

// If a new user is registering
if ($_POST['action'] == "newuser") {
    $name = mse($_POST['name']);
    $email = mse($_POST['email']);
    $pass = $_POST['pass'];
    $code = $_POST['code'];
    $results = mysql_query("SELECT * FROM `users` WHERE `name`='" . $name . "'");
    $check_name = mysql_fetch_array($results);
    if ($check_name != null) {
        messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['already_registered']);
    }
    $results = mysql_query("SELECT * FROM `users` WHERE `email`='" . $email . "'");
    $check_mail = mysql_fetch_array($results);
    if ($check_mail != null) {
        messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['already_registered_mail']);
    }
    if ($code != $_SESSION['seccode']) {
        messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['security_code']);
    }
    if ($email != mse($_POST['cemail'])){
        messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['email_no_match']);
    }
    if ($pass != $_POST['cpass']){
        messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['password_no_match']);
    }
    // If we get to this point, then the user's registration checks out. We'll start by sending the email.
    $message = "";
    if ($CONFIG_MAIL) {
        $body = $_PWNDATA['forum']['confirm_email'][1] . $site_info['name'] . ".\n";
        $body = $body . $_PWNDATA['forum']['confirm_email'][2] . "'$name' with the password '$pass'.\n";
        $body = $body . $_PWNDATA['forum']['confirm_email'][3] . $conf_email . ".";
        if (!mail($email, $_PWNDATA['forum']['confirm_email'][4] . $site_info['name'], $body)) {
            $message = $message . $_PWNDATA['forum']['send_email_failed'] . "<br />";
        }
    }
    mysql_query("INSERT INTO `users` ( `id` , `name` , `email` , `password` , `sig` , `avatar` ) VALUES ( NULL , '" . $name . "', '" . $email . "', '" . md5($pass) . "', '', '' );");
    $_POST['action'] = "";
    $message = $message . $_PWNDATA['forum']['create_account_success'];
    messageRedirect($_PWNDATA['forum_page_title'],$message,"forum.php?do=login");
}

// Delete a post
if ($_GET['do'] == "delete") {
	if ($user['level'] < 2) {
		messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['not_authorized_del_post']);
	}
	mysql_query("DELETE FROM `posts` WHERE `posts`.`id` =" . $_GET['id']);
	messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['post_deleted'],"forum.php");
}

// Delete a topic
if ($_GET['do'] == "deltop") {
	if ($user['level'] < 2) {
		messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['not_authorized_del_topic']);
	}
	mysql_query("DELETE FROM `topics` WHERE `topics`.`id` =" . $_GET['id']);
	mysql_query("DELETE FROM `posts` WHERE `posts`.`topicid` =" . $_GET['id']);
	messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['topic_posts_deleted'],"forum.php");
}

// Sticky a topic
if ($_GET['do'] == "sticktop") {
	if ($user['level'] < 2) {
		messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['not_authorized_sticky_topic']);
	}
	mysql_query("UPDATE `topics` SET `stick` = 1 WHERE `topics`.`id`=" . $_GET['id']);
	messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['topic_stickied'],"forum.php?do=viewtopic&amp;id=" . $_GET['id']);
}

// Unsticky a topic
if ($_GET['do'] == "unsticktop") {
	if ($user['level'] < 2) {
		messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['not_authorized_sticky_topic']);
	}
	mysql_query("UPDATE `topics` SET `stick` = 0 WHERE `topics`.`id`=" . $_GET['id']);
	messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['topic_unstickied'],"forum.php?do=viewtopic&amp;id=" . $_GET['id']);
}

// Lock a topic
if ($_GET['do'] == "locktop") {
	if ($user['level'] < 2) {
		messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['not_authorized_lock_topic']);
	}
	mysql_query("UPDATE `topics` SET `locked` = 1 WHERE `topics`.`id`=" . $_GET['id']);
	messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['topic_locked'],"forum.php?do=viewtopic&amp;id=" . $_GET['id']);
}

// Unlock a topic
if ($_GET['do'] == "unlocktop") {
	if ($user['level'] < 2) {
		messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['not_authorized_unlock_topic']);
	}
	mysql_query("UPDATE `topics` SET `locked` = 0 WHERE `topics`.`id`=" . $_GET['id']);
	messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['topic_unlocked'],"forum.php?do=viewtopic&amp;id=" . $_GET['id']);
}

// XXX: Begin function calls for output

// Return the display for the right side of the subbar
function post_sub_r($userid) {
    global $_PWNDATA;
    if (isset($_SESSION['sess_id'])){
        $post_sub_r = "<a href=\"forum.php?do=logoff\">{$_PWNDATA['forum']['logout']}</a> | <a href=\"forum.php?do=editprofile\">{$_PWNDATA['forum']['edit_profile']}</a> | ";
        $unread_temp = mysql_query("SELECT `pms`.*, COUNT(`read`) FROM `pms` WHERE `to`=$userid AND `read`=0 GROUP BY `read` ");
        $num_unread_t = mysql_fetch_array($unread_temp);
        $num_unread = $num_unread_t['COUNT(`read`)'];
        if ($num_unread == 0) {
            $post_sub_r = $post_sub_r . "<a href=\"forum.php?do=pmbox\">{$_PWNDATA['pm']['view']}</a>";
        } elseif ($num_unread == 1) {
            $post_sub_r = $post_sub_r . "<a href=\"forum.php?do=pmbox\">{$_PWNDATA['pm']['you_have']}$num_unread {$_PWNDATA['pm']['one_new']}</a>";
        } else {
            $post_sub_r = $post_sub_r . "<a href=\"forum.php?do=pmbox\">{$_PWNDATA['pm']['you_have']}$num_unread {$_PWNDATA['pm']['some_new']}</a>";
        }
    } else {
        $post_sub_r = "<a href=\"forum.php?do=login\">{$_PWNDATA['forum']['login']}</a> or <a href=\"forum.php?do=newuser\">{$_PWNDATA['forum']['register']}</a>";
    }
    return $post_sub_r;
}

// Return the preview box Iframe
function previewBox() {
    return <<<END
    <iframe name="previewbox" id="previewbox" height="0px" style="width: 500px; border: 0px; position: absolute; top: 0px; left: 0px;"></iframe>
END;
}

// Return the preview box javascript
function previewBoxScript() {
    return <<<END
<script type="text/javascript">
//<![CDATA[
function showPrev(url) {
    if (url == 'EXIT') {
        frames['previewbox'].location.href = "about:blank";
    } else {
        frames['previewbox'].location.href = "forum.php?do=preview&a=" + url
    }
    return true;
}
var IE = document.all?true:false;
if (!IE) document.captureEvents(Event.MOUSEMOVE);
document.onmousemove = getMouseXY;
var tempX = 0;
var tempY = 0;
var blam = false;
var blama = false;
var magicnumber = 0;
function getMouseXY(e) {
    if (IE) {
        tempX = event.clientX + document.body.scrollLeft;
        tempY = event.clientY + document.body.scrollTop;
    } else {
        tempX = e.pageX;
        tempY = e.pageY;
    }
    if (!blam && !blama) {
        document.getElementById('previewbox').style.width = "0px"
    } else {
        blam = false
        if (blama) {
            magicnumber = 520;
        }
        blama = false;
        document.getElementById('previewbox').style.width = "500px";
        document.getElementById('previewbox').style.left = (tempX + 10 - magicnumber) + 'px';
        document.getElementById('previewbox').style.top = (tempY + 10) + 'px';
        magicnumber = 0;
        return true;
    }
}
//]]>
</script>
END;
}

// Forum post preview generator
if ($_GET['do'] == "preview") {
    standardHeaders("",false);
    print <<<END
<script type="text/javascript">
//<![CDATA[
function autofitIframe(id){
    try {
        parent.document.getElementById(id).style.height = "60px";
    } catch (err) {
        window.status = err.message;
    }
    try {
	    if (!window.opera && !document.mimeType && document.all && document.getElementById){
		    parent.document.getElementById(id).style.height=this.document.body.offsetHeight+"px";
	    }
	    else if(document.getElementById) {
		    parent.document.getElementById(id).style.height=this.document.body.scrollHeight+"px";
	    }
    } catch(err) {
        window.status = err.message;
    }
}
//]]>
</script>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
</head>
<body onload="autofitIframe('previewbox')" style="margin: 0px; padding: 0px;">
<table style="border-style: solid; border-width: 0px; padding: 0px; margin: 0px; border-collapse: collapse;" width="100%">
<tr><td class="pan_body" width="100%"><div style="border-width: 1px; border-style: solid; padding: 1px;"><font class="forum_body">

END;
    $bbtext = str_replace("!NL!","\n",$_GET['a']);
    $bbtext = str_replace("\\'","'",$bbtext);
    $bbtext = str_replace("\\\"","\"",$bbtext);
    $bbtext = str_replace("\\\\","\\",$bbtext);
    print BBDecode($bbtext);
    print "\n</font>\n</div></td></tr></table>\n</body>\n</html>";
    die("");
}

// Set an entire board as read by this user.
if ($_GET['do'] == "setread") {
    $id = $_GET['id'];
    $temp_res = mysql_query("SELECT * FROM `topics` WHERE board=$id");
    while ($topic = mysql_fetch_array($temp_res)) { 
        set_read($topic['id'],$user['id']); 
    }
    $_GET['do'] = "";
}

// Forum root.
if ($_GET['do'] == "") {
    $post_title_add = "";
    $post_sub_add = "";
    $post_sub_r = post_sub_r($user['id']);
    $post_content = "";
    $cats = mysql_query("SELECT * FROM categories ORDER BY orderid", $db);
    
    while ($cat = mysql_fetch_array($cats)) {
        $category = $cat['id'];
        $block_content =  <<<END
	<div id="category_$category" style="border: 0px">
		<table class="forum_base" width="100%">
END;
        $result = mysql_query("SELECT * FROM boards WHERE `catid`=$category ORDER BY orderid", $db);
        while ($row = mysql_fetch_array($result)) {
            if (!($row['vis_level'] > $user['level'])) {
                if ($row['link'] == "NONE") {
                    $readmb = check_read_forum($row['id'],$user['id']);
                    $idd = $row['id'];
                    if ($readmb) {
                        $read_or_not = "<img src=\"smiles/forum_read.png\" align=\"left\" alt=\"{$_PWNDATA['forum']['board_has_no']}\"/>";
                    } else {
                        $read_or_not = "<a href=\"forum.php?do=setread&amp;id=$idd\"><img src=\"smiles/forum_unread.png\" align=\"left\" alt=\"{$_PWNDATA['forum']['board_has_new']}\" /></a>";
                    }
                    $block_content = $block_content .  <<<END
	<tr><td rowspan="2" width="32">$read_or_not</td>
		<td><a href="forum.php?do=viewforum&amp;id=
END;
                    $block_content = $block_content . $row['id'] . "\">" . $row['title'];
                    $block_content = $block_content . "</a></td><td rowspan=\"2\" width=\"30%\">";
                    $resulta = mysql_query("SELECT * FROM topics WHERE board='" . $row['id'] . "' ORDER BY lastpost DESC", $db);
                    $topic = mysql_fetch_array($resulta);
                    $resulta = mysql_query("SELECT * FROM posts WHERE topicid='" . $topic['id'] . "' ORDER BY id DESC LIMIT 1", $db);
                    $post = mysql_fetch_array($resulta);
                    $resulta = mysql_query("SELECT * FROM users WHERE id='" . $post['authorid'] . "'" , $db);
                    $poster= mysql_fetch_array($resulta);
                    $authid = $poster['id'];
                    $resulta = mysql_query("SELECT COUNT(*) FROM topics WHERE board='" . $row['id'] . "' ORDER BY lastpost DESC", $db);
                    $counter = mysql_fetch_array($resulta);
                    $topics_in_board = $counter["COUNT(*)"];
                    $post_time = date("M jS, g:i a", $post['time']);

                    $post_bb = "[b]Posted by:[/b] " . $poster['name'] . "!NL!" . substr(str_replace("\n","!NL!",$post['content']),0,500);
                    $post_bb = str_replace("\r","",$post_bb);
                    $post_bb = str_replace("\"","&quot;",$post_bb);
                    $post_bb = str_replace("'","\\'",$post_bb);
                    $post_bb = str_replace("<","&lt;",$post_bb);
                    $post_bb = str_replace(">","&gt;",$post_bb);
                    $spazm = "onmousemove=\"blama=true\" onmouseout=\"showPrev('EXIT');\" onmouseover=\"showPrev('$post_bb');\"";   

                    $block_content = $block_content . "<center><font size=\"2\"><strong>{$_PWNDATA['forum']['last']}: <a href=\"forum.php?do=viewtopic&amp;last=1&amp;id=" . $topic['id'] . "\" $spazm>" . $topic['title'] . "</a></strong><br />{$_PWNDATA['forum']['by']}: <a href=\"forum.php?do=viewprofile&amp;id=$authid\">" . $poster['name'] . "</a> $post_time</font></center></td>";
                    $block_content = $block_content . "<td rowspan=\"2\" align=\"center\" valign=\"top\" width=\"70\"><font size=\"2\">$topics_in_board {$_PWNDATA['forum']['topics']}</font></td>";
                    $block_content = $block_content . "<td rowspan=\"2\" align=\"center\" valign=\"top\" width=\"70\"><font size=\"2\">" . getPostsInBoard($row['id']) . " {$_PWNDATA['forum']['posts']}</font></td></tr>";
                    $block_content = $block_content . "\n	<tr><td><font size=\"2\">" . $row['desc'] . "</font></td></tr>";
                } else {
                    // Has a link.
                    $link = $row['link'];
                    $block_content = $block_content .  <<<END
	<tr><td rowspan="2" width="32"><img src="smiles/globe.png" alt="{$_PWNDATA['forum']['board_weblink']}"/></td>
END;
                    $block_content = $block_content . "<td><a href=\"$link\">" . $row['title'];
                    $block_content = $block_content . "</a></td><td rowspan=\"2\" width=\"30%\"></td>";
                    $block_content = $block_content . "<td rowspan=\"2\" align=\"center\" valign=\"top\" width=\"70\"></td></tr>";
                    $block_content = $block_content . "\n	<tr><td><font size=\"2\">" . $row['desc'] . "</font></td></tr>";
                }
            }
        }
        $block_content = $block_content . "</table></div>";
        $post_content = $post_content . makeBlock("<a href=\"javascript:flipVisibility('category_$category')\">" . $cat['name'] . "</a>","&nbsp;",$block_content);
    } // End category
    $use_previewbox = "yes";
}



// Login page
if ($_GET['do'] == "login") {
    $post_title_add = "";
    $post_sub_add = "";
    $adminlog = $_GET['admin'];
    $block_content = <<<END
		<table class="forum_base" width="100%">
            <tr><td align="center">
                <form action="forum.php" method="post">
                  <input type="hidden" name="admin" value="$adminlog" />
                  <input type="hidden" name="action" value="login" />
                  {$_PWNDATA['profile']['username']}:<br />
                  <input type="text" name="uname" size="20" /><br />
                  {$_PWNDATA['profile']['password']}:<br />
                  <input type="password" name="upass" size="20" /><br />
                  <input type="checkbox" name="remember" value="ON" />{$_PWNDATA['forum']['remember_me']}<br />
                  <input type="submit" value="{$_PWNDATA['forum']['login']}" name="B1" />
                </form>
            </td></tr>
        </table>
END;
    $post_content = makeBlock("&nbsp;",$_PWNDATA['forum']['login'],$block_content);
}

// Captcha. Sort of.
// FIXME: This thing sucks. Find an open-source PHP captcha library!
if ($_GET['do'] == "secimg") {
    header("Content-type: image/png");
    srand(time());
    $randnum = rand(100000,999999);
    $_SESSION['seccode'] = $randnum;
    $im = imagecreatetruecolor(48,16);
    $white = imagecolorallocate($im, 255, 255, 255);
    $black = imagecolorallocate($im, 0, 0, 0);
    $red = imagecolorallocate($im, 240, 0, 0);
    $green = imagecolorallocate($im, 0, 240, 0);
    $blue = imagecolorallocate($im, 0, 0, 240);
    $yellow = imagecolorallocate($im, 200, 200, 0);
    $grey = imagecolorallocate($im, 100, 100, 100);
    imagefill($im,0,0,$white);
    $randarr = str_split($randnum);
    ImageString ($im, 1, 0, 0, $randarr[0], $black); 
    ImageString ($im, 1, 8, 8, $randarr[1], $red);
    ImageString ($im, 1, 16, 0, $randarr[2], $green); 
    ImageString ($im, 1, 24, 8, $randarr[3], $blue); 
    ImageString ($im, 1, 32, 0, $randarr[4], $yellow); 
    ImageString ($im, 1, 40, 8, $randarr[5], $grey);  
    imagepng($im);
    die ('');
}

// Register a new member
if ($_GET['do'] == "newuser") {
    $post_title_add = "";
    $post_sub_add = "";
    
    $block_content = <<<END
		<table class="forum_base" width="100%">
            <tr><td><form method="post" action="forum.php"><font class="forum_base_text">
              <input type="hidden" name="action" value="newuser" />
              {$_PWNDATA['profile']['username']}: <input type="text" name="name" size="20" /><br />
              {$_PWNDATA['profile']['email']}: <input type="text" name="email" size="20" /><br />
              {$_PWNDATA['profile']['confirm']}: <input type="text" name="cemail" size="20" /><br />
              {$_PWNDATA['profile']['password']}: <input type="password" name="pass" size="20" /><br />
              {$_PWNDATA['profile']['confirm']}: <input type="password" name="cpass" size="20" /><br />
              <img src="forum.php?do=secimg" alt="{$_PWNDATA['forum']['secimg']}" /><br />
              {$_PWNDATA['forum']['sec_code']}: <input type="text" name="code" size="20" /><br />
              <input type="submit" value="{$_PWNDATA['forum']['register']}" /></font>
            </form></td></tr></table>
END;
    $post_content = makeBlock("&nbsp;",$_PWNDATA['forum']['register'],$block_content);
}

// Show the topics in this board.
if ($_GET['do'] == "viewforum") {
    $result = mysql_query("SELECT * FROM boards WHERE id='" . $_GET['id'] . "'", $db);
    $board = mysql_fetch_array($result);
    if ($board['vis_level'] > $user['level']) {
        messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['improper_permission']);
    }
    $post_title_add = " :: " . $board['title'];
    $post_sub_add = " > <a href=\"forum.php?do=viewforum&amp;id=" . $board['id'] . "\">" . $board['title'] . "</a>";
    $post_sub_r = post_sub_r($user['id']);
    $block_content = "";
    $block_content = $block_content .   <<<END
	<table class="mod_set"><tr>
END;
    if (!($board['top_level'] > $user['level'])) {
        $block_content = $block_content . drawButton("forum.php?do=newtopic&amp;id=" . $board['id'], $_PWNDATA['forum']['new_topic']);
    }
    if (!isset($_GET['p'])) {
        $page = 0;
    } else {
        $page = ($_GET['p'] - 1) * $_THREADSPERPAGE;
    }
    if ($page > 0) {
        $block_content = $block_content . drawButton("forum.php?do=viewforum&amp;id=" . $board['id'] . "&amp;p=" . ($page / $_THREADSPERPAGE), $_PWNDATA['forum']['previous_page']);
    }
    $temp_mysql = mysql_query("SELECT COUNT(*) FROM topics WHERE board='" . $board['id'] . "'", $db);
    $temp_res = mysql_fetch_array($temp_mysql);
    $total_posts = $temp_res['COUNT(*)'];
    if ($total_posts > $page + $_THREADSPERPAGE) {
        $block_content = $block_content . drawButton("forum.php?do=viewforum&amp;id=" . $board['id'] . "&amp;p=" . ($page / $_THREADSPERPAGE + 2), $_PWNDATA['forum']['next_page']);
    }
    $block_content = $block_content .   <<<END
		</tr></table>
		<table class="forum_base" width="100%">
END;
    $result = mysql_query("SELECT * FROM topics WHERE board='" . $board['id'] . "' ORDER BY stick DESC, lastpost DESC LIMIT $page, $_THREADSPERPAGE", $db);
    while ($row = mysql_fetch_array($result)) {
        $readmb = check_read($row['id'],$user['id']);
        $resultb = mysql_query("SELECT * FROM users WHERE id='" . $row['authorid'] . "'" , $db);
        $rowb = mysql_fetch_array($resultb);
        $resultc = mysql_query("SELECT * FROM posts WHERE topicid='" . $row['id'] . "' ORDER BY id ASC LIMIT 1", $db);
        $firstpost = mysql_fetch_array($resultc);
        $resultc = mysql_query("SELECT * FROM posts WHERE topicid='" . $row['id'] . "' ORDER BY id DESC LIMIT 1", $db);
        $rowc = mysql_fetch_array($resultc);
        $result_posts = mysql_query("SELECT COUNT(*) FROM posts WHERE topicid='" . $row['id'] . "'", $db);
        $posts_counter = mysql_fetch_array($result_posts);
        $resultd = mysql_query("SELECT * FROM users WHERE id='" . $rowc['authorid'] . "'" , $db);
        $rowd = mysql_fetch_array($resultd);
        $post_bb = "[b]Posted by:[/b] " . $rowb['name'] . "!NL!" . substr(str_replace("\n","!NL!",$firstpost['content']),0,500);
        $post_time = date("M jS, g:i a", $rowc['time']);
        $post_bb = str_replace("\r","",$post_bb);
        $post_bb = str_replace("\"","&quot;",$post_bb);
        $post_bb = str_replace("'","\\'",$post_bb);
        $post_bb = str_replace("<","&lt;",$post_bb);
        $post_bb = str_replace(">","&gt;",$post_bb);
        $spazm = "onmousemove=\"blam=true\" onmouseout=\"showPrev('EXIT');\" onmouseover=\"showPrev('$post_bb');\"";
        $post_bb = "[b]Posted by:[/b] " . $rowd['name'] . "!NL!" . substr(str_replace("\n","!NL!",$rowc['content']),0,500);
        $post_bb = str_replace("\r","",$post_bb);
        $post_bb = str_replace("\"","&quot;",$post_bb);
        $post_bb = str_replace("'","\\'",$post_bb);
        $post_bb = str_replace("<","&lt;",$post_bb);
        $post_bb = str_replace(">","&gt;",$post_bb);
        $spazma = "onmousemove=\"blama=true\" onmouseout=\"showPrev('EXIT');\" onmouseover=\"showPrev('$post_bb');\"";
        if ($readmb) {
            $read_or_not = "<img src=\"smiles/read.png\" align=\"left\" alt=\"{$_PWNDATA['forum']['new_posts']}\"/>";
        } else {
            $read_or_not = "<img src=\"smiles/unread.png\" align=\"left\" alt=\"{$_PWNDATA['forum']['no_new_posts']}\"/>";
        }
        if ($row['has_poll'] == 1) {
	        if ($readmb) {
	            $read_or_not = "<img src=\"smiles/readp.png\" align=\"left\" alt=\"{$_PWNDATA['forum']['no_new_posts']}, {$_PWNDATA['forum']['poll']}\"/>";
	        } else {
	            $read_or_not = "<img src=\"smiles/unreadp.png\" align=\"left\" alt=\"{$_PWNDATA['forum']['new_posts']}, {$_PWNDATA['forum']['poll']}\"/>";
	        }
	        $read_or_not = $read_or_not . "<font class=\"forum_base_text\"><b>{$_PWNDATA['forum']['poll']}</b></font> ";
        }
        if ($row['locked'] == 1) {
	        if ($readmb) {
	            $read_or_not = "<img src=\"smiles/lread.png\" align=\"left\" alt=\"{$_PWNDATA['forum']['no_new_posts']}, {$_PWNDATA['forum']['locked']}\"/>";
	        } else {
	            $read_or_not = "<img src=\"smiles/lunread.png\" align=\"left\" alt=\"{$_PWNDATA['forum']['new_posts']}, {$_PWNDATA['forum']['locked']}\"/>";
	        }
	        $read_or_not = $read_or_not . "<font class=\"forum_base_text\"><b>{$_PWNDATA['forum']['locked']}</b></font> ";
        }
        if ($row['stick'] == 1) {
	        if ($readmb) {
	            $read_or_not = "<img src=\"smiles/sread.png\" align=\"left\" alt=\"{$_PWNDATA['forum']['no_new_posts']}, {$_PWNDATA['forum']['sticky']}\"/>";
	        } else {
	            $read_or_not = "<img src=\"smiles/sunread.png\" align=\"left\" alt=\"{$_PWNDATA['forum']['new_posts']}, {$_PWNDATA['forum']['sticky']}\"/>";
	        }
	        $read_or_not = $read_or_not . "<font class=\"forum_base_text\"><b>{$_PWNDATA['forum']['sticky']}</b></font> ";
        }
        $diver = $row['id'];
        $block_content = $block_content .   <<<END
	<tr>
		<td>$read_or_not<div id="title_$diver" style="display: inline;" $spazm><a href="forum.php?do=viewtopic&amp;id=
END;
        $block_content = $block_content .  $row['id'] . "\">" . $row['title'];
        $top_temp = $row['id'];
        $author = $rowb['name'];
        $authid = $rowb['id'];
        $posts_in_topic = $posts_counter['COUNT(*)'];
        $pages = (floor(($posts_in_topic - 1) / $_POSTSPERPAGE));
        $pagination = "";
        if ($pages > 0) {
            $pagination = " &nbsp;&nbsp;&nbsp;" . $_PWNDATA['forum']['goto'] . ": ";
            for ($page_count = 1; $page_count <= $pages + 1; $page_count += 1) {
                $pagination = $pagination . "<a href=\"forum.php?do=viewtopic&amp;id=$top_temp&amp;p=$page_count\">$page_count</a>";
                if ($page_count != $pages + 1) {
                    $pagination = $pagination . ", ";
                }
            }
        }
        $toptitle = $row['title'];
        if ($user['level'] >= $site_info['mod_rank']) {
        $edtitle = <<<END
<div id="titleedit_$diver" style="display: none;">
<form action="forum.php" method="post" style="display: inline;">
<input type="hidden" name="action" value="edit_title" />
<input type="hidden" name="topicid" value="$diver" />
<input type="text" name="title" value="$toptitle" />
<input type="submit" name="sub" value="{$_PWNDATA['forum']['edit_title']}" />
</form>
</div>
<a href="javascript: flipVisibility('title_$diver'); flipVisibility('titleedit_$diver');"><font style="font-size: 8px">{$_PWNDATA['forum']['edit_title']}</font></a>

END;
        } else {
            $edtitle = " ";
        }
        $block_content = $block_content .  "</a></div>\n$edtitle<br /><font size=\"2\">\n{$_PWNDATA['forum']['author']}: <a href=\"forum.php?do=viewprofile&amp;id=$authid\">$author</a>$pagination</font></td><td rowspan=\"1\" width=\"30%\">";
        $authid = $rowd['id'];
        $block_content = $block_content .  "<center>\n<font size=\"2\"><strong><a href=\"forum.php?do=viewtopic&amp;id=$top_temp&amp;last=1\" $spazma>{$_PWNDATA['forum']['last_post']}</a> {$_PWNDATA['forum']['by']}:</strong> <a href=\"forum.php?do=viewprofile&amp;id=$authid\">" . $rowd['name'] . "</a><br />{$_PWNDATA['forum']['at']}: $post_time</font></center></td></tr>";
    }
    $block_content = $block_content .   <<<END
	</table>
END;
    $post_content = makeBlock($board['title'], "&nbsp;",$block_content);
    $use_previewbox = "yes";
}

// Show the PM box
if ($_GET['do'] == "pmbox") {
    $post_title_add = " :: {$_PWNDATA['pm']['view']}";
    $post_sub_add = " > <a href=\"forum.php?do=pmbox\">{$_PWNDATA['pm']['view']}</a>";
    $post_sub_r = post_sub_r($user['id']);
    $post_content = "";
    $post_content = $post_content .  <<<END
	
      <tr>
        <td width="100%">
    <table class="borderless_table" width="100%">
      <tr>
        <td class="pan_ul">&nbsp;</td>
        <td class="pan_um">
        <font class="pan_title_text">
END;
    $post_content = $post_content . $_PWNDATA['pm']['view'];
    $post_content = $post_content .  <<<END
	</font>
        <td class="pan_um">
        <p align="right"><font class="pan_title_text">
END;
    $post_content = $post_content .  "";
    $post_content = $post_content .  <<<END
	</font></td>
        <td class="pan_ur">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_ml">&nbsp;</td>
        <td class="pan_body" valign="top" colspan="2">
		<font class="forum_base_text">
END;
    if(isset($_SESSION['sess_id'])) {
        $post_content = $post_content . <<<END
<table class="mod_set">
<tr><td>
	<table class="forum_button">
	<tr>
    <td class="but_left"></td>
    <td class="but_mid">
    <font class="forum_button_text">
END;
        $post_content = $post_content . "<a href=\"forum.php?do=newpm\">{$_PWNDATA['pm']['new_pm']}</a>";
        $post_content = $post_content . <<<END
</font></td>
<td class="but_right"></td>
</tr></table></td><td>
<table class="forum_button">
<tr>
	<td class="but_left"></td> 
    <td class="but_mid">
    <font class="forum_button_text">
END;
        $post_content = $post_content . "<a href=\"forum.php?do=delpm&amp;id=ALL\">{$_PWNDATA['pm']['empty_box']}</a>";
        $post_content = $post_content . <<<END
</font></td>
<td class="but_right"></td>
</tr>
</table>
</td></tr>
</table>
END;
    }
    $post_content = $post_content .  <<<END
		<table class="forum_base" width="100%">
END;

    $pmresult = mysql_query("SELECT * FROM pms WHERE `to`=" . $user['id'] . " ORDER BY id DESC", $db);
    while ($row = mysql_fetch_array($pmresult)) {
        $readmb = $row['read'];
        if ($readmb == 1) {
            $read_or_not = "<img src=\"smiles/read.png\" align=\"left\" alt=\"\"/>";
        } else {
            $read_or_not = "<img src=\"smiles/unread.png\" align=\"left\" alt=\"**\"/>";
        }
        $post_content = $post_content .  <<<END
	<tr>
		<td>$read_or_not<a href="forum.php?do=readpm&amp;id=
END;
//"
        $resultb = mysql_query("SELECT * FROM users WHERE id='" . $row['from'] . "'" , $db);
        $rowb = mysql_fetch_array($resultb);
        $post_content = $post_content . $row['id'] . "\">" . $row['title'];
        $author = $rowb['name'];
        $authid = $rowb['id'];
        $tim = date("F j, Y (g:ia T)", $row['time']);
        $post_content = $post_content . "</a><br /><font size=\"2\">{$_PWNDATA['pm']['from']}: <a href=\"forum.php?do=viewprofile&amp;id=$authid\">$author</a>, {$_PWNDATA['pm']['sent_at']} $tim</td></td></tr>";
    }

    $post_content = $post_content .  <<<END
	</table>
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
}

// Delete PM
if ($_GET['do'] == "delpm") {
    $tomustbe = $user['id'];
    if ($_GET['id'] != "ALL") {
        $pmresult = mysql_query("SELECT * FROM pms WHERE `id`=" . $_GET['id'] . " AND `to`=$tomustbe", $db);
    } else {
        $pmresult = mysql_query("SELECT * FROM pms WHERE `to`=$tomustbe", $db);
    }
    $pm = mysql_fetch_array($pmresult);
    if (!isset($_SESSION['sess_id'])) {
        messageBack($_PWNDATA['pm']['view'],$_PWNDATA['pm']['must_be_logged_in']);
    }
    if (($user['id'] != $pm['to']) and ($user['level'] < 3)) {
        messageBack($_PWNDATA['pm']['view'],$_PWNDATA['pm']['only_admins']);
    }
    if ($_GET['id'] != "ALL") {
	    mysql_query("DELETE FROM `pms` WHERE `pms`.`id` =" . $_GET['id']);
	    messageRedirect($_PWNDATA['pm']['view'],$_PWNDATA['pm']['pm_deleted'],"forum.php?do=mbox");
    } else {
	    mysql_query("DELETE FROM `pms` WHERE `to`=$tomustbe");
	    messageRedirect($_PWNDATA['pm']['view'],$_PWNDATA['pm']['box_emptied'],"forum.php?do=mbox");
    }
}

// View a PM
if ($_GET['do'] == "readpm") {
    $pmresult = mysql_query("SELECT * FROM pms WHERE `id`=" . $_GET['id'], $db);
    $pm = mysql_fetch_array($pmresult);
    $resultb = mysql_query("SELECT * FROM users WHERE id='" . $pm['from'] . "'" , $db);
    $fromuser = mysql_fetch_array($resultb);
    if (($user['id'] != $pm['to']) and ($user['level'] < 3)) {
        messageBack($_PWNDATA['pm']['view'],$_PWNDATA['pm']['only_admins']);
    }
    mysql_query("UPDATE `pms` SET `read` =1 WHERE `pms`.`id` =" . $pm['id']);
    $post_title_add = " :: {$_PWNDATA['pm']['view']} :: {$_PWNDATA['pm']['reading']} '" . $pm['title'] . "'";
    $post_sub_add = " > <a href=\"forum.php?do=pmbox\">{$_PWNDATA['pm']['view']}</a> > {$_PWNDATA['pm']['reading']} \"" . $pm['title'] . "\"";
    $post_sub_r = post_sub_r($user['id']);
    $post_content = "";
    $post_content = $post_content .  <<<END
	
      <tr>
        <td width="100%">
    <table class="borderless_table" width="100%">
      <tr>
        <td class="pan_ul">&nbsp;</td>
        <td class="pan_um">
        <font class="pan_title_text">
END;
    $post_content = $post_content . $pm['title'] . " {$_PWNDATA['pm']['from']} <a href=\"forum.php?do=viewprofile&amp;id=" . $fromuser['id'] . "\">" . $fromuser['name'] . "</a>";
    $post_content = $post_content .  <<<END
	</font>
        <td class="pan_um">
        <p align="right"><font class="pan_title_text">
END;
    $post_content = $post_content .   "{$_PWNDATA['pm']['sent_at']} " . date("F j, Y (g:ia T)", $pm['time']);
    $post_content = $post_content .  <<<END
	</font></td>
        <td class="pan_ur">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_ml">&nbsp;</td>
        <td class="pan_body" valign="top" colspan="2">
		<font class="forum_base_text"><table class="mod_set"><tr>
END;
    $post_content = $post_content . drawButton("forum.php?do=newpm&amp;to=$replyto&amp;s=$replytitle",$_PWNDATA['pm']['reply']);
    $post_content = $post_content . drawButton("forum.php?do=delpm&amp;id=$pid",$_PWNDATA['pm']['delete']);
    $post_content = $post_content . drawButton("forum.php?do=newpm&amp;to=$replyto&amp;s=$replytitle&amp;q=$pid",$_PWNDATA['pm']['quote']);
    $post_content = $post_content .  <<<END
		</tr></table><table class="forum_base" width="100%"><tr><td><font class="forum_body">
END;
    $post_content = $post_content . BBDecode($pm['content']);
    $post_content = $post_content .  <<<END
	</font></td></tr></table>
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
}

// New PM (compose)
if ($_GET['do'] == "newpm") {
    if (!isset($_SESSION['sess_id'])){
        messageBack($_PWNDATA['pm']['view'],$_PWNDATA['pm']['not_logged_in']);
    }
    if ($_GET['to'] != "") {
        $result = mysql_query("SELECT * FROM users WHERE id='" . $_GET['to'] . "'", $db);
        $touser = mysql_fetch_array($result);
        $tousername = $touser['name'];
    }
    $quoted = "";
    if ($_GET['q'] != "") {
        $result = mysql_query("SELECT * FROM pms WHERE id='" . $_GET['q'] . "'", $db);
        $quotedpm = mysql_fetch_array($result);
        $result = mysql_query("SELECT * FROM users WHERE id='" . $quotedpm['from'] . "'", $db);
        $quoteduser = mysql_fetch_array($result);
        $quoted = "[quote][b]{$_PWNDATA['pm']['original_message']} " . $quoteduser['name'] . ":[/b]\n" . $quotedpm['content']. "[/quote]\n";
    }
    $subjto = $_GET['s'];
    $post_title_add = " :: " . $_PWNDATA['pm']['composing'];
    $post_sub_add = " > " . $_PWNDATA['pm']['composing'];
    $post_sub_r = post_sub_r($user['id']);
    $post_content = "";
    $post_content = $post_content .  <<<END
	
      <tr>
        <td width="100%">
    <table class="borderless_table" width="100%">
      <tr>
        <td class="pan_ul">&nbsp;</td>
        <td class="pan_um">
        <font class="pan_title_text">
END;
    $post_content = $post_content . $_PWNDATA['pm']['composing'];
    $post_content = $post_content .  <<<END
	</font></td>
        <td class="pan_um">
        <p align="right"><font class="pan_title_text">
END;
    $post_content = $post_content .  "";
    $post_content = $post_content .  <<<END
	</font></td>
        <td class="pan_ur">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_ml">&nbsp;</td>
        <td class="pan_body" valign="top" colspan="2">
		<font class="forum_base_text">
END;
    $post_content = $post_content .  printPoster('content') . <<<END
<form action="forum.php" method="post" name="form">
<input type="hidden" name="action" value="new_pm">
{$_PWNDATA['pm']['to']}: <br />
<input type="text" name="toline" size="51" style="width:100%" value="$tousername"><br />
{$_PWNDATA['pm']['subject']}: <br />
<input type="text" name="subj" size="51" style="width:100%" value="$subjto"><br />
{$_PWNDATA['pm']['body']}:<br />
<textarea rows="11" name="content" style="width:100%;" cols="20">$quoted</textarea><br />
<input type="submit" value="{$_PWNDATA['pm']['send']}" name="sub">
END;
    $post_content = $post_content . "<input type=\"hidden\" name=\"board\" value=\"" . $board['id'] . "\">";
    $post_content = $post_content . "<input type=\"hidden\" name=\"user\" value=\"" . $user['id'] . "\">";
    $post_content = $post_content .  <<<END
	</form></font></td>
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
}

// Show the posts in this topic.
if ($_GET['do'] == "viewtopic") {
    $result = mysql_query("SELECT * FROM topics WHERE id='" . $_GET['id'] . "'", $db);
    $topic = mysql_fetch_array($result);
    $resultb = mysql_query("SELECT * FROM boards WHERE id='" . $topic['board'] . "'", $db);
    $board = mysql_fetch_array($resultb);
    if ($board['vis_level'] > $user['level']) {
        messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['not_permitted_topic']);
    }
    set_read($topic['id'],$user['id']);
    if ($topic['locked'] == 0) {
        $islocked = false;
    } else {
        if ($user['level'] >= $site_info['mod_rank']) {
            $islocked = false;
        } else {
            $islocked = true;
        }
    }
    $post_title_add = " :: " . $topic['title'];
    $post_sub_r = post_sub_r($user['id']);
    $post_sub_add = " > <a href=\"forum.php?do=viewforum&amp;id=" . $board['id'] . "\">" . $board['title'] . "</a> > <a href=\"forum.php?do=viewtopic&amp;id=" . $topic['id'] . "\">" . $topic['title'] . "</a>";
    $post_content = "";
    $post_content = $post_content .  <<<END
	
      <tr>
        <td width="100%">
    <table class="borderless_table" width="100%">
      <tr>
        <td class="pan_ul">&nbsp;</td>
        <td class="pan_um">
        <font class="pan_title_text">
END;
    if ($topic['locked'] == 1) {
        $post_content = $post_content . "[{$_PWNDATA['forum']['locked']}] ";
    }
    $post_content = $post_content . "<a href=\"#qreply_bm\">" . $topic['title'];
    $post_content = $post_content .  <<<END
</a>
	</font></td>
        <td class="pan_um">
        <p align="right"><font class="pan_title_text">
END;
    $resultb = mysql_query("SELECT * FROM users WHERE id='" .  $topic['authorid'] . "'", $db);
    $author = mysql_fetch_array($resultb);
    $post_content = $post_content .  "{$_PWNDATA['forum']['by']}: " . $author['name'];
    $post_content = $post_content .  <<<END
	</font></td>
        <td class="pan_ur">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_ml">&nbsp;</td>
        <td class="pan_body" valign="top" colspan="2">
		<font class="forum_base_text">
		<table class="forum_base" width="100%">
END;
    if ($topic['has_poll'] == 1) {
        $pollresults = mysql_query("SELECT * FROM `polls` WHERE `id`=" . $topic['poll_id']);
        $poll = mysql_fetch_array($pollresults);
        // Our topic has a poll, draw the voting array.
        $pid = $poll['id'];
        $tid = $topic['id'];
        $post_content = $post_content . <<<END
	<tr width="100%">
		<td colspan="2" align="center"><form name="pollresponse" method="post" action="forum.php">
		<input type="hidden" name="action" value="vote_poll" />
		<input type="hidden" name="pid" value="$pid" />
		<input type="hidden" name="tid" value="$tid" />
		<table class="borderless_table">
END;
        $post_content = $post_content . "<tr style=\"border: 0px\"><td style=\"border: 0px\" colspan=\"2\" align=\"center\"><font class=\"forum_body\"><b>" . $poll['title'] . "</b></font></td></tr>\n\n";
        $hasVoted = check_voted($poll['id'],$user['id']);
        if ($user['level'] < 1) {
	        $hasVoted = true;
        }
        $poll_options = split(",",$poll['op1_name']);
        $poll_votes = split(",",$poll['op1_votes']);
        $poll_count = count($poll_options);
        $totalVotes = 0;
        for ($i=0;$i<$poll_count;$i++) {
	        if (!isset($poll_votes[$i])) {
		        $poll_votes[$i] = 0;
	        }
	        $totalVotes += $poll_votes[$i];
        }
        if ($totalVotes == 0) {
	        $totalVotes = 1;
        }
        $widthOfBar = 300; // For easy changing
        for ($i=0;$i<$poll_count;$i++) {
	        if ($hasVoted == false) {
		        $bounce = "<input type=\"radio\" name=\"poll\" value=\"$i\">";
	        }
	        $post_content = $post_content . "<tr style=\"border: 0px\"><td style=\"border: 0px\" align=\"right\">$bounce<font class=\"forum_body\">" . $poll_options[$i] . "</font></td>\n";
	        $wid = ($poll_votes[$i] / $totalVotes) * $widthOfBar;
	        $post_content = $post_content . "<td style=\"border: 0px\" align=\"left\"><img src=\"smiles/poll_bars/$i/poll_left.png\" alt=\"[\"/><img src=\"smiles/poll_bars/$i/poll_mid.png\" height=\"10\" width=\"$wid\" alt=\"$wid\"/><img src=\"smiles/poll_bars/$i/poll_right.png\" alt=\"]\"/><font size=\"1\"> (" . $poll_votes[$i] . ") </font></td></tr>\n";
        }
        if ($hasVoted == false) {
	        $submitPoll = "<input type=\"submit\" value=\"{$_PWNDATA['forum']['vote']}\" />";
        }
        $post_content = $post_content . <<<END
		</table>$submitPoll</form></td>
	</tr>
END;
    }
    if (!isset($_GET['p'])) {
        $page = 0;
    } else {
        $page = ($_GET['p'] - 1) * $_POSTSPERPAGE;
    }
    if (isset($_GET['rep'])) {
        $page = (floor($_GET['rep'] / $_POSTSPERPAGE)) * $_POSTSPERPAGE;
    }
    if (isset($_GET['last'])) {
        $temp_mysql = mysql_query("SELECT COUNT(*) FROM posts WHERE topicid='" . $topic['id'] . "'", $db);
        $temp_res = mysql_fetch_array($temp_mysql);
        $last_rep_id = $temp_res['COUNT(*)'] - 1;
        $page = (floor($last_rep_id / $_POSTSPERPAGE)) * $_POSTSPERPAGE;
    }
    $result = mysql_query("SELECT * FROM posts WHERE topicid='" . $topic['id'] . "' LIMIT $page, $_POSTSPERPAGE", $db);
    while ($row = mysql_fetch_array($result)) {
        $resultb = mysql_query("SELECT * FROM users WHERE id='" .  $row['authorid'] . "'", $db);
        $post_author = mysql_fetch_array($resultb);
        $topglow = "";
        if ($post_author['level'] >= $site_info['mod_rank']) {
            $topglow = "class=\"glow_mod\"";
        }
        if ($post_author['level'] >= $site_info['admin_rank']) {
            $topglow = "class=\"glow_admin\"";
        }
        $post_content = $post_content .  <<<END
	<tr>
		<td width=\"15%\" valign=top $topglow>
END;
        if ($post_author['avatar'] != "") {
            $ava = "<img src=\"" . $post_author['avatar'] . "\" alt=\"" . $post_auther['name']  . "'s {$_PWNDATA['profile']['avatar']}\"/><br />";
        } else {
            $ava = "";
        }
        $pCount = postCount($post_author['id']);
        if ($post_author['level'] >= $site_info['admin_rank']) {
            $ava = "\n<font class='adm_name'>" . getRankName($post_author['level'],$site_info,$pCount) . "</font><br />" . $ava;
        } elseif ($post_author['level'] >= $site_info['mod_rank']) {
            $ava = "\n<font class='mod_name'>" . getRankName($post_author['level'],$site_info,$pCount) . "</font><br />" . $ava;
        } elseif ($post_author['level'] < $site_info['mod_rank']) {
            $ava = "\n" . getRankName($post_author['level'],$site_info,$pCount) . "<br />" . $ava;
        }
        // User info panel shown on side...
        $contenta = BBDecode($row['content']);
        $contentb = BBDecode($post_author['sig']);
        $authid = $post_author['id'];
        $auth_info = ""; // Define our place to build the user's info, 
        $has_messenger = false; // then we'll go through the IMs...
        $authmsn = "";
        $authaim = "";
        $authyahoo = "";
        $authicq = "";
        $authlive = "";
        $authxf = "";
        if ($post_author['msn'] != "") {
            $has_messenger = true;
            $authmsn = $post_author['msn'];
            $auth_info = $auth_info . "<a href=\"forum.php?do=viewprofile&amp;id=$authid\"><img src=\"smiles/msn.png\" border=\"0\" /></a>";
        }
        if ($post_author['yahoo'] != "") {
            $has_messenger = true;
            $authyahoo = $post_author['yahoo'];
            $auth_info = $auth_info . "<a href=\"forum.php?do=viewprofile&amp;id=$authid\"><img src=\"smiles/yahoo.png\" border=\"0\" /></a>";
        }
        if ($post_author['aim'] != "") { // AIM we're actually going to do something usefull for...
            $has_messenger = true;
            $authaim = $post_author['aim'];
            $auth_info = $auth_info . "<a href=\"aim:goim?screenname=$authaim&amp;message=Hello+Are+you+there?\"><img src=\"smiles/aim.png\" border=\"0\" /></a>";
        }
        if ($post_author['icq'] != "") { // ICQ as well...
            $has_messenger = true;
            $authicq = $post_author['icq'];
            $auth_info = $auth_info . "<a href=\"http://wwp.icq.com/scripts/search.dll?to=$authicq\"><img src=\"smiles/icq.png\" border=\"0\" /></a>";
        }
        if ($post_author['xfire'] != "") { // xfire
            $has_messenger = true;
            $authxf = $post_author['xfire'];
            $auth_info = $auth_info . "<a href=\"http://www.xfire.com/profile/$authxf\"><img src=\"smiles/xfire.png\" border=\"0\" /></a>";
        }
        if ($post_author['live'] != "") { // xfire
            $has_messenger = true;
            $authlive = str_replace(" ","+",$post_author['live']);
            $auth_info = $auth_info . "<a href=\"http://live.xbox.com/en-US/profile/profile.aspx?pp=0&amp;GamerTag=$authlive\"><img src=\"smiles/live.png\" border=\"0\" /></a>";
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
	            $auth_info = $auth_info . "<img src=\"smiles/pan.png\" border=\"0\" /></a>";
	            } else {
	            $auth_info = $auth_info . "<img src=\"smiles/panoff.png\" border=\"0\" /></a>";
	            }
            } else {
                $auth_info = $auth_info . "<img src=\"smiles/pan.png\" border=\"0\" /></a>";
            }
        }
        if ($has_messenger) {
            $messaging = "[b]" . $post_author['name'] . "[/b]!NL![img]smiles/mess.png[/img]!NL![img]smiles/aim.png[/img]: $authaim!NL![img]smiles/msn.png[/img]: $authmsn!NL![img]smiles/yahoo.png[/img]: $authyahoo!NL![img]smiles/icq.png[/img]: $authicq!NL![img]smiles/xfire.png[/img]: $authxf!NL![img]smiles/live.png[/img]: $authlive!NL![img]smiles/pan.png[/img]: $authpand!NL!";
            $auth_info = "<img src=\"smiles/mess.png\" onmousemove=\"blam=true\" onmouseout=\"showPrev('EXIT');\" onmouseover=\"showPrev('$messaging')\" /><br />" . $auth_info;
        }
        $postinfo = "";
        if ($user['level'] > 0) {
	        // Yes, this can exclude some members, but we don't really care because they're BANNED. (Level = 0)
	        $postinfo = "<br />$pCount posts";
        }
        $post_content = $post_content . "<font class=\"forum_user\"><a href=\"forum.php?do=viewprofile&amp;id=$authid\">" . $post_author['name'] . "</a><br />" . $ava . $auth_info . $postinfo . "</font>";
        $post_content = $post_content . "</td>\n<td valign=top style=\"padding:5px\"><font class=\"forum_time\"><p align=\"right\">{$_PWNDATA['forum']['posted_at']} " . date("F j, Y (g:ia T)", $row['time']) . "</p></font>\n";
        $post_content = $post_content . "<font class=\"forum_body\">\n" . $contenta;
        $post_content = $post_content . "\n</font>\n<br /><hr color=\"#555555\" size=\"1\" width=\"50%\">\n<font class=\"forum_body\">" . $contentb;
        $post_content = $post_content . "\n</font><br />\n<table class=\"mod_set\"><tr>\n";
        // Is this the viewing member's post?
        if (($user['id'] == $post_author['id']) or ($user['level'] >= $site_info['mod_rank'])) {
            $post_content = $post_content . drawButton("forum.php?do=editreply&amp;id=" . $row['id'],$_PWNDATA['forum']['edit']);
        }
        // Moderation Tools 
        if ($user['level'] >= $site_info['mod_rank']) {
            if ($user['level'] >= $site_info['admin_rank']) {
                $post_content = $post_content . drawButton("javascript:buddyAlert('IP: " . $row['ip'] . "');",$_PWNDATA['forum']['ip']);
            } // Only administrators can view the IP of a post. This is to keep moderators from h4xing
            $post_content = $post_content . drawButton("javascript:buddyAlert('" . $_PWNDATA['forum']['delete_confirm'] . " <a href=\\'forum.php?do=delete&amp;id=" . $row['id'] . "\\'>" . $_PWNDATA['forum']['delete_confirm_accept'] . "</a>');", $_PWNDATA['forum']['delete']);
        }
        if (($user['id'] != $post_author['id']) and (!($board['post_level'] > $user['level'])) and ($islocked == false)) {
            $post_content = $post_content . drawButton("<a href=\"forum.php?do=newrepl&amp;id=" . $topic['id'] . "&amp;quote=" . $row['id'],$_PWNDATA['forum']['quote']);
        }
        $post_content = $post_content . "</tr></table></td></tr>";
    }
    $post_content = $post_content .  <<<END
	</table><table border="0"><tr>
END;
    if ((!($board['post_level'] > $user['level'])) and ($islocked == false)) {
        $post_content = $post_content . drawButton("forum.php?do=newreply&amp;id=" . $topic['id'],$_PWNDATA['forum']['add_reply']);
    }
    if ($user['level'] >= $site_info['mod_rank']) {
        $post_content = $post_content . drawButton("javascript:buddyAlert('" . $_PWNDATA['forum']['delete_confirm'] . " <a href=\\'forum.php?do=deltop&amp;id=" . $topic['id'] . "\\'>" . $_PWNDATA['forum']['delete_confirm_accept'] . "</a>');", $_PWNDATA['forum']['del_topic']);
        if ($topic['stick'] == 0) { // Stick
            $post_content = $post_content . drawButton("forum.php?do=sticktop&amp;id=" . $topic['id'],$_PWNDATA['forum']['stick_topic']);
        } else { // Unstick
            $post_content = $post_content . drawButton("forum.php?do=unsticktop&amp;id=" . $topic['id'],$_PWNDATA['forum']['unstick_topic']);
        }
        if ($topic['locked'] == 0) {
            $post_content = $post_content . drawButton("forum.php?do=locktop&amp;id=" . $topic['id'],$_PWNDATA['forum']['lock_topic']);
        } else {
            $post_content = $post_content . drawButton("forum.php?do=unlocktop&amp;id=" . $topic['id'],$_PWNDATA['forum']['unlock_topic']);
        }
        $post_content = $post_content . <<<END
<td>
<div id="movebutton" style="display:inline;">
	<table class="forum_button">
	<tr>
    <td class="but_left"></td>
    <td class="but_mid">
    <font class="forum_button_text">
END;
        $post_content = $post_content . "<a href=\"javascript:flipVisibility('movebutton'); flipVisibility('movebox');\">{$_PWNDATA['forum']['move_topic']}</a>";
        $post_content = $post_content . <<<END
	</font></td>
    <td class="but_right"></td>
  </tr>
</table>
</div>
</td>
END;
        $top_id = $topic['id'];
        $post_content = $post_content . <<<END
<td><div id="movebox" style="display:none;">
<form action="forum.php" method="post" style="display:inline;">
<input type="hidden" name="action" value="move_topic">
<input type="hidden" name="topid" value="$top_id">
<SELECT name="board">
END;
        $result = mysql_query("SELECT * FROM `categories` ORDER BY `orderid`");
        while ($cat = mysql_fetch_array($result)) {
	        $post_content = $post_content . "\n<OPTGROUP label=\"" . $cat['name'] . "\">";
	        $catid = $cat['id'];
	        $resultb = mysql_query("SELECT * FROM `boards` WHERE `catid`=$catid ORDER BY `orderid`");
	        while ($board = mysql_fetch_array($resultb)) {
		        if ($user['level'] >= $board['vis_level']) {
		            if ($topic['board'] == $board['id']) {
		            $post_content = $post_content . "\n<OPTION selected label=\"" . $board['title'] . "\" value=" . $board['id'] . ">" . $board['title'] . "</OPTION>";
		            } else {
		            $post_content = $post_content . "\n<OPTION label=\"" . $board['title'] . "\" value=" . $board['id'] . ">" . $board['title'] . "</OPTION>";
		            }
		        }	
	        }
	        $post_content = $post_content . "\n</OPTGROUP>";
        }
        $post_content = $post_content . "</SELECT>\n<input type=\"submit\" value=\"{$_PWNDATA['forum']['move_topic']}\"></form></div></td>";
    }
    $temp_mysql = mysql_query("SELECT COUNT(*) FROM posts WHERE topicid='" . $topic['id'] . "'", $db);
    $posts_counter = mysql_fetch_array($temp_mysql);
    $posts_in_topic = $posts_counter['COUNT(*)'];
    $pages = (floor(($posts_in_topic - 1) / $_POSTSPERPAGE));
    $top_id = $topic['id'];
    if ($pages > 0) {
        $post_content = $post_content . "<td> &nbsp;&nbsp;&nbsp;{$_PWNDATA['forum']['goto']}: ";
        for ($page_count = 1; $page_count <= $pages + 1; $page_count += 1) {
            if ($page_count != (floor($page / $_POSTSPERPAGE)) + 1) {
                $post_content = $post_content . "<a href=\"forum.php?do=viewtopic&amp;id=$top_id&amp;p=$page_count\">$page_count</a>"; 
            } else {
                $post_content = $post_content . "<strong>$page_count</strong>";
            }
            if ($page_count != $pages + 1) {
                $post_content = $post_content . ", ";
            }
        }
        $post_content = $post_content . "</td>";
    }
    $post_content = $post_content .  <<<END
	</tr></table></font>
END;
    if (($user['level'] >= $board['post_level']) and ($islocked == false)) {
        $post_content = $post_content . <<<END
<div>
<p align="center">
<script type="text/javascript">
//<![CDATA[
function changeOpacity(obj, percent)
{
if (navigator.appName.indexOf("Microsoft")!= -1 
  &&parseInt(navigator.appVersion)>=4)
	obj.filters.alpha.opacity = percent
else if (navigator.appName.indexOf("Netscape")!=-1
  &&parseInt(navigator.appVersion)>=5)
	obj.style.MozOpacity = percent / 100
}
//]]>
</script>
<a name="qreply_bm"></a>
<table name="quick_reply" style="border: 1px solid;" width="95%">
<tr><td align="center">
<font class="forum_body">
<b><a href="javascript:flipVisibility('qreply');">{$_PWNDATA['forum']['quick_reply']}</a></b><br />
<div id="qreply" style="display: none";>
END;
        $post_content = $post_content . printPosterMini('content', $topic['id']) . <<<END
<form action="forum.php" method="post" name="form">
<input type="hidden" name="action" value="new_reply">
END;
        $post_content = $post_content . "<input type=\"hidden\" name=\"topic\" value=\"" . $topic['id'] . "\">";
        $post_content = $post_content . "<input type=\"hidden\" name=\"user\" value=\"" . $user['id'] . "\">";
        $post_content = $post_content . <<<END
<textarea name="content" style="width: 95%;" rows="5"></textarea><br />
<input type="submit" name="sub" value="{$_PWNDATA['forum']['submit_post']}">
</form>
</div>
</font>
</td></tr>
</table>
</p></div>
END;
    }
    $post_content = $post_content . <<<END
</td>
        <td class="pan_mr">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_bl"></td>
        <td class="pan_bm" colspan="2"></td>
        <td class="pan_br"></td>
      </tr>
    </table>
END;
    $use_previewbox = "yes";
}

// Create a new topic.
if ($_GET['do'] == "newtopic") {
    $result = mysql_query("SELECT * FROM boards WHERE id='" . $_GET['id'] . "'", $db);
    $board = mysql_fetch_array($result);
    if ($board['top_level'] > $user['level']) {
        messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['not_permitted_topic_new']);
    }
    $post_title_add = " :: " . $board['title'] . " :: " . $_PWNDATA['forum']['new_topic'];
    $post_sub_add = " > <a href=\"forum.php?do=viewforum&amp;id=" . $board['id'] . "\">" . $board['title'] . "</a> > " . $_PWNDATA['forum']['new_topic'];
    $post_sub_r = post_sub_r($user['id']);
    $post_content = "";
    $post_content = $post_content .  <<<END
	
      <tr>
        <td width="100%">
    <table class="borderless_table" width="100%">
      <tr>
        <td class="pan_ul">&nbsp;</td>
        <td class="pan_um">
        <font class="pan_title_text">
END;
    $post_content = $post_content . $board['title'];
    $post_content = $post_content .  <<<END
	</font></td>
        <td class="pan_um">
        <p align="right"><font class="pan_title_text">
END;
    $post_content = $post_content .  $_PWNDATA['forum']['new_topic'];
    $post_content = $post_content .  <<<END
	</font></td>
        <td class="pan_ur">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_ml">&nbsp;</td>
        <td class="pan_body" valign="top" colspan="2">
END;
    $post_content = $post_content .  printPoster('content') . <<<END
<form action="forum.php" method="post" name="form">
<input type="hidden" name="action" value="new_topic">
{$_PWNDATA['forum']['subject']}: <br />
<input type="text" name="subj" size="51" style="width:100%"><br />
{$_PWNDATA['forum']['body']}:<br />
<textarea rows="11" name="content" style="width:100%; font-family:Tahoma; font-size:10pt" cols="20" onselect="copySelection(this)"></textarea><br />
<input type="submit" value="{$_PWNDATA['forum']['submit_post']}" name="sub"><br />
END;
    $post_content = $post_content . "<input type=\"hidden\" name=\"board\" value=\"" . $board['id'] . "\">";
    $post_content = $post_content . "<input type=\"hidden\" name=\"user\" value=\"" . $user['id'] . "\">";
    $post_content = $post_content .  <<<END
	<input type="checkbox" name="add_poll" /> {$_PWNDATA['forum']['poll_add']}<br />
	{$_PWNDATA['forum']['poll_title']}: <input type="text" name="p_name" width="300" /><br />
	{$_PWNDATA['forum']['poll_options']}: <input type="text" name="op1" /><br />
	</form></font></td>
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
}

// Create a new reply.
if ($_GET['do'] == "newreply") {
    $result = mysql_query("SELECT * FROM topics WHERE id='" . $_GET['id'] . "'", $db);
    $topic = mysql_fetch_array($result);
    $result = mysql_query("SELECT * FROM boards WHERE id='" . $topic['board'] . "'", $db);
    $board = mysql_fetch_array($result);
    if ($board['post_level'] > $user['level']) {
        messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['not_permitted_reply']);
    }
    if ($topic['locked'] == 0) {
        $islocked = false;
    } else {
        if ($user['level'] >= $site_info['mod_rank']) {
        $islocked = false;
        } else {
            messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['not_permitted_reply']);
        }
    }
    $post_title_add = " :: " . $board['title'] . " :: {$_PWNDATA['forum']['replying_to']} " . $topic['title'];
    $post_sub_add = " > <a href=\"forum.php?do=viewforum&amp;id=" . $board['id'] . "\">" . $board['title'] . "</a> > {$_PWNDATA['forum']['replying_to']} <a href=\"forum.php?do=viewtopic&amp;id=" . $topic['id'] . "\">" . $topic['title'] . "</a>";
    $post_sub_r = post_sub_r($user['id']);
    $post_content = "";
    $post_content = $post_content .  <<<END
	
      <tr>
        <td width="100%">
    <table class="borderless_table" width="100%">
      <tr>
        <td class="pan_ul">&nbsp;</td>
        <td class="pan_um">
        <font class="pan_title_text">
END;
    $post_content = $post_content . $topic['title'];
    $post_content = $post_content .  <<<END
	</font></td>
        <td class="pan_um">
        <p align="right"><font class="pan_title_text">
END;
    $post_content = $post_content .  $_PWNDATA['forum']['replying'];
    $post_content = $post_content .  <<<END
	</font></td>
        <td class="pan_ur">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_ml">&nbsp;</td>
        <td class="pan_body" valign="top" colspan="2">
		<font class="forum_base_text">
END;
    if ($_GET['quote'] != 0) {
	    $result = mysql_query("SELECT * FROM posts WHERE id='" . $_GET['quote'] . "'", $db);
	    $quoted = mysql_fetch_array($result);
	    $result = mysql_query("SELECT * FROM users WHERE id='" . $quoted['authorid'] . "'", $db);
	    $quotedauthor = mysql_fetch_array($result);
	    $postquoted = preg_replace("/(\[quote\])(.+?)(\[\/quote\])/si","",$quoted['content']);
	    $cont = "[quote][b]{$_PWNDATA['forum']['original']}[/b] " . $quotedauthor['name'] . "\n" . $postquoted . "[/quote]";
    }
    $post_content = $post_content .  printPoster('content') . <<<END
<form action="forum.php" method="post" name="form">
<input type="hidden" name="action" value="new_reply">
{$_PWNDATA['forum']['body']}:<br />
<textarea rows="11" name="content" style="width:100%; font-family:Tahoma; font-size:10pt" cols="20" onselect="copySelection(this)">$cont</textarea><br />
<input type="submit" value="{$_PWNDATA['forum']['submit_post']}" name="sub">
END;
    $post_content = $post_content . "<input type=\"hidden\" name=\"topic\" value=\"" . $topic['id'] . "\">";
    $post_content = $post_content . "<input type=\"hidden\" name=\"user\" value=\"" . $user['id'] . "\"></form>";
    $resultz = mysql_query("SELECT * FROM posts WHERE topicid='" . $topic['id'] . "' ORDER BY `id` DESC LIMIT 5", $db);
    $post_content = $post_content . "<br /><b>{$_PWNDATA['forum']['recent']}:</b><table class=\"forum_base\" width=\"100%\">\n";
    while ($rowz = mysql_fetch_array($resultz)) {
        $resultb = mysql_query("SELECT * FROM users WHERE id='" .  $rowz['authorid'] . "'", $db);
        $post_author = mysql_fetch_array($resultb);
        $auth_name = $post_author['name'];
        $dec_post = BBDecode($rowz['content']);
        $post_content = $post_content . "<tr><td width=\"20%\" valign=\"top\"><font size=\"2\">$auth_name</font></td><td><font size=\"2\">$dec_post</font></td></tr>\n";
    }
    $post_content = $post_content .  <<<END
	</table></font></td>
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
}

// Edit a past post
if ($_GET['do'] == "editreply") {
    if (!isset($_SESSION['sess_id'])) {
        messageBack($_PWNDATA['forum']['not_logged_in']);
    }
    $result = mysql_query("SELECT * FROM posts WHERE id='" . $_GET['id'] . "'", $db);
    $reply = mysql_fetch_array($result);
    if (($reply['authorid'] != $user['id']) and ($user['level'] < 2)) {
	    messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['not_yours']);
    }
    $result = mysql_query("SELECT * FROM topics WHERE id='" . $reply['topicid'] . "'", $db);
    $topic = mysql_fetch_array($result);
    $result = mysql_query("SELECT * FROM boards WHERE id='" . $topic['board'] . "'", $db);
    $board = mysql_fetch_array($result);
    $post_title_add = " :: " . $board['title'] . " :: " . $_PWNDATA['forum']['editing'];
    $post_sub_add = " > <a href=\"forum.php?do=viewforum&amp;id=" . $board['id'] . "\">" . $board['title'] . "</a> > " . $_PWNDATA['forum']['editing'];
    $post_sub_r = post_sub_r($user['id']);
    $post_content = "";
    $post_content = $post_content .  <<<END
	
      <tr>
        <td width="100%">
    <table class="borderless_table" width="100%">
      <tr>
        <td class="pan_ul">&nbsp;</td>
        <td class="pan_um">
        <font class="pan_title_text">
END;
    $post_content = $post_content . $topic['title'];
    $post_content = $post_content .  <<<END
	</font></td>
        <td class="pan_um">
        <p align="right"><font class="pan_title_text">
END;
    $post_content = $post_content .  $_PWNDATA['forum']['editing'];
    $post_content = $post_content .  <<<END
	</font></td>
        <td class="pan_ur">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_ml">&nbsp;</td>
        <td class="pan_body" valign="top" colspan="2">
		<font class="forum_base_text">
END;
    $post_content = $post_content . printPoster('content') . <<<END
<form action="forum.php" method="post" name="form">
<input type="hidden" name="action" value="edit_reply">
{$_PWNDATA['forum']['body']}:<br />
<textarea rows="11" name="content" style="width:100%; font-family:Tahoma; font-size:10pt" cols="20" onselect="copySelection(this)">
END;
    $post_content = $post_content . $reply['content'];
    $post_content = $post_content . <<<END
</textarea><br />
<input type="submit" value="{$_PWNDATA['forum']['save_changes']}" name="sub">
END;
    $post_content = $post_content . "<input type=\"hidden\" name=\"id\" value=\"" . $reply['id'] . "\">";
    $post_content = $post_content . "<input type=\"hidden\" name=\"topic\" value=\"" . $topic['id'] . "\">";
    $post_content = $post_content .  <<<END
	</form></font></td>
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
}

// Edit a profile
if ($_GET['do'] == "editprofile") {
    $post_title_add = " :: " . $_PWNDATA['profile']['editing'];
    $post_sub_add = " > " . $_PWNDATA['profile']['editing'];
    $post_sub_r = post_sub_r($user['id']);
    $post_content = "";
    $post_content = $post_content .  <<<END
	
      <tr>
        <td width="100%">
    <table class="borderless_table" width="100%">
      <tr>
        <td class="pan_ul">&nbsp;</td>
        <td class="pan_um">
        <font class="pan_title_text">
END;
    $post_content = $post_content . $_PWNDATA['profile']['title'];
    $post_content = $post_content .  <<<END
	</font></td>
        <td class="pan_um">
        <p align="right"><font class="pan_title_text">
END;
    $post_content = $post_content .  $_PWNDATA['profile']['editing'];
    $post_content = $post_content .  <<<END
	</font></td>
        <td class="pan_ur">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_ml">&nbsp;</td>
        <td class="pan_body" valign="top" colspan="2">
		<font class="forum_base_text">
END;
    $uid = $user['id'];
    $umail = $user['email'];
    $uname = $user['name'];
    $sig = $user['sig'];
    $ava = $user['avatar'];
    $uyah = $user['yahoo'];
    $umsn = $user['msn'];
    $uicq = $user['icq'];
    $uaim = $user['aim'];
    $uxfire = $user['xfire'];
    $ulive = $user['live'];
    $sbona = $user['sbonforum'];
    $pand = $user['pand'];
    $u_theme = $user['theme'];
    $u_color = $user['color'];
    if ($sbona == 1) {
        $sbon = "checked";
    } else {
        $sbon = "";
    }
    $theme_list = themeList($u_theme);
    $color_list = colorList($u_color);
    $post_content = $post_content . <<<END
<form method="post" action="forum.php" name="form">
  <input type="hidden" name="action" value="edit_profile">
  <input type="hidden" name="id" value="$uid">
  <strong>{$_PWNDATA['profile']['registration']}:</strong><br />
  {$_PWNDATA['profile']['username']}: $uname <input type="hidden" name="name" size="20" value="$uname"><br />
  {$_PWNDATA['profile']['email']}: <input type="text" name="email" size="20" value="$umail"><br />
  {$_PWNDATA['profile']['password']}: <input type="password" name="apass" size="20" value=""><br />
  {$_PWNDATA['profile']['confirm']}: <input type="password" name="cpass" size="20" value=""><br />
  <br /><strong>{$_PWNDATA['profile']['messaging']}:</strong><br />
  MSN: <input type="text" name="msn" size="20" value="$umsn"><br />
  AIM: <input type="text" name="aim" size="20" value="$uaim"><br />
  Yahoo: <input type="text" name="yah" size="20" value="$uyah"><br />
  ICQ: <input type="text" name="icq" size="20" value="$uicq"><br />
  xFire: <input type="text" name="xfire" size="20" value="$uxfire"><br />
  Gamertag: <input type="text" name="live" size="20" value="$ulive"><br />
  Pandemic: <input type="text" name="pand" size="20" value="$pand"><br />
  <br /><strong>{$_PWNDATA['profile']['posting']}:</strong><br />
  {$_PWNDATA['profile']['sig']}:<br />
END;
    $post_content = $post_content . printPoster('sig') . <<<END
  <textarea rows="5" name="sig" style="width:100%">$sig</textarea><br />
  {$_PWNDATA['profile']['avatar']}<br />
  <input type="text" name="avatar" size="20" value="$ava"><br />
  <br /><strong>{$_PWNDATA['profile']['settings']}:</strong><br />
  {$_PWNDATA['profile']['sidebar']}: <input name="sbonforum" type="checkbox" $sbon><br />
  {$_PWNDATA['profile']['theme']}: $theme_list<br />
  {$_PWNDATA['profile']['color']}: $color_list<br /><br />
  <input type="submit" value="{$_PWNDATA['profile']['save']}" name="sub">
END;
    $post_content = $post_content .  <<<END
	</form></font></td>
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
}

// View a user's profile
if ($_GET['do'] == "viewprofile") {
    $result = mysql_query("SELECT * FROM users WHERE id='" . $_GET['id'] . "'", $db);
    $vuser = mysql_fetch_array($result);
    $uid = $vuser['id'];
    $umail = $vuser['email'];
    $uname = $vuser['name'];
    $sig = BBDecode($vuser['sig']);
    $ava = $vuser['avatar'];
    $uyah = $vuser['yahoo'];
    $umsn = $vuser['msn'];
    $uicq = $vuser['icq'];
    $uaim = $vuser['aim'];
    $uxfire = $vuser['xfire'];
    $ulive = $vuser['live'];
    $pand = $vuser['pand'];
    $post_title_add = " :: {$_PWNDATA['profile']['view']}'$uname'";
    $post_sub_add = " > {$_PWNDATA['profile']['view']} '$uname'";
    $post_sub_r = post_sub_r($user['id']);
    $post_content = "";
    $post_content = $post_content .  <<<END
	
      <tr>
        <td width="100%">
    <table class="borderless_table" width="100%">
      <tr>
        <td class="pan_ul">&nbsp;</td>
        <td class="pan_um">
        <font class="pan_title_text">
END;
    $post_content = $post_content . $uname . $_PWNDATA['profile']['possessive_profile'];
    $post_content = $post_content .  <<<END
	</font></td>
        <td class="pan_um">
        <p align="right"><font class="pan_title_text">
END;
    $post_content = $post_content .  $_PWNDATA['profile']['view'];
    $post_content = $post_content .  <<<END
	</font></td>
        <td class="pan_ur">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_ml">&nbsp;</td>
        <td class="pan_body" valign="top" colspan="2">
		<font face="Tahoma" color="#FFFFFF">
END;
    $replyto = $vuser['id'];
    $posts = postCount($replyto);
    $modstatus = getRankName($vuser['level'],$site_info,$posts);
    if ($ava != "") {
        $post_content = $post_content . "<img src=\"$ava\" align=\"top\" />";
    }
    $post_content = $post_content . <<<END
  <strong> $uname</strong><br />$modstatus<br />
  <br />
  {$_PWNDATA['forum']['posts']}: $posts
  <br />
  <strong>{$_PWNDATA['profile']['messaging']}:</strong><br />
  <img src="smiles/msn.png" /> MSN: $umsn<br />
  <img src="smiles/aim.png" /> AIM: $uaim<br />
  <img src="smiles/yahoo.png" /> Yahoo: $uyah<br />
  <img src="smiles/icq.png" /> ICQ: $uicq<br />
  <img src="smiles/xfire.png" /> xFire: $uxfire<br />
  <img src="smiles/live.png" /> Gamertag: $ulive<br />
  <img src="smiles/pan.png" /> Pandemic: $pand<br />
  <a href="forum.php?do=newpm&amp;to=$replyto">{$_PWNDATA['pm']['send_a']}</a><br /><br />
END;
    $post_content = $post_content .  <<<END
	<img src="smiles/quotea.png" align="top" /><font face="Times New Roman" size="5"><i>$sig</i></font><img src="smiles/quoteb.png" /><br />
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
}

// Search the forum
if ($_GET['do'] == "search_form") {
    $post_title_add = " :: " . $_PWNDATA['forum']['search'];
    $post_sub_add = " > " . $_PWNDATA['forum']['search'];
    $post_sub_r = post_sub_r($user['id']);
    $post_content = "";
    $post_content = $post_content .  <<<END
      <tr>
        <td width="100%">
    <table class="borderless_table" width="100%">
      <tr>
        <td class="pan_ul">&nbsp;</td>
        <td class="pan_um">
        <font class="pan_title_text">
END;
    $post_content = $post_content . $_PWNDATA['forum']['search'];
    $post_content = $post_content .  <<<END
	</font></td>
        <td class="pan_um">
        <p align="right"><font class="pan_title_text">
	</font></td>
        <td class="pan_ur">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_ml">&nbsp;</td>
        <td class="pan_body" valign="top" colspan="2">
		<font face="Tahoma" color="#FFFFFF">
		<form action="forum.php?do=search" method="post" name="form">
		{$_PWNDATA['forum']['search_terms']}: <input type="text" name="q">
		<input type="submit" value="{$_PWNDATA['forum']['search_submit']}" name="sub">
END;
    $post_content = $post_content .  <<<END
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
}

// Search results
if ($_GET['do'] == "search") {
    // XXX: SELECT * FROM posts WHERE MATCH (content) AGAINST ('hmmm')
    $search = $_POST['q'];
    $post_title_add = " :: {$_PWNDATA['forum']['searching_for']} '$search'";
    $post_sub_add = " > {$_PWNDATA['forum']['searching_for']} '$search'";
    $post_sub_r = post_sub_r($user['id']);
    $resultz = mysql_query("SELECT * FROM posts WHERE MATCH (content) AGAINST ('$search')", $db);
    $post_content = "";
    $post_content = $post_content .  <<<END
      <tr>
        <td width="100%">
    <table class="borderless_table" width="100%">
      <tr>
        <td class="pan_ul">&nbsp;</td>
        <td class="pan_um">
        <font class="pan_title_text">
END;
    $post_content = $post_content . "{$_PWNDATA['forum']['search_results']} '$search'";
    $post_content = $post_content .  <<<END
	</font></td>
        <td class="pan_um">
        <p align="right"><font class="pan_title_text">{$_PWNDATA['forum']['search']}
	</font></td>
        <td class="pan_ur">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_ml">&nbsp;</td>
        <td class="pan_body" valign="top" colspan="2">
		<font face="Tahoma" color="#FFFFFF">
END;
    $post_content = $post_content . "<b>{$_PWNDATA['forum']['search_resultsb']}:</b><table class=\"forum_base\" width=\"100%\">\n";
    while ($rowz = mysql_fetch_array($resultz)) {
        $resultb = mysql_query("SELECT * FROM users WHERE id='" .  $rowz['authorid'] . "'", $db);
        $post_author = mysql_fetch_array($resultb);
        $resultc = mysql_query("SELECT * FROM topics WHERE id='" .  $rowz['topicid'] . "'", $db);
        $post_topic = mysql_fetch_array($resultc);
        $resultc = mysql_query("SELECT * FROM boards WHERE id='" .  $post_topic['board'] . "'", $db);
        $post_board = mysql_fetch_array($resultc);
        $auth_name = $post_author['name'];
        $dec_post = BBDecode($rowz['content']);
        if ($post_board['vis_level'] > $user['level']) {
            // Do nothing, this post is in a board the user isn't allowed to see!
        } else {
            $post_content = $post_content . "<tr><td width=\"20%\" valign=\"top\"><font size=\"2\">$auth_name</font></td><td><font size=\"2\"><b><i>{$_PWNDATA['forum']['posted_in']}: <a href=\"forum.php?do=viewtopic&amp;id=" . $post_topic['id'] . "\">" . $post_topic['title'] . "</a></i></b><br />$dec_post</font></td></tr>\n";
        }
    }
    $post_content = $post_content . "</table>";
    $post_content = $post_content .  <<<END
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
}

// XXX: Begin core output.

if ($use_previewbox == "yes") {
    standardHeaders($site_info['name'] . " :: " . $_PWNDATA['forum_page_title'] . $post_title_add, true,previewBoxScript());
} else {
    standardHeaders($site_info['name'] . " :: " . $_PWNDATA['forum_page_title'] . $post_title_add, true);
}

print <<<END
    <script type="text/javascript">
    //<![CDATA[
function flipVisibility(what) {
    if (document.getElementById(what).style.display != "none") {
        document.getElementById(what).style.display = "none"
    } else {
        document.getElementById(what).style.display = "inline"
    }
}
    //]]>
    </script>
END;


drawSubbar("<a href=\"index.php\">" . $site_info['name'] . "</a> > <a href=\"forum.php\">{$_PWNDATA['forum_page_title']}</a>" . $post_sub_add,$post_sub_r);

if ($user['level'] < 1) {
    require 'sidebar.php';
} else {
    if ($user['sbonforum'] == 1) {
        require 'sidebar.php';
    } else {
        print "<table class=\"borderless_table\" width=\"100%\"><tr>";
    }
}

print <<<END
<td valign="top">
<table class="borderless_table" width="100%">
END;
// CONTENT OF FORUM PAGE GOES HERE!!
print $post_content;
// Print the board statistics -----------------------------------------------------------------------------

$sql_temp = mysql_query("SELECT COUNT(*) FROM `users`");
$stat_a = mysql_fetch_array($sql_temp);
$sql_temp = mysql_query("SELECT COUNT(*) FROM `topics`");
$stat_b = mysql_fetch_array($sql_temp);
$sql_temp = mysql_query("SELECT COUNT(*) FROM `posts`");
$stat_c = mysql_fetch_array($sql_temp);
$sql_temp = mysql_query("SELECT COUNT(*) FROM `topics` WHERE `topics`.`stick`=1");
$stat_d = mysql_fetch_array($sql_temp);
$sql_temp = mysql_query("SELECT * FROM `users` ORDER BY `id` DESC");
$stat_e = mysql_fetch_array($sql_temp);
$num_users = $stat_a['COUNT(*)'];
$num_topics = $stat_b['COUNT(*)'];
$num_posts = $stat_c['COUNT(*)'];
$num_sticks = $stat_d['COUNT(*)'];
$last_member = $stat_e['name'];
$last_member_id = $stat_e['id'];
$block_content = "<div style=\"text-align: center;\">";
$block_content = $block_content . "<table border=\"0px\" cellspacing=\"8px\" align=\"center\"><tr><td align=\"center\"><img src=\"smiles/forum_read.png\" alt=\"{$_PWNDATA['forum']['board_has_no']}\"/><br /><font size=\"2\">{$_PWNDATA['forum']['board_has_no']}</font></td>\n";
$block_content = $block_content . "<td align=\"center\"><img src=\"smiles/forum_unread.png\" alt=\"{$_PWNDATA['forum']['board_has_new']}\"/><br /><font size=\"2\">{$_PWNDATA['forum']['board_has_new']}</font></td>\n";
$block_content = $block_content . "<td width=\"15\">&nbsp;</td>\n";
$block_content = $block_content . "<td align=\"center\"><img src=\"smiles/read.png\" alt=\"{$_PWNDATA['forum']['no_new_posts']}\"/><br /><font size=\"2\">{$_PWNDATA['forum']['no_new_posts']}</font></td>\n";
$block_content = $block_content . "<td align=\"center\"><img src=\"smiles/unread.png\" alt=\"{$_PWNDATA['forum']['new_posts']}\"/><br /><font size=\"2\">{$_PWNDATA['forum']['new_posts']}</font></td>\n";
$block_content = $block_content . "<td align=\"center\"><img src=\"smiles/lread.png\" alt=\"{$_PWNDATA['forum']['locked']}\"/><br /><font size=\"2\">{$_PWNDATA['forum']['locked']}</font></td>\n";
$block_content = $block_content . "<td align=\"center\"><img src=\"smiles/readp.png\" alt=\"{$_PWNDATA['forum']['poll']}\"/><br /><font size=\"2\">{$_PWNDATA['forum']['poll']}</font></td>\n";
$block_content = $block_content . "<td align=\"center\"><img src=\"smiles/sread.png\" alt=\"{$_PWNDATA['forum']['sticky']}\"/><br /><font size=\"2\">{$_PWNDATA['forum']['sticky']}</font></td></tr></table>\n";
$block_content = $block_content . "{$_PWNDATA['forum']['there_are']}$num_posts{$_PWNDATA['forum']['posts_by']}$num_users{$_PWNDATA['forum']['members_in']}$num_topics{$_PWNDATA['forum']['_topics']}\n";
$block_content = $block_content . "$num_sticks{$_PWNDATA['forum']['are_sticky']}\n";
$block_content = $block_content . "<a href=\"forum.php?do=viewprofile&amp;id=$last_member_id\">$last_member</a>\n<br />";

$block_content = $block_content . "<strong>{$_PWNDATA['forum']['members_online']}</strong>: ";
$sql_temp = mysql_query("SELECT * FROM `sessions` ORDER BY `user`");
while ($on_session = mysql_fetch_array($sql_temp)) {
$on_temp = mysql_query("SELECT * FROM `users` WHERE `id`=" . $on_session['user']);
$on_user = mysql_fetch_array($on_temp);
$on_id = $on_session['user'];
$block_content = $block_content . "<a href=\"forum.php?do=viewprofile&amp;id=$on_id\">";
if ($on_user['level'] < $site_info['mod_rank']) { $block_content = $block_content . $on_user['name']; }
if (($on_user['level'] >= $site_info['mod_rank']) and ($on_user['level'] < $site_info['admin_rank'])) { $block_content = $block_content . "<font class='mod_name'>" . $on_user['name'] . "</font>"; }
if ($on_user['level'] >= $site_info['admin_rank']) { $block_content = $block_content . "<font class='adm_name'>" . $on_user['name'] . "</font>"; }
$block_content = $block_content . "</a> ";
}
$block_content = $block_content .  <<<END
	<br /><font size="1">({$_PWNDATA['forum']['user']} <font class='mod_name'>{$_PWNDATA['forum']['moderator']}</font> <font class='adm_name'>{$_PWNDATA['forum']['admin']}</font>)</font></div>
END;
print makeBlock($_PWNDATA['forum']['stats'],$_PWNDATA['forum']['at'] . " " . $site_info['name'],$block_content);
// End         --------------------------------------------------------------------------

print <<<END
	</table>
        </td>
  </tr>
</table>
END;
if ($use_previewbox == "yes") {
    print previewBox();
}
require 'footer.php';
?>
