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


if ($_POST['action'] == "login") {
    $userresult = mysql_query("SELECT * FROM `{$_PREFIX}users` WHERE UCASE(name)=UCASE('" . $_POST['uname'] . "')", $db);
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
        $result = mysql_query("SELECT * FROM `{$_PREFIX}users` WHERE UCASE(name)=UCASE('" . $_SESSION['user_name'] . "')", $db);
        $user = mysql_fetch_array($result);
        mysql_query("DELETE FROM `{$_PREFIX}sessions` WHERE `user`=" . $user['id'] . "");
        mysql_query("INSERT INTO `{$_PREFIX}sessions` VALUES (" . $_SESSION['sess_id'] . ", " . $user['id'] . ", " . $_SESSION['last_on'] . ");");
        if ($_POST['admin'] == "yes") {
            messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['signedin'],"admin.php");
        } else if ($_POST['mobile'] == "yes") {
            messageRedirectLight($_PWNDATA['signedin'],"mobile.php");
        } else {
            messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['signedin'],"forum.php");
        }
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
        $name = $_POST['uname'];
        mysql_query("INSERT INTO `{$_PREFIX}security` ( `time` , `passused`, `where`, `ip` ) VALUES ( '" . time() . "', '" . md5($_POST['upass']) . "', 'Forum, $name', '" . $ip . "' );");
        messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['failed_login']);
    }
}

// If a new topic is being posted
if ($_POST['action'] == "new_topic") {
    $content = $_POST['content'];
    $results =  mysql_query("SELECT * FROM `{$_PREFIX}boards` WHERE `id`=" . $_POST['board']);
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
	    mysql_query("INSERT INTO `{$_PREFIX}polls` ( `id`, `title`, `op1_name`) VALUES (NULL, '" . mse($_POST['p_name']) . "', '" . mse($_POST['op1']) . "');");
	    $testresult = mysql_query("SELECT * FROM `{$_PREFIX}polls` ORDER BY `id` DESC LIMIT 1");
	    $poll = mysql_fetch_array($testresult);
	    $poll_id = $poll['id'];
    } else {
	    $has_poll = "0";
	    $poll_id = "0";
    }
    mysql_query("INSERT INTO `{$_PREFIX}topics` ( `id` , `authorid` , `board` , `title`, `has_poll`, `poll_id` ) VALUES (NULL , " . $_POST['user'] . ", " . $_POST['board'] . ", '" . mse($_POST['subj']) . "', " . $has_poll . ", " . $poll_id . " );");
    $result = mysql_query("SELECT * FROM `{$_PREFIX}topics` ORDER BY `id` DESC LIMIT 1");
    $topic = mysql_fetch_array($result);
    $ip=$_SERVER['REMOTE_ADDR'];
    mysql_query("INSERT INTO `{$_PREFIX}posts` ( `id` , `topicid` , `authorid` , `content`, `time`, `ip`) VALUES ( NULL , " . $topic['id'] . " , " . $_POST['user'] . " , '" . mse($content) . "' , " . time() . " , '" . $ip . "');");
    $result = mysql_query("SELECT * FROM `{$_PREFIX}posts` ORDER BY `id` DESC LIMIT 1");
    $reply = mysql_fetch_array($result);
    mysql_query("UPDATE `{$_PREFIX}topics` SET `lastpost` = " . $reply['id'] . " WHERE `{$_PREFIX}topics`.`id` =" . $topic['id']);
    mysql_query("ALTER TABLE `{$_PREFIX}posts`  ORDER BY `id`");
    mysql_query("ALTER TABLE `{$_PREFIX}topics`  ORDER BY `id`");
    messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['new_topic_added'],"forum.php?do=viewtopic&amp;id=" . $topic['id']);
}

// If a new PM is being sent
if ($_POST['action'] == "new_pm") {
    if (strlen($_POST['subj']) < 3) {
        messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['pm_too_short']);
    }
    $pmdate = time();
    $toname = $_POST['toline'];
    $result = mysql_query("SELECT * FROM `{$_PREFIX}users` WHERE `name`='$toname'");
    $temp_user = mysql_fetch_array($result);
    $pmto = $temp_user['id'];
    $pmtitle = $_POST['subj'];
    $pmcontent = $_POST['content'];
    $pmfrom = $user['id'];
    mysql_query("INSERT INTO `{$_PREFIX}pms` ( `id` , `to` , `from` , `title` , `content` , `read` , `time` )
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
    $topic_sql = mysql_query("SELECT * FROM `{$_PREFIX}topics` WHERE `id`=$topic");
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
    mysql_query("INSERT INTO `{$_PREFIX}posts` ( `id` , `topicid` , `authorid` , `content` , `time` , `ip` ) VALUES ( NULL , '" . $topic . "', '" . $user['id'] . "', '" . mse($content) . "' , '" . time() . "', '" . $ip . "' );");
    $result = mysql_query("SELECT * FROM `{$_PREFIX}posts` ORDER BY `id` DESC LIMIT 1");
    $reply = mysql_fetch_array($result);
    mysql_query("UPDATE `{$_PREFIX}topics` SET `lastpost` = '" . $reply['id'] . "' WHERE `{$_PREFIX}topics`.`id` =" . $topic);
    mysql_query("ALTER TABLE `{$_PREFIX}posts`  ORDER BY `id`");
    if ($_POST['mobile'] == "yes") {
        messageRedirectLight($_PWNDATA['forum']['new_reply_added'],"mobile.php?do=viewtopic&amp;id=" . $topic);
    }
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
	$topic_sql = mysql_query("SELECT * FROM `{$_PREFIX}topics` WHERE `id`=$tid");
	$this_topic = mysql_fetch_array($topic_sql);
	if ($this_topic['locked'] == 1) {
		if ($user['level'] < 2) {
		messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['locked_topic_poll']);
		}
	}
	set_voted($pid,$user['id']);
	$poll_sql = mysql_query("SELECT * FROM `{$_PREFIX}polls` WHERE `id`=$pid");
	$poll = mysql_fetch_array($poll_sql);
	$poll_ops = split(",",$poll['op1_name']);
	$poll_votes = split(",",$poll['op1_votes']);
	$poll_count = count($poll_ops);
	$stri = "";
	for ($i=0;$i<$poll_count;$i++) {
		if (!isset($poll_votes[$i])) {
			$poll_votes[$i] = 0;
		}
		$a = (int)$poll_votes[$i];
		if ($i == $vote) {
			$a = $a + 1;
		}
		$stri = $stri . $a . ",";
	}
	$stri = substr($stri,0,strlen($stri)-1);
	mysql_query("UPDATE `{$_PREFIX}polls` SET `op1_votes`='" . $stri . "' WHERE `id`=$pid");
	messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['vote_cast'],"forum.php?do=viewtopic&amp;last=1&amp;id=" . $tid);
}

// If an old post is being edited
if ($_POST['action'] == "edit_reply") {
    $content = $_POST['content'];
    mysql_query("UPDATE `{$_PREFIX}posts` SET `content` = '" . mse($content) . "' WHERE `{$_PREFIX}posts`.`id` =" . $_POST['id'] . ";");
    messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['post_edited'],"forum.php?do=viewtopic&amp;id=" . $_POST['topic'] . "&p=" . findPage($_POST['id']));
}

// If a topic title is being changed
if ($_POST['action'] == "edit_title") {
    $title = $_POST['title'];
    mysql_query("UPDATE `{$_PREFIX}topics` SET `title` = '" . mse($title) . "' WHERE `{$_PREFIX}topics`.`id` =" . $_POST['topicid'] . ";");
    messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['topic_title_edited'],"forum.php");
}

// If a topic is being moved
if ($_POST['action'] == "move_topic") {
    $board = $_POST['board'];
    mysql_query("UPDATE `{$_PREFIX}topics` SET `board` = $board WHERE `{$_PREFIX}topics`.`id` =" . $_POST['topid'] . ";");
    messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['topic_moved'],"forum.php?do=viewtopic&amp;id=" . $_POST['topid']);
}

// Topic is being split
if ($_POST['action'] == "split_topic") {
    // Author is whoever split the topic off in the first place, as a way to track it.
    if (strlen($_POST['newtitle']) < 3) {
        messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['modtools']['tooshort']);
    }
    mysql_query("INSERT INTO `{$_PREFIX}topics` ( `id` , `authorid` , `board` , `title`, `has_poll`, `poll_id` ) VALUES (NULL , " . $user['id'] . ", " . $_POST['board'] . ", '" . mse($_POST['newtitle']) . "', 0, 0 );");
    $result = mysql_query("SELECT * FROM `{$_PREFIX}topics` ORDER BY `id` DESC LIMIT 1");
    $topic = mysql_fetch_array($result);
    $where = "WHERE `id`=";
    while (list($key,$value) = each($_POST)) {
        if (strstr($key,"post_")) {
            if ($value == "on") {
                $lastpost = str_replace("post_", "", $key);
                $where = $where . $lastpost . " OR `id`=";
            }
        }
    }
    $where = $where . "0";
    mysql_query("UPDATE `{$_PREFIX}posts` SET `topicid`=" . $topic['id'] . " " . $where);
    mysql_query("UPDATE `{$_PREFIX}topics` SET `lastpost`=" . $lastpost . " WHERE `id`=" . $topic['id']);
    messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['modtools']['topic_split'],"forum.php?do=viewforum&amp;id=" . $_POST['board']);    
}

if ($_POST['action'] == "merge_topics") {
    $mergeid = "NONE";
    while (list($key,$value) = each($_POST)) {
        if (strstr($key,"topic_")) {
            if ($value == "on") {
                $mergeid = str_replace("topic_","",$key);
            }
        }
    }
    if ($mergeid == "NONE") {
        messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['modtools']['nonespecified']);
    }
    $result = mysql_query("SELECT * FROM `{$_PREFIX}posts` WHERE `topicid`={$_POST['topic']}");
    while ($post = mysql_fetch_array($result)) {
        mysql_query("UPDATE `{$_PREFIX}posts` SET `topicid`={$mergeid} WHERE `id`={$post['id']}");
    }
    mysql_query("DELETE FROM `{$_PREFIX}topics` WHERE `{$_PREFIX}topics`.`id` =" . $_POST['topic']);
    messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['modtools']['merged'],"forum.php?do=viewforum&amp;id=" . $_POST['board']);
}

// If an old post is being edited
if ($_POST['action'] == "edit_profile") {
    $userid = $user['id'];
    if ($_POST['adm'] == "true") {
        if ($user['level'] >= $site_info['mod_rank']) {
            $userid = $_POST['id'];
        }
    }
    mysql_query("UPDATE `{$_PREFIX}users` SET `name` = '" . mse($_POST['name']) . "' WHERE `{$_PREFIX}users`.`id` =" . $userid);
    mysql_query("UPDATE `{$_PREFIX}users` SET `sig` = '" . mse($_POST['sig']) . "' WHERE `{$_PREFIX}users`.`id` =" . $userid);
    mysql_query("UPDATE `{$_PREFIX}users` SET `avatar` = '" . mse($_POST['avatar']) . "' WHERE `{$_PREFIX}users`.`id` =" . $userid);
    mysql_query("UPDATE `{$_PREFIX}users` SET `email` = '" . mse($_POST['email']) . "' WHERE `{$_PREFIX}users`.`id` =" . $userid);
    mysql_query("UPDATE `{$_PREFIX}users` SET `aim` = '" . mse($_POST['aim']) . "' WHERE `{$_PREFIX}users`.`id` =" . $userid);
    mysql_query("UPDATE `{$_PREFIX}users` SET `msn` = '" . mse($_POST['msn']) . "' WHERE `{$_PREFIX}users`.`id` =" . $userid);
    mysql_query("UPDATE `{$_PREFIX}users` SET `yahoo` = '" . mse($_POST['yah']) . "' WHERE `{$_PREFIX}users`.`id` =" . $userid);
    mysql_query("UPDATE `{$_PREFIX}users` SET `icq` = '" . mse($_POST['icq']) . "' WHERE `{$_PREFIX}users`.`id` =" . $userid);
    mysql_query("UPDATE `{$_PREFIX}users` SET `live` = '" . mse($_POST['live']) . "' WHERE `{$_PREFIX}users`.`id` =" . $userid);
    mysql_query("UPDATE `{$_PREFIX}users` SET `xfire` = '" . mse($_POST['xfire']) . "' WHERE `{$_PREFIX}users`.`id` =" . $userid);
    mysql_query("UPDATE `{$_PREFIX}users` SET `pand` = '" . mse($_POST['pand']) . "' WHERE `{$_PREFIX}users`.`id` =" . $userid);
    mysql_query("UPDATE `{$_PREFIX}users` SET `theme` = '" . mse($_POST['theme'] . "," . $_POST['icons'] . "," . $_POST['lang']) . "' WHERE `{$_PREFIX}users`.`id` =" . $userid);
    mysql_query("UPDATE `{$_PREFIX}users` SET `color` = '" . mse($_POST['color']) . "' WHERE `{$_PREFIX}users`.`id` =" . $userid);
    if ($_POST['sbonforum'] == "on") {
        $sbon = 1;
    } else {
        $sbon = 0;
    }
    mysql_query("UPDATE `{$_PREFIX}users` SET `sbonforum` = " . $sbon . " WHERE `{$_PREFIX}users`.`id` =" . $userid);
    if ($_POST['apass'] != "") {
	    if ($_POST['apass'] == $_POST['cpass']) {
	        mysql_query("UPDATE `{$_PREFIX}users` SET `password` = '" . md5($_POST['apass']) . "' WHERE `{$_PREFIX}users`.`id` =" . $userid);
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
    $results = mysql_query("SELECT * FROM `{$_PREFIX}users` WHERE `name`='" . $name . "'");
    $check_name = mysql_fetch_array($results);
    if ($check_name != null) {
        messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['already_registered']);
    }
    $results = mysql_query("SELECT * FROM `{$_PREFIX}users` WHERE `email`='" . $email . "'");
    $check_mail = mysql_fetch_array($results);
    if ($check_mail != null) {
        messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['already_registered_mail']);
    }
    if ($site_info['security_mode'] < 2) {
        if ($code != $_SESSION['seccode']) {
            messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['security_code']);
        }
    } else if ($site_info['security_mode'] == 2) {
        require_once('recaptchalib.php');
        $resp = recaptcha_check_answer ($site_info['recap_priv'], $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);
        if (!$resp->is_valid) {
            messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['recaptchafail']);
        }                    
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
    mysql_query("INSERT INTO `{$_PREFIX}users` ( `id` , `name` , `email` , `password` , `sig` , `avatar` ) VALUES ( NULL , '" . $name . "', '" . $email . "', '" . md5($pass) . "', '', '' );");
    $_POST['action'] = "";
    $message = $message . $_PWNDATA['forum']['create_account_success'];
    messageRedirect($_PWNDATA['forum_page_title'],$message,"forum.php?do=login");
}

// Delete a post
if ($_GET['do'] == "delete") {
	if ($user['level'] < 2) {
		messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['not_authorized_del_post']);
	}
	$topic = findTopic($_GET['id']);
	mysql_query("DELETE FROM `{$_PREFIX}posts` WHERE `{$_PREFIX}posts`.`id` =" . $_GET['id']);
	messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['post_deleted'],"forum.php?do=viewtopic&id=$topic&p=" . findPage($_GET['id'],$topic));
}

// Delete a topic
if ($_GET['do'] == "deltop") {
	if ($user['level'] < 2) {
		messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['not_authorized_del_topic']);
	}
	mysql_query("DELETE FROM `{$_PREFIX}topics` WHERE `{$_PREFIX}topics`.`id` =" . $_GET['id']);
	mysql_query("DELETE FROM `{$_PREFIX}posts` WHERE `{$_PREFIX}posts`.`topicid` =" . $_GET['id']);
	messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['topic_posts_deleted'],"forum.php");
}

// Sticky a topic
if ($_GET['do'] == "sticktop") {
	if ($user['level'] < 2) {
		messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['not_authorized_sticky_topic']);
	}
	mysql_query("UPDATE `{$_PREFIX}topics` SET `stick` = 1 WHERE `{$_PREFIX}topics`.`id`=" . $_GET['id']);
	messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['topic_stickied'],"forum.php?do=viewtopic&amp;id=" . $_GET['id']);
}

// Unsticky a topic
if ($_GET['do'] == "unsticktop") {
	if ($user['level'] < 2) {
		messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['not_authorized_sticky_topic']);
	}
	mysql_query("UPDATE `{$_PREFIX}topics` SET `stick` = 0 WHERE `{$_PREFIX}topics`.`id`=" . $_GET['id']);
	messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['topic_unsticked'],"forum.php?do=viewtopic&amp;id=" . $_GET['id']);
}

// Sink a topic
if ($_GET['do'] == "sinktop") {
	if ($user['level'] < 2) {
		messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['not_authorized_sticky_topic']);
	}
	mysql_query("UPDATE `{$_PREFIX}topics` SET `stick` = -1 WHERE `{$_PREFIX}topics`.`id`=" . $_GET['id']);
	messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['sunk'],"forum.php?do=viewtopic&amp;id=" . $_GET['id']);
}

// Unsink a topic
if ($_GET['do'] == "unsinktop") {
	if ($user['level'] < 2) {
		messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['not_authorized_sticky_topic']);
	}
	mysql_query("UPDATE `{$_PREFIX}topics` SET `stick` = 0 WHERE `{$_PREFIX}topics`.`id`=" . $_GET['id']);
	messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['unsunk'],"forum.php?do=viewtopic&amp;id=" . $_GET['id']);
}

// Lock a topic
if ($_GET['do'] == "locktop") {
	if ($user['level'] < 2) {
		messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['not_authorized_lock_topic']);
	}
	mysql_query("UPDATE `{$_PREFIX}topics` SET `locked` = 1 WHERE `{$_PREFIX}topics`.`id`=" . $_GET['id']);
	messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['topic_locked'],"forum.php?do=viewtopic&amp;id=" . $_GET['id']);
}

// Unlock a topic
if ($_GET['do'] == "unlocktop") {
	if ($user['level'] < 2) {
		messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['not_authorized_unlock_topic']);
	}
	mysql_query("UPDATE `{$_PREFIX}topics` SET `locked` = 0 WHERE `{$_PREFIX}topics`.`id`=" . $_GET['id']);
	messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['topic_unlocked'],"forum.php?do=viewtopic&amp;id=" . $_GET['id']);
}

// XXX: Begin function calls for output

// Return the display for the right side of the subbar
function post_sub_r($userid) {
    global $_PWNDATA;
    if (isset($_SESSION['sess_id'])){
        $post_sub_r = "<a href=\"forum.php?do=logoff\">{$_PWNDATA['forum']['logout']}</a> | <a href=\"forum.php?do=editprofile\">{$_PWNDATA['forum']['edit_profile']}</a> | ";
        $unread_temp = mysql_query("SELECT `{$_PREFIX}pms`.*, COUNT(`read`) FROM `{$_PREFIX}pms` WHERE `to`=$userid AND `read`=0 GROUP BY `read` ");
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
    $post_sub_r = $post_sub_r . " | <a href=\"forum.php?do=search_form\">{$_PWNDATA['forum']['search_link']}</a>";
    return $post_sub_r;
}

// Return the preview box Iframe
function previewBox() {
    return "<div id=\"previewbox\" style=\"width: 500px; border: 0px; position: absolute; top: 0px; left: 0px;\"></div>";
}

// Return the preview box javascript
function previewBoxScript() {
    return <<<END
<script type="text/javascript">
//<![CDATA[
function showPrev(url) {
    if (url == 'EXIT') {
        document.getElementById('previewbox').innerHTML = "";
    } else {
        document.getElementById('previewbox').innerHTML = "<table class=\"forum_base\" width=\"100%\">" +
                                                          "<tr><td class=\"forum_topic_content\">" + url +
                                                          "</td></tr></table>";
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
        tempX = event.clientX + document.documentElement.scrollLeft;
        tempY = event.clientY + document.documentElement.scrollTop;
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
		    parent.document.getElementById(id).style.height = this.document.body.offsetHeight + "px";
		} else if(document.getElementById) {
		    parent.document.getElementById(id).style.height = this.document.body.scrollHeight + "px";
	    }
    } catch(err) {
        window.status = err.message;
    }
    try {
        if (document.all) {
            parent.document.getElementById(id).style.height = (this.document.body.offsetHeight + 4) + "px";
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
<table class="forum_base" width="100%">
<tr><td class="forum_topic_content" width="100%">

END;
    $bbtext = str_replace("!NL!","\n",$_GET['a']);
    $bbtext = str_replace("\\'","'",$bbtext);
    $bbtext = str_replace("\\\"","\"",$bbtext);
    $bbtext = str_replace("\\\\","\\",$bbtext);
    print BBDecode($bbtext);
    print "\n</td></tr></table>\n</body>\n</html>";
    die("");
}

// Set an entire board as read by this user.
if ($_GET['do'] == "setread") {
    $id = $_GET['id'];
    $temp_res = mysql_query("SELECT * FROM `{$_PREFIX}topics` WHERE board=$id");
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
    $cats = mysql_query("SELECT * FROM `{$_PREFIX}categories` ORDER BY orderid", $db);
    
    while ($cat = mysql_fetch_array($cats)) {
        $category = $cat['id'];
        $block_content =  <<<END
	<div id="category_$category" style="border: 0px">
		<table class="forum_base" width="100%">
END;
        $result = mysql_query("SELECT * FROM `{$_PREFIX}boards` WHERE `catid`=$category ORDER BY orderid", $db);
        while ($row = mysql_fetch_array($result)) {
            if (!($row['vis_level'] > $user['level'])) {
                if ($row['link'] == "NONE") {
                    $readmb = check_read_forum($row['id'],$user['id']);
                    $idd = $row['id'];
                    if ($readmb) {
                        $read_or_not = $_PWNICONS['forum']['board_read'];
                    } else {
                        $read_or_not = "<a href=\"forum.php?do=setread&amp;id=$idd\">{$_PWNICONS['forum']['board_new']}</a>";
                    }
                    $block_content = $block_content .  <<<END
	<tr><td rowspan="2" {$_PWNICONS['forum']['icon_width']} class="forum_board_readicon">$read_or_not</td>
		<td class="forum_board_title"><a href="forum.php?do=viewforum&amp;id=
END;
                    $block_content = $block_content . $row['id'] . "\">" . $row['title'];
                    $block_content = $block_content . "</a></td><td rowspan=\"2\" width=\"30%\" class=\"forum_board_last\" align=\"center\">";
                    $resulta = mysql_query("SELECT * FROM `{$_PREFIX}topics` WHERE board='" . $row['id'] . "' ORDER BY lastpost DESC", $db);
                    $topic = mysql_fetch_array($resulta);
                    $resulta = mysql_query("SELECT * FROM `{$_PREFIX}posts` WHERE topicid='" . $topic['id'] . "' ORDER BY id DESC LIMIT 1", $db);
                    $post = mysql_fetch_array($resulta);
                    $resulta = mysql_query("SELECT * FROM `{$_PREFIX}users` WHERE id='" . $post['authorid'] . "'" , $db);
                    $poster= mysql_fetch_array($resulta);
                    $authid = $poster['id'];
                    $resulta = mysql_query("SELECT COUNT(*) FROM `{$_PREFIX}topics` WHERE board='" . $row['id'] . "' ORDER BY lastpost DESC", $db);
                    $counter = mysql_fetch_array($resulta);
                    $topics_in_board = $counter["COUNT(*)"];
                    $post_time = date("M jS, g:i a", $post['time']);

                    $post_bb = "[b]Posted by:[/b] " . $poster['name'] . "\n" . substr($post['content'],0,500);
                    $post_bb = bbDecode($post_bb);
                    $post_bb = str_replace("\\","\\\\",$post_bb);
                    $post_bb = str_replace("'","\\'",$post_bb);
                    $post_bb = str_replace("\"","&quot;",$post_bb);
                    $post_bb = str_replace("&lt;","&amp;lt;",$post_bb);
                    $post_bb = str_replace("&gt;","&amp;gt;",$post_bb);
                    $post_bb = str_replace("<","&lt;",$post_bb);
                    $post_bb = str_replace(">","&gt;",$post_bb);
                    $spazm = "onmousemove=\"blama=true\" onmouseout=\"showPrev('EXIT');\" onmouseover=\"showPrev('$post_bb');\"";   

                    $block_content = $block_content . "<font size=\"2\"><b>{$_PWNDATA['forum']['last']}: <a href=\"forum.php?do=viewtopic&amp;last=1&amp;id=" . $topic['id'] . "\" $spazm>" . $topic['title'] . "</a></b><br />{$_PWNDATA['forum']['by']}: <a href=\"forum.php?do=viewprofile&amp;id=$authid\">" . $poster['name'] . "</a> $post_time</font></td>";
                    $block_content = $block_content . "<td rowspan=\"2\" align=\"center\" width=\"70\" class=\"forum_board_topics\">$topics_in_board {$_PWNDATA['forum']['topics']}</td>";
                    $block_content = $block_content . "<td rowspan=\"2\" align=\"center\" width=\"70\" class=\"forum_board_posts\">" . getPostsInBoard($row['id']) . " {$_PWNDATA['forum']['posts']}</td></tr>";
                    $block_content = $block_content . "\n	<tr><td class=\"forum_board_desc\">" . $row['desc'] . "</td></tr>";
                } else {
                    // Has a link.
                    $link = $row['link'];
                    $block_content = $block_content .  <<<END
	<tr><td rowspan="2" {$_PWNICONS['forum']['icon_width']} class="forum_board_linkicon">{$_PWNICONS['forum']['weblink']}</td>
END;
                    $block_content = $block_content . "<td class=\"forum_board_linktitle\"><a href=\"$link\">" . $row['title'];
                    $block_content = $block_content . "</a></td><td rowspan=\"2\" width=\"30%\" class=\"forum_board_last\"></td>";
                    $block_content = $block_content . "<td rowspan=\"2\" align=\"center\" width=\"70\" class=\"forum_board_topics\"></td><td rowspan=\"2\" align=\"center\" width=\"70\" class=\"forum_board_posts\"></td></tr>";
                    $block_content = $block_content . "\n	<tr><td class=\"forum_board_linkdesc\">" . $row['desc'] . "</td></tr>";
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
           <div align="center">
                <form action="forum.php" method="post">
                  <input type="hidden" name="admin" value="$adminlog" />
                  <input type="hidden" name="action" value="login" />
                  {$_PWNDATA['profile']['username']}:<br />
                  <input type="text" name="uname" size="20" /><br />
                  {$_PWNDATA['profile']['password']}:<br />
                  <input type="password" name="upass" size="20" /><br />
                  <input type="checkbox" name="remember" value="ON" />{$_PWNDATA['forum']['remember_me']}<br />
                  <input type="submit" value="{$_PWNDATA['forum']['login']}" name="B1" />
                </form></div>
END;
    $post_content = makeBlock($_PWNDATA['forum']['login'],"&nbsp;",$block_content);
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
    if ($site_info['security_mode'] == 0) {
    $SECURITY = <<<END
<tr><td class="forum_topic_sig" colspan="2"><img src="forum.php?do=secimg" alt="{$_PWNDATA['forum']['secimg']}" /></td></tr>
<tr><td class="forum_topic_sig">{$_PWNDATA['forum']['sec_code']}</td><td class="forum_topic_sig"><input type="text" name="code" size="20" style="width: 100%" /></td></tr>
END;
    } else if ($site_info['security_mode'] == 1) {
        $SECURITY = "(Your registration will automatically fail as this CAPTCHA mode is invalid)<br />";
    } else if ($site_info['security_mode'] == 2) {
        require_once('recaptchalib.php');
        $SECURITY = "<tr><td class=\"forum_topic_sig\" colspan=\"2\">" . recaptcha_get_html($site_info['recap_pub']) . "</td></tr>";
    }
    $block_content = <<<END
		<form method="post" action="forum.php">
              <input type="hidden" name="action" value="newuser" />
              <table class="forum_base" width="100%">
              <tr><td class="forum_topic_sig" width="200">{$_PWNDATA['profile']['username']}</td><td class="forum_topic_sig"><input type="text" name="name" size="20" style="width: 100%" /></td></tr>
              <tr><td class="forum_topic_sig">{$_PWNDATA['profile']['email']}</td><td class="forum_topic_sig"><input type="text" name="email" size="20" style="width: 100%" /></td></tr>
              <tr><td class="forum_topic_sig">{$_PWNDATA['profile']['confirm']}</td><td class="forum_topic_sig"><input type="text" name="cemail" size="20" style="width: 100%" /></td></tr>
              <tr><td class="forum_topic_sig">{$_PWNDATA['profile']['password']}</td><td class="forum_topic_sig"><input type="password" name="pass" size="20" style="width: 100%" /></td></tr>
              <tr><td class="forum_topic_sig">{$_PWNDATA['profile']['confirm']}</td><td class="forum_topic_sig"><input type="password" name="cpass" size="20" style="width: 100%" /></td></tr>
              $SECURITY
              <tr><td class="forum_topic_sig" colspan="2"><input type="submit" value="{$_PWNDATA['forum']['register']}" /></td></tr>
              </table>
            </form>
END;
    $post_content = makeBlock($_PWNDATA['forum']['register'],"&nbsp;",$block_content);
}

// Show the topics in this board.
if ($_GET['do'] == "viewforum") {
    $result = mysql_query("SELECT * FROM `{$_PREFIX}boards` WHERE id='" . $_GET['id'] . "'", $db);
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
        $block_content = $block_content . drawButton("forum.php?do=newtopic&amp;id=" . $board['id'], $_PWNDATA['forum']['new_topic'],$_PWNICONS['buttons']['new_topic']);
    }
    if (!isset($_GET['p'])) {
        $page = 0;
    } else {
        $page = ($_GET['p'] - 1) * $_THREADSPERPAGE;
    }
    if ($page > 0) {
        $block_content = $block_content . drawButton("forum.php?do=viewforum&amp;id=" . $board['id'] . "&amp;p=" . ($page / $_THREADSPERPAGE), $_PWNDATA['forum']['previous_page'],$_PWNICONS['buttons']['previous']);
    }
    $temp_mysql = mysql_query("SELECT COUNT(*) FROM `{$_PREFIX}topics` WHERE board='" . $board['id'] . "'", $db);
    $temp_res = mysql_fetch_array($temp_mysql);
    $total_posts = $temp_res['COUNT(*)'];
    if ((int)(($total_posts - 1) / $_THREADSPERPAGE + 1) > 1) {
        $block_content = $block_content . printPager("forum.php?do=viewforum&amp;id={$board['id']}&amp;p=",(int)($page / $_THREADSPERPAGE + 1),(int)(($total_posts - 1) / $_THREADSPERPAGE + 1));
    }
    if ($total_posts > $page + $_THREADSPERPAGE) {
        $block_content = $block_content . drawButton("forum.php?do=viewforum&amp;id=" . $board['id'] . "&amp;p=" . ($page / $_THREADSPERPAGE + 2), $_PWNDATA['forum']['next_page'],$_PWNICONS['buttons']['next']);
    }
    $block_content = $block_content .   <<<END
		</tr></table>
		<table class="forum_base" width="100%">
END;
    $result = mysql_query("SELECT * FROM `{$_PREFIX}topics` WHERE board='" . $board['id'] . "' ORDER BY stick DESC, lastpost DESC LIMIT $page, $_THREADSPERPAGE", $db);
    while ($row = mysql_fetch_array($result)) {
        $readmb = check_read($row['id'],$user['id']);
        $resultb = mysql_query("SELECT * FROM `{$_PREFIX}users` WHERE id='" . $row['authorid'] . "'" , $db);
        $rowb = mysql_fetch_array($resultb);
        $resultc = mysql_query("SELECT * FROM `{$_PREFIX}posts` WHERE topicid='" . $row['id'] . "' ORDER BY id ASC LIMIT 1", $db);
        $firstpost = mysql_fetch_array($resultc);
        $resultc = mysql_query("SELECT * FROM `{$_PREFIX}posts` WHERE topicid='" . $row['id'] . "' ORDER BY id DESC LIMIT 1", $db);
        $rowc = mysql_fetch_array($resultc);
        $result_posts = mysql_query("SELECT COUNT(*) FROM `{$_PREFIX}posts` WHERE topicid='" . $row['id'] . "'", $db);
        $posts_counter = mysql_fetch_array($result_posts);
        $resultd = mysql_query("SELECT * FROM `{$_PREFIX}users` WHERE id='" . $rowc['authorid'] . "'" , $db);
        $rowd = mysql_fetch_array($resultd);
        $post_bb = "[b]Posted by:[/b] " . $rowb['name'] . "\n" . substr($firstpost['content'],0,500);
        $post_time = date("M jS, g:i a", $rowc['time']);
        $post_bb = bbDecode($post_bb);
        $post_bb = str_replace("\\","\\\\",$post_bb);
        $post_bb = str_replace("'","\\'",$post_bb);
        $post_bb = str_replace("\"","&quot;",$post_bb); 
        $post_bb = str_replace("&lt;","&amp;lt;",$post_bb);
        $post_bb = str_replace("&gt;","&amp;gt;",$post_bb);
        $post_bb = str_replace("<","&lt;",$post_bb);
        $post_bb = str_replace(">","&gt;",$post_bb);
        $spazm = "onmousemove=\"blam=true\" onmouseout=\"showPrev('EXIT');\" onmouseover=\"showPrev('$post_bb');\"";
        $post_bb = "[b]Posted by:[/b] " . $rowd['name'] . "\n" . substr($rowc['content'],0,500);
        $post_bb = bbDecode($post_bb);
        $post_bb = str_replace("\\","\\\\",$post_bb);
        $post_bb = str_replace("'","\\'",$post_bb);
        $post_bb = str_replace("\"","&quot;",$post_bb);
        $post_bb = str_replace("&lt;","&amp;lt;",$post_bb);
        $post_bb = str_replace("&gt;","&amp;gt;",$post_bb);
        $post_bb = str_replace("<","&lt;",$post_bb);
        $post_bb = str_replace(">","&gt;",$post_bb);
        $spazma = "onmousemove=\"blama=true\" onmouseout=\"showPrev('EXIT');\" onmouseover=\"showPrev('$post_bb');\"";
        $read_or_not = "<td rowspan=\"2\" class=\"forum_thread_icon\" {$_PWNICONS['forum']['topic']}>";
        $topic_type = "";
        if (!$readmb) {
            $read_or_not = $read_or_not . $_PWNICONS['forum']['topic_read'];
        }
        if ($row['has_poll'] == 1) {
	        $read_or_not = $read_or_not . $_PWNICONS['forum']['topic_poll'];
	        $topic_type = $topic_type . $_PWNDATA['forum']['poll'] . " ";
        }
        if ($row['locked'] == 1) {
	        $read_or_not = $read_or_not . $_PWNICONS['forum']['topic_lock'];
	        $topic_type = $topic_type . $_PWNDATA['forum']['locked'] . " ";
        }
        if ($row['stick'] == 1) {
	        $read_or_not = $read_or_not . $_PWNICONS['forum']['topic_stick'];
	        $topic_type = $topic_type . $_PWNDATA['forum']['sticky'] . " ";
        } else if ($row['stick'] == -1) {
            $read_or_not = $read_or_not . $_PWNICONS['forum']['topic_sink'];
            $topic_type = $topic_type . $_PWNDATA['forum']['issunk'] . " ";
        }
        $read_or_not = $read_or_not . "</td><td class=\"forum_thread_title\"><font class=\"forum_base_text\"><b>{$topic_type}</b></font> ";
        $diver = $row['id'];
        $block_content = $block_content .   <<<END
	<tr>
		$read_or_not<div id="title_$diver" style="display: inline;" $spazm><a href="forum.php?do=viewtopic&amp;id=
END;
        $block_content = $block_content .  $row['id'] . "\">" . $row['title'] . "</a>";
        $top_temp = $row['id'];
        $author = $rowb['name'];
        $authid = $rowb['id'];
        $posts_in_topic = $posts_counter['COUNT(*)'];
        $pages = (floor(($posts_in_topic - 1) / $_POSTSPERPAGE));
        $pagination = "";
        if ($pages > 0) {
            $pagination = printPagerNonTabular("forum.php?do=viewtopic&amp;id=$top_temp&amp;p=",0,$pages + 1);
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
</div><div class="forum_edit_title">&nbsp;<a href="javascript: flipVisibility('title_$diver'); flipVisibility('titleedit_$diver');">{$_PWNDATA['forum']['edit_title']}</a></div>

END;
        } else {
            $edtitle = " ";
        }
        $block_content = $block_content .  "</div>\n$edtitle</td><td rowspan=\"2\" width=\"30%\" class=\"forum_thread_last\" align=\"center\">";
        $authid = $rowd['id'];
        $block_content = $block_content .  "\n<b><a href=\"forum.php?do=viewtopic&amp;id=$top_temp&amp;last=1\" $spazma>{$_PWNDATA['forum']['last_post']}</a> {$_PWNDATA['forum']['by']}:</b> <a href=\"forum.php?do=viewprofile&amp;id=$authid\">" . $rowd['name'] . "</a><br />{$_PWNDATA['forum']['at']}: $post_time</td></tr>";
        $block_content = $block_content . "<tr><td class=\"forum_thread_author\">\n{$_PWNDATA['forum']['author']}: <a href=\"forum.php?do=viewprofile&amp;id=$authid\">$author</a>$pagination</td></tr>";
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
    $block_content = "";

    if(isset($_SESSION['sess_id'])) {
        $block_content = $block_content . <<<END
<table class="mod_set">
<tr>
END;
        $block_content = $block_content . drawButton("forum.php?do=newpm",$_PWNDATA['pm']['new_pm'],$_PWNICONS['buttons']['new_pm']);
        $block_content = $block_content . drawButton("forum.php?do=delpm&amp;id=ALL",$_PWNDATA['pm']['empty_box']);
        $block_content = $block_content . <<<END
</tr>
</table>
END;
    }
    $block_content = $block_content .  <<<END
		<table class="forum_base" width="100%">
END;

    $pmresult = mysql_query("SELECT * FROM `{$_PREFIX}pms` WHERE `to`=" . $user['id'] . " ORDER BY id DESC", $db);
    while ($row = mysql_fetch_array($pmresult)) {
        $readmb = $row['read'];
        if ($readmb == 1) {
            $read_or_not = $_PWNICONS['forum']['pm_read'];
        } else {
            $read_or_not = $_PWNICONS['forum']['pm_new'];
        }
        $block_content = $block_content .  <<<END
	<tr>
		<td class="forum_thread_icon" {$_PWNICONS['forum']['icon_width']} rowspan="2">$read_or_not</td><td class="forum_thread_title"><a href="forum.php?do=readpm&amp;id=
END;
//"
        $resultb = mysql_query("SELECT * FROM `{$_PREFIX}users` WHERE id='" . $row['from'] . "'" , $db);
        $rowb = mysql_fetch_array($resultb);
        $block_content = $block_content . $row['id'] . "\">" . $row['title'];
        $author = $rowb['name'];
        $authid = $rowb['id'];
        $tim = date("F j, Y (g:ia T)", $row['time']);
        $block_content = $block_content . "</a></td></tr><tr><td class=\"forum_thread_author\">{$_PWNDATA['pm']['from']}: <a href=\"forum.php?do=viewprofile&amp;id=$authid\">$author</a>, {$_PWNDATA['pm']['sent_at']} $tim</td></tr>";
    }

    $block_content = $block_content . "</table>";
    $post_content = makeBlock($_PWNDATA['pm']['view'],"",$block_content);
}

// Delete PM
if ($_GET['do'] == "delpm") {
    $tomustbe = $user['id'];
    if ($_GET['id'] != "ALL") {
        $pmresult = mysql_query("SELECT * FROM `{$_PREFIX}pms` WHERE `id`=" . $_GET['id'] . " AND `to`=$tomustbe", $db);
    } else {
        $pmresult = mysql_query("SELECT * FROM `{$_PREFIX}pms` WHERE `to`=$tomustbe", $db);
    }
    $pm = mysql_fetch_array($pmresult);
    if (!isset($_SESSION['sess_id'])) {
        messageBack($_PWNDATA['pm']['view'],$_PWNDATA['pm']['must_be_logged_in']);
    }
    if (($user['id'] != $pm['to']) and ($user['level'] < 3)) {
        messageBack($_PWNDATA['pm']['view'],$_PWNDATA['pm']['only_admins']);
    }
    if ($_GET['id'] != "ALL") {
	    mysql_query("DELETE FROM `{$_PREFIX}pms` WHERE `{$_PREFIX}pms`.`id` =" . $_GET['id']);
	    messageRedirect($_PWNDATA['pm']['view'],$_PWNDATA['pm']['pm_deleted'],"forum.php?do=pmbox");
    } else {
	    mysql_query("DELETE FROM `{$_PREFIX}pms` WHERE `to`=$tomustbe");
	    messageRedirect($_PWNDATA['pm']['view'],$_PWNDATA['pm']['box_emptied'],"forum.php?do=pmbox");
    }
}

// View a PM
if ($_GET['do'] == "readpm") {
    $pmresult = mysql_query("SELECT * FROM `{$_PREFIX}pms` WHERE `id`=" . $_GET['id'], $db);
    $pm = mysql_fetch_array($pmresult);
    $resultb = mysql_query("SELECT * FROM `{$_PREFIX}users` WHERE id='" . $pm['from'] . "'" , $db);
    $fromuser = mysql_fetch_array($resultb);
    if (($user['id'] != $pm['to']) and ($user['level'] < 3)) {
        messageBack($_PWNDATA['pm']['view'],$_PWNDATA['pm']['only_admins']);
    }
    $replyto = $fromuser['id'];
    $replytitle = "Re: " . $pm['title'];
    $pid = $pm['id'];
    mysql_query("UPDATE `{$_PREFIX}pms` SET `read` =1 WHERE `{$_PREFIX}pms`.`id` =" . $pm['id']);
    $post_title_add = " :: {$_PWNDATA['pm']['view']} :: {$_PWNDATA['pm']['reading']} '" . $pm['title'] . "'";
    $post_sub_add = " > <a href=\"forum.php?do=pmbox\">{$_PWNDATA['pm']['view']}</a> > {$_PWNDATA['pm']['reading']} \"" . $pm['title'] . "\"";
    $post_sub_r = post_sub_r($user['id']);
    $block_content = "";
    $block_content = $block_content .  "<table class=\"mod_set\"><tr>";
    $block_content = $block_content . drawButton("forum.php?do=newpm&amp;to=$replyto&amp;s=$replytitle",$_PWNDATA['pm']['reply']);
    $block_content = $block_content . drawButton("forum.php?do=delpm&amp;id=$pid",$_PWNDATA['pm']['delete']);
    $block_content = $block_content . drawButton("forum.php?do=newpm&amp;to=$replyto&amp;s=$replytitle&amp;q=$pid",$_PWNDATA['pm']['quote']);
    $block_content = $block_content . "</tr></table><table class=\"forum_base\" width=\"100%\"><tr><td class=\"forum_topic_content\">";
    $block_content = $block_content . BBDecode($pm['content']);
    $block_content = $block_content . "</td></tr></table>";
    $post_content = makeBlock($pm['title'] . " {$_PWNDATA['pm']['from']} <a href=\"forum.php?do=viewprofile&amp;id=" . $fromuser['id'] . "\">" . $fromuser['name'] . "</a>","{$_PWNDATA['pm']['sent_at']} " . date("F j, Y (g:ia T)", $pm['time']),$block_content);
    
}

// New PM (compose)
if ($_GET['do'] == "newpm") {
    if (!isset($_SESSION['sess_id'])){
        messageBack($_PWNDATA['pm']['view'],$_PWNDATA['pm']['not_logged_in']);
    }
    if ($_GET['to'] != "") {
        $result = mysql_query("SELECT * FROM `{$_PREFIX}users` WHERE id='" . $_GET['to'] . "'", $db);
        $touser = mysql_fetch_array($result);
        $tousername = $touser['name'];
    }
    $quoted = "";
    if ($_GET['q'] != "") {
        $result = mysql_query("SELECT * FROM `{$_PREFIX}pms` WHERE id='" . $_GET['q'] . "'", $db);
        $quotedpm = mysql_fetch_array($result);
        $result = mysql_query("SELECT * FROM `{$_PREFIX}users` WHERE id='" . $quotedpm['from'] . "'", $db);
        $quoteduser = mysql_fetch_array($result);
        $quoted = "[quote][b]{$_PWNDATA['pm']['original_message']} " . $quoteduser['name'] . ":[/b]\n" . $quotedpm['content']. "[/quote]\n";
    }
    $subjto = $_GET['s'];
    $post_title_add = " :: " . $_PWNDATA['pm']['composing'];
    $post_sub_add = " > " . $_PWNDATA['pm']['composing'];
    $post_sub_r = post_sub_r($user['id']);
    $block_content = "";
    $block_content = $block_content .  printPoster('content') . <<<END
<form action="forum.php" method="post" name="form">
<input type="hidden" name="action" value="new_pm" />
<table class="forum_base" width="100%">
<tr><td class="forum_topic_content" width="300">{$_PWNDATA['pm']['to']}</td>
<td class="forum_topic_content"><input type="text" name="toline" style="width:100%" value="$tousername" /></td></tr>
<tr><td class="forum_topic_sig">{$_PWNDATA['pm']['subject']}</td>
<td class="forum_topic_sig"><input type="text" name="subj" style="width:100%" value="$subjto" /></td></tr>
<tr><td class="forum_topic_sig" colspan="2">{$_PWNDATA['pm']['body']}</td></tr>
<tr><td class="forum_topic_sig" colspan="2"><textarea rows="11" name="content" style="width:100%;" cols="20">$quoted</textarea></td></tr>
<tr><td class="forum_topic_sig" colspan="2"><input type="submit" value="{$_PWNDATA['pm']['send']}" name="sub" /></td></tr>
</table>
END;
    $block_content = $block_content . "<input type=\"hidden\" name=\"board\" value=\"" . $board['id'] . "\" />";
    $block_content = $block_content . "<input type=\"hidden\" name=\"user\" value=\"" . $user['id'] . "\" />";
    $block_content = $block_content . "</form>";
    $post_content = makeBlock($_PWNDATA['pm']['composing'],"",$block_content);
}

// Split topic
if ($_GET['do'] == "splittopic") {
    if ($user['level'] < $site_info['mod_rank']) {
        messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['not_permitted'],"index.php");
    }
    $result = mysql_query("SELECT * FROM `{$_PREFIX}topics` WHERE id='" . $_GET['id'] . "'", $db);
    $topic = mysql_fetch_array($result);
    $resultb = mysql_query("SELECT * FROM `{$_PREFIX}boards` WHERE id='" . $topic['board'] . "'", $db);
    $board = mysql_fetch_array($resultb);   
    $post_title_add = " :: " . $_PWNDATA['forum']['modtools']['splittopic'];
    $post_sub_add = " > " . $_PWNDATA['forum']['modtools']['splittopic'];
    $post_sub_r = post_sub_r($user['id']);
    $block_content = <<<END
<form method="post" action="forum.php" name="form">
<input type="hidden" name="action" value="split_topic" />
<input type="hidden" name="topic" value="{$topic['id']}" />
<input type="hidden" name="board" value="{$board['id']}" />
<table class="forum_base" width="100%">
<tr><td class="forum_topic_content" width="200" align="center">{$_PWNDATA['forum']['modtools']['new_title']}</td>
<td colspan="2" class="forum_topic_content"><input type="text" style="width: 100%;" name="newtitle" /></td></tr>
END;
    $result = mysql_query("SELECT * FROM `{$_PREFIX}posts` WHERE topicid='" . $topic['id'] . "'", $db);
    while ($row = mysql_fetch_array($result)) {
        $resultb = mysql_query("SELECT * FROM `{$_PREFIX}users` WHERE id='" .  $row['authorid'] . "'", $db);
        $post_author = mysql_fetch_array($resultb);
        $block_content = $block_content . "<tr><td class=\"glow\">";
        $block_content = $block_content . $post_author['name'];
        $block_content = $block_content . "</td><td class=\"forum_topic_content\">";
        $postbb = bbDecode(substr($row['content'],0,500));
        $block_content = $block_content . $postbb . "</td><td class=\"forum_topic_content\" width=\"20\">";
        $block_content = $block_content . "<input type=\"checkbox\" name=\"post_" . $row['id'] . "\" />";
        $block_content = $block_content . "</td></tr>";
    }
    $block_content = $block_content . <<<END
<tr><td colspan="3" class="forum_topic_sig">
<input type="submit" value="{$_PWNDATA['forum']['modtools']['split']}" />
</td></tr>
END;
    $block_content = $block_content . "</table></form>";
    $post_content = makeBlock($_PWNDATA['forum']['modtools']['splittopic'],"",$block_content);
}

if ($_GET['do'] == "mergetopics") {
    if ($user['level'] < $site_info['mod_rank']) {
        messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['not_permitted'],"index.php");
    }
    $result = mysql_query("SELECT * FROM `{$_PREFIX}topics` WHERE id='" . $_GET['id'] . "'", $db);
    $topic = mysql_fetch_array($result);
    $resultb = mysql_query("SELECT * FROM `{$_PREFIX}boards` WHERE id='" . $topic['board'] . "'", $db);
    $board = mysql_fetch_array($resultb);   
    $post_title_add = " :: " . $_PWNDATA['forum']['modtools']['mergetopic'];
    $post_sub_add = " > " . $_PWNDATA['forum']['modtools']['mergetopic'];
        $post_sub_r = post_sub_r($user['id']);
    $block_content = <<<END
<form method="post" action="forum.php" name="form">
<input type="hidden" name="action" value="merge_topics" />
<input type="hidden" name="topic" value="{$topic['id']}" />
<input type="hidden" name="board" value="{$board['id']}" />
<table class="forum_base" width="100%">
<tr><td colspan="3" class="forum_topic_content">{$_PWNDATA['forum']['modtools']['merging']}<a href="forum.php?do=viewtopic&amp;id={$topic['id']}">{$topic['title']}</a></td></tr>
END;
    $result = mysql_query("SELECT * FROM `{$_PREFIX}topics` WHERE `board`={$board['id']}", $db);
    while ($row = mysql_fetch_array($result)) {
        if ($row['id'] != $topic['id']) {
            $resultb = mysql_query("SELECT * FROM `{$_PREFIX}users` WHERE id='" .  $row['authorid'] . "'", $db);
            $post_author = mysql_fetch_array($resultb);
            $block_content = $block_content . "<tr><td class=\"glow\" width=\"150\">";
            $block_content = $block_content . $post_author['name'];
            $block_content = $block_content . "</td><td class=\"forum_topic_content\">";
            $block_content = $block_content . $row['title'] . "</td><td class=\"forum_topic_content\" width=\"20\">";
            $block_content = $block_content . "<input type=\"radio\" name=\"topic_" . $row['id'] . "\" />";
            $block_content = $block_content . "</td></tr>";
        }
    }
    $block_content = $block_content . <<<END
<tr><td colspan="3" class="forum_topic_sig">
<input type="submit" value="{$_PWNDATA['forum']['modtools']['merge']}" />
</td></tr>
END;
    $block_content = $block_content . "</table></form>";
    $post_content = makeBlock($_PWNDATA['forum']['modtools']['splittopic'],"",$block_content);
}

// Show the posts in this topic.
if ($_GET['do'] == "viewtopic") {
    $result = mysql_query("SELECT * FROM `{$_PREFIX}topics` WHERE id='" . $_GET['id'] . "'", $db);
    $topic = mysql_fetch_array($result);
    $resultb = mysql_query("SELECT * FROM `{$_PREFIX}boards` WHERE id='" . $topic['board'] . "'", $db);
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
    $title_content = "";
    if ($topic['locked'] == 1) {
        $title_content = $title_content . "[{$_PWNDATA['forum']['locked']}] ";
    }
    $title_content = $title_content . "<a href=\"#qreply_bm\">" . $topic['title'] . "</a>";
    $resultb = mysql_query("SELECT * FROM `{$_PREFIX}users` WHERE id='" .  $topic['authorid'] . "'", $db);
    $author = mysql_fetch_array($resultb);
    $block_content = "";
    $block_content = $block_content .  <<<END
		<table class="forum_base" width="100%">
END;
    if ($topic['has_poll'] == 1) {
        $pollresults = mysql_query("SELECT * FROM `{$_PREFIX}polls` WHERE `id`=" . $topic['poll_id']);
        $poll = mysql_fetch_array($pollresults);
        // Our topic has a poll, draw the voting array.
        $pid = $poll['id'];
        $tid = $topic['id'];
        $block_content = $block_content . <<<END
	<tr>
		<td colspan="2" align="center" class="forum_topic_poll"><form name="pollresponse" method="post" action="forum.php">
		<input type="hidden" name="action" value="vote_poll" />
		<input type="hidden" name="pid" value="$pid" />
		<input type="hidden" name="tid" value="$tid" />
		<table class="forum_poll_table">
END;
        $block_content = $block_content . "<tr><td colspan=\"2\" align=\"center\" class=\"forum_topic_poll_title\">" . $poll['title'] . "</td></tr>\n\n";
        $hasVoted = check_voted($poll['id'],$user['id']);
        if ($user['level'] < 1) {
	        $hasVoted = true;
        }
        $poll_options = split(",",$poll['op1_name']);
        $poll_count = count($poll_options);
        $poll_votes = split(",",$poll['op1_votes']);
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
        $img = 0;
        for ($i=0;$i<$poll_count;$i++) {
            if ($img == $_PWNICONS['forum']['pollbars']) {
                $img = 0;
            }
	        if ($hasVoted == false) {
		        $bounce = "<input type=\"radio\" name=\"poll\" value=\"$i\" />";
	        }
	        $block_content = $block_content . "<tr><td class=\"forum_topic_poll_option\" align=\"right\">$bounce<font class=\"forum_body\">" . $poll_options[$i] . "</font></td>\n";
	        $wid = ($poll_votes[$i] / $totalVotes) * $widthOfBar;
	        $block_content = $block_content . "<td class=\"forum_topic_poll_votebar\" align=\"left\"><img src=\"{$_PWNICONS['forum']['pollpath']}$img/poll_left.png\" alt=\"[\"/><img src=\"{$_PWNICONS['forum']['pollpath']}$img/poll_mid.png\" height=\"10\" width=\"$wid\" alt=\"$wid\"/><img src=\"{$_PWNICONS['forum']['pollpath']}$img/poll_right.png\" alt=\"]\"/><font size=\"1\"> (" . (int)$poll_votes[$i] . ") </font></td></tr>\n";
	        $img++;
        }
        if ($hasVoted == false) {
	        $submitPoll = "<input type=\"submit\" value=\"{$_PWNDATA['forum']['vote']}\" />";
        }
        $block_content = $block_content . <<<END
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
        $temp_mysql = mysql_query("SELECT COUNT(*) FROM `{$_PREFIX}posts` WHERE topicid='" . $topic['id'] . "'", $db);
        $temp_res = mysql_fetch_array($temp_mysql);
        $last_rep_id = $temp_res['COUNT(*)'] - 1;
        $page = (floor($last_rep_id / $_POSTSPERPAGE)) * $_POSTSPERPAGE;
    }
    $PAGING = "";
    $temp_mysql = mysql_query("SELECT COUNT(*) FROM `{$_PREFIX}posts` WHERE topicid='" . $topic['id'] . "'", $db);
    $posts_counter = mysql_fetch_array($temp_mysql);
    $posts_in_topic = $posts_counter['COUNT(*)'];
    $pages = (floor(($posts_in_topic - 1) / $_POSTSPERPAGE));
    $top_id = $topic['id'];
    if ($pages > 0) {
        $PAGING = printPager("forum.php?do=viewtopic&amp;id=$top_id&amp;p=",(floor($page / $_POSTSPERPAGE)) + 1,$pages+1);
    }
    /*if ($pages > 0) {
        $PAGING = $PAGING . "<td> &nbsp;&nbsp;&nbsp;{$_PWNDATA['forum']['goto']}: ";
        for ($page_count = 1; $page_count <= $pages + 1; $page_count += 1) {
            if ($page_count != (floor($page / $_POSTSPERPAGE)) + 1) {
                $PAGING = $PAGING . "<a href=\"forum.php?do=viewtopic&amp;id=$top_id&amp;p=$page_count\">$page_count</a>"; 
            } else {
                $PAGING = $PAGING . "<b>$page_count</b>";
            }
            if ($page_count != $pages + 1) {
                $PAGING = $PAGING . ", ";
            }
        }
        $PAGING = $PAGING . "</td>";
    }*/
    $block_content = $block_content .  <<<END
	<tr><td class="forum_topic_buttonbar" colspan="2"><table style="border: 0px" class="borderless_table"><tr>
END;
    if ((!($board['post_level'] > $user['level'])) and ($islocked == false)) {
        $block_content = $block_content . drawButton("forum.php?do=newreply&amp;id=" . $topic['id'],$_PWNDATA['forum']['add_reply'],$_PWNICONS['buttons']['new_reply']);
    }
    $block_content = $block_content . $PAGING . "</tr></table></td></tr>";
    $result = mysql_query("SELECT * FROM `{$_PREFIX}posts` WHERE topicid='" . $topic['id'] . "' LIMIT $page, $_POSTSPERPAGE", $db);
    while ($row = mysql_fetch_array($result)) {
        $resultb = mysql_query("SELECT * FROM `{$_PREFIX}users` WHERE id='" .  $row['authorid'] . "'", $db);
        $post_author = mysql_fetch_array($resultb);
        $topglow = "class=\"glow\"";
        if ($post_author['level'] >= $site_info['mod_rank']) {
            $topglow = "class=\"glow_mod\"";
        }
        if ($post_author['level'] >= $site_info['admin_rank']) {
            $topglow = "class=\"glow_admin\"";
        }
        $block_content = $block_content .  <<<END
	<tr>
		<td width="15%" valign="top" $topglow rowspan="2">
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
	            $auth_info = $auth_info . $_PWNICONS['protocols']['pand_on'] . "</a>";
	            } else {
	            $auth_info = $auth_info . $_PWNICONS['protocols']['pand_off'] . "</a>";
	            }
            } else {
                $auth_info = $auth_info . $_PWNICONS['protocols']['pand_on'] . "</a>";
            }
        }
        if ($has_messenger) {
            $messaging = "[b]" . $post_author['name'] . "[/b]\n[img]{$_PWNICONS['protocols']['messaging']}[/img]\n[img]{$_PWNICONS['protocols']['icons']['msn']}[/img]: $authmsn\n[img]{$_PWNICONS['protocols']['icons']['yahoo']}[/img]: $authyahoo\n[img]{$_PWNICONS['protocols']['icons']['aim']}[/img]: $authaim\n[img]{$_PWNICONS['protocols']['icons']['icq']}[/img]: $authicq\n[img]{$_PWNICONS['protocols']['icons']['xfire']}[/img]: $authxf\n[img]{$_PWNICONS['protocols']['icons']['live']}[/img]: $authlive\n[img]{$_PWNICONS['protocols']['icons']['pand']}[/img]: $authpand\n";
            $post_bb = bbDecode($messaging);
            $post_bb = str_replace("\\","\\\\",$post_bb);
            $post_bb = str_replace("'","\\'",$post_bb);
            $post_bb = str_replace("\"","&quot;",$post_bb);
            $post_bb = str_replace("&lt;","&amp;lt;",$post_bb);
            $post_bb = str_replace("&gt;","&amp;gt;",$post_bb);
            $post_bb = str_replace("<","&lt;",$post_bb);
            $post_bb = str_replace(">","&gt;",$post_bb);
            $auth_info = "<img src=\"{$_PWNICONS['protocols']['messaging']}\" onmousemove=\"blam=true\" onmouseout=\"showPrev('EXIT');\" onmouseover=\"showPrev('$post_bb')\" alt=\"Messaging\"/><br />" . $auth_info;
        }
        $postinfo = "";
        if ($user['level'] > 0) {
	        // Yes, this can exclude some members, but we don't really care because they're BANNED. (Level = 0)
	        $postinfo = "<br />$pCount posts";
        }
        $block_content = $block_content . "<font class=\"forum_user\"><a href=\"forum.php?do=viewprofile&amp;id=$authid\">" . $post_author['name'] . "</a><br />" . $ava . $auth_info . $postinfo . "</font>";
        $block_content = $block_content . "</td>\n<td valign=\"top\" class=\"forum_topic_content\"><div align=\"right\" class=\"forum_time\">{$_PWNDATA['forum']['posted_at']} " . date("F j, Y (g:ia T)", $row['time']) . "</div>\n<div id=\"post_content_" . $row['id'] . "\">";
        $block_content = $block_content . "\n" . $contenta;
        if (($user['id'] == $post_author['id']) or ($user['level'] >= $site_info['mod_rank'])) {
            $post_bb = str_replace("\"","&quot;",$row['content']);
            $post_bb = str_replace("<","&lt;",$post_bb);
            $post_bb = str_replace(">","&gt;",$post_bb);
            $block_content = $block_content . "</div><div style=\"display: none\" id=\"post_edit_" . $row['id'] . "\">";
            $block_content = $block_content . printPosterEditor('content',$row['id']) . <<<END
    <form action="forum.php" method="post" name="form_{$row['id']}">
        <input type="hidden" name="action" value="edit_reply" />
        <table class="forum_base" width="100%">
            <tr><td class="forum_topic_content"><textarea rows="11" name="content" style="width:100%; font-family:Tahoma; font-size:10pt" cols="80">{$post_bb}</textarea></td></tr>
            <tr><td class="forum_topic_sig"><input type="submit" value="{$_PWNDATA['forum']['save_changes']}" name="sub" /></td></tr>
        </table>
        <input type="hidden" name="id" value="{$row['id']}" />
        <input type="hidden" name="topic" value="{$row['topicid']}" />
    </form>
END;
        }
        $block_content = $block_content . "\n</div></td></tr><tr><td class=\"forum_topic_sig\">" . $contentb;
        $block_content = $block_content . "\n</td></tr><tr><td colspan=\"2\" class=\"forum_button_bar\" align=\"right\"><table class=\"borderless_table\"><tr>\n";
        // Is this the viewing member's post?
        if (($user['id'] == $post_author['id']) or ($user['level'] >= $site_info['mod_rank'])) {
            
            $block_content = $block_content . drawButton("javascript:flipVisibility('post_content_{$row['id']}'); flipVisibility('post_edit_{$row['id']}');",$_PWNDATA['forum']['qedit'],$_PWNICONS['buttons']['qedit']);
            $block_content = $block_content . drawButton("forum.php?do=editreply&amp;id=" . $row['id'],$_PWNDATA['forum']['edit'],$_PWNICONS['buttons']['edit']);
        }
        // Moderation Tools 
        if ($user['level'] >= $site_info['mod_rank']) {
            if ($user['level'] >= $site_info['admin_rank']) {
                $block_content = $block_content . drawButton("javascript:buddyAlert('IP: " . $row['ip'] . "');",$_PWNDATA['forum']['ip']);
            } // Only administrators can view the IP of a post. This is to keep moderators from h4xing
            $block_content = $block_content . drawButton("javascript:buddyAlert('" . $_PWNDATA['forum']['delete_confirm'] . " &lt;a href=\\'forum.php?do=delete&amp;id=" . $row['id'] . "\\'&gt;" . $_PWNDATA['forum']['delete_confirm_accept'] . "&lt;/a&gt;');", $_PWNDATA['forum']['delete'],$_PWNICONS['buttons']['del_reply']);
        }
        if (($user['id'] != $post_author['id']) and (!($board['post_level'] > $user['level'])) and ($islocked == false)) {
            $block_content = $block_content . drawButton("forum.php?do=newreply&amp;id=" . $topic['id'] . "&amp;quote=" . $row['id'],$_PWNDATA['forum']['quote'],$_PWNICONS['buttons']['quote']);
        }
        $block_content = $block_content . "</tr></table></td></tr>";
    }
    $block_content = $block_content .  <<<END
	<tr><td class="forum_topic_buttonbar" colspan="2"><table style="border: 0px" class="borderless_table"><tr>
END;
    if ((!($board['post_level'] > $user['level'])) and ($islocked == false)) {
        $block_content = $block_content . drawButton("forum.php?do=newreply&amp;id=" . $topic['id'],$_PWNDATA['forum']['add_reply'],$_PWNICONS['buttons']['new_reply']);
    }
    if ($user['level'] >= $site_info['mod_rank']) {
        $block_content = $block_content . drawButton("javascript:buddyAlert('" . $_PWNDATA['forum']['delete_confirm'] . " &lt;a href=\\'forum.php?do=deltop&amp;id=" . $topic['id'] . "\\'&gt;" . $_PWNDATA['forum']['delete_confirm_accept'] . "&lt;/a&gt;');", $_PWNDATA['forum']['del_topic'],$_PWNICONS['buttons']['del_topic']);
        if ($topic['stick'] == 0) { // Stick
            $block_content = $block_content . drawButton("forum.php?do=sticktop&amp;id=" . $topic['id'],$_PWNDATA['forum']['stick_topic'],$_PWNICONS['buttons']['stick']);
            $block_content = $block_content . drawButton("forum.php?do=sinktop&amp;id=" . $topic['id'],$_PWNDATA['forum']['sink'],$_PWNICONS['buttons']['sink']);
        } else if ($topic['stick'] == 1) { // Unstick
            $block_content = $block_content . drawButton("forum.php?do=unsticktop&amp;id=" . $topic['id'],$_PWNDATA['forum']['unstick_topic'],$_PWNICONS['buttons']['unstick']);
        } else if ($topic['stick'] == -1) {
            $block_content = $block_content . drawButton("forum.php?do=unsinktop&amp;id=" . $topic['id'],$_PWNDATA['forum']['unsink'],$_PWNICONS['buttons']['unsink']);
        }
        if ($topic['locked'] == 0) {
            $block_content = $block_content . drawButton("forum.php?do=locktop&amp;id=" . $topic['id'],$_PWNDATA['forum']['lock_topic'],$_PWNICONS['buttons']['lock']);
        } else {
            $block_content = $block_content . drawButton("forum.php?do=unlocktop&amp;id=" . $topic['id'],$_PWNDATA['forum']['unlock_topic'],$_PWNICONS['buttons']['unlock']);
        }
        $block_content = $block_content . drawButton("javascript:flipVisibility('movebox');",$_PWNDATA['forum']['move_topic'],$_PWNICONS['buttons']['move']);
        $top_id = $topic['id'];
        $block_content = $block_content . <<<END
<td  style="border: 0px"><div id="movebox" style="display:none;">
<form action="forum.php" method="post" style="display:inline;">
<input type="hidden" name="action" value="move_topic" />
<input type="hidden" name="topid" value="$top_id" />
<select name="board">
END;
        $result = mysql_query("SELECT * FROM `{$_PREFIX}categories` ORDER BY `orderid`");
        while ($cat = mysql_fetch_array($result)) {
	        $block_content = $block_content . "\n<optgroup label=\"" . $cat['name'] . "\">";
	        $catid = $cat['id'];
	        $resultb = mysql_query("SELECT * FROM `{$_PREFIX}boards` WHERE `catid`=$catid ORDER BY `orderid`");
	        while ($board = mysql_fetch_array($resultb)) {
	            if ($board['link'] == "NONE") {
		            if ($user['level'] >= $board['vis_level']) {
		                if ($topic['board'] == $board['id']) {
		                $block_content = $block_content . "\n<option selected=\"selected\" label=\"" . $board['title'] . "\" value=\"" . $board['id'] . "\">" . $board['title'] . "</option>";
		                } else {
		                $block_content = $block_content . "\n<option label=\"" . $board['title'] . "\" value=\"" . $board['id'] . "\">" . $board['title'] . "</option>";
		                }
		            }
		        }
	        }
	        $block_content = $block_content . "\n</optgroup>";
        }
        $block_content = $block_content . "</select>\n<input type=\"submit\" value=\"{$_PWNDATA['forum']['move_topic']}\" /></form></div></td>";
        $block_content = $block_content . drawButton("forum.php?do=splittopic&amp;id=" . $topic['id'],$_PWNDATA['forum']['modtools']['splittopic'],$_PWNICONS['buttons']['split']);
        $block_content = $block_content . drawButton("forum.php?do=mergetopics&amp;id=" . $topic['id'],$_PWNDATA['forum']['modtools']['mergetopic'],$_PWNICONS['buttons']['merge']);
    }
    $block_content = $block_content . $PAGING;
    $block_content = $block_content .  <<<END
	</tr></table></td></tr></table>
END;
    if (($user['level'] >= $board['post_level']) and ($islocked == false)) {
        $block_content = $block_content . <<<END
<a name="qreply_bm"></a>
<table class="forum_quickreply" width="100%">
<tr><td align="center" class="forum_quickreply_title">
<b><a href="javascript:flipVisibility('qreply');">{$_PWNDATA['forum']['quick_reply']}</a></b><br /></td></tr>
<tr><td align="center" class="forum_quickreply_box">
<div id="qreply" style="display: none;">
END;
        $block_content = $block_content . printPosterMini('content', $topic['id']) . <<<END
<form action="forum.php" method="post" name="form">
<input type="hidden" name="action" value="new_reply" />
END;
        $block_content = $block_content . "<input type=\"hidden\" name=\"topic\" value=\"" . $topic['id'] . "\" />";
        $block_content = $block_content . "<input type=\"hidden\" name=\"user\" value=\"" . $user['id'] . "\" />";
        $block_content = $block_content . <<<END
<textarea name="content" style="width: 95%;" rows="5" cols="80"></textarea><br />
<input type="submit" name="sub" value="{$_PWNDATA['forum']['submit_post']}" />
</form>
</div>
</td></tr>
</table>
END;
    }
    $use_previewbox = "yes";
    $post_content = makeBlock($title_content,"{$_PWNDATA['forum']['by']}: " . $author['name'],$block_content);
    
}

// Create a new topic.
if ($_GET['do'] == "newtopic") {
    $result = mysql_query("SELECT * FROM `{$_PREFIX}boards` WHERE id='" . $_GET['id'] . "'", $db);
    $board = mysql_fetch_array($result);
    if ($board['top_level'] > $user['level']) {
        messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['not_permitted_topic_new']);
    }
    $post_title_add = " :: " . $board['title'] . " :: " . $_PWNDATA['forum']['new_topic'];
    $post_sub_add = " > <a href=\"forum.php?do=viewforum&amp;id=" . $board['id'] . "\">" . $board['title'] . "</a> > " . $_PWNDATA['forum']['new_topic'];
    $post_sub_r = post_sub_r($user['id']);
    $block_content = "";
    $block_content = $block_content .  printPoster('content') . <<<END
<form action="forum.php" method="post" name="form">
<input type="hidden" name="action" value="new_topic" />
<table class="forum_base" width="100%">
<tr><td class="forum_topic_content" width="300">{$_PWNDATA['forum']['subject']}</td>
<td class="forum_topic_content"><input type="text" name="subj" style="width:100%" /></td></tr>
<tr><td class="forum_topic_sig" colspan="2">{$_PWNDATA['forum']['body']}</td></tr>
<tr><td class="forum_topic_sig" colspan="2"><textarea rows="11" name="content" style="width:100%; font-family:Tahoma; font-size:10pt" cols="80"></textarea></td></tr>
<tr><td class="forum_topic_sig" colspan="2">
<input type="submit" value="{$_PWNDATA['forum']['submit_post']}" name="sub" /></td></tr>
<tr><td class="forum_topic_sig" colspan="2">
<input type="checkbox" name="add_poll" /> {$_PWNDATA['forum']['poll_add']}</td></tr>
<tr><td class="forum_topic_sig">{$_PWNDATA['forum']['poll_title']}</td>
<td class="forum_topic_sig"><input type="text" name="p_name" style="width:100%" /></td></tr>
<tr><td class="forum_topic_sig">{$_PWNDATA['forum']['poll_options']}</td>
<td class="forum_topic_sig"><input type="text" name="op1" style="width:100%" /></td></tr>
</table>
END;
    $block_content = $block_content . "<input type=\"hidden\" name=\"board\" value=\"" . $board['id'] . "\" />";
    $block_content = $block_content . "<input type=\"hidden\" name=\"user\" value=\"" . $user['id'] . "\" />";
    $block_content = $block_content .  <<<END
</form>
END;
    $post_content = makeBlock($board['title'],$_PWNDATA['forum']['new_topic'],$block_content);  
}

// Create a new reply.
if ($_GET['do'] == "newreply") {
    $result = mysql_query("SELECT * FROM `{$_PREFIX}topics` WHERE id='" . $_GET['id'] . "'", $db);
    $topic = mysql_fetch_array($result);
    $result = mysql_query("SELECT * FROM `{$_PREFIX}boards` WHERE id='" . $topic['board'] . "'", $db);
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
    $block_content = "";
    if ($_GET['quote'] != 0) {
	    $result = mysql_query("SELECT * FROM `{$_PREFIX}posts` WHERE id='" . $_GET['quote'] . "'", $db);
	    $quoted = mysql_fetch_array($result);
	    $result = mysql_query("SELECT * FROM `{$_PREFIX}users` WHERE id='" . $quoted['authorid'] . "'", $db);
	    $quotedauthor = mysql_fetch_array($result);
	    $postquoted = preg_replace("/(\[quote\])(.+?)(\[\/quote\])/si","",$quoted['content']);
	    $cont = "[quote][b]{$_PWNDATA['forum']['original']}[/b] " . $quotedauthor['name'] . "\n" . $postquoted . "[/quote]";
    }
    $block_content = $block_content .  printPoster('content') . <<<END
<form action="forum.php" method="post" name="form">
<input type="hidden" name="action" value="new_reply" />
<table class="forum_base" width="100%">
<tr><td class="forum_topic_content">
<textarea rows="11" name="content" style="width:100%; font-family:Tahoma; font-size:10pt" cols="80">$cont</textarea></td></tr>
<tr><td class="forum_topic_sig">
<input type="submit" value="{$_PWNDATA['forum']['submit_post']}" name="sub" />
</td></tr>
END;
    $block_content = $block_content . "<input type=\"hidden\" name=\"topic\" value=\"" . $topic['id'] . "\" />";
    $block_content = $block_content . "<input type=\"hidden\" name=\"user\" value=\"" . $user['id'] . "\" /></form>";
    $resultz = mysql_query("SELECT * FROM `{$_PREFIX}posts` WHERE topicid='" . $topic['id'] . "' ORDER BY `id` DESC LIMIT 5", $db);
    $block_content = $block_content . "<tr><td class=\"forum_topic_sig\" align=\"center\"><b>{$_PWNDATA['forum']['recent']}</b></td></tr></table><table class=\"forum_base\" width=\"100%\">\n";
    while ($rowz = mysql_fetch_array($resultz)) {
        $resultb = mysql_query("SELECT * FROM `{$_PREFIX}users` WHERE id='" .  $rowz['authorid'] . "'", $db);
        $post_author = mysql_fetch_array($resultb);
        $auth_name = $post_author['name'];
        $dec_post = BBDecode($rowz['content']);
        $block_content = $block_content . "<tr><td width=\"20%\" valign=\"top\" class=\"glow\">$auth_name</td><td class=\"forum_topic_content\">$dec_post</td></tr>\n";
    }
    $block_content = $block_content .  <<<END
	</table>
	
END;

    $post_content = makeBlock($topic['title'],$_PWNDATA['forum']['replying'],$block_content);
}

// Edit a past post
if ($_GET['do'] == "editreply") {
    if (!isset($_SESSION['sess_id'])) {
        messageBack($_PWNDATA['forum']['not_logged_in']);
    }
    $result = mysql_query("SELECT * FROM `{$_PREFIX}posts` WHERE id='" . $_GET['id'] . "'", $db);
    $reply = mysql_fetch_array($result);
    if (($reply['authorid'] != $user['id']) and ($user['level'] < 2)) {
	    messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['not_yours']);
    }
    $result = mysql_query("SELECT * FROM `{$_PREFIX}topics` WHERE id='" . $reply['topicid'] . "'", $db);
    $topic = mysql_fetch_array($result);
    $result = mysql_query("SELECT * FROM `{$_PREFIX}boards` WHERE id='" . $topic['board'] . "'", $db);
    $board = mysql_fetch_array($result);
    $post_title_add = " :: " . $board['title'] . " :: " . $_PWNDATA['forum']['editing'];
    $post_sub_add = " > <a href=\"forum.php?do=viewforum&amp;id=" . $board['id'] . "\">" . $board['title'] . "</a> > " . $_PWNDATA['forum']['editing'];
    $post_sub_r = post_sub_r($user['id']);
    $block_content = "";
    $block_content = $block_content . printPoster('content') . <<<END
<form action="forum.php" method="post" name="form">
<input type="hidden" name="action" value="edit_reply" />
<table class="forum_base" width="100%">
<tr><td class="forum_topic_content"><textarea rows="11" name="content" style="width:100%; font-family:Tahoma; font-size:10pt" cols="80">
END;
    $post_bb = str_replace("\"","&quot;",$reply['content']);
    $post_bb = str_replace("<","&lt;",$post_bb);
    $post_bb = str_replace(">","&gt;",$post_bb);
    $block_content = $block_content . $post_bb;
    $block_content = $block_content . <<<END
</textarea></td></tr>
<tr><td class="forum_topic_sig">
<input type="submit" value="{$_PWNDATA['forum']['save_changes']}" name="sub" /></td></tr>
</table>
END;
    $block_content = $block_content . "<input type=\"hidden\" name=\"id\" value=\"" . $reply['id'] . "\" />";
    $block_content = $block_content . "<input type=\"hidden\" name=\"topic\" value=\"" . $topic['id'] . "\" />";
    $block_content = $block_content .  <<<END
	</form>
END;
    $post_content = makeBlock($topic['title'],$_PWNDATA['forum']['editing'],$block_content);
}

// Edit a profile
if ($_GET['do'] == "editprofile") {
    $post_title_add = " :: " . $_PWNDATA['profile']['editing'];
    $post_sub_add = " > " . $_PWNDATA['profile']['editing'];
    $post_sub_r = post_sub_r($user['id']);
    $block_content = "";

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
    $themes = explode(",",$user['theme']);
    $u_theme = $themes[0];
    $u_icons = $themes[1];
    $u_lang = $themes[2];
    $u_color = $user['color'];
    if ($sbona == 1) {
        $sbon = "checked";
    } else {
        $sbon = "";
    }
    $theme_list = themeList($u_theme);
    $color_list = colorList($u_color);
    $icons_list = iconsList($u_icons);
    $lang_list = langList($u_lang);
    $block_content = $block_content . <<<END
<form method="post" action="forum.php" name="form">
  <input type="hidden" name="action" value="edit_profile" />
  <input type="hidden" name="id" value="$uid" />
  <table class="forum_base" width="100%">
  <tr><td class="forum_thread_title" colspan="2"><b>{$_PWNDATA['profile']['registration']}</b></td></tr>
  <tr><td class="forum_topic_sig" width="300">{$_PWNDATA['profile']['username']}</td><td class="forum_topic_sig">$uname <input type="hidden" name="name" value="$uname" /></td></tr>
  <tr><td class="forum_topic_sig">{$_PWNDATA['profile']['email']}</td><td class="forum_topic_sig"><input type="text" name="email" value="$umail" style="width: 100%" /></td></tr>
  <tr><td class="forum_topic_sig">{$_PWNDATA['profile']['password']}</td><td class="forum_topic_sig"><input type="password" name="apass" value="" style="width: 100%" /></td></tr>
  <tr><td class="forum_topic_sig">{$_PWNDATA['profile']['confirm']}</td><td class="forum_topic_sig"><input type="password" name="cpass" value="" style="width: 100%" /></td></tr>
  <tr><td class="forum_thread_title" colspan="2"><b>{$_PWNDATA['profile']['messaging']}</b></td></tr>
  <tr><td class="forum_topic_sig">{$_PWNICONS['protocols']['msn']} MSN</td><td class="forum_topic_sig"><input type="text" name="msn" value="$umsn" style="width: 100%" /></td></tr>
  <tr><td class="forum_topic_sig">{$_PWNICONS['protocols']['aim']} AIM</td><td class="forum_topic_sig"><input type="text" name="aim" value="$uaim" style="width: 100%" /></td></tr>
  <tr><td class="forum_topic_sig">{$_PWNICONS['protocols']['yahoo']} Yahoo</td><td class="forum_topic_sig"><input type="text" name="yah" value="$uyah" style="width: 100%" /></td></tr>
  <tr><td class="forum_topic_sig">{$_PWNICONS['protocols']['icq']} ICQ</td><td class="forum_topic_sig"><input type="text" name="icq" value="$uicq" style="width: 100%" /></td></tr>
  <tr><td class="forum_topic_sig">{$_PWNICONS['protocols']['xfire']} xFire</td><td class="forum_topic_sig"><input type="text" name="xfire" value="$uxfire" style="width: 100%" /></td></tr>
  <tr><td class="forum_topic_sig">{$_PWNICONS['protocols']['live']} Gamertag</td><td class="forum_topic_sig"><input type="text" name="live" value="$ulive" style="width: 100%" /></td></tr>
  <tr><td class="forum_topic_sig">{$_PWNICONS['protocols']['pand_on']} Pandemic</td><td class="forum_topic_sig"><input type="text" name="pand" value="$pand" style="width: 100%" /></td></tr>
  <tr><td class="forum_thread_title" colspan="2"><b>{$_PWNDATA['profile']['posting']}</b></td></tr>
  <tr><td class="forum_topic_sig" colspan="2">{$_PWNDATA['profile']['sig']}</td></tr>
  <tr><td class="forum_topic_sig" colspan="2">
END;
    $block_content = $block_content . printPoster('sig') . <<<END
  </td></tr>
  <tr><td class="forum_topic_sig" colspan="2"><textarea rows="5" name="sig" style="width:100%" cols="80">$sig</textarea></td></tr>
  <tr><td class="forum_topic_sig">{$_PWNDATA['profile']['avatar']}</td>
  <td class="forum_topic_sig"><input type="text" name="avatar" value="$ava" style="width: 100%" /></td></tr>
  <tr><td class="forum_thread_title" colspan="2"><b>{$_PWNDATA['profile']['settings']}</b></td></tr>
  <tr><td class="forum_topic_sig">{$_PWNDATA['profile']['sidebar']}</td><td class="forum_topic_sig"><input name="sbonforum" type="checkbox" $sbon /> {$_PWNDATA['profile']['sidebar']}</td></tr>
  <tr><td class="forum_topic_sig">{$_PWNDATA['profile']['theme']}</td><td class="forum_topic_sig">$theme_list</td></tr>
  <tr><td class="forum_topic_sig">{$_PWNDATA['profile']['color']}</td><td class="forum_topic_sig">$color_list</td></tr>
  <tr><td class="forum_topic_sig">{$_PWNDATA['profile']['icons']}</td><td class="forum_topic_sig">$icons_list</td></tr>
  <tr><td class="forum_topic_sig">{$_PWNDATA['profile']['language']}</td><td class="forum_topic_sig">$lang_list</td></tr>
  <tr><td class="forum_topic_sig" colspan="2"><input type="submit" value="{$_PWNDATA['profile']['save']}" name="sub" /></td></tr>
  </table>
	</form>	
END;
    $post_content = makeBlock($_PWNDATA['profile']['title'], $_PWNDATA['profile']['editing'],$block_content);
}

// View a user's profile
if ($_GET['do'] == "viewprofile") {
    $result = mysql_query("SELECT * FROM `{$_PREFIX}users` WHERE id='" . $_GET['id'] . "'", $db);
    $vuser = mysql_fetch_array($result);
    $uid = $vuser['id'];
    $umail = $vuser['email'];
    $uname = $vuser['name'];
    $sig = BBDecode($vuser['sig']);
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
    $block_content = "";
    $replyto = $vuser['id'];
    $posts = postCount($replyto);
    $modstatus = getRankName($vuser['level'],$site_info,$posts);
    if ($vuser['avatar'] != "") {
       $ava = "<img src=\"" . $vuser['avatar'] . "\" align=\"top\" />";
    }
    $block_content = $block_content . <<<END
    <table class="forum_base" width="100%">
    <tr><td class="forum_profile_user" align="center">$ava</td><td class="forum_profile_user">$uname</td></tr>
    <tr><td class="forum_topic_sig" width="300">$modstatus</td>
    <td class="forum_topic_sig" rowspan="11" valign="top">{$_PWNICONS['profile']['quote_left']}$sig{$_PWNICONS['profile']['quote_right']}</td></tr>
  <tr><td class="forum_topic_sig">$posts {$_PWNDATA['forum']['posts']}</td></tr>
  <tr><td class="forum_thread_title"><b>{$_PWNDATA['profile']['messaging']}:</b></td></tr>
  <tr><td class="forum_topic_sig">{$_PWNICONS['protocols']['msn']} $umsn</td></tr>
  <tr><td class="forum_topic_sig">{$_PWNICONS['protocols']['aim']} $uaim</td></tr>
  <tr><td class="forum_topic_sig">{$_PWNICONS['protocols']['yahoo']} $uyah</td></tr>
  <tr><td class="forum_topic_sig">{$_PWNICONS['protocols']['icq']} $uicq</td></tr>
  <tr><td class="forum_topic_sig">{$_PWNICONS['protocols']['xfire']} $uxfire</td></tr>
  <tr><td class="forum_topic_sig">{$_PWNICONS['protocols']['live']} $ulive</td></tr>
  <tr><td class="forum_topic_sig">{$_PWNICONS['protocols']['pand_on']} $pand</td></tr>
  <tr><td class="forum_topic_sig"><a href="forum.php?do=newpm&amp;to=$replyto">{$_PWNDATA['pm']['send_a']}</a></td></tr>
  </table>
END;
    $post_content = makeBlock($uname . $_PWNDATA['profile']['possessive_profile'],$_PWNDATA['profile']['view'],$block_content);
    
}

// Search the forum
if ($_GET['do'] == "search_form") {
    $post_title_add = " :: " . $_PWNDATA['forum']['search'];
    $post_sub_add = " > " . $_PWNDATA['forum']['search'];
    $post_sub_r = post_sub_r($user['id']);
    $block_content = <<<END
<form action="forum.php?do=search" method="post" name="form">
	<table class="forum_base" width="100%">
		<tr><td class="forum_topic_content" width="200">{$_PWNDATA['forum']['search_terms']}</td><td class="forum_topic_content"><input type="text" name="q" /></td></tr>
		<tr><td class="forum_topic_sig">{$_PWNDATA['forum']['search_author']}</td><td class="forum_topic_sig"><input type="text" name="a" /></td></tr>
		<tr><td class="forum_topic_sig" colspan="2"><input type="submit" value="{$_PWNDATA['forum']['search_submit']}" name="sub" /></td></tr>
	</table>
</form>
END;
$post_content = makeBlock($_PWNDATA['forum']['search'],"",$block_content);
    
}

// Search results
if ($_GET['do'] == "search") {
    // XXX: SELECT * FROM posts WHERE MATCH (content) AGAINST ('hmmm')
    $search = mse($_POST['q']);
    $auth = mse($_POST['a']);
    $post_title_add = " :: {$_PWNDATA['forum']['searching_for']} '$search'";
    $post_sub_add = " > {$_PWNDATA['forum']['searching_for']} '$search'";
    $post_sub_r = post_sub_r($user['id']);
    if ($auth == "") {
        $resultz = mysql_query("SELECT * FROM `{$_PREFIX}posts` WHERE MATCH (content) AGAINST ('$search')", $db);
    } else if ($search == "" && $auth != "") {
        $auth_result = mysql_query("SELECT `id` FROM `{$_PREFIX}users` WHERE UCASE(name)=UCASE('{$auth}')");
        $temp = mysql_fetch_array($auth_result);
        $authid = $temp['id'];
        $resultz = mysql_query("SELECT * FROM `{$_PREFIX}posts` WHERE `authorid`=$authid ORDER BY `id` DESC", $db);
    } else {
        $auth_result = mysql_query("SELECT `id` FROM `{$_PREFIX}users` WHERE UCASE(name)=UCASE('{$auth}')");
        $temp = mysql_fetch_array($auth_result);
        $authid = $temp['id'];
        $resultz = mysql_query("SELECT * FROM `{$_PREFIX}posts` WHERE MATCH (content) AGAINST ('$search') AND `authorid`=$authid", $db);
    }
    $block_content =  "<table class=\"forum_base\" width=\"100%\">\n";
    $block_content = $block_content . "<tr><td class=\"forum_thread_title\" colspan=\"2\"><b>{$_PWNDATA['forum']['search_resultsb']}:</b></td></tr>";
    $results_count = 0;
    while ($rowz = mysql_fetch_array($resultz)) {
        $resultb = mysql_query("SELECT * FROM `{$_PREFIX}users` WHERE id='" .  $rowz['authorid'] . "'", $db);
        $post_author = mysql_fetch_array($resultb);
        $resultc = mysql_query("SELECT * FROM `{$_PREFIX}topics` WHERE id='" .  $rowz['topicid'] . "'", $db);
        $post_topic = mysql_fetch_array($resultc);
        $resultc = mysql_query("SELECT * FROM `{$_PREFIX}boards` WHERE id='" .  $post_topic['board'] . "'", $db);
        $post_board = mysql_fetch_array($resultc);
        $auth_name = $post_author['name'];
        $dec_post = BBDecode($rowz['content']);
        if ($post_board['vis_level'] > $user['level']) {
            // Do nothing, this post is in a board the user isn't allowed to see!
        } else {
            $block_content = $block_content . "<tr><td width=\"20%\" valign=\"top\" class=\"glow\">$auth_name</td><td  class=\"forum_topic_content\"><b><i>{$_PWNDATA['forum']['posted_in']}: <a href=\"forum.php?do=viewtopic&amp;id=" . $post_topic['id'] . "\">" . $post_topic['title'] . "</a></i></b><br />$dec_post</td></tr>\n";
            $results_count++;
        }
    }
    if ($results_count < 1) {
        $block_content = $block_content . "<tr><td class=\"forum_topic_content\" colspan=\"2\">No results.</td></tr>";
    }
    $block_content = $block_content . "</table>";
    $post_content = makeBlock("{$_PWNDATA['forum']['search_results']} '$search'",$_PWNDATA['forum']['search'],$block_content);
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
function setContent(what, content) {
    document.getElementById(what).innerHTML = content;
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

$sql_temp = mysql_query("SELECT COUNT(*) FROM `{$_PREFIX}users`");
$stat_a = mysql_fetch_array($sql_temp);
$sql_temp = mysql_query("SELECT COUNT(*) FROM `{$_PREFIX}topics`");
$stat_b = mysql_fetch_array($sql_temp);
$sql_temp = mysql_query("SELECT COUNT(*) FROM `{$_PREFIX}posts`");
$stat_c = mysql_fetch_array($sql_temp);
$sql_temp = mysql_query("SELECT COUNT(*) FROM `{$_PREFIX}topics` WHERE `{$_PREFIX}topics`.`stick`=1");
$stat_d = mysql_fetch_array($sql_temp);
$sql_temp = mysql_query("SELECT * FROM `{$_PREFIX}users` ORDER BY `id` DESC");
$stat_e = mysql_fetch_array($sql_temp);
$num_users = $stat_a['COUNT(*)'];
$num_topics = $stat_b['COUNT(*)'];
$num_posts = $stat_c['COUNT(*)'];
$num_sticks = $stat_d['COUNT(*)'];
$last_member = $stat_e['name'];
$last_member_id = $stat_e['id'];
$block_content = "<div style=\"text-align: center;\">";
$block_content = $block_content . "{$_PWNDATA['forum']['there_are']}$num_posts{$_PWNDATA['forum']['posts_by']}$num_users{$_PWNDATA['forum']['members_in']}$num_topics{$_PWNDATA['forum']['_topics']}\n";
$block_content = $block_content . "$num_sticks{$_PWNDATA['forum']['are_sticky']}\n";
$block_content = $block_content . "<a href=\"forum.php?do=viewprofile&amp;id=$last_member_id\">$last_member</a>\n<br />";

$block_content = $block_content . "<b>{$_PWNDATA['forum']['members_online']}</b>: ";
$sql_temp = mysql_query("SELECT * FROM `{$_PREFIX}sessions` ORDER BY `user`");
while ($on_session = mysql_fetch_array($sql_temp)) {
$on_temp = mysql_query("SELECT * FROM `{$_PREFIX}users` WHERE `id`=" . $on_session['user']);
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
