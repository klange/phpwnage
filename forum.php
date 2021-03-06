<?php
/*
	This file is part of PHPwnage (Forum Module)

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

if (!isset($_POST['action'])) {
    $_POST['action'] = "";
}
if (!isset($_GET['do'])) {
    $_GET['do'] = "";
}


if ($_POST['action'] == "login") {
    $userresult = override_sql_query("SELECT `id`,`name`,`password` FROM `{$_PREFIX}users` WHERE UCASE(name)=UCASE('" . $_POST['uname'] . "')", $db);
    $tempuser = mysql_fetch_array($userresult);
    if ((strtoupper($_POST['uname']) == strtoupper($tempuser['name'])) and (md5($_POST['upass']) == $tempuser['password'])) {
        $_SESSION['user_name'] = $tempuser['name'];
        $_SESSION['user_pass'] = md5($_POST['upass']);
        $_SESSION['sess_id'] = time();
        $_SESSION['last_on'] = time();
        if ($_POST['remember'] == "ON") {
            setcookie("rem_user", $tempuser['name'], time()+60*60*24*365*10);
            setcookie("rem_pass", md5($_POST['upass']), time()+60*60*24*365*10);
            setcookie("rem_yes", "yes", time()+60*60*24*365*10);
        } else {
            setcookie("rem_user", "", time()+60*60*24*365*10);
            setcookie("rem_pass", "", time()+60*60*24*365*10);
            setcookie("rem_yes", "no", time()+60*60*24*365*10);
        }
        $result = override_sql_query("SELECT * FROM `{$_PREFIX}users` WHERE UCASE(name)=UCASE('" . $_SESSION['user_name'] . "')", $db);
        $user = mysql_fetch_array($result);
        override_sql_query("DELETE FROM `{$_PREFIX}sessions` WHERE `user`=" . $user['id'] . "");
        override_sql_query("INSERT INTO `{$_PREFIX}sessions` VALUES (" . $_SESSION['sess_id'] . ", " . $user['id'] . ", " . $_SESSION['last_on'] . ");");
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
        override_sql_query("INSERT INTO `{$_PREFIX}security` ( `time` , `passused`, `where`, `ip` ) VALUES ( '" . time() . "', '" . md5($_POST['upass']) . "', 'Forum, $name', '" . $ip . "' );");
        messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['failed_login']);
    }
}

// If a new topic is being posted
if ($_POST['action'] == "new_topic") {
    $content = $_POST['content'];
    $results =  override_sql_query("SELECT `id` FROM `{$_PREFIX}boards` WHERE `id`=" . $_POST['board']);
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
	    override_sql_query("INSERT INTO `{$_PREFIX}polls` ( `id`, `title`, `op1_name`) VALUES (NULL, '" . mse($_POST['p_name']) . "', '" . mse($_POST['op1']) . "');");
	    $testresult = override_sql_query("SELECT `id` FROM `{$_PREFIX}polls` ORDER BY `id` DESC LIMIT 1");
	    $poll = mysql_fetch_array($testresult);
	    $poll_id = $poll['id'];
    } else {
	    $has_poll = "0";
	    $poll_id = "0";
    }
    override_sql_query("INSERT INTO `{$_PREFIX}topics` ( `id` , `authorid` , `board` , `title`, `has_poll`, `poll_id` ) VALUES (NULL , " . $_POST['user'] . ", " . $_POST['board'] . ", '" . mse($_POST['subj']) . "', " . $has_poll . ", " . $poll_id . " );");
    $result = override_sql_query("SELECT `id` FROM `{$_PREFIX}topics` ORDER BY `id` DESC LIMIT 1");
    $topic = mysql_fetch_array($result);
    $ip=$_SERVER['REMOTE_ADDR'];
    override_sql_query("INSERT INTO `{$_PREFIX}posts` ( `id` , `topicid` , `authorid` , `content`, `time`, `ip`) VALUES ( NULL , " . $topic['id'] . " , " . $_POST['user'] . " , '" . mse($content) . "' , " . time() . " , '" . $ip . "');");
    $result = override_sql_query("SELECT `id` FROM `{$_PREFIX}posts` ORDER BY `id` DESC LIMIT 1");
    $reply = mysql_fetch_array($result);
    override_sql_query("UPDATE `{$_PREFIX}topics` SET `lastpost` = " . $reply['id'] . " WHERE `{$_PREFIX}topics`.`id` =" . $topic['id']);
    override_sql_query("ALTER TABLE `{$_PREFIX}posts`  ORDER BY `id`");
    override_sql_query("ALTER TABLE `{$_PREFIX}topics`  ORDER BY `id`");
    messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['new_topic_added'],"forum.php?do=viewtopic&amp;id=" . $topic['id']);
}

// If a new PM is being sent
if ($_POST['action'] == "new_pm") {
    if (strlen($_POST['subj']) < 3) {
        messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['pm_too_short']);
    }
    $pmdate = time();
    $toname = $_POST['toline'];
    $result = override_sql_query("SELECT `id` FROM `{$_PREFIX}users` WHERE `name`='$toname'");
    $temp_user = mysql_fetch_array($result);
    $pmto = $temp_user['id'];
    $pmtitle = $_POST['subj'];
    $pmcontent = $_POST['content'];
    $pmfrom = $user['id'];
    override_sql_query("INSERT INTO `{$_PREFIX}pms` ( `id` , `to` , `from` , `title` , `content` , `read` , `time` )
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
    $topic_sql = override_sql_query("SELECT `id`,`board`,`locked` FROM `{$_PREFIX}topics` WHERE `id`=$topic");
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
    override_sql_query("INSERT INTO `{$_PREFIX}posts` ( `id` , `topicid` , `authorid` , `content` , `time` , `ip` ) VALUES ( NULL , '" . $topic . "', '" . $user['id'] . "', '" . mse($content) . "' , '" . time() . "', '" . $ip . "' );");
    $result = override_sql_query("SELECT `id` FROM `{$_PREFIX}posts` ORDER BY `id` DESC LIMIT 1");
    $reply = mysql_fetch_array($result);
    override_sql_query("UPDATE `{$_PREFIX}topics` SET `lastpost` = '" . $reply['id'] . "' WHERE `{$_PREFIX}topics`.`id` =" . $topic);
    override_sql_query("ALTER TABLE `{$_PREFIX}posts`  ORDER BY `id`");
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
	$topic_sql = override_sql_query("SELECT `id`,`locked` FROM `{$_PREFIX}topics` WHERE `id`=$tid");
	$this_topic = mysql_fetch_array($topic_sql);
	if ($this_topic['locked'] == 1) {
		if ($user['level'] < 2) {
		messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['locked_topic_poll']);
		}
	}
	set_voted($pid,$user['id']);
	$poll_sql = override_sql_query("SELECT `op1_name`,`op1_votes` FROM `{$_PREFIX}polls` WHERE `id`=$pid");
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
		$stri .= $a . ",";
	}
	$stri = substr($stri,0,strlen($stri)-1);
	override_sql_query("UPDATE `{$_PREFIX}polls` SET `op1_votes`='" . $stri . "' WHERE `id`=$pid");
	messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['vote_cast'],"forum.php?do=viewtopic&amp;last=1&amp;id=" . $tid);
}

// If an old post is being edited
if ($_POST['action'] == "edit_reply") {
    $_POST['id'] = (int)$_POST['id'];
    $temp_query = override_sql_query("SELECT `id`, `authorid` FROM `posts` WHERE `id`={$_POST['id']}");
    $temp = mysql_fetch_array($temp_query);
    if (!isset($user['id']) || ($user['level'] < $site_info['mod_rank'] && $user['id'] != $temp['authorid'])) {
        messageBack($_PWNDATA['post_attack'], $_PWNDATA['not_permitted']);
    }
    $content = $_POST['content'];
    override_sql_query("UPDATE `{$_PREFIX}posts` SET `content` = '" . mse($content) . "' WHERE `{$_PREFIX}posts`.`id` =" . $_POST['id'] . ";");
    messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['post_edited'],"forum.php?do=viewtopic&amp;id=" . $_POST['topic'] . "&p=" . findPage($_POST['id']));
}

// If a topic title is being changed
if ($_POST['action'] == "edit_title") {
    $title = $_POST['title'];
    $_POST['id'] = (int)$_POST['id'];
    $temp_query = override_sql_query("SELECT `id`, `authorid` FROM `topics` WHERE `id`={$_POST['id']}");
    $temp = mysql_fetch_array($temp_query);
    if (!isset($user['id']) || ($user['level'] < $site_info['mod_rank'] && $user['id'] != $temp['authorid'])) {
        messageBack($_PWNDATA['post_attack'], $_PWNDATA['not_permitted']);
    }
    override_sql_query("UPDATE `{$_PREFIX}topics` SET `title` = '" . mse($title) . "' WHERE `{$_PREFIX}topics`.`id` =" . $_POST['topicid'] . ";");
    messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['topic_title_edited'],"forum.php");
}

// If a topic is being moved
if ($_POST['action'] == "move_topic") {
    $board = $_POST['board'];
    $_POST['topid'] = (int)$_POST['topid'];
    $temp_query = override_sql_query("SELECT `id`, `authorid` FROM `topics` WHERE `id`={$_POST['topid']}");
    $temp = mysql_fetch_array($temp_query);
    if ($user['level'] < $site_info['mod_rank']) {
        messageBack($_PWNDATA['post_attack'], $_PWNDATA['not_permitted']);
    }
    override_sql_query("UPDATE `{$_PREFIX}topics` SET `board` = $board WHERE `{$_PREFIX}topics`.`id` =" . $_POST['topid'] . ";");
    messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['topic_moved'],"forum.php?do=viewtopic&amp;id=" . $_POST['topid']);
}

// Topic is being split
if ($_POST['action'] == "split_topic") {
    if ($user['level'] < $site_info['mod_rank']) {
        messageBack($_PWNDATA['post_attack'], $_PWNDATA['not_permitted']);
    }
    // Author is whoever split the topic off in the first place, as a way to track it.
    if (strlen($_POST['newtitle']) < 3) {
        messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['modtools']['tooshort']);
    }
    override_sql_query("INSERT INTO `{$_PREFIX}topics` ( `id` , `authorid` , `board` , `title`, `has_poll`, `poll_id` ) VALUES (NULL , " . $user['id'] . ", " . $_POST['board'] . ", '" . mse($_POST['newtitle']) . "', 0, 0 );");
    $result = override_sql_query("SELECT `id` FROM `{$_PREFIX}topics` ORDER BY `id` DESC LIMIT 1");
    $topic = mysql_fetch_array($result);
    $where = "WHERE `id`=";
    while (list($key,$value) = each($_POST)) {
        if (strstr($key,"post_")) {
            if ($value == "on") {
                $lastpost = str_replace("post_", "", $key);
                $where .= $lastpost . " OR `id`=";
            }
        }
    }
    $where .= "0";
    override_sql_query("UPDATE `{$_PREFIX}posts` SET `topicid`=" . $topic['id'] . " " . $where);
    override_sql_query("UPDATE `{$_PREFIX}topics` SET `lastpost`=" . $lastpost . " WHERE `id`=" . $topic['id']);
    messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['modtools']['topic_split'],"forum.php?do=viewforum&amp;id=" . $_POST['board']);    
}

if ($_POST['action'] == "merge_topics") {
    if ($user['level'] < $site_info['mod_rank']) {
        messageBack($_PWNDATA['post_attack'], $_PWNDATA['not_permitted']);
    }
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
    $result = override_sql_query("SELECT `id` FROM `{$_PREFIX}posts` WHERE `topicid`={$_POST['topic']}");
    while ($post = mysql_fetch_array($result)) {
        override_sql_query("UPDATE `{$_PREFIX}posts` SET `topicid`={$mergeid} WHERE `id`={$post['id']}");
    }
    override_sql_query("DELETE FROM `{$_PREFIX}topics` WHERE `{$_PREFIX}topics`.`id` =" . $_POST['topic']);
    messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['modtools']['merged'],"forum.php?do=viewforum&amp;id=" . $_POST['board']);
}

// Profile editing
if ($_POST['action'] == "edit_profile") {
    $userid = $user['id'];
    if ($_POST['adm'] == "true") {
        if ($user['level'] >= $site_info['mod_rank']) {
            $userid = $_POST['id'];
        }
    }
    override_sql_query("UPDATE `{$_PREFIX}users` SET `name` = '" . mse($_POST['name']) . "' WHERE `{$_PREFIX}users`.`id` =" . $userid);
    override_sql_query("UPDATE `{$_PREFIX}users` SET `sig` = '" . mse($_POST['sig']) . "' WHERE `{$_PREFIX}users`.`id` =" . $userid);
    override_sql_query("UPDATE `{$_PREFIX}users` SET `avatar` = '" . mse($_POST['avatar']) . "' WHERE `{$_PREFIX}users`.`id` =" . $userid);
    override_sql_query("UPDATE `{$_PREFIX}users` SET `email` = '" . mse($_POST['email']) . "' WHERE `{$_PREFIX}users`.`id` =" . $userid);
    override_sql_query("UPDATE `{$_PREFIX}users` SET `theme` = '" . mse($_POST['theme'] . "," . $_POST['icons'] . "," . $_POST['lang']) . "' WHERE `{$_PREFIX}users`.`id` =" . $userid);
    override_sql_query("UPDATE `{$_PREFIX}users` SET `color` = '" . mse($_POST['color']) . "' WHERE `{$_PREFIX}users`.`id` =" . $userid);
    $ims = "";
    foreach ($_POST as $input_name => $input_value) {
        if (strstr($input_name,"im_")) {
            $ims .= $input_value . ",";           
        }
    }
    $ims = substr($ims,0,strlen($ims)-1);
    override_sql_query("UPDATE `{$_PREFIX}users` SET `ims` = '" . mse($ims) . "' WHERE `{$_PREFIX}users`.`id` =" . $userid);
    if ($_POST['sbonforum'] == "on") {
        $sbon = 1;
    } else {
        $sbon = 0;
    }
    override_sql_query("UPDATE `{$_PREFIX}users` SET `sbonforum` = " . $sbon . " WHERE `{$_PREFIX}users`.`id` =" . $userid);
    if ($_POST['richedit'] == "on") {
        $reon = 1;
    } else {
        $reon = 0;
    }
    override_sql_query("UPDATE `{$_PREFIX}users` SET `rich_edit` = " . $reon . " WHERE `{$_PREFIX}users`.`id` =" . $userid);
    if ($_POST['apass'] != "") {
	    if ($_POST['apass'] == $_POST['cpass']) {
	        override_sql_query("UPDATE `{$_PREFIX}users` SET `password` = '" . md5($_POST['apass']) . "' WHERE `{$_PREFIX}users`.`id` =" . $userid);
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
    $results = override_sql_query("SELECT `id` FROM `{$_PREFIX}users` WHERE `name`='" . $name . "'");
    $check_name = mysql_fetch_array($results);
    if ($check_name != null) {
        messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['already_registered']);
    }
    $results = override_sql_query("SELECT `id` FROM `{$_PREFIX}users` WHERE `email`='" . $email . "'");
    $check_mail = mysql_fetch_array($results);
    if ($check_mail != null) {
        messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['already_registered_email']);
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
    if ($_CONFIG_MAIL) {
        $body = $_PWNDATA['forum']['confirm_email'][1] . $site_info['name'] . ".\n";
        $body .= $_PWNDATA['forum']['confirm_email'][2] . "'$name' with the password '$pass'.\n";
        $body .= $_PWNDATA['forum']['confirm_email'][3] . $conf_email . ".";
        if (!mail($email, $_PWNDATA['forum']['confirm_email'][4] . $site_info['name'], $body)) {
            $message .= $_PWNDATA['forum']['send_email_failed'] . "<br />";
        }
    }
    $time = time();
    override_sql_query("INSERT INTO `{$_PREFIX}users` ( `id` , `name` , `email` , `password` , `sig` , `avatar` , `time` ) VALUES ( NULL , '{$name}', '{$email}', '" . md5($pass) . "', '', '', '{$time}' );");
    $_POST['action'] = "";
    $message .= $_PWNDATA['forum']['create_account_success'];
    messageRedirect($_PWNDATA['forum_page_title'],$message,"forum.php?do=login");
}

// Delete a post
if ($_GET['do'] == "delete") {
	$_GET['id'] = (int)$_GET['id'];
	$temp_query = override_sql_query("SELECT `id`, `authorid` FROM `posts` WHERE `id`={$_GET['id']}");
    $temp = mysql_fetch_array($temp_query);
    if (!isset($user['id']) || ($user['level'] < $site_info['mod_rank'] && $user['id'] != $temp['authorid'])) {
        messageBack($_PWNDATA['post_attack'], $_PWNDATA['not_permitted']);
    }
	$_GET['id'] = (int)$_GET['id'];
	$topic = findTopic($_GET['id']);
	override_sql_query("DELETE FROM `{$_PREFIX}posts` WHERE `{$_PREFIX}posts`.`id` =" . $_GET['id']);
	messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['post_deleted'],"forum.php?do=viewtopic&id=$topic&p=" . findPage($_GET['id'],$topic));
}

// Delete a topic
if ($_GET['do'] == "deltop") {
    $_GET['id'] = (int)$_GET['id'];
	$temp_query = override_sql_query("SELECT `id`, `authorid` FROM `topics` WHERE `id`={$_GET['id']}");
    $temp = mysql_fetch_array($temp_query);
    if (!isset($user['id']) || ($user['level'] < $site_info['mod_rank'] && $user['id'] != $temp['authorid'])) {
        messageBack($_PWNDATA['post_attack'], $_PWNDATA['not_permitted']);
    }
	override_sql_query("DELETE FROM `{$_PREFIX}topics` WHERE `{$_PREFIX}topics`.`id` =" . $_GET['id']);
	override_sql_query("DELETE FROM `{$_PREFIX}posts` WHERE `{$_PREFIX}posts`.`topicid` =" . $_GET['id']);
	messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['topic_posts_deleted'],"forum.php");
}

// Sticky a topic
if ($_GET['do'] == "sticktop") {
	if ($user['level'] < $site_info['mod_rank']) {
		messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['not_authorized_sticky_topic']);
	}
	override_sql_query("UPDATE `{$_PREFIX}topics` SET `stick` = 1 WHERE `{$_PREFIX}topics`.`id`=" . $_GET['id']);
	messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['topic_stickied'],"forum.php?do=viewtopic&amp;id=" . $_GET['id']);
}

// Unsticky a topic
if ($_GET['do'] == "unsticktop") {
	if ($user['level'] < $site_info['mod_rank']) {
		messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['not_authorized_sticky_topic']);
	}
	override_sql_query("UPDATE `{$_PREFIX}topics` SET `stick` = 0 WHERE `{$_PREFIX}topics`.`id`=" . $_GET['id']);
	messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['topic_unsticked'],"forum.php?do=viewtopic&amp;id=" . $_GET['id']);
}

// Sink a topic
if ($_GET['do'] == "sinktop") {
	if ($user['level'] < $site_info['mod_rank']) {
		messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['not_authorized_sticky_topic']);
	}
	override_sql_query("UPDATE `{$_PREFIX}topics` SET `stick` = -1 WHERE `{$_PREFIX}topics`.`id`=" . $_GET['id']);
	messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['sunk'],"forum.php?do=viewtopic&amp;id=" . $_GET['id']);
}

// Unsink a topic
if ($_GET['do'] == "unsinktop") {
	if ($user['level'] < $site_info['mod_rank']) {
		messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['not_authorized_sticky_topic']);
	}
	override_sql_query("UPDATE `{$_PREFIX}topics` SET `stick` = 0 WHERE `{$_PREFIX}topics`.`id`=" . $_GET['id']);
	messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['unsunk'],"forum.php?do=viewtopic&amp;id=" . $_GET['id']);
}

// Lock a topic
if ($_GET['do'] == "locktop") {
	if ($user['level'] < $site_info['mod_rank']) {
		messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['not_authorized_lock_topic']);
	}
	override_sql_query("UPDATE `{$_PREFIX}topics` SET `locked` = 1 WHERE `{$_PREFIX}topics`.`id`=" . $_GET['id']);
	messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['topic_locked'],"forum.php?do=viewtopic&amp;id=" . $_GET['id']);
}

// Unlock a topic
if ($_GET['do'] == "unlocktop") {
	if ($user['level'] < $site_info['mod_rank']) {
		messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['not_authorized_unlock_topic']);
	}
	override_sql_query("UPDATE `{$_PREFIX}topics` SET `locked` = 0 WHERE `{$_PREFIX}topics`.`id`=" . $_GET['id']);
	messageRedirect($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['topic_unlocked'],"forum.php?do=viewtopic&amp;id=" . $_GET['id']);
}

// XXX: Begin function calls for output

// Return the display for the right side of the subbar
function post_sub_r($userid) {
    global $_PWNDATA, $_PREFIX;
    if (isset($_SESSION['sess_id'])){
        $post_sub_r = "<a href=\"forum.php?do=logoff\">{$_PWNDATA['forum']['logout']}</a> | <a href=\"forum.php?do=editprofile\">{$_PWNDATA['forum']['edit_profile']}</a> | ";
        $unread_temp = override_sql_query("SELECT `{$_PREFIX}pms`.*, COUNT(`read`) FROM `{$_PREFIX}pms` WHERE `to`=$userid AND `read`=0 GROUP BY `read` ");
        $num_unread_t = mysql_fetch_array($unread_temp);
        $num_unread = $num_unread_t['COUNT(`read`)'];
        if ($num_unread == 0) {
            $post_sub_r .= "<a href=\"forum.php?do=pmbox\">{$_PWNDATA['pm']['view']}</a>";
        } elseif ($num_unread == 1) {
            $post_sub_r .= "<a href=\"forum.php?do=pmbox\">{$_PWNDATA['pm']['you_have']}$num_unread {$_PWNDATA['pm']['one_new']}</a>";
        } else {
            $post_sub_r .= "<a href=\"forum.php?do=pmbox\">{$_PWNDATA['pm']['you_have']}$num_unread {$_PWNDATA['pm']['some_new']}</a>";
        }
    } else {
        $post_sub_r = "<a href=\"forum.php?do=login\">{$_PWNDATA['forum']['login']}</a> or <a href=\"forum.php?do=newuser\">{$_PWNDATA['forum']['register']}</a>";
    }
    $post_sub_r .= " | <a href=\"forum.php?do=search_form\">{$_PWNDATA['forum']['search_link']}</a>";
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
    $temp_res = override_sql_query("SELECT `id` FROM `{$_PREFIX}topics` WHERE board=$id");
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
    $cats = override_sql_query("SELECT * FROM `{$_PREFIX}categories` ORDER BY orderid", $db);
    
    while ($cat = mysql_fetch_array($cats)) {
        $category = $cat['id'];
        $block_content =  <<<END
	<div id="category_$category" style="border: 0px">
		<table class="forum_base" width="100%">
END;
        $result = override_sql_query("SELECT * FROM `{$_PREFIX}boards` WHERE `catid`=$category ORDER BY orderid", $db);
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
                    $block_content .=  <<<END
	<tr><td rowspan="2" {$_PWNICONS['forum']['icon_width']} class="forum_board_readicon">$read_or_not</td>
		<td class="forum_board_title"><a href="forum.php?do=viewforum&amp;id=
END;
                    $block_content .= $row['id'] . "\">" . $row['title'];
                    $block_content .= "</a></td><td rowspan=\"2\" width=\"30%\" class=\"forum_board_last\" align=\"center\">";
                    $resulta = override_sql_query("SELECT `id`,`title` FROM `{$_PREFIX}topics` WHERE board='" . $row['id'] . "' ORDER BY lastpost DESC", $db);
                    $topic = mysql_fetch_array($resulta);
                    $resulta = override_sql_query("SELECT `id`,`time`,`content`,`authorid` FROM `{$_PREFIX}posts` WHERE topicid='" . $topic['id'] . "' ORDER BY id DESC LIMIT 1", $db);
                    $post = mysql_fetch_array($resulta);
                    $resulta = override_sql_query("SELECT `id`,`name` FROM `{$_PREFIX}users` WHERE id='" . $post['authorid'] . "'" , $db);
                    $poster= mysql_fetch_array($resulta);
                    $authid = $poster['id'];
                    $resulta = override_sql_query("SELECT COUNT(*) FROM `{$_PREFIX}topics` WHERE board='" . $row['id'] . "' ORDER BY lastpost DESC", $db);
                    $counter = mysql_fetch_array($resulta);
                    $topics_in_board = $counter["COUNT(*)"];
                    $post_time = date("M jS Y, g:i a", $post['time']);

                    $post_bb = "[b]Posted by:[/b] " . $poster['name'] . "\n" . substr($post['content'],0,500);
                    $post_bb = bbDecode($post_bb);
                    $post_bb = str_replace("\\","\\\\",$post_bb);
                    $post_bb = str_replace("'","\\'",$post_bb);
                    $post_bb = str_replace("\"","&quot;",$post_bb);
                    $post_bb = str_replace("&lt;","&amp;lt;",$post_bb);
                    $post_bb = str_replace("&gt;","&amp;gt;",$post_bb);
                    $post_bb = str_replace("<","&lt;",$post_bb);
                    $post_bb = str_replace(">","&gt;",$post_bb);
                    $preview_js = "onmousemove=\"blama=true\" onmouseout=\"showPrev('EXIT');\" onmouseover=\"showPrev('$post_bb');\"";   
                    if ($topics_in_board > 0) {
                        $block_content .= "<b>{$_PWNDATA['forum']['last']}: <a href=\"forum.php?do=viewtopic&amp;last=1&amp;id=" . $topic['id'] . "\" $preview_js>" . $topic['title'] . "</a></b><br />{$_PWNDATA['forum']['by']}: <a href=\"forum.php?do=viewprofile&amp;id=$authid\">" . $poster['name'] . "</a> $post_time</td>";
                    } else {
                        $block_content .= "<b>{$_PWNDATA['forum']['noposts']}</b></td>";
                    }
                    $block_content .= "<td rowspan=\"2\" align=\"center\" width=\"70\" class=\"forum_board_topics\">$topics_in_board {$_PWNDATA['forum']['topics']}</td>";
                    $block_content .= "<td rowspan=\"2\" align=\"center\" width=\"70\" class=\"forum_board_posts\">" . getPostsInBoard($row['id']) . " {$_PWNDATA['forum']['posts']}</td></tr>";
                    $block_content .= "\n	<tr><td class=\"forum_board_desc\">" . $row['desc'] . "</td></tr>";
                } else {
                    // Has a link.
                    $link = $row['link'];
                    $block_content .=  <<<END
	<tr><td rowspan="2" {$_PWNICONS['forum']['icon_width']} class="forum_board_linkicon">{$_PWNICONS['forum']['weblink']}</td>
END;
                    $block_content .= "<td class=\"forum_board_linktitle\"><a href=\"$link\">" . $row['title'];
                    $block_content .= "</a></td><td rowspan=\"2\" width=\"30%\" class=\"forum_board_last\"></td>";
                    $block_content .= "<td rowspan=\"2\" align=\"center\" width=\"70\" class=\"forum_board_topics\"></td><td rowspan=\"2\" align=\"center\" width=\"70\" class=\"forum_board_posts\"></td></tr>";
                    $block_content .= "\n	<tr><td class=\"forum_board_linkdesc\">" . $row['desc'] . "</td></tr>";
                }
            }
        }
        $block_content .= "</table></div>";
        $post_content .= makeBlock("<a href=\"javascript:flipVisibility('category_$category')\">" . $cat['name'] . "</a>","&nbsp;",$block_content);
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
// XXX: Don't use this. It's horrible.
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
    $result = override_sql_query("SELECT `id`,`vis_level`,`title` FROM `{$_PREFIX}boards` WHERE id='" . $_GET['id'] . "'", $db);
    $board = mysql_fetch_array($result);
    if (!isset($board['id'])) {
        messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['board_does_not_exist']);
    }
    if ($board['vis_level'] > $user['level']) {
        messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['improper_permission']);
    }
    $post_title_add = " :: " . $board['title'];
    $post_sub_add = " > <a href=\"forum.php?do=viewforum&amp;id=" . $board['id'] . "\">" . $board['title'] . "</a>";
    $post_sub_r = post_sub_r($user['id']);
    $block_content = "";
    $block_content .= <<<END
	<table class="mod_set"><tr>
END;
    if (!($board['top_level'] > $user['level'])) {
        $block_content .= drawButton("forum.php?do=newtopic&amp;id=" . $board['id'], $_PWNDATA['forum']['new_topic'],$_PWNICONS['buttons']['new_topic']);
    }
    if (!isset($_GET['p'])) {
        $page = 0;
    } else {
        $page = ($_GET['p'] - 1) * $_THREADSPERPAGE;
    }
    if ($page > 0) {
        $block_content .= drawButton("forum.php?do=viewforum&amp;id=" . $board['id'] . "&amp;p=" . ($page / $_THREADSPERPAGE), $_PWNDATA['forum']['previous_page'],$_PWNICONS['buttons']['previous']);
    }
    $temp_mysql = override_sql_query("SELECT COUNT(*) FROM `{$_PREFIX}topics` WHERE board='" . $board['id'] . "'", $db);
    $temp_res = mysql_fetch_array($temp_mysql);
    $total_posts = $temp_res['COUNT(*)'];
    if ((int)(($total_posts - 1) / $_THREADSPERPAGE + 1) > 1) {
        $block_content .= printPager("forum.php?do=viewforum&amp;id={$board['id']}&amp;p=",(int)($page / $_THREADSPERPAGE + 1),(int)(($total_posts - 1) / $_THREADSPERPAGE + 1));
    }
    if ($total_posts > $page + $_THREADSPERPAGE) {
        $block_content .= drawButton("forum.php?do=viewforum&amp;id=" . $board['id'] . "&amp;p=" . ($page / $_THREADSPERPAGE + 2), $_PWNDATA['forum']['next_page'],$_PWNICONS['buttons']['next']);
    }
    $block_content .=   <<<END
		</tr></table>
		<table class="forum_base" width="100%">
END;
    $result = override_sql_query("SELECT * FROM `{$_PREFIX}topics` WHERE board='" . $board['id'] . "' ORDER BY stick DESC, lastpost DESC LIMIT $page, $_THREADSPERPAGE", $db);
    while ($row = mysql_fetch_array($result)) {
        $readmb = check_read($row['id'],$user['id']);
        $resultb = override_sql_query("SELECT `id`,`name` FROM `{$_PREFIX}users` WHERE id='" . $row['authorid'] . "'" , $db);
        $rowb = mysql_fetch_array($resultb);
        $resultc = override_sql_query("SELECT `id`,`content`,`authorid` FROM `{$_PREFIX}posts` WHERE topicid='" . $row['id'] . "' ORDER BY id ASC LIMIT 1", $db);
        $firstpost = mysql_fetch_array($resultc);
        $resultc = override_sql_query("SELECT `id`,`content`,`authorid`,`time` FROM `{$_PREFIX}posts` WHERE topicid='" . $row['id'] . "' ORDER BY id DESC LIMIT 1", $db);
        $rowc = mysql_fetch_array($resultc);
        $result_posts = override_sql_query("SELECT COUNT(*) FROM `{$_PREFIX}posts` WHERE topicid='" . $row['id'] . "'", $db);
        $posts_counter = mysql_fetch_array($result_posts);
        $resultd = override_sql_query("SELECT `id`,`name` FROM `{$_PREFIX}users` WHERE id='" . $rowc['authorid'] . "'" , $db);
        $rowd = mysql_fetch_array($resultd);
        $post_bb = "[b]Posted by:[/b] " . $rowb['name'] . "\n" . substr($firstpost['content'],0,500);
        $post_time = date("M jS Y, g:i a", $rowc['time']);
        $post_bb = bbDecode($post_bb);
        $post_bb = str_replace("\\","\\\\",$post_bb);
        $post_bb = str_replace("'","\\'",$post_bb);
        $post_bb = str_replace("\"","&quot;",$post_bb); 
        $post_bb = str_replace("&lt;","&amp;lt;",$post_bb);
        $post_bb = str_replace("&gt;","&amp;gt;",$post_bb);
        $post_bb = str_replace("<","&lt;",$post_bb);
        $post_bb = str_replace(">","&gt;",$post_bb);
        $preview_a = "onmousemove=\"blam=true\" onmouseout=\"showPrev('EXIT');\" onmouseover=\"showPrev('$post_bb');\"";
        $post_bb = "[b]Posted by:[/b] " . $rowd['name'] . "\n" . substr($rowc['content'],0,500);
        $post_bb = bbDecode($post_bb);
        $post_bb = str_replace("\\","\\\\",$post_bb);
        $post_bb = str_replace("'","\\'",$post_bb);
        $post_bb = str_replace("\"","&quot;",$post_bb);
        $post_bb = str_replace("&lt;","&amp;lt;",$post_bb);
        $post_bb = str_replace("&gt;","&amp;gt;",$post_bb);
        $post_bb = str_replace("<","&lt;",$post_bb);
        $post_bb = str_replace(">","&gt;",$post_bb);
        $preview_b = "onmousemove=\"blama=true\" onmouseout=\"showPrev('EXIT');\" onmouseover=\"showPrev('$post_bb');\"";
        $read_or_not = "<td rowspan=\"2\" class=\"forum_thread_icon\" {$_PWNICONS['forum']['topic']}>";
        $topic_type = "";
        if (!$readmb) {
            $read_or_not .= $_PWNICONS['forum']['topic_read'];
        }
        if ($row['has_poll'] == 1) {
	        $read_or_not .= $_PWNICONS['forum']['topic_poll'];
	        $topic_type .= $_PWNDATA['forum']['poll'] . " ";
        }
        if ($row['locked'] == 1) {
	        $read_or_not .= $_PWNICONS['forum']['topic_lock'];
	        $topic_type .= $_PWNDATA['forum']['locked'] . " ";
        }
        if ($row['stick'] == 1) {
	        $read_or_not .= $_PWNICONS['forum']['topic_stick'];
	        $topic_type .= $_PWNDATA['forum']['sticky'] . " ";
        } else if ($row['stick'] == -1) {
            $read_or_not .= $_PWNICONS['forum']['topic_sink'];
            $topic_type .= $_PWNDATA['forum']['issunk'] . " ";
        }
        $read_or_not .= "</td><td class=\"forum_thread_title\"><span class=\"forum_base_text\"><b>{$topic_type}</b></span> ";
        $diver = $row['id'];
        $block_content .=   <<<END
	<tr>
		$read_or_not<div id="title_$diver" style="display: inline;" $preview_a><a href="forum.php?do=viewtopic&amp;id=
END;
        $block_content .=  $row['id'] . "\">" . $row['title'] . "</a>";
        $top_temp = $row['id'];
        $author = $rowb['name'];
        $authid = $rowb['id'];
        $posts_in_topic = $posts_counter['COUNT(*)'];
        $pages = (floor(($posts_in_topic - 1) / $_POSTSPERPAGE));
        $pagination = "";
        if ($pages > 0) {
            $pagination = printPagerNonTabular("forum.php?do=viewtopic&amp;id=$top_temp&amp;p=",0,$pages + 1);
        }
        $toptitle = str_replace("\"","&quot;",$row['title']);
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
        $block_content .=  "</div>\n$edtitle</td><td rowspan=\"2\" width=\"30%\" class=\"forum_thread_last\" align=\"center\">";
        $authid = $rowd['id'];
        $block_content .=  "\n<b><a href=\"forum.php?do=viewtopic&amp;id=$top_temp&amp;last=1\" $preview_b>{$_PWNDATA['forum']['last_post']}</a> {$_PWNDATA['forum']['by']}:</b> <a href=\"forum.php?do=viewprofile&amp;id=$authid\">" . $rowd['name'] . "</a><br />{$_PWNDATA['forum']['at']}: $post_time</td></tr>";
        $block_content .= "<tr><td class=\"forum_thread_author\">\n{$_PWNDATA['forum']['author']}: <a href=\"forum.php?do=viewprofile&amp;id=$authid\">$author</a>$pagination</td></tr>";
    }
    $block_content .=   <<<END
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
        $block_content .= <<<END
<table class="mod_set">
<tr>
END;
        $block_content .= drawButton("forum.php?do=newpm",$_PWNDATA['pm']['new_pm'],$_PWNICONS['buttons']['new_pm']);
        $block_content .= drawButton("forum.php?do=delpm&amp;id=ALL",$_PWNDATA['pm']['empty_box']);
        $block_content .= <<<END
</tr>
</table>
END;
    }
    $block_content .=  <<<END
		<table class="forum_base" width="100%">
END;

    $pmresult = override_sql_query("SELECT * FROM `{$_PREFIX}pms` WHERE `to`=" . $user['id'] . " ORDER BY id DESC", $db);
    while ($row = mysql_fetch_array($pmresult)) {
        $readmb = $row['read'];
        if ($readmb == 1) {
            $read_or_not = $_PWNICONS['forum']['pm_read'];
        } else {
            $read_or_not = $_PWNICONS['forum']['pm_new'];
        }
        $block_content .=  <<<END
	<tr>
		<td class="forum_thread_icon" {$_PWNICONS['forum']['icon_width']} rowspan="2">$read_or_not</td><td class="forum_thread_title"><a href="forum.php?do=readpm&amp;id=
END;
//"
        $resultb = override_sql_query("SELECT `id`,`name` FROM `{$_PREFIX}users` WHERE id='" . $row['from'] . "'" , $db);
        $rowb = mysql_fetch_array($resultb);
        $block_content .= $row['id'] . "\">" . $row['title'];
        $author = $rowb['name'];
        $authid = $rowb['id'];
        $tim = date("F j, Y (g:ia T)", $row['time']);
        $block_content .= "</a></td></tr><tr><td class=\"forum_thread_author\">{$_PWNDATA['pm']['from']}: <a href=\"forum.php?do=viewprofile&amp;id=$authid\">$author</a>, {$_PWNDATA['pm']['sent_at']} $tim</td></tr>";
    }

    $block_content .= "</table>";
    $post_content = makeBlock($_PWNDATA['pm']['view'],"",$block_content);
}

// Delete PM
if ($_GET['do'] == "delpm") {
    $tomustbe = $user['id'];
    if ($_GET['id'] != "ALL") {
        $pmresult = override_sql_query("SELECT `id`,`to` FROM `{$_PREFIX}pms` WHERE `id`=" . $_GET['id'] . " AND `to`=$tomustbe", $db);
    } else {
        $pmresult = override_sql_query("SELECT `id`,`to` FROM `{$_PREFIX}pms` WHERE `to`=$tomustbe", $db);
    }
    $pm = mysql_fetch_array($pmresult);
    if (!isset($_SESSION['sess_id'])) {
        messageBack($_PWNDATA['pm']['view'],$_PWNDATA['pm']['must_be_logged_in']);
    }
    // XXX: The following is an impossible condition...
    if (($user['id'] != $pm['to']) and ($user['level'] < 3)) {
        messageBack($_PWNDATA['pm']['view'],$_PWNDATA['pm']['only_admins']);
    }
    if ($_GET['id'] != "ALL") {
	    override_sql_query("DELETE FROM `{$_PREFIX}pms` WHERE `{$_PREFIX}pms`.`id` =" . $_GET['id']);
	    messageRedirect($_PWNDATA['pm']['view'],$_PWNDATA['pm']['pm_deleted'],"forum.php?do=pmbox");
    } else {
	    override_sql_query("DELETE FROM `{$_PREFIX}pms` WHERE `to`=$tomustbe");
	    messageRedirect($_PWNDATA['pm']['view'],$_PWNDATA['pm']['box_emptied'],"forum.php?do=pmbox");
    }
}

// View a PM
if ($_GET['do'] == "readpm") {
    $pmresult = override_sql_query("SELECT * FROM `{$_PREFIX}pms` WHERE `id`=" . $_GET['id'], $db);
    $pm = mysql_fetch_array($pmresult);
    $resultb = override_sql_query("SELECT `id`,`name` FROM `{$_PREFIX}users` WHERE id='" . $pm['from'] . "'" , $db);
    $fromuser = mysql_fetch_array($resultb);
    if (($user['id'] != $pm['to']) and ($user['level'] < 3)) {
        messageBack($_PWNDATA['pm']['view'],$_PWNDATA['pm']['only_admins']);
    }
    $replyto = $fromuser['id'];
    $replytitle = "Re: " . $pm['title'];
    $pid = $pm['id'];
    override_sql_query("UPDATE `{$_PREFIX}pms` SET `read` =1 WHERE `{$_PREFIX}pms`.`id` =" . $pm['id']);
    $post_title_add = " :: {$_PWNDATA['pm']['view']} :: {$_PWNDATA['pm']['reading']} '" . $pm['title'] . "'";
    $post_sub_add = " > <a href=\"forum.php?do=pmbox\">{$_PWNDATA['pm']['view']}</a> > {$_PWNDATA['pm']['reading']} \"" . $pm['title'] . "\"";
    $post_sub_r = post_sub_r($user['id']);
    $block_content = "";
    $block_content .=  "<table class=\"mod_set\"><tr>";
    $block_content .= drawButton("forum.php?do=newpm&amp;to=$replyto&amp;s=$replytitle",$_PWNDATA['pm']['reply']);
    $block_content .= drawButton("forum.php?do=delpm&amp;id=$pid",$_PWNDATA['pm']['delete']);
    $block_content .= drawButton("forum.php?do=newpm&amp;to=$replyto&amp;s=$replytitle&amp;q=$pid",$_PWNDATA['pm']['quote']);
    $block_content .= "</tr></table><table class=\"forum_base\" width=\"100%\"><tr><td class=\"forum_topic_content\">";
    $block_content .= BBDecode($pm['content']);
    $block_content .= "</td></tr></table>";
    $post_content = makeBlock($pm['title'] . " {$_PWNDATA['pm']['from']} <a href=\"forum.php?do=viewprofile&amp;id=" . $fromuser['id'] . "\">" . $fromuser['name'] . "</a>","{$_PWNDATA['pm']['sent_at']} " . date("F j, Y (g:ia T)", $pm['time']),$block_content);
    
}

// New PM (compose)
if ($_GET['do'] == "newpm") {
    if (!isset($_SESSION['sess_id'])){
        messageBack($_PWNDATA['pm']['view'],$_PWNDATA['pm']['not_logged_in']);
    }
    if ($_GET['to'] != "") {
        $result = override_sql_query("SELECT `id`,`name` FROM `{$_PREFIX}users` WHERE id='" . $_GET['to'] . "'", $db);
        $touser = mysql_fetch_array($result);
        $tousername = $touser['name'];
    }
    $quoted = "";
    if ($_GET['q'] != "") {
        $result = override_sql_query("SELECT * FROM `{$_PREFIX}pms` WHERE id='" . $_GET['q'] . "'", $db);
        $quotedpm = mysql_fetch_array($result);
        $result = override_sql_query("SELECT `id`,`name` FROM `{$_PREFIX}users` WHERE id='" . $quotedpm['from'] . "'", $db);
        $quoteduser = mysql_fetch_array($result);
        $quoted = "[quote][b]{$_PWNDATA['pm']['original_message']} " . $quoteduser['name'] . ":[/b]\n" . $quotedpm['content']. "[/quote]\n";
    }
    $subjto = $_GET['s'];
    $post_title_add = " :: " . $_PWNDATA['pm']['composing'];
    $post_sub_add = " > " . $_PWNDATA['pm']['composing'];
    $post_sub_r = post_sub_r($user['id']);
    $block_content = "";
    $block_content .=  printPoster('content') . <<<END
<form action="forum.php" method="post" name="form">
<input type="hidden" name="action" value="new_pm" />
<table class="forum_base" width="100%">
<tr><td class="forum_topic_content" width="300">{$_PWNDATA['pm']['to']}</td>
<td class="forum_topic_content"><input type="text" name="toline" style="width:100%" value="$tousername" /></td></tr>
<tr><td class="forum_topic_sig">{$_PWNDATA['pm']['subject']}</td>
<td class="forum_topic_sig"><input type="text" name="subj" style="width:100%" value="$subjto" /></td></tr>
<tr><td class="forum_topic_sig" colspan="2">{$_PWNDATA['pm']['body']}</td></tr>
<tr><td class="forum_topic_sig" colspan="2"><textarea rows="11" name="content" style="width:100%;" cols="20" class="content_editor">$quoted</textarea></td></tr>
<tr><td class="forum_topic_sig" colspan="2"><input type="submit" value="{$_PWNDATA['pm']['send']}" name="sub" /></td></tr>
</table>
END;
    $block_content .= "<input type=\"hidden\" name=\"board\" value=\"" . $board['id'] . "\" />";
    $block_content .= "<input type=\"hidden\" name=\"user\" value=\"" . $user['id'] . "\" />";
    $block_content .= "</form>";
    $post_content = makeBlock($_PWNDATA['pm']['composing'],"",$block_content);
}

// Split topic
if ($_GET['do'] == "splittopic") {
    if ($user['level'] < $site_info['mod_rank']) {
        messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['not_permitted'],"index.php");
    }
    $result = override_sql_query("SELECT `id`,`board`,`title` FROM `{$_PREFIX}topics` WHERE id='" . $_GET['id'] . "'", $db);
    $topic = mysql_fetch_array($result);
    $resultb = override_sql_query("SELECT `id`,`title` FROM `{$_PREFIX}boards` WHERE id='" . $topic['board'] . "'", $db);
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
    $result = override_sql_query("SELECT `id`,`content`,`authorid` FROM `{$_PREFIX}posts` WHERE topicid='" . $topic['id'] . "'", $db);
    while ($row = mysql_fetch_array($result)) {
        $resultb = override_sql_query("SELECT `id`,`name` FROM `{$_PREFIX}users` WHERE id='" .  $row['authorid'] . "'", $db);
        $post_author = mysql_fetch_array($resultb);
        $block_content .= "<tr><td class=\"glow\">";
        $block_content .= $post_author['name'];
        $block_content .= "</td><td class=\"forum_topic_content\">";
        $postbb = bbDecode(substr($row['content'],0,500));
        $block_content .= $postbb . "</td><td class=\"forum_topic_content\" width=\"20\">";
        $block_content .= "<input type=\"checkbox\" name=\"post_" . $row['id'] . "\" />";
        $block_content .= "</td></tr>";
    }
    $block_content .= <<<END
<tr><td colspan="3" class="forum_topic_sig">
<input type="submit" value="{$_PWNDATA['forum']['modtools']['split']}" />
</td></tr>
END;
    $block_content .= "</table></form>";
    $post_content = makeBlock($_PWNDATA['forum']['modtools']['splittopic'],"",$block_content);
}

if ($_GET['do'] == "mergetopics") {
    if ($user['level'] < $site_info['mod_rank']) {
        messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['not_permitted'],"index.php");
    }
    $result = override_sql_query("SELECT `id`,`title`,`board` FROM `{$_PREFIX}topics` WHERE id='" . $_GET['id'] . "'", $db);
    $topic = mysql_fetch_array($result);
    $resultb = override_sql_query("SELECT `id`,`title` FROM `{$_PREFIX}boards` WHERE id='" . $topic['board'] . "'", $db);
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
    $result = override_sql_query("SELECT `id`,`title`,`authorid` FROM `{$_PREFIX}topics` WHERE `board`={$board['id']}", $db);
    while ($row = mysql_fetch_array($result)) {
        if ($row['id'] != $topic['id']) {
            $resultb = override_sql_query("SELECT `id`,`name` FROM `{$_PREFIX}users` WHERE id='" .  $row['authorid'] . "'", $db);
            $post_author = mysql_fetch_array($resultb);
            $block_content .= "<tr><td class=\"glow\" width=\"150\">";
            $block_content .= $post_author['name'];
            $block_content .= "</td><td class=\"forum_topic_content\">";
            $block_content .= $row['title'] . "</td><td class=\"forum_topic_content\" width=\"20\">";
            $block_content .= "<input type=\"radio\" name=\"topic_" . $row['id'] . "\" />";
            $block_content .= "</td></tr>";
        }
    }
    $block_content .= <<<END
<tr><td colspan="3" class="forum_topic_sig">
<input type="submit" value="{$_PWNDATA['forum']['modtools']['merge']}" />
</td></tr>
END;
    $block_content .= "</table></form>";
    $post_content = makeBlock($_PWNDATA['forum']['modtools']['splittopic'],"",$block_content);
}

// Show the posts in this topic.
if ($_GET['do'] == "viewtopic") {
    $result = override_sql_query("SELECT * FROM `{$_PREFIX}topics` WHERE id='" . $_GET['id'] . "'", $db);
    $topic = mysql_fetch_array($result);
    if (!isset($topic['id'])) {
        messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['invalid_topic']);
    }
    $resultb = override_sql_query("SELECT * FROM `{$_PREFIX}boards` WHERE id='" . $topic['board'] . "'", $db);
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
        $title_content .= "[{$_PWNDATA['forum']['locked']}] ";
    }
    $title_content .= "<a href=\"#qreply_bm\">" . $topic['title'] . "</a>";
    $resultb = override_sql_query("SELECT * FROM `{$_PREFIX}users` WHERE id='" .  $topic['authorid'] . "'", $db);
    $author = mysql_fetch_array($resultb);
    $block_content = "";
    $block_content .=  <<<END
		<table class="forum_base" width="100%">
END;
    if ($topic['has_poll'] == 1) {
        $pollresults = override_sql_query("SELECT * FROM `{$_PREFIX}polls` WHERE `id`=" . $topic['poll_id']);
        $poll = mysql_fetch_array($pollresults);
        // Our topic has a poll, draw the voting array.
        $pid = $poll['id'];
        $tid = $topic['id'];
        $block_content .= <<<END
	<tr>
		<td colspan="2" align="center" class="forum_topic_poll"><form name="pollresponse" method="post" action="forum.php">
		<input type="hidden" name="action" value="vote_poll" />
		<input type="hidden" name="pid" value="$pid" />
		<input type="hidden" name="tid" value="$tid" />
		<table class="forum_poll_table">
END;
        $block_content .= "<tr><td colspan=\"2\" align=\"center\" class=\"forum_topic_poll_title\">" . $poll['title'] . "</td></tr>\n\n";
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
	        $block_content .= "<tr><td class=\"forum_topic_poll_option\" align=\"right\">$bounce<span class=\"forum_body\">" . $poll_options[$i] . "</span></td>\n";
	        $wid = ($poll_votes[$i] / $totalVotes) * $widthOfBar;
	        $block_content .= "<td class=\"forum_topic_poll_votebar\" align=\"left\"><img src=\"{$_PWNICONS['forum']['pollpath']}$img/poll_left.png\" alt=\"[\"/><img src=\"{$_PWNICONS['forum']['pollpath']}$img/poll_mid.png\" height=\"10\" width=\"$wid\" alt=\"$wid\"/><img src=\"{$_PWNICONS['forum']['pollpath']}$img/poll_right.png\" alt=\"]\"/><span size=\"1\"> (" . (int)$poll_votes[$i] . ") </span></td></tr>\n";
	        $img++;
        }
        if ($hasVoted == false) {
	        $submitPoll = "<input type=\"submit\" value=\"{$_PWNDATA['forum']['vote']}\" />";
        }
        $block_content .= <<<END
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
        $temp_mysql = override_sql_query("SELECT COUNT(*) FROM `{$_PREFIX}posts` WHERE topicid='" . $topic['id'] . "'", $db);
        $temp_res = mysql_fetch_array($temp_mysql);
        $last_rep_id = $temp_res['COUNT(*)'] - 1;
        $page = (floor($last_rep_id / $_POSTSPERPAGE)) * $_POSTSPERPAGE;
    }
    $PAGING = "";
    $temp_mysql = override_sql_query("SELECT COUNT(*) FROM `{$_PREFIX}posts` WHERE topicid='" . $topic['id'] . "'", $db);
    $posts_counter = mysql_fetch_array($temp_mysql);
    $posts_in_topic = $posts_counter['COUNT(*)'];
    $pages = (floor(($posts_in_topic - 1) / $_POSTSPERPAGE));
    $top_id = $topic['id'];
    if ($pages > 0) {
        $PAGING = printPager("forum.php?do=viewtopic&amp;id=$top_id&amp;p=",(floor($page / $_POSTSPERPAGE)) + 1,$pages+1);
    }
    $block_content .=  <<<END
	<tr><td class="forum_topic_buttonbar" colspan="2"><table style="border: 0px" class="borderless_table"><tr>
END;
    if ((!($board['post_level'] > $user['level'])) and ($islocked == false)) {
        $block_content .= drawButton("forum.php?do=newreply&amp;id=" . $topic['id'],$_PWNDATA['forum']['add_reply'],$_PWNICONS['buttons']['new_reply']);
    }
    $block_content .= $PAGING . "</tr></table></td></tr>";
    $result = override_sql_query("SELECT * FROM `{$_PREFIX}posts` WHERE topicid='" . $topic['id'] . "' LIMIT $page, $_POSTSPERPAGE", $db);
    $im_names = explode(",",$site_info['ims']);
    $im_titles = explode(",",$site_info['ims_title']);
    $im_table = array_combine($im_names,$im_titles);
    while ($row = mysql_fetch_array($result)) {
        $resultb = override_sql_query("SELECT `id`,`name`,`level`,`ims`,`sig`,`avatar` FROM `{$_PREFIX}users` WHERE id='" .  $row['authorid'] . "'", $db);
        $post_author = mysql_fetch_array($resultb);
        if (!$post_author) {
            $post_author['name'] = "Guest";
            $post_author['level'] = "0";
        }
        $topglow = "class=\"glow\"";
        if ($post_author['level'] >= $site_info['mod_rank']) {
            $topglow = "class=\"glow_mod\"";
        }
        if ($post_author['level'] >= $site_info['admin_rank']) {
            $topglow = "class=\"glow_admin\"";
        }
        $block_content .=  <<<END
	<tr>
		<td width="15%" valign="top" $topglow rowspan="2">
END;
        if ($post_author['avatar'] != "") {
            $ava = "<img src=\"" . $post_author['avatar'] . "\" alt=\"" . $post_author['name']  . "'s {$_PWNDATA['profile']['avatar']}\"/><br />";
        } else {
            $ava = "";
        }
        $pCount = postCount($post_author['id']);
        if ($post_author['level'] >= $site_info['admin_rank']) {
            $ava = "\n<span class='adm_name'>" . getRankName($post_author['level'],$site_info,$pCount) . "</span><br />" . $ava;
        } elseif ($post_author['level'] >= $site_info['mod_rank']) {
            $ava = "\n<span class='mod_name'>" . getRankName($post_author['level'],$site_info,$pCount) . "</span><br />" . $ava;
        } elseif ($post_author['level'] < $site_info['mod_rank']) {
            $ava = "\n" . getRankName($post_author['level'],$site_info,$pCount) . "<br />" . $ava;
        }
        // User info panel shown on side...
        $contenta = BBDecode($row['content']);
        $contentb = BBDecode($post_author['sig']);
        $authid = $post_author['id'];
        $auth_info = ""; // Define our place to build the user's info, 
        $auth_card = "";
        $has_messenger = false; // then we'll go through the IMs...
        $im_values = explode(",",$post_author['ims']);
        $i = 0;
        foreach ($im_table as $im_name => $im_title) {
            if (strlen($im_values[$i]) > 0) {
                $has_messenger = true;
                $auth_info .= $_PWNICONS['protocols'][$im_name];
            }
            $auth_card .= "[img]" . $_PWNICONS['protocols']['icons'][$im_name] . "[/img]: {$im_values[$i]}\n";
            $i += 1;
        }
        if ($has_messenger) {
            $messaging = "[b]" . $post_author['name'] . "[/b]\n[img]{$_PWNICONS['protocols']['messaging']}[/img]\n";
            $post_bb = bbDecode($messaging . $auth_card);
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
        if (!$post_author['level'] < 1) {
            $block_content .= "<span class=\"forum_user\"><a href=\"forum.php?do=viewprofile&amp;id=$authid\">" . $post_author['name'] . "</a><br />" . $ava . $auth_info . $postinfo . "</span>";
        } else {
            $block_content .= "<span class=\"forum_user\">" . $post_author['name'] . "<br />" . $ava . $auth_info . $postinfo . "</span>";
        }
        $block_content .= "</td>\n<td valign=\"top\" class=\"forum_topic_content\"><div align=\"right\" class=\"forum_time\">{$_PWNDATA['forum']['posted_at']} " . date("F j, Y (g:ia T)", $row['time']) . "</div>\n<div id=\"post_content_" . $row['id'] . "\">";
        $block_content .= "\n" . $contenta;
        if (($user['id'] == $post_author['id']) or ($user['level'] >= $site_info['mod_rank'])) {
            $post_bb = str_replace("\"","&quot;",$row['content']);
            $post_bb = str_replace("<","&lt;",$post_bb);
            $post_bb = str_replace(">","&gt;",$post_bb);
            $block_content .= "</div><div style=\"display: none\" id=\"post_edit_" . $row['id'] . "\">";
            $block_content .= printPosterEditor('content',$row['id']) . <<<END
    <form action="forum.php" method="post" name="form_{$row['id']}">
        <input type="hidden" name="action" value="edit_reply" />
        <table class="forum_base" width="100%">
            <tr><td class="forum_topic_content"><textarea rows="11" name="content" style="width:100%; font-family:Tahoma; font-size:10pt" cols="80" class="content_editor" id="content_{$row['id']}">{$post_bb}</textarea></td></tr>
            <tr><td class="forum_topic_sig"><input type="submit" value="{$_PWNDATA['forum']['save_changes']}" name="sub" /></td></tr>
        </table>
        <input type="hidden" name="id" value="{$row['id']}" />
        <input type="hidden" name="topic" value="{$row['topicid']}" />
    </form>
END;
        }
        $block_content .= "\n</div></td></tr><tr><td class=\"forum_topic_sig\">" . $contentb;
        $block_content .= "\n</td></tr><tr><td colspan=\"2\" class=\"forum_button_bar\" align=\"right\"><table class=\"borderless_table\"><tr>\n";
        // Is this the viewing member's post?
        if (($user['id'] == $post_author['id']) or ($user['level'] >= $site_info['mod_rank'])) {
            
            $block_content .= drawButton("javascript:flipVisibility('post_content_{$row['id']}'); flipVisibility('post_edit_{$row['id']}');",$_PWNDATA['forum']['qedit'],$_PWNICONS['buttons']['qedit']);
            $block_content .= drawButton("forum.php?do=editreply&amp;id=" . $row['id'],$_PWNDATA['forum']['edit'],$_PWNICONS['buttons']['edit']);
        }
        // Moderation Tools 
        if ($user['level'] >= $site_info['mod_rank']) {
            if ($user['level'] >= $site_info['admin_rank']) {
                $block_content .= drawButton("javascript:alert('IP: " . $row['ip'] . "');",$_PWNDATA['forum']['ip']);
            } // Only administrators can view the IP of a post. This is to keep moderators from h4xing
            $block_content .= drawButton("javascript:if (confirm('" . $_PWNDATA['forum']['delete_confirm'] . "')) { window.location.href = 'forum.php?do=delete&amp;id=" . $row['id'] . "'; }", $_PWNDATA['forum']['delete'],$_PWNICONS['buttons']['del_reply']);
        }
        if (($user['id'] != $post_author['id']) and (!($board['post_level'] > $user['level'])) and ($islocked == false)) {
            $block_content .= drawButton("forum.php?do=newreply&amp;id=" . $topic['id'] . "&amp;quote=" . $row['id'],$_PWNDATA['forum']['quote'],$_PWNICONS['buttons']['quote']);
        }
        $block_content .= "</tr></table></td></tr>";
    }
    $block_content .=  <<<END
	<tr><td class="forum_topic_buttonbar" colspan="2"><table style="border: 0px" class="borderless_table"><tr>
END;
    if ((!($board['post_level'] > $user['level'])) and ($islocked == false)) {
        $block_content .= drawButton("forum.php?do=newreply&amp;id=" . $topic['id'],$_PWNDATA['forum']['add_reply'],$_PWNICONS['buttons']['new_reply']);
    }
    if ($user['level'] >= $site_info['mod_rank']) {
        $block_content .= drawButton("javascript:if (confirm('" . $_PWNDATA['forum']['delete_confirm'] . "')) { window.location.href = 'forum.php?do=deltop&amp;id=" . $topic['id'] . "'; }", $_PWNDATA['forum']['del_topic'],$_PWNICONS['buttons']['del_topic']);
        if ($topic['stick'] == 0) { // Stick
            $block_content .= drawButton("forum.php?do=sticktop&amp;id=" . $topic['id'],$_PWNDATA['forum']['stick_topic'],$_PWNICONS['buttons']['stick']);
            $block_content .= drawButton("forum.php?do=sinktop&amp;id=" . $topic['id'],$_PWNDATA['forum']['sink'],$_PWNICONS['buttons']['sink']);
        } else if ($topic['stick'] == 1) { // Unstick
            $block_content .= drawButton("forum.php?do=unsticktop&amp;id=" . $topic['id'],$_PWNDATA['forum']['unstick_topic'],$_PWNICONS['buttons']['unstick']);
        } else if ($topic['stick'] == -1) {
            $block_content .= drawButton("forum.php?do=unsinktop&amp;id=" . $topic['id'],$_PWNDATA['forum']['unsink'],$_PWNICONS['buttons']['unsink']);
        }
        if ($topic['locked'] == 0) {
            $block_content .= drawButton("forum.php?do=locktop&amp;id=" . $topic['id'],$_PWNDATA['forum']['lock_topic'],$_PWNICONS['buttons']['lock']);
        } else {
            $block_content .= drawButton("forum.php?do=unlocktop&amp;id=" . $topic['id'],$_PWNDATA['forum']['unlock_topic'],$_PWNICONS['buttons']['unlock']);
        }
        $block_content .= drawButton("javascript:flipVisibility('movebox');",$_PWNDATA['forum']['move_topic'],$_PWNICONS['buttons']['move']);
        $top_id = $topic['id'];
        $block_content .= <<<END
<td  style="border: 0px"><div id="movebox" style="display:none;">
<form action="forum.php" method="post" style="display:inline;">
<input type="hidden" name="action" value="move_topic" />
<input type="hidden" name="topid" value="$top_id" />
<select name="board">
END;
        $result = override_sql_query("SELECT `id`,`name` FROM `{$_PREFIX}categories` ORDER BY `orderid`");
        while ($cat = mysql_fetch_array($result)) {
	        $block_content .= "\n<optgroup label=\"" . $cat['name'] . "\">";
	        $catid = $cat['id'];
	        $resultb = override_sql_query("SELECT `id`,`title`,`vis_level`,`link` FROM `{$_PREFIX}boards` WHERE `catid`=$catid ORDER BY `orderid`");
	        while ($board = mysql_fetch_array($resultb)) {
	            if ($board['link'] == "NONE") {
		            if ($user['level'] >= $board['vis_level']) {
		                if ($topic['board'] == $board['id']) {
		                $block_content .= "\n<option selected=\"selected\" label=\"" . $board['title'] . "\" value=\"" . $board['id'] . "\">" . $board['title'] . "</option>";
		                } else {
		                $block_content .= "\n<option label=\"" . $board['title'] . "\" value=\"" . $board['id'] . "\">" . $board['title'] . "</option>";
		                }
		            }
		        }
	        }
	        $block_content .= "\n</optgroup>";
        }
        $block_content .= "</select>\n<input type=\"submit\" value=\"{$_PWNDATA['forum']['move_topic']}\" /></form></div></td>";
        $block_content .= drawButton("forum.php?do=splittopic&amp;id=" . $topic['id'],$_PWNDATA['forum']['modtools']['splittopic'],$_PWNICONS['buttons']['split']);
        $block_content .= drawButton("forum.php?do=mergetopics&amp;id=" . $topic['id'],$_PWNDATA['forum']['modtools']['mergetopic'],$_PWNICONS['buttons']['merge']);
    }
    $block_content .= $PAGING;
    $block_content .=  <<<END
	</tr></table></td></tr></table>
END;
    if (($user['level'] >= $board['post_level']) and ($islocked == false)) {
        $block_content .= <<<END
<a name="qreply_bm"></a>
<table class="forum_quickreply" width="100%">
<tr><td align="center" class="forum_quickreply_title">
<b><a href="javascript:flipVisibility('qreply');">{$_PWNDATA['forum']['quick_reply']}</a></b><br /></td></tr>
<tr><td align="center" class="forum_quickreply_box">
<div id="qreply" style="display: none;">
END;
        $block_content .= printPosterMini('content', $topic['id']) . <<<END
<form action="forum.php" method="post" name="form">
<input type="hidden" name="action" value="new_reply" />
END;
        $block_content .= "<input type=\"hidden\" name=\"topic\" value=\"" . $topic['id'] . "\" />";
        $block_content .= "<input type=\"hidden\" name=\"user\" value=\"" . $user['id'] . "\" />";
        $block_content .= <<<END
<textarea name="content" style="width: 95%;" rows="5" cols="80" class="content_editor"></textarea><br />
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
    $result = override_sql_query("SELECT `id`,`top_level`,`title` FROM `{$_PREFIX}boards` WHERE id='" . $_GET['id'] . "'", $db);
    $board = mysql_fetch_array($result);
    if ($board['top_level'] > $user['level']) {
        messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['not_permitted_topic_new']);
    }
    $post_title_add = " :: " . $board['title'] . " :: " . $_PWNDATA['forum']['new_topic'];
    $post_sub_add = " > <a href=\"forum.php?do=viewforum&amp;id=" . $board['id'] . "\">" . $board['title'] . "</a> > " . $_PWNDATA['forum']['new_topic'];
    $post_sub_r = post_sub_r($user['id']);
    $block_content = "";
    $block_content .=  printPoster('content') . <<<END
<form action="forum.php" method="post" name="form">
<input type="hidden" name="action" value="new_topic" />
<table class="forum_base" width="100%">
<tr><td class="forum_topic_content" width="300">{$_PWNDATA['forum']['subject']}</td>
<td class="forum_topic_content"><input type="text" name="subj" style="width:100%" /></td></tr>
<tr><td class="forum_topic_sig" colspan="2">{$_PWNDATA['forum']['body']}</td></tr>
<tr><td class="forum_topic_sig" colspan="2"><textarea rows="11" name="content" style="width:100%; font-family:Tahoma; font-size:10pt" cols="80" class="content_editor"></textarea></td></tr>
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
    $block_content .= "<input type=\"hidden\" name=\"board\" value=\"" . $board['id'] . "\" />";
    $block_content .= "<input type=\"hidden\" name=\"user\" value=\"" . $user['id'] . "\" />";
    $block_content .=  <<<END
</form>
END;
    $post_content = makeBlock($board['title'],$_PWNDATA['forum']['new_topic'],$block_content);  
}

// Create a new reply.
if ($_GET['do'] == "newreply") {
    $result = override_sql_query("SELECT `id`,`title`,`locked`,`board` FROM `{$_PREFIX}topics` WHERE id='" . $_GET['id'] . "'", $db);
    $topic = mysql_fetch_array($result);
    $result = override_sql_query("SELECT `id`,`title`,`post_level` FROM `{$_PREFIX}boards` WHERE id='" . $topic['board'] . "'", $db);
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
	    $result = override_sql_query("SELECT `id`,`content`,`authorid` FROM `{$_PREFIX}posts` WHERE id='" . $_GET['quote'] . "'", $db);
	    $quoted = mysql_fetch_array($result);
	    if ($quoted['authorid'] == 0) {
	        $quotedauthor['name'] = "Guest";
        } else {
	        $result = override_sql_query("SELECT `id`,`name` FROM `{$_PREFIX}users` WHERE id='" . $quoted['authorid'] . "'", $db);
	        $quotedauthor = mysql_fetch_array($result);
	    }
	    $postquoted = preg_replace("/(\[quote\])(.+?)(\[\/quote\])/si","",$quoted['content']);
	    $cont = "[quote][b]{$_PWNDATA['forum']['original']}[/b] " . $quotedauthor['name'] . "\n" . $postquoted . "[/quote]";
    }
    $block_content .=  printPoster('content') . <<<END
<form action="forum.php" method="post" name="form">
<input type="hidden" name="action" value="new_reply" />
<table class="forum_base" width="100%">
<tr><td class="forum_topic_content">
<textarea rows="11" name="content" style="width:100%; font-family:Tahoma; font-size:10pt" cols="80" class="content_editor">$cont</textarea></td></tr>
<tr><td class="forum_topic_sig">
<input type="submit" value="{$_PWNDATA['forum']['submit_post']}" name="sub" />
</td></tr>
END;
    $block_content .= "<input type=\"hidden\" name=\"topic\" value=\"" . $topic['id'] . "\" />";
    $block_content .= "<input type=\"hidden\" name=\"user\" value=\"" . $user['id'] . "\" /></form>";
    $resultz = override_sql_query("SELECT `id`,`content`,`authorid` FROM `{$_PREFIX}posts` WHERE topicid='" . $topic['id'] . "' ORDER BY `id` DESC LIMIT 5", $db);
    $block_content .= "<tr><td class=\"forum_topic_sig\" align=\"center\"><b>{$_PWNDATA['forum']['recent']}</b></td></tr></table><table class=\"forum_base\" width=\"100%\">\n";
    while ($rowz = mysql_fetch_array($resultz)) {
        $resultb = override_sql_query("SELECT `id`,`name` FROM `{$_PREFIX}users` WHERE id='" .  $rowz['authorid'] . "'", $db);
        $post_author = mysql_fetch_array($resultb);
        $auth_name = $post_author['name'];
        $dec_post = BBDecode($rowz['content']);
        $block_content .= "<tr><td width=\"20%\" valign=\"top\" class=\"glow\">$auth_name</td><td class=\"forum_topic_content\">$dec_post</td></tr>\n";
    }
    $block_content .=  <<<END
	</table>
	
END;

    $post_content = makeBlock($topic['title'],$_PWNDATA['forum']['replying'],$block_content);
}

// Edit a past post
if ($_GET['do'] == "editreply") {
    if (!isset($_SESSION['sess_id'])) {
        messageBack($_PWNDATA['forum']['not_logged_in']);
    }
    $result = override_sql_query("SELECT * FROM `{$_PREFIX}posts` WHERE id='" . $_GET['id'] . "'", $db);
    $reply = mysql_fetch_array($result);
    if (($reply['authorid'] != $user['id']) and ($user['level'] < 2)) {
	    messageBack($_PWNDATA['forum_page_title'],$_PWNDATA['forum']['not_yours']);
    }
    $result = override_sql_query("SELECT * FROM `{$_PREFIX}topics` WHERE id='" . $reply['topicid'] . "'", $db);
    $topic = mysql_fetch_array($result);
    $result = override_sql_query("SELECT * FROM `{$_PREFIX}boards` WHERE id='" . $topic['board'] . "'", $db);
    $board = mysql_fetch_array($result);
    $post_title_add = " :: " . $board['title'] . " :: " . $_PWNDATA['forum']['editing'];
    $post_sub_add = " > <a href=\"forum.php?do=viewforum&amp;id=" . $board['id'] . "\">" . $board['title'] . "</a> > " . $_PWNDATA['forum']['editing'];
    $post_sub_r = post_sub_r($user['id']);
    $block_content = "";
    $block_content .= printPoster('content') . <<<END
<form action="forum.php" method="post" name="form">
<input type="hidden" name="action" value="edit_reply" />
<table class="forum_base" width="100%">
<tr><td class="forum_topic_content"><textarea rows="11" name="content" style="width:100%; font-family:Tahoma; font-size:10pt" cols="80" class="content_editor">
END;
    $post_bb = str_replace("\"","&quot;",$reply['content']);
    $post_bb = str_replace("<","&lt;",$post_bb);
    $post_bb = str_replace(">","&gt;",$post_bb);
    $block_content .= $post_bb;
    $block_content .= <<<END
</textarea></td></tr>
<tr><td class="forum_topic_sig">
<input type="submit" value="{$_PWNDATA['forum']['save_changes']}" name="sub" /></td></tr>
</table>
END;
    $block_content .= "<input type=\"hidden\" name=\"id\" value=\"" . $reply['id'] . "\" />";
    $block_content .= "<input type=\"hidden\" name=\"topic\" value=\"" . $topic['id'] . "\" />";
    $block_content .=  <<<END
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
    $sbona = $user['sbonforum'];
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
    if ($user['rich_edit'] == 1) {
        $reon = "checked";
    } else {
        $reon = "";
    }
    $theme_list = themeList($u_theme);
    $color_list = colorList($u_color);
    $icons_list = iconsList($u_icons);
    $lang_list = langList($u_lang);
    $block_content .= <<<END
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
END;
    $im_names = explode(",",$site_info['ims']);
    $im_titles = explode(",",$site_info['ims_title']);
    $im_values = explode(",",$user['ims']);
    $im_table = array_combine($im_names,$im_titles);
    $i = 0;
    foreach ($im_table as $im_name => $im_title) {
            $block_content .= "<tr><td class=\"forum_topic_sig\">{$_PWNICONS['protocols'][$im_name]} {$im_title}</td><td class=\"forum_topic_sig\"><input type=\"text\" name=\"im_{$im_name}\" value=\"{$im_values[$i]}\" style=\"width: 100%\" /></td></tr>\n";
            $i += 1;
    }
    $block_content .= <<<END
  <tr><td class="forum_thread_title" colspan="2"><b>{$_PWNDATA['profile']['posting']}</b></td></tr>
  <tr><td class="forum_topic_sig" colspan="2">{$_PWNDATA['profile']['sig']}</td></tr>
  <tr><td class="forum_topic_sig" colspan="2">
END;
    $block_content .= printPoster('sig') . <<<END
  </td></tr>
  <tr><td class="forum_topic_sig" colspan="2"><textarea rows="5" name="sig" style="width:100%" cols="80" class="content_editor">$sig</textarea></td></tr>
  <tr><td class="forum_topic_sig">{$_PWNDATA['profile']['avatar']}</td>
  <td class="forum_topic_sig"><input type="text" name="avatar" value="$ava" style="width: 100%" /></td></tr>
  <tr><td class="forum_thread_title" colspan="2"><b>{$_PWNDATA['profile']['settings']}</b></td></tr>
  <tr><td class="forum_topic_sig">{$_PWNDATA['profile']['sidebar']}</td><td class="forum_topic_sig"><input name="sbonforum" type="checkbox" $sbon /> {$_PWNDATA['profile']['sidebar']}</td></tr>
  <tr><td class="forum_topic_sig">{$_PWNDATA['profile']['rich_edit']}</td><td class="forum_topic_sig"><input name="richedit" type="checkbox" $reon /> {$_PWNDATA['profile']['rich_edit']}</td></tr>
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
    $result = override_sql_query("SELECT * FROM `{$_PREFIX}users` WHERE id='" . $_GET['id'] . "'", $db);
    $vuser = mysql_fetch_array($result);
    if (!isset($vuser['id'])) {
        messageBack($_PWNDATA['forum_page_title'],"User does not exist.");
    }
    $uid = $vuser['id'];
    $umail = $vuser['email'];
    $uname = $vuser['name'];
    $sig = BBDecode($vuser['sig']);
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
    $im_names = explode(",",$site_info['ims']);
    $im_titles = explode(",",$site_info['ims_title']);
    $im_values = explode(",",$vuser['ims']);
    $im_table = array_combine($im_names,$im_titles);
    $i = 0;
    $row_span = 5 + count($im_names);
    $reg_date = date("M j, Y",$vuser['time']);
    $block_content .= <<<END
    <table class="forum_base" width="100%">
    <tr><td class="forum_profile_user" align="center">$ava</td><td class="forum_profile_user">$uname</td></tr>
    <tr><td class="forum_topic_sig" width="300">$modstatus</td>
    <td class="forum_topic_sig" rowspan="11" valign="top">{$_PWNICONS['profile']['quote_left']}$sig{$_PWNICONS['profile']['quote_right']}</td></tr>
  <tr><td class="forum_topic_sig">$posts {$_PWNDATA['forum']['posts']}</td></tr>
  <tr><td class="forum_topic_sig">{$_PWNDATA['profile']['registered_on']} {$reg_date}</td></tr>
  <tr><td class="forum_thread_title"><b>{$_PWNDATA['profile']['messaging']}:</b></td></tr>
END;
    foreach ($im_table as $im_name => $im_title) {
            $block_content .= "<tr><td class=\"forum_topic_sig\">{$_PWNICONS['protocols'][$im_name]} {$im_values[$i]}</td></tr>\n";
            $i += 1;
    }
    $block_content .= <<<END
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
    if (!isset($_POST['q']) && !isset($_POST['a'])) {
        $search = $_SESSION['search_'  . $_GET['sid'] . '_search'];
        $auth = $_SESSION['search_' . $_GET['sid'] . '_auth'];
        if (!isset($_GET['p'])) {
            $page = 0;
        } else {
            $page = (int)(($_GET['p'] - 1) * $_POSTSPERPAGE);
        }
        $sid = (int)$_GET['sid'];
    } else {
        $search = mse($_POST['q']);
        $auth = mse($_POST['a']);
        $sid = time();
        $_SESSION['search_' . $sid . '_search'] = $search;
        $_SESSION['search_' . $sid . '_auth'] = $auth;
        $page = 0;
    }
    $post_title_add = " :: {$_PWNDATA['forum']['searching_for']} '$search'";
    $post_sub_add = " > {$_PWNDATA['forum']['searching_for']} '$search'";
    $post_sub_r = post_sub_r($user['id']);
    if ($auth == "") {
        $Query = "MATCH (content) AGAINST ('$search')";
    } else if ($search == "" && $auth != "") {
        $auth_result = override_sql_query("SELECT `id` FROM `{$_PREFIX}users` WHERE UCASE(name)=UCASE('{$auth}')");
        $temp = mysql_fetch_array($auth_result);
        $authid = $temp['id'];
        $Query = "`authorid`=$authid ORDER BY `id` DESC";
    } else {
        $auth_result = override_sql_query("SELECT `id` FROM `{$_PREFIX}users` WHERE UCASE(name)=UCASE('{$auth}')");
        $temp = mysql_fetch_array($auth_result);
        $authid = $temp['id'];
        $Query = "MATCH (content) AGAINST ('$search') AND `authorid`=$authid";
    }
    $temp = override_sql_query("SELECT COUNT(*) FROM `{$_PREFIX}posts` WHERE $Query", $db);
    $page_array = mysql_fetch_array($temp);
    $total_pages = $page_array['COUNT(*)'];
    $resultz = override_sql_query("SELECT `id`,`content`,`topicid`,`authorid` FROM `{$_PREFIX}posts` WHERE $Query LIMIT $page,$_POSTSPERPAGE", $db);
    if ($total_pages > 1) {
        $block_content .= "<table class=\"mod_set\"><tr>" . printPager("forum.php?do=search&amp;sid={$sid}&amp;p=",(int)($page / $_POSTSPERPAGE + 1),(int)(($total_pages - 1) / $_POSTSPERPAGE + 1)) . "</tr></table>";
    }
    $block_content .= "<table class=\"forum_base\" width=\"100%\">\n";
    $block_content .= "<tr><td class=\"forum_thread_title\" colspan=\"2\"><b>{$_PWNDATA['forum']['search_resultsb']}:</b></td></tr>";
    $results_count = 0;
    while ($rowz = mysql_fetch_array($resultz)) {
        $resultb = override_sql_query("SELECT `id`,`name` FROM `{$_PREFIX}users` WHERE id='" .  $rowz['authorid'] . "'", $db);
        $post_author = mysql_fetch_array($resultb);
        $resultc = override_sql_query("SELECT `id`,`title`,`board` FROM `{$_PREFIX}topics` WHERE id='" .  $rowz['topicid'] . "'", $db);
        $post_topic = mysql_fetch_array($resultc);
        $resultc = override_sql_query("SELECT `id`,`title`,`vis_level` FROM `{$_PREFIX}boards` WHERE id='" .  $post_topic['board'] . "'", $db);
        $post_board = mysql_fetch_array($resultc);
        $auth_name = $post_author['name'];
        $dec_post = BBDecode($rowz['content']);
        if ($post_board['vis_level'] > $user['level']) {
            // Do nothing, this post is in a board the user isn't allowed to see!
        } else {
            $block_content .= "<tr><td width=\"20%\" valign=\"top\" class=\"glow\">$auth_name</td><td  class=\"forum_topic_content\"><b><i>{$_PWNDATA['forum']['posted_in']}: <a href=\"forum.php?do=viewtopic&amp;id=" . $post_topic['id'] . "\">" . $post_topic['title'] . "</a></i></b><br />$dec_post</td></tr>\n";
            $results_count++;
        }
    }
    if ($results_count < 1) {
        $block_content .= "<tr><td class=\"forum_topic_content\" colspan=\"2\">No results.</td></tr>";
    }
    $block_content .= "</table>";
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

print $post_content;

// Print the board statistics

$sql_temp = override_sql_query("SELECT COUNT(*) FROM `{$_PREFIX}users`");
$stat_a = mysql_fetch_array($sql_temp);
$sql_temp = override_sql_query("SELECT COUNT(*) FROM `{$_PREFIX}topics`");
$stat_b = mysql_fetch_array($sql_temp);
$sql_temp = override_sql_query("SELECT COUNT(*) FROM `{$_PREFIX}posts`");
$stat_c = mysql_fetch_array($sql_temp);
$sql_temp = override_sql_query("SELECT COUNT(*) FROM `{$_PREFIX}topics` WHERE `{$_PREFIX}topics`.`stick`=1");
$stat_d = mysql_fetch_array($sql_temp);
$sql_temp = override_sql_query("SELECT `id`,`name` FROM `{$_PREFIX}users` ORDER BY `id` DESC");
$stat_e = mysql_fetch_array($sql_temp);
$num_users = $stat_a['COUNT(*)'];
$num_topics = $stat_b['COUNT(*)'];
$num_posts = $stat_c['COUNT(*)'];
$num_sticks = $stat_d['COUNT(*)'];
$last_member = $stat_e['name'];
$last_member_id = $stat_e['id'];
$block_content = "<div style=\"text-align: center;\">";
$block_content .= "{$_PWNDATA['forum']['there_are']}$num_posts{$_PWNDATA['forum']['posts_by']}$num_users{$_PWNDATA['forum']['members_in']}$num_topics{$_PWNDATA['forum']['_topics']}\n";
$block_content .= "$num_sticks{$_PWNDATA['forum']['are_sticky']}\n";
$block_content .= "<a href=\"forum.php?do=viewprofile&amp;id=$last_member_id\">$last_member</a>\n<br />";

$block_content .= "<b>{$_PWNDATA['forum']['members_online']}</b>: ";
$sql_temp = override_sql_query("SELECT `id`,`user` FROM `{$_PREFIX}sessions` ORDER BY `user`");
while ($on_session = mysql_fetch_array($sql_temp)) {
    $on_temp = override_sql_query("SELECT `id`,`name`,`level` FROM `{$_PREFIX}users` WHERE `id`=" . $on_session['user']);
    $on_user = mysql_fetch_array($on_temp);
    $on_id = $on_session['user'];
    $block_content .= "<a href=\"forum.php?do=viewprofile&amp;id=$on_id\">";
    if ($on_user['level'] < $site_info['mod_rank']) {
        $block_content .= $on_user['name'];
    } else if (($on_user['level'] >= $site_info['mod_rank']) and ($on_user['level'] < $site_info['admin_rank'])) {
        $block_content .= "<span class='mod_name'>" . $on_user['name'] . "</span>";
    } else if ($on_user['level'] >= $site_info['admin_rank']) {
        $block_content .= "<span class='adm_name'>" . $on_user['name'] . "</span>";
    }
    $block_content .= "</a> ";
}
$block_content .=  <<<END
	<br /><span size="1">({$_PWNDATA['forum']['user']} <span class='mod_name'>{$_PWNDATA['forum']['moderator']}</span> <span class='adm_name'>{$_PWNDATA['forum']['admin']}</span>)</span></div>
END;
print makeBlock($_PWNDATA['forum']['stats'],$_PWNDATA['forum']['at'] . " " . $site_info['name'],$block_content);
// End

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
