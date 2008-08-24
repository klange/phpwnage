<?php
/*
	This file is part of PHPwnage (Admin Control Panel)

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
// Jump to the login page instead of yelling and screaming.
if (isset($_SESSION['sess_id'])) {
    if ($user['level'] < $site_info['mod_rank']) {
        messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['not_permitted'],"index.php");
    }
} else {
    $ip = $_SERVER['REMOTE_ADDR'];
    $name = $_SESSION['user_name'];
    mysql_query("INSERT INTO `security` ( `time` , `passused`, `where`, `ip` ) VALUES ( '" . time() . "', '" . md5($_SESSION['user_pass']) . "', 'Admin, $name', '" . $ip . "' );");
    messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['please_wait_redirecting'],"forum.php?do=login&amp;amp;admin=yes"); 
}

// XXX: Begin POST functions

// Add a new article
if ($_POST['action'] == "add_article") {
    $newcontent = $_POST['content'];
    mysql_query("INSERT INTO `news` ( `id` , `title` , `content` , `time_code`, `user` )
VALUES (
NULL , '" . $_POST['title'] . "', '" . $newcontent . "', '" . time() . "', '" . $_SESSION['user_name'] . "'
);");
    $article_id = mysql_insert_id();
    mysql_query("UPDATE `info` SET `last_updated` = '" . time() . "' WHERE `info`.`id` =1");
    $message = $_PWNDATA['admin']['article_add_suc'];
    if ($_POST['add_to_forum'] == true) {
        $content = "[url=[site_url]article.php?id=" . $article_id . "]" . $_PWNDATA['read_article_here'] . "[/url]";
        mysql_query("INSERT INTO `topics` ( `id` , `authorid` , `board` , `title` ) VALUES (NULL , " . $user['id'] . ", " . $_POST['board'] . ", '" . mysql_real_escape_string($_POST['title']) . "');");
        $result = mysql_query("SELECT * FROM `topics` ORDER BY `id` DESC LIMIT 1");
        $topic = mysql_fetch_array($result);
        $ip=$_SERVER['REMOTE_ADDR'];
        mysql_query("INSERT INTO `posts` ( `id` , `topicid` , `authorid` , `content`, `time`, `ip` ) VALUES ( NULL , " . $topic['id'] . " , " . $user['id'] . " , '" . mysql_real_escape_string($content) . "' , " . time() . " , '" . $ip . "' );");
        $result = mysql_query("SELECT * FROM `posts` ORDER BY `id` DESC LIMIT 1");
        $reply = mysql_fetch_array($result);
        mysql_query("UPDATE `topics` SET `lastpost` = '" . $reply['id'] . "' WHERE `topics`.`id` =" . $topic['id']);
        mysql_query("ALTER TABLE `posts`  ORDER BY `id`");
        mysql_query("ALTER TABLE `topics`  ORDER BY `id`");
        $newcontenta = $newcontent . "\n\n\n[url=[site_url]article.php?id=" . $article_id . "]" . $_PWNDATA['discuss_article_here'] . "[/url].";
        mysql_query("UPDATE `news` SET `content` = '" . $newcontenta . "' WHERE `news`.`id` =" . $article_id);
        mysql_query("UPDATE `news` SET `topicid` = " . $topic['id'] . " WHERE `news`.`id` =" . $article_id);
        $message = $message . "<br />" . $_PWNDATA['admin']['news_post_added'] . "\n";
    }
    messageRedirect($_PWNDATA['admin_page_title'],$message,"admin.php?view=news"); 
}

// Add a new rank
if ($_POST['action'] == "addrank") {
    if ($user['level'] < $site_info['admin_rank']) {
        die("<font face=\"Tahoma\">" . $_PWNDATA['admin']['only_moderators_ranks'] . "</font>");
    }
    $rank = $_POST['level'];
    $name = $_POST['name'];
    $posts = $_POST['posts'];
    mysql_query("INSERT INTO `ranks` (`value`, `name`, `posts`) VALUES ($rank, '$name', $posts)");
    messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['rank_added'] . ": '$name'","admin.php?view=promo"); 
}

// Clear the security log
if ($_POST['action'] == "clear_security") {
    mysql_query("TRUNCATE TABLE `security`");
    messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['security_log_cleared'],"admin.php?view=bans"); 
}

// Custom pages
if ($_POST['action'] == "custom_page") {
    mysql_query("INSERT INTO `pages` ( `name` , `display_name` , `content` , `showsidebar` , `author`)
VALUES (
'" . $_POST['name'] . "', '" . $_POST['display_name'] . "', '" . $_POST['content'] . "', '" . $_POST['showsidebar'] . "', '" . $_POST['author'] . "'
);");
    messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['page_added'],"admin.php?view=pages"); 
}

// Update site information
if ($_POST['action'] == "site_info") {
    mysql_query("UPDATE `info` SET `name` = '" . $_POST['name'] . "' WHERE `info`.`id` =1");
    mysql_query("UPDATE `info` SET `url` = '" . $_POST['url'] . "' WHERE `info`.`id` =1");
    mysql_query("UPDATE `info` SET `copyright` = '" . $_POST['copyright'] . "' WHERE `info`.`id` =1");
    mysql_query("UPDATE `info` SET `pheader` = '" . $_POST['pheader'] . "' WHERE `info`.`id` =1");
    mysql_query("UPDATE `info` SET `right_data` = '" . $_POST['right_data'] . "' WHERE `info`.`id` =1");
    messageRedirect($_PWNDATA['admin_page_title'], $_PWNDATA['admin']['site_info_updated'], "admin.php?view=site_info");
}

if ($_POST['action'] == "captcha") {
    mysql_query("UPDATE `info` SET `security_mode` = " . $_POST['sec_mode'] . " WHERE `info`.`id` =1");
    mysql_query("UPDATE `info` SET `recap_pub` = '" . $_POST['recap_pub'] . "' WHERE `info`.`id` =1");
    mysql_query("UPDATE `info` SET `recap_priv` = '" . $_POST['recap_priv'] . "' WHERE `info`.`id` =1");
    messageRedirect($_PWNDATA['admin_page_title'], $_PWNDATA['admin']['captcha_updated'], "admin.php?view=bans");
}

// Update existing block
if ($_POST['action'] == "edit_block") {
    mysql_query("UPDATE `blocks` SET `title` = '" . $_POST['title'] . "' WHERE `blocks`.`id` =" . $_POST['blockid'] . ";");
    mysql_query("UPDATE `blocks` SET `content` = '" . $_POST['content'] . "' WHERE `blocks`.`id` =" . $_POST['blockid'] . ";");
    messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['block_edited'] . ": '" . $_POST['title'] . "'","admin.php?view=blocks");
}

// Add new block
if ($_POST['action'] == "add_block") {
    mysql_query("INSERT INTO `blocks` ( `id` , `title` , `content` )
VALUES (
NULL , '" . $_POST['title'] . "', '" . $_POST['content'] . "'
);");
    messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['block_added']. ": '" . $_POST['title'] . "'","admin.php?view=blocks");
}

// Add new board
if ($_POST['action'] == "add_board") {
    mysql_query("INSERT INTO `boards` 
VALUES (
NULL , '" . $_POST['title'] . "', '" . $_POST['content'] . "', " . $_POST['order'] . ", " . $_POST['cat'] . ", " . $_POST['perma'] . ", " . $_POST['permb'] . ", " . $_POST['permc'] . ",'" . $_POST['link'] . "');");
    messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['board_added'] . ": '" . $_POST['title'] . "'", "admin.php?view=forum");
}

// Edit existing board
if ($_POST['action'] == "edit_board") {
    mysql_query("UPDATE `boards` SET `title`= '" . $_POST['title'] . "', `desc`='" . $_POST['content'] . "', `vis_level`=" . $_POST['perma'] . ", `top_level`=" . $_POST['permb'] . ", `post_level`=" . $_POST['permc'] . ", `link`='" . $_POST['link'] . "' WHERE `id` =" . $_POST['id'] . ";");
    messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['board_edited'] . ": '" . $_POST['title'] . "'", "admin.php?view=forum");
}

// Add category
if ($_POST['action'] == "add_category") {
    mysql_query("INSERT INTO `categories` VALUES (
NULL , " . $_POST['order'] . ", '" . $_POST['title'] . "');");
    messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['category_added'] . ": '" . $_POST['title'] . "'", "admin.php?view=forum");
}

// Edit existing category
if ($_POST['action'] == "edit_category") {
    mysql_query("UPDATE `categories` SET `name`= '" . $_POST['title'] . "' WHERE `id` =" . $_POST['id'] . ";");
    messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['category_edited'] . ": '" . $_POST['title'] . "'", "admin.php?view=forum");
}

// Add an IP ban
if ($_POST['action'] == "add_ban") {
    mysql_query("INSERT INTO `banlist` VALUES ('" . $_POST['ip'] . "');");
    messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['ip_banned'] . ": '" . $_POST['ip'],"admin.php?view=bans");
}

// Set up ranks
if ($_POST['action'] == "setranks") {
    if ($user['id'] != 1) {
        messageBack($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['only_root_ranks']);
    }
    mysql_query("UPDATE `info` SET `mod_rank`=" . $_POST['mod'] . " WHERE `id`=1");
    mysql_query("UPDATE `info` SET `admin_rank`=" . $_POST['adm'] . " WHERE `id`=1");
    mysql_query("UPDATE `users` SET `level`=" . $_POST['mod'] . " WHERE `level`>=" . $_POST['mod_old'] . " AND `level`<" . $_POST['adm_old']);
    mysql_query("UPDATE `users` SET `level`=" . $_POST['adm'] . " WHERE `level`>=" . $_POST['adm_old']);
    messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['ranks_update'],"admin.php?view=promo");
}

// Edit existing smiley
if ($_POST['action'] == "editsmiley") {
    $id = $_POST['id'];
    $name = $_POST['smileys'];
    $code = $_POST['code'];
    mysql_query("UPDATE `smileys` SET `code`='$code' WHERE `id`=$id");
    mysql_query("UPDATE `smileys` SET `image`='$name' WHERE `id`=$id");
    messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['smiley_edited'],"admin.php?view=forum");
}

// Add new smiley
if ($_POST['action'] == "addsmiley") {
    $name = $_POST['smileys'];
    $code = $_POST['code'];
    mysql_query("INSERT INTO `smileys` (`code`, `image`) VALUES ('$code','$name')");
    messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['smiley_added'],"admin.php?view=forum");
}

// Delete existing smiley
if ($_GET['do'] == "delsmile") {
    mysql_query("DELETE FROM `smileys` WHERE `id`=" . $_GET['id']);
    messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['smiley_deleted'],"admin.php?view=forum");
}

// Delete news item
if ($_GET['do'] == "del_news") {
    mysql_query("DELETE FROM `news` WHERE `id`=" . $_GET['id']);
    messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['article_deleted'],"admin.php?view=news");
}

// Delete custom page
if ($_GET['do'] == "del_page") {
    mysql_query("DELETE FROM `pages` WHERE `name`='" . $_GET['page'] . "'");
    messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['page_deleted'],"admin.php?view=pages");
}

// Delete IP ban
if ($_GET['do'] == "del_ban") {
    mysql_query("DELETE FROM `banlist` WHERE `ip`='" . $_GET['ban'] . "'");
    messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['ban_lifted'] . ": " . $_GET['ban'],"admin.php?view=bans");
}

// Delete board
if ($_GET['do'] == "del_brd") {
    mysql_query("DELETE FROM `boards` WHERE `id`=" . $_GET['id']);
    $top_count = 0;
    $temp = mysql_query("SELECT * FROM `topics` WHERE `board`=" . $_GET['id']);
    while ($top = mysql_fetch_array($temp)) {
        $top_count++;
        mysql_query("DELETE FROM `posts` WHERE `topicid`=" . $top['id']);
    }
    mysql_query("DELETE FROM `topics` WHERE `board`=" . $_GET['id']);
    $message = $_PWNDATA['admin']['board_deleted'] . "<br />$top_count " . $_PWNDATA['admin']['topics_deleted'];
    messageRedirect($_PWNDATA['admin_page_title'],$message,"admin.php?view=forum");
}

// Delete category
if ($_GET['do'] == "del_cat") {
    mysql_query("DELETE FROM `categories` WHERE `id`=" . $_GET['cat']);
    $brd_count = 0;
    $top_count = 0;
    $temp = mysql_query("SELECT * FROM `boards` WHERE `catid`=" . $_GET['cat']);
    while ($brd = mysql_fetch_array($temp)) {
        $brd_count++;
        $tempb = mysql_query("SELECT * FROM `topics` WHERE `board`=" . $brd['id']);
        while ($top = mysql_fetch_array($tempb)) {
            $top_count++;
            mysql_query("DELETE FROM `posts` WHERE `topicid`=" . $top['id']);
        }
        mysql_query("DELETE FROM `topics` WHERE `board`=" . $brd['id']);
    }
    mysql_query("DELETE FROM `boards` WHERE `catid`=" . $_GET['cat']);
    $message = $_PWNDATA['admin']['category_deleted'] . "<br />$brd_count " . $_PWNDATA['admin']['boards_deleted'] . "<br />$top_count " . $_PWNDATA['admin']['topics_deleted'];
    messageRedirect($_PWNDATA['admin_page_title'],$message,"admin.php?view=forum");
}

// Move board
if ($_GET['do'] == "mov_brd") {
    $temp = mysql_query("SELECT * FROM `boards` WHERE `id`=" . $_GET['id']);
    $board = mysql_fetch_array($temp);
    $cat = $board['catid'];
    $my_id = $_GET['id'];
    $cur = $_GET['cur'];
    if ($_GET['g'] == "up") {
        $up = $cur - 1;
        mysql_query("UPDATE `boards` SET `orderid`=$cur WHERE `catid`=$cat AND `orderid`=$up");
        mysql_query("UPDATE `boards` SET `orderid`=$up WHERE `id`=$my_id");
    } elseif ($_GET['g'] == "down") {
        $down = $cur + 1;
        mysql_query("UPDATE `boards` SET `orderid`=$cur WHERE `catid`=$cat AND `orderid`=$down");
        mysql_query("UPDATE `boards` SET `orderid`=$down WHERE `id`=$my_id");
    }
    messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['board_moved'],"admin.php?view=forum");
}

// Move a forum to a different category. Specify: id = board; catid = category to move to - IE: admin.php?do=recat&id=1&catid=4 (will move board #1 to category #4 and give it an orderid of 0 (top)
if ($_GET['do'] == "recat") {
    $temp = mysql_query("SELECT * FROM `boards` WHERE `id`=" . $_GET['id']);
    $board = mysql_fetch_array($temp);
    $cat = $board['catid'];
    $my_id = $_GET['id'];
    $up = $_GET['cat'];    mysql_query("UPDATE `boards` SET `catid`=$up WHERE `id`=$my_id");
    mysql_query("UPDATE `boards` SET `orderid`=0 WHERE `id`=$my_id");
    messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['board_moved'],"admin.php?view=forum");
}

// Move category
if ($_GET['do'] == "mov_cat") {
    $temp = mysql_query("SELECT * FROM `categories` WHERE `id`=" . $_GET['id']);
    $board = mysql_fetch_array($temp);
    $my_id = $_GET['id'];
    $cur = $_GET['cur'];
    if ($_GET['g'] == "up") {
        $up = $cur - 1;
        mysql_query("UPDATE `categories` SET `orderid`=$cur WHERE `orderid`=$up");
        mysql_query("UPDATE `categories` SET `orderid`=$up WHERE `id`=$my_id");
    } elseif ($_GET['g'] == "down") {
        $down = $cur + 1;
        mysql_query("UPDATE `categories` SET `orderid`=$cur WHERE `orderid`=$down");
        mysql_query("UPDATE `categories` SET `orderid`=$down WHERE `id`=$my_id");
    }
    messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['category_moved'],"admin.php?view=forum");
}

// Delete existing block
if ($_GET['do'] == "del_block") {
    mysql_query("DELETE FROM `blocks` WHERE `id`='" . $_GET['id'] . "'");
    messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['block_deleted'],"admin.php?view=blocks");
}

// Delete user
if ($_GET['do'] == "del_user") {
    mysql_query("DELETE FROM `users` WHERE `id`='" . $_GET['id'] . "'");
    mysql_query("DELETE FROM `posts` WHERE `authorid`='" . $_GET['id'] . "'");
    messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['user_posts_deleted'],"admin.php?view=members");
}

// Move block
if ($_GET['do'] == "mov_block") {
    $my_id = $_GET['id'];
    if ($_GET['g'] == "up") {
        $up = $my_id - 1;
        mysql_query("UPDATE `blocks` SET `id`=1234 WHERE `id`=$my_id");
        mysql_query("UPDATE `blocks` SET `id`=$my_id WHERE `id`=$up");
        mysql_query("UPDATE `blocks` SET `id`=$up WHERE `id`=1234");
    } elseif ($_GET['g'] == "down") {
        $down = $my_id + 1;
        mysql_query("UPDATE `blocks` SET `id`=1234 WHERE `id`=$my_id");
        mysql_query("UPDATE `blocks` SET `id`=$my_id WHERE `id`=$down");
        mysql_query("UPDATE `blocks` SET `id`=$down WHERE `id`=1234");
    }
    $temp_query = mysql_query("SELECT COUNT(`id`) FROM `blocks`");
    $temp_ret = mysql_fetch_array($temp_query);
    $highest = $temp_ret['COUNT(`id`)'] + 1;
    mysql_query("ALTER TABLE `blocks` auto_increment = $highest");
    messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['block_moved'],"admin.php?view=blocks");
}

// Delete rank
if ($_GET['do'] == "delrank") {
    if ($user['level'] < $site_info['admin_rank']) {
        messageBack($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['only_moderators_delranks']);
    }
    mysql_query("DELETE FROM `ranks` WHERE `id`=" . $_GET['rank']);
    messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['rank_deleted'],"admin.php?view=promo");
}

// Promote user
if ($_GET['do'] == "promote") {
    if ($user['level'] < $site_info['admin_rank']) {
        messageBack($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['only_moderators_promote']);
    }
    $temp = mysql_query("SELECT * FROM `users` WHERE `id`=" . $_GET['id']);
    $auser = mysql_fetch_array($temp);
    if ($user['id'] == $auser['id'] && $user['id'] != 1) {
        messageBack($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['promote_self']);
    }
    if ($auser['level'] == $user['level'] && $user['id'] != 1) {
        messageBack($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['promote_beyond']);
    }
    $level = $auser['level'] + 1;
    $my_id = $_GET['id'];
    mysql_query("UPDATE `users` SET `level`=$level WHERE `id`=$my_id");
    messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['user_promoted'],"admin.php?view=promo");
}

// Demote user
if ($_GET['do'] == "demote") {
    if ($user['level'] < $site_info['admin_rank']) {
        messageBack($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['only_moderators_demote']);
    }
    $temp = mysql_query("SELECT * FROM `users` WHERE `id`=" . $_GET['id']);
    $auser = mysql_fetch_array($temp);
    if ($auser['level'] > $user['level'] && $user['id'] != 1) {
        messageBack($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['demote_above']);
    }
    $level = $auser['level'] - 1;
    $my_id = $_GET['id'];
    mysql_query("UPDATE `users` SET `level`=$level WHERE `id`=$my_id");
    messageRedirect($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['user_demoted'],"admin.php?view=promo");
}

standardHeaders($site_info['name'] . " :: " . $_PWNDATA['admin_page_title'],true);
drawSubbar("<a href=\"index.php\">" . $site_info['name'] . "</a> > " . $_PWNDATA['admin_page_title'],$site_info['right_data']);

print <<<END
<table class="borderless_table" width="100%">
END;

// ------------------------------------------------------------ News
if ($_GET['view'] == "news") {
// Add article
$content = printPoster('content') . <<<END
<form action="admin.php" method="post" name="form">
<input type="hidden" name="action" value="add_article" />
<table class="forum_base" width="100%">
<tr><td class="forum_topic_content" width="300">{$_PWNDATA['admin']['forms']['article_title']}</td>
<td class="forum_topic_content"><input type="text" name="title" value="" style="width: 100%;" /></td></tr>
<tr><td class="forum_topic_sig"><input type="checkbox" name="add_to_forum" />{$_PWNDATA['admin']['forms']['article_forum_post']}</td><td class="forum_topic_sig">
<select name="board">
END;
$result = mysql_query("SELECT * FROM `categories` ORDER BY `orderid`");
while ($cat = mysql_fetch_array($result))
{
    $content = $content . "\n<optgroup label=\"" . $cat['name'] . "\">";
    $catid = $cat['id'];
    $resultb = mysql_query("SELECT * FROM `boards` WHERE `catid`=$catid ORDER BY `orderid`");
    while ($board = mysql_fetch_array($resultb)) {
        if ($board['link'] == "NONE") {
            $content = $content . "\n<option label=\"" . $board['title'] . "\" value=\"" . $board['id'] . "\">" . $board['title'] . "</option>";
        }
    }
    $content = $content . "\n</optgroup>";
}
$content = $content . <<<END
</select></td></tr>
END;
$content = $content . <<<END
<tr><td colspan="2" class="forum_thread_title">{$_PWNDATA['admin']['forms']['article_content']}</td></tr>
<tr><td colspan="2" class="forum_topic_sig"><textarea rows="6" name="content" style="width:100%;" cols="80" ></textarea></td></tr>
END;
$content = $content . "<tr><td class=\"forum_topic_sig\" colspan=\"2\"><input type=\"submit\" value=\"{$_PWNDATA['admin']['forms']['article_add']}\" /></td></tr></table></form>";
drawBlock("{$_PWNDATA['admin']['forms']['article_add']}","{$_PWNDATA['last_updated']} " . date("F j, Y (g:ia T)", $site_info['last_updated']),$content);

// View article, jump to edit
$content = "<table class=\"forum_base\" width=\"100%\">";
$odd = 1;
if (!isset($_GET['nolimit'])) {
    $result = mysql_query("SELECT * FROM `news` ORDER BY `id` DESC LIMIT 10");
    $content = $content . "<tr><td class=\"forum_topic_content\" colspan=\"3\">{$_PWNDATA['admin']['forms']['news_limit']} <a href=\"admin.php?view=news&amp;nolimit=1\">{$_PWNDATA['admin']['forms']['news_limit_all']}</a></td></tr>";
} else {
    $result = mysql_query("SELECT * FROM `news` ORDER BY `id` DESC");
}
while ($article = mysql_fetch_array($result))
{
$odd = 1 - $odd;
if ($odd == 1) {
$back = "class=\"forum_odd_row\"";
} else {
$back = "class=\"forum_topic_sig\" ";
}
$content = $content . "<tr><td $back>";
$content = $content . $article['title'] . " ({$_PWNDATA['posted_on']} " . date("n/j/y", $article['time_code']) . "; {$_PWNDATA['posted_on_by']} " . $article['user'] . ")</td>";
$content = $content . "<td $back><a href=\"admin.php?do=del_news&amp;id=" . $article['id'] . "\">{$_PWNDATA['admin']['forms']['delete']}</a></td><td $back><a href=\"article.php?id=" . $article['id'] . "\">{$_PWNDATA['admin']['forms']['view']} / {$_PWNDATA['admin']['forms']['edit']}</a></td>";
$content = $content . "</tr>";

}
$content = $content . "</table>";
drawBlock($_PWNDATA['admin']['forms']['articles'],"",$content);

}

if ($_GET['view'] == "pages") {
// Add a new page
$content = <<<END
<form action="admin.php" method="post">
<input type="hidden" name="action" value="custom_page" />
<table class="forum_base" width="100%">
<tr><td class="forum_topic_content" width="300">{$_PWNDATA['admin']['forms']['page_link']}</td><td class="forum_topic_content"><input type="text" name="name" value="" style="width: 100%;" /></td></tr>
<tr><td class="forum_topic_sig">{$_PWNDATA['admin']['forms']['page_title']}</td><td class="forum_topic_sig"><input type="text" name="display_name" value="" style="width: 100%;" /></td></tr>
<tr><td class="forum_topic_sig" colspan="2">{$_PWNDATA['admin']['forms']['page_content']}</td></tr><tr><td class="forum_topic_sig" colspan="2"><textarea rows="6" name="content" style="width:100%;" cols="80"></textarea><br />
<tr><td class="forum_topic_sig">{$_PWNDATA['admin']['forms']['page_author']}</td><td class="forum_topic_sig"><input type="text" name="author" value="" style="width: 100%;" /></td></tr>
<tr><td class="forum_topic_sig">{$_PWNDATA['admin']['forms']['page_sidebar']} (true / false)</td><td class="forum_topic_sig"><input type="text" name="showsidebar" value="false" style="width: 100%;" /></td></tr>
<tr><td class="forum_topic_sig" colspan="2"><input type="submit" value="{$_PWNDATA['admin']['forms']['page_add']}" /></td></tr>

</table></form>
END;
drawBlock("{$_PWNDATA['admin']['forms']['page_add']}","",$content);

// View, edit, and delete the existing pages
$content = "<table class=\"forum_base\" width=\"100%\">";
$odd = 1;
$result = mysql_query("SELECT * FROM `pages` ORDER BY `display_name` DESC");
while ($page = mysql_fetch_array($result))
{
$odd = 1 - $odd;
if ($odd == 1) {
$back = "class=\"forum_odd_row\"";
} else {
$back = "class=\"forum_topic_sig\" ";
}
$content = $content . "<tr><td $back>";
$content = $content . $page['display_name'] . "</td>";
$content = $content . "<td $back width=\"200\"><a href=\"admin.php?do=del_page&amp;page=" . $page['name'] . "\">{$_PWNDATA['admin']['forms']['delete']}</a></td><td $back width=\"200\"><a href=\"pages.php?page=" . $page['name'] . "\">{$_PWNDATA['admin']['forms']['view']} / {$_PWNDATA['admin']['forms']['edit']}</a></td>";
$content = $content . "</tr>";

}
$content = $content . "</table>";
drawBlock($_PWNDATA['admin']['forms']['pages'],"",$content);
}

if ($_GET['view'] == "forum") {
$result = mysql_query("SELECT * FROM `categories` ORDER BY `orderid`");
$content =  <<<END
<script type="text/javascript">
//<![CDATA[
function move_board(boardId) {
	var radioLength = document.cats.cats.length;
	var catId = -1;
	for(var i = 0; i < radioLength; i++) {
		if(document.cats.cats[i].checked) {
			catId = document.cats.cats[i].value;
		}
	}
	if (catId == -1) {
		alert("{$_PWNDATA['admin']['forms']['forum_alert_cat']}");
	} else {
	window.location = 'admin.php?do=recat&id='+boardId+'&cat='+catId;
	}
}
//]]>
</script>
END;
$content = $content . "<form name=\"cats\" action=\"admin.php\"><table class=\"forum_base\" width=\"100%\">\n";
$odd = 1;
while ($cat = mysql_fetch_array($result))
{
$odd = 1 - $odd;
if ($odd == 1) {
$back = "class=\"forum_odd_row\"";
} else {
$back = "class=\"forum_topic_sig\" ";
}
$catid = $cat['id'];
$content = $content . "<tr><td $back><input type=\"radio\" name=\"cats\" value=\"$catid\" /><b>\n";
$content = $content . $cat['name'] . "</b> <a href=\"admin.php?do=edit_cat&amp;id=$catid\">[{$_PWNDATA['admin']['forms']['edit']}]</a></td>\n";
$content = $content . "<td $back><b><a href=\"admin.php?do=del_cat&amp;cat=" . $cat['id'] . "\">{$_PWNDATA['admin']['forms']['delete']}</a>, <a href=\"admin.php?do=mov_cat&amp;g=up&amp;id=" . $cat['id'] . "&amp;cur=" . $cat['orderid'] . "\">{$_PWNDATA['admin']['forms']['forum_move_up']}</a>, <a href=\"admin.php?do=mov_cat&amp;g=down&amp;id=" . $cat['id'] . "&amp;cur=" . $cat['orderid'] . "\">{$_PWNDATA['admin']['forms']['forum_move_down']}</a></b></td>\n";
$content = $content . "</tr>";

	$resultb = mysql_query("SELECT * FROM `boards` WHERE `catid`=$catid ORDER BY `orderid`");
	while ($board = mysql_fetch_array($resultb))
	{
$odd = 1 - $odd;
if ($odd == 1) {
$back = "class=\"forum_odd_row\"";
} else {
$back = "class=\"forum_topic_sig\" ";
}
		$content = $content . "<tr><td $back> ---- ";
		$brdid = $board['id'];
		$content = $content . $board['title'] . " <a href=\"admin.php?do=edit_brd&amp;id=" . $board['id'] . "\">[{$_PWNDATA['admin']['forms']['edit']}]</a></td>\n";
		$content = $content . "<td $back><a href=\"admin.php?do=del_brd&amp;id=" . $board['id'] . "\">{$_PWNDATA['admin']['forms']['delete']}</a>, <a href=\"admin.php?do=mov_brd&amp;g=up&amp;id=" . $board['id'] . "&amp;cur=" . $board['orderid'] . "\">{$_PWNDATA['admin']['forms']['forum_move_up']}</a>, <a href=\"admin.php?do=mov_brd&amp;g=down&amp;id=" . $board['id'] . "&amp;cur=" . $board['orderid'] . "\">{$_PWNDATA['admin']['forms']['forum_move_down']}</a>, <a href=\"javascript: move_board('$brdid')\">{$_PWNDATA['admin']['forms']['forum_move_to_cat']}</a></td>\n";
		$content = $content . "</tr>";
		$lastbrd = $board['id'];
	}
$odd = 1 - $odd;
if ($odd == 1) {
$back = "class=\"forum_odd_row\"";
} else {
$back = "class=\"forum_topic_sig\" ";
}
$content = $content . "<tr><td $back colspan=\"2\"> -- <b><a href=\"admin.php?do=add_brd&amp;cat=" . $cat['id'] . "&amp;last=$lastbrd\">[{$_PWNDATA['admin']['forms']['forum_add_board']}]</a></b></td></tr>\n";
$lastcat = $catid;
}
$content = $content . "<tr><td $back colspan=\"2\"><a href=\"admin.php?do=new_cat&amp;last=$lastcat\">[{$_PWNDATA['admin']['forms']['forum_add_cat']}]</a></td></tr></table></form>\n";
drawBlock("{$_PWNDATA['admin']['forms']['forums']} - {$_PWNDATA['admin']['forms']['forum_order']}","",$content);
// Smiley Control
$content = "<b>{$_PWNDATA['admin']['forms']['forum_smileys']}: ({$_PWNDATA['admin']['forms']['forum_click_edit']})</b><br />";
$smilesSet = mysql_query("SELECT * FROM `smileys`");
while ($smile = mysql_fetch_array($smilesSet)) {
$content = $content . "<a href=\"admin.php?do=editsmiley&amp;id=" . $smile['id'] . "\"><img src=\"smiles/" . $smile['image'] . "\" alt=\"" . $smile['code'] . "\" /></a>";
}
$smileyList = "<select name=\"smileys\">";
// The following will never validate, but we honestly don't care.
$smileyStyle = "<style type=\"text/css\">";
$myDirectory = opendir("smiles"); // Open smiles directory
while($entryName = readdir($myDirectory)) {
	$dirArray[] = $entryName; // Get our list of files
}
closedir($myDirectory); // Close the directory
sort($dirArray); // Sort the array
$indexCount	= count($dirArray); // Count...
// This directory should not contain any files other than valid images (though most aren't smileys)
// So if it's a file (contains . but isn't hidden), it better be an image.
for($index=0; $index < $indexCount; $index++) {
if (substr("$dirArray[$index]", 0, 1) != "."){
if (strstr($dirArray[$index],".")) {
	$heightArray = getimagesize("smiles/" . $dirArray[$index]);
	$height = $heightArray[1];
	$smileyStyle = $smileyStyle . "\n.smiley" . $index . " { height: $height; background: url(\"smiles/" . $dirArray[$index] . "\"); background-repeat: no-repeat; }";
	$smileyList = $smileyList . "\n<option class=\"smiley" . $index . "\" value=\"" . $dirArray[$index] . "\">" . $dirArray[$index] . "</option>";
}
}
}
$smileyList = $smileyList . "</select>";
$smileyStyle = $smileyStyle . "\nselect { height: 3ex }\n</style>";
$content = $content . "<br />" . <<<END
<br />
<form action="admin.php" method="post">
<input type="hidden" name="action" value="addsmiley" />
{$_PWNDATA['admin']['forms']['forum_smileys_code']}: <input type="text" name="code" value="" />
$smileyStyle
$smileyList<br />
<input type="submit" value="{$_PWNDATA['admin']['forms']['forum_smileys_add']}" /></form>
END;
drawBlock($_PWNDATA['admin']['forms']['forum_smileys'],"",$content);
}
if ($_GET['do'] == "editsmiley") {
$smilesSet = mysql_query("SELECT * FROM `smileys` WHERE `id`=" . $_GET['id']);
$smile = mysql_fetch_array($smilesSet);
$content = "<b>{$_PWNDATA['admin']['forms']['forum_smileys_editing']} </b><img src=\"smiles/" . $smile['image'] . "\"><br />\n";
$name = $smile['image'];
$code = $smile['code'];
$id = $_GET['id'];
$smileyList = "<select name=\"smileys\">";
$smileyStyle = "<style>";
$myDirectory = opendir("smiles"); // Open 'blocks'
while($entryName = readdir($myDirectory)) {
	$dirArray[] = $entryName; // Get our list of files
}
closedir($myDirectory); // Close the directory
sort($dirArray); // Sort the array
$indexCount	= count($dirArray); // Count...
// This direct should not contain any files other than valid images (though most aren't smileys)
// So if it's a file (contains . but isn't hidden), it better be an image.
for($index=0; $index < $indexCount; $index++) {
if (substr("$dirArray[$index]", 0, 1) != "."){
if (strstr($dirArray[$index],".")) {
	$heightArray = getimagesize("smiles/" . $dirArray[$index]);
	$height = $heightArray[1];
	$smileyStyle = $smileyStyle . "\n.smiley" . $index . " { height: $height; background: url(\"smiles/" . $dirArray[$index] . "\"); background-repeat: no-repeat; }";
	if ($name == $dirArray[$index]) {
	$selected = " selected";
	} else { $selected = ""; }
	$smileyList = $smileyList . "\n<option class=\"smiley" . $index . "\" value=\"" . $dirArray[$index] . "\" $selected>" . $dirArray[$index] . "</option>";
}
}
}
$smileyList = $smileyList . "</select>";
$smileyStyle = $smileyStyle . "\nselect { height: 3ex }\n</style>";
$content = $content . "<br />" . <<<END
<form action="admin.php" method="post">
<input type="hidden" name="action" value="editsmiley" />
<input type="hidden" name="id" value="$id" />
{$_PWNDATA['admin']['forms']['forum_smileys_code']}: <input type="text" name="code" value="$code" />
$smileyStyle
$smileyList<br />
<input type="submit" value="{$_PWNDATA['admin']['forms']['forum_smileys_save']}" /><input type="button" value="{$_PWNDATA['admin']['forms']['forum_smileys_delete']}" onclick="window.location.href='admin.php?do=delsmile&amp;id=$id'" /></form>
END;

drawBlock($_PWNDATA['admin']['forms']['forum_smileys_edit'],"",$content);
}

if ($_GET['do'] == "new_cat") {
$neworder = $_GET['last'] + 1;
$content = "";
$content = $content . <<<END
<form action="admin.php" method="post">
<input type="hidden" name="action" value="add_category" />
END;
$content = $content . "<input name=\"title\" type=\"text\" value=\"{$_PWNDATA['admin']['forms']['forum_cat_name']}\" /><br />";
$content = $content . "<input name=\"order\" type=\"hidden\" value=\"$neworder\" />";
$content = $content . "<input type=\"submit\" value=\"{$_PWNDATA['admin']['forms']['forum_add_cat']}\" /></form>";
drawBlock($_PWNDATA['admin']['forms']['forum_add_cat'],"",$content);
}

if ($_GET['do'] == "edit_cat") {
$content = "";
$result = mysql_query("SELECT * FROM `categories` WHERE `id`=" . $_GET['id']);
$cat= mysql_fetch_array($result);
$cat_name = $cat['name'];
$cat_id = $cat['id'];
$content = $content . <<<END
<form action="admin.php" method="post">
<input type="hidden" name="action" value="edit_category" />
<input type="hidden" name="id" value="$cat_id" />
END;
$content = $content . "<input name=\"title\" type=\"text\" value=\"$cat_name\" /><br />";
$content = $content . "<input type=\"submit\" value=\"{$_PWNDATA['admin']['forms']['forum_save_cat']}\" /></form>";
drawBlock($_PWNDATA['admin']['forms']['forum_edit_cat'],"",$content);
}

if ($_GET['do'] == "add_brd") {
$content = "";
$content = $content . <<<END
<form action="admin.php" method="post">
<input type="hidden" name="action" value="add_board" />
<table class="forum_base" width="100%">
<tr><td class="forum_topic_content" width="300">
END;
$newcat = $_GET['cat'];
$neword = $_GET['last'] + 1;
$content = $content . "{$_PWNDATA['admin']['forms']['forum_board_name']}</td><td class=\"forum_topic_content\"><input name=\"title\" type=\"text\" style=\"width: 100%\" /></td></tr>";
$content = $content . "<tr><td class=\"forum_topic_sig\" colspan=\"2\"><textarea rows=\"3\" name=\"content\" style=\"width:100%;\" cols=\"80\">{$_PWNDATA['admin']['forms']['forum_board_desc']}</textarea></td></tr>\n";
$content = $content . "<tr><td class=\"forum_topic_sig\">{$_PWNDATA['admin']['forms']['forum_board_read']}</td><td class=\"forum_topic_sig\"><input name=\"perma\" type=\"text\" value=\"0\" style=\"width: 100%\" /></td></tr>";
$content = $content . "<tr><td class=\"forum_topic_sig\">{$_PWNDATA['admin']['forms']['forum_board_write']}</td><td class=\"forum_topic_sig\"><input name=\"permb\" type=\"text\" value=\"1\" style=\"width: 100%\" /></td></tr>";
$content = $content . "<tr><td class=\"forum_topic_sig\">{$_PWNDATA['admin']['forms']['forum_board_post']}</td><td class=\"forum_topic_sig\"><input name=\"permc\" type=\"text\" value=\"1\" style=\"width: 100%\" /></td></tr>";
$content = $content . "<tr><td class=\"forum_topic_sig\">{$_PWNDATA['admin']['forms']['forum_board_url']}</td><td class=\"forum_topic_sig\"><input name=\"link\" type=\"text\" value=\"NONE\" style=\"width: 100%\" /></td></tr>";
$content = $content . "<tr><td colspan=\"2\" class=\"forum_topic_sig\"><input type=\"submit\" value=\"{$_PWNDATA['admin']['forms']['forum_add_board']}\" /></td></tr></table>";
$content = $content . "<input name=\"cat\" type=\"hidden\" value=\"$newcat\" />";
$content = $content . "<input name=\"order\" type=\"hidden\" value=\"$neword\" /></form>";
drawBlock($_PWNDATA['admin']['forms']['forum_add_board'],"",$content);
}

if ($_GET['do'] == "edit_brd") {
$content = "";
$result = mysql_query("SELECT * FROM `boards` WHERE `id`=" . $_GET['id']);
$board = mysql_fetch_array($result);
$brd_name = $board['title'];
$brd_desc = $board['desc'];
$brd_perma = $board['vis_level'];
$brd_permb = $board['top_level'];
$brd_permc = $board['post_level'];
$brd_id = $board['id'];
$brd_lnk = $board['link'];
$content = $content . <<<END
<form action="admin.php" method="post">
<input type="hidden" name="action" value="edit_board" />
<input type="hidden" name="id" value="$brd_id" />
<table class="forum_base" width="100%">
END;
$content = $content . "<tr><td class=\"forum_topic_content\" width=\"300\">{$_PWNDATA['admin']['forms']['forum_board_name']}</td><td class=\"forum_topic_content\"><input name=\"title\" type=\"text\" value=\"$brd_name\" style=\"width: 100%\"/></td></tr>";
$content = $content . "<tr><td class=\"forum_topic_sig\" colspan=\"2\"><textarea rows=\"3\" name=\"content\" style=\"width:100%;\" cols=\"80\">$brd_desc</textarea></td></tr>\n";
$content = $content . "<tr><td class=\"forum_topic_sig\">{$_PWNDATA['admin']['forms']['forum_board_read']}</td><td class=\"forum_topic_sig\"><input name=\"perma\" type=\"text\" value=\"$brd_perma\" style=\"width: 100%\" /></td></tr>";
$content = $content . "<tr><td class=\"forum_topic_sig\">{$_PWNDATA['admin']['forms']['forum_board_write']}</td><td class=\"forum_topic_sig\"><input name=\"permb\" type=\"text\" value=\"$brd_permb\" style=\"width: 100%\" /></td></tr>";
$content = $content . "<tr><td class=\"forum_topic_sig\">{$_PWNDATA['admin']['forms']['forum_board_post']}</td><td class=\"forum_topic_sig\"><input name=\"permc\" type=\"text\" value=\"$brd_permc\" style=\"width: 100%\" /></td></tr>";
$content = $content . "<tr><td class=\"forum_topic_sig\">{$_PWNDATA['admin']['forms']['forum_board_url']}</td><td class=\"forum_topic_sig\"><input name=\"link\" type=\"text\" value=\"$brd_lnk\" style=\"width: 100%\" ></td></tr>";
$content = $content . "<tr><td class=\"forum_topic_sig\" colspan=\"2\"><input type=\"submit\" value=\"{$_PWNDATA['admin']['forms']['forum_board_save']}\" /></td></tr></table></form>";
drawBlock($_PWNDATA['admin']['forms']['forum_board_edit'],"",$content);
}

if ($_GET['view'] == "blocks") {
$content = "";
$content = $content . <<<END
<form action="admin.php" method="post">
<input type="hidden" name="action" value="add_block" />
END;
$content = $content . "<input name=\"title\" type=\"text\" value=\"{$_PWNDATA['admin']['forms']['block_name']}\" /><br />";
$content = $content . "<textarea rows=\"7\" name=\"content\" style=\"width:95%;\" cols=\"80\">{$_PWNDATA['admin']['forms']['block_content']}</textarea><br />\n";
$content = $content . "<input type=\"submit\" value=\"{$_PWNDATA['admin']['forms']['block_add']}\" /></form>";
drawBlock($_PWNDATA['admin']['forms']['block_add'],"",$content);
$content = " ";

$myDirectory = opendir("blocks"); // Open 'blocks'
while($entryName = readdir($myDirectory)) {
	$dirArray[] = $entryName; // Get our list of files
}
closedir($myDirectory); // Close the directory
sort($dirArray); // Sort the array (names should be changed for order, adding 01, etc)
$indexCount	= count($dirArray); // Count...
$odd = 1;
$content = $content . "<table class=\"forum_base\" width=\"100%\">";
for($index=0; $index < $indexCount; $index++) {
    if (substr("$dirArray[$index]", 0, 1) != "."){ // don't list hidden files
		if (substr("$dirArray[$index]", strlen($dirArray[$index]) - 4, 4) == ".php") {
            $odd = 1 - $odd;
            if ($odd == 1) {
                $back = "class=\"forum_odd_row\"";
            } else {
                $back = "class=\"forum_topic_sig\" ";
            }
			$block_title = "";
			require "blocks/" . $dirArray[$index];
			$content = $content . "<tr><td $back>" . $block_title . " - " . $dirArray[$index] . "</td></tr>\n";
		}
	}
}
$content = $content . "</table>";
drawBlock($_PWNDATA['admin']['forms']['blocks_ext'], "", $content);

$content = "<table class=\"borderless_table\" width=\"100%\">";
$result = mysql_query("SELECT * FROM blocks ORDER BY `id`", $db);
while ($row = mysql_fetch_array($result)) {
$block_id = $row['id'];
$bl_content = str_replace("<","&lt;",$row['content']);
$bl_content = str_replace(">","&gt;",$bl_content);
// Print the title
$content = $content . "<tr><td width=\"100%\"><form action=\"admin.php\" method=\"post\"><input type=\"hidden\" name=\"action\" value=\"edit_block\" />" . makeBlockSA("<input type=\"hidden\" name=\"blockid\" value=\"" . $row['id'] . "\" /><input type=\"text\" name=\"title\" value=\"" . $row['title'] . "\" />","<a href=\"admin.php?do=del_block&amp;id=$block_id\">{$_PWNDATA['admin']['forms']['delete']}</a>, <a href=\"admin.php?do=mov_block&amp;g=up&amp;id=$block_id\">{$_PWNDATA['admin']['forms']['block_move_up']}</a>, <a href=\"admin.php?do=mov_block&amp;g=down&amp;id=$block_id\">{$_PWNDATA['admin']['forms']['block_move_down']}</a>", "<textarea rows=\"9\" name=\"content\" style=\"width:100%;\" cols=\"80\">" . $bl_content . "</textarea><br />\n<input type=\"submit\" value=\"{$_PWNDATA['admin']['forms']['block_save']}\" />") . "</form></td></tr>";
}
$content = $content . "</table>";
drawBlock($_PWNDATA['admin']['forms']['block_edit'],"",$content);
}

if ($_GET['view'] == "site_info") {
if ($user['level'] < $site_info['admin_rank']) { messageBack($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['edit_site_info']); }
$content = "";
$content = $content . <<<END
<form action="admin.php" method="post">
<input type="hidden" name="action" value="site_info" />
<table class="forum_base" width="100%">
END;
$content = $content . "<tr><td class=\"forum_topic_content\" width=\"300\">{$_PWNDATA['admin']['forms']['si_name']}</td><td class=\"forum_topic_content\"><input name=\"name\" type=\"text\" value=\"" . $site_info['name'] . "\" style=\"width:100%;\" /></td></tr>\n";
$content = $content . "<tr><td class=\"forum_topic_sig\">{$_PWNDATA['admin']['forms']['si_url']}</td><td class=\"forum_topic_sig\"><input name=\"url\" type=\"text\" value=\"" . $site_info['url'] . "\" style=\"width:100%;\" /></td></tr>\n";
$content = $content . "<tr><td class=\"forum_topic_sig\">{$_PWNDATA['admin']['forms']['si_copy']}</td><td class=\"forum_topic_sig\"><input name=\"copyright\" type=\"text\" value=\"" . $site_info['copyright'] . "\" style=\"width:100%;\" /></td></tr>\n";
$rd = str_replace("<","&lt;",$site_info['right_data']);
$rd = str_replace(">","&gt;",$rd);
$content = $content . "<tr><td class=\"forum_topic_sig\" colspan=\"2\">{$_PWNDATA['admin']['forms']['si_rightbar']}</td></tr><tr><td class=\"forum_topic_sig\" colspan=\"2\"><textarea rows=\"1\" name=\"right_data\" style=\"width:100%;\"  cols=\"80\" >" . $rd . "</textarea></td></tr>\n";
$content = $content . "<tr><td class=\"forum_topic_sig\">{$_PWNDATA['admin']['forms']['si_header']}</td><td class=\"forum_topic_sig\"><input name=\"pheader\" type=\"text\" value=\"" . $site_info['pheader'] . "\" style=\"width:100%;\" /></td></tr>\n";
$content = $content . "<tr><td class=\"forum_topic_sig\" colspan=\"2\"><input type=\"submit\" value=\"{$_PWNDATA['admin']['forms']['si_save']}\" /></td></tr></table></form>";
drawBlock($_PWNDATA['admin']['forms']['si'],"",$content);
}



if ($_GET['view'] == "members") {
$content = "";
$members_result = mysql_query("SELECT * FROM `users` ORDER BY `name`");
$odd = 1;
$content = $content . "<table class=\"forum_base\" width=\"100%\">";
while ($member = mysql_fetch_array($members_result))
{
$odd = 1 - $odd;
if ($odd == 1) {
$back = "class=\"forum_odd_row\"";
} else {
$back = "class=\"forum_topic_sig\"";
}
$content = $content . "<tr><td $back><a href=\"forum.php?do=viewprofile&amp;id=" . $member['id'] . "\">" . $member['name'] . "</a></td>\n";
$content = $content . "<td $back><a href=\"forum.php?do=newpm&amp;to=" . $member['id'] . "\">{$_PWNDATA['admin']['forms']['members_pm']}</a></td>\n";
$content = $content . "<td $back><a href=\"admin.php?do=edit_prof&amp;id=" . $member['id'] . "\">{$_PWNDATA['admin']['forms']['members_edit']}</a></td>\n";
$content = $content . "<td $back><a href=\"admin.php?do=del_user&amp;id=" . $member['id'] . "\">{$_PWNDATA['admin']['forms']['members_delete']}</a></td>\n";
$content = $content . "</tr>";
}
$content = $content . "</table>";
drawBlock($_PWNDATA['admin']['forms']['members'],"",$content);
}

if ($_GET['do'] == "edit_prof") // Edit a profile
{
$members_result = mysql_query("SELECT * FROM `users` WHERE `id`=" . $_GET['id']);
$vuser = mysql_fetch_array($members_result);
if ($vuser['level'] > $user['level']) {
  $content = $_PWNDATA['admin']['forms']['sorry_rank'];
} else {
$post_content = "";
$uid = $vuser['id'];
$umail = $vuser['email'];
$uname = $vuser['name'];
$sig = $vuser['sig'];
$ava = $vuser['avatar'];
$uyah = $vuser['yahoo'];
$umsn = $vuser['msn'];
$uicq = $vuser['icq'];
$uaim = $vuser['aim'];
$uxfire = $vuser['xfire'];
$ulive = $vuser['live'];
$post_content = $post_content . <<<END
<form method="post" action="forum.php" name="form">
<input type="hidden" name="action" value="edit_profile" />
<input type="hidden" name="adm" value="true" />
<input type="hidden" name="id" value="$uid" />
  <table class="forum_base" width="100%">
  <tr><td class="forum_thread_title" colspan="2"><b>{$_PWNDATA['profile']['registration']}</b></td></tr>
  <tr><td class="forum_topic_sig" width="300">{$_PWNDATA['profile']['username']}</td><td class="forum_topic_sig"><input type="text" name="name" value="$uname" style="width: 100%;" /></td></tr>
  <tr><td class="forum_topic_sig">{$_PWNDATA['profile']['email']}</td><td class="forum_topic_sig"><input type="text" name="email" value="$umail" style="width: 100%" /></td></tr>
  <tr><td class="forum_topic_sig">{$_PWNDATA['profile']['password']}</td><td class="forum_topic_sig"><input type="password" name="apass" value="" style="width: 100%" /></td></tr>
  <tr><td class="forum_topic_sig">{$_PWNDATA['profile']['confirm']}</td><td class="forum_topic_sig"><input type="password" name="cpass" value="" style="width: 100%" /></td></tr>
  <tr><td class="forum_thread_title" colspan="2"><b>{$_PWNDATA['profile']['messaging']}</b></td></tr>
  <tr><td class="forum_topic_sig">MSN</td><td class="forum_topic_sig"><input type="text" name="msn" value="$umsn" style="width: 100%" /></td></tr>
  <tr><td class="forum_topic_sig">AIM</td><td class="forum_topic_sig"><input type="text" name="aim" value="$uaim" style="width: 100%" /></td></tr>
  <tr><td class="forum_topic_sig">Yahoo</td><td class="forum_topic_sig"><input type="text" name="yah" value="$uyah" style="width: 100%" /></td></tr>
  <tr><td class="forum_topic_sig">ICQ</td><td class="forum_topic_sig"><input type="text" name="icq" value="$uicq" style="width: 100%" /></td></tr>
  <tr><td class="forum_topic_sig">xFire</td><td class="forum_topic_sig"><input type="text" name="xfire" value="$uxfire" style="width: 100%" /></td></tr>
  <tr><td class="forum_topic_sig">Gamertag</td><td class="forum_topic_sig"><input type="text" name="live" value="$ulive" style="width: 100%" /></td></tr>
  <tr><td class="forum_topic_sig">Pandemic</td><td class="forum_topic_sig"><input type="text" name="pand" value="$pand" style="width: 100%" /></td></tr>
  <tr><td class="forum_thread_title" colspan="2"><b>{$_PWNDATA['profile']['posting']}</b></td></tr>
  <tr><td class="forum_topic_sig" colspan="2">{$_PWNDATA['profile']['sig']}</td></tr>
  <tr><td class="forum_topic_sig" colspan="2"><textarea rows="5" name="sig" style="width:100%" cols="80">$sig</textarea></td></tr>
  <tr><td class="forum_topic_sig">{$_PWNDATA['profile']['avatar']}</td>
  <td class="forum_topic_sig"><input type="text" name="avatar" value="$ava" style="width: 100%" /></td></tr>
  
  <tr><td class="forum_topic_sig" colspan="2"><input type="submit" value="{$_PWNDATA['profile']['save']}" name="sub" /></td></tr>
  </table>
  </form>
END;
}
drawBlock($_PWNDATA['admin']['forms']['members_edit'],"",$post_content);
}

// Security
if ($_GET['view'] == "bans") {
if ($user['level'] < $site_info['admin_rank']) { messageBack($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['banunban']); }
// FIXME: Make this cleaner
if ($site_info['security_mode'] == 0) {
    $sel_a = "selected=\"selected\"";
    $sel_b = "";
    $sel_c = "";
} else if ($site_info['security_mode'] == 1) {
    $sel_a = "";
    $sel_b = "selected=\"selected\"";
    $sel_c = "";
} else if ($site_info['security_mode'] == 2) {
    $sel_a = "";
    $sel_b = "";
    $sel_c = "selected=\"selected\"";
}
$content = <<<END
<form action="admin.php" method="post">
<input type="hidden" name="action" value="captcha" />
<table class="forum_base" width="100%">
<tr><td class="forum_topic_content" width="300">{$_PWNDATA['admin']['forms']['security_mode']}</td>
<td class="forum_topic_content">
<select name="sec_mode">
    <option value="0" $sel_a>{$_PWNDATA['admin']['forms']['sec_mod_a']}</option>
    <option value="1" $sel_b>{$_PWNDATA['admin']['forms']['sec_mod_b']}</option>
    <option value="2" $sel_c>{$_PWNDATA['admin']['forms']['sec_mod_c']}</option>
</select></td></tr>
<tr><td class="forum_topic_sig">{$_PWNDATA['admin']['forms']['recap_pub']}</td><td class="forum_topic_sig"><input type="text" name="recap_pub" value="{$site_info['recap_pub']}" style="width: 100%;" /></td></tr>
<tr><td class="forum_topic_sig">{$_PWNDATA['admin']['forms']['recap_priv']}</td><td class="forum_topic_sig"><input type="text" name="recap_priv" value="{$site_info['recap_priv']}" style="width: 100%;" /></td></tr>
<tr><td class="forum_topic_sig" colspan="2"><input type="submit" value="{$_PWNDATA['admin']['forms']['sec_save']}" name="save" /></td></tr>
</table>
</form>
END;
drawBlock($_PWNDATA['admin']['forms']['captcha'],"",$content);

$content = <<<END
<form action="admin.php" method="post">
<input type="hidden" name="action" value="add_ban" />
<table class="forum_base" width="100%">
<tr><td class="forum_topic_content">{$_PWNDATA['admin']['forms']['banipip']}</td><td class="forum_topic_content"><input type="text" name="ip" value="XX.XX.XX.XX" style="width: 100%;" /></td></tr>
<tr><td class="forum_topic_sig" colspan="2"><input type="submit" value="{$_PWNDATA['admin']['forms']['banip']}" name="ban" /></td></tr>
</table>
</form>
END;
drawBlock($_PWNDATA['admin']['forms']['banip'],"",$content);

$content = "<table class=\"forum_base\" width=\"100%\">";
if ($_GET['all'] != 1) {
$content = $content . "<tr><td class=\"forum_topic_content\" colspan=\"2\">({$_PWNDATA['admin']['forms']['ban_limit']}, <a href=\"admin.php?view=bans&amp;all=1\">{$_PWNDATA['admin']['forms']['ban_click']}</a> {$_PWNDATA['admin']['forms']['ban_showall']})</td></tr>";
$members_result = mysql_query("SELECT * FROM `banlist` LIMIT 20");
} else {
$members_result = mysql_query("SELECT * FROM `banlist`");
}
$odd = 1;
while ($ban = mysql_fetch_array($members_result))
{
$odd = 1 - $odd;
if ($odd == 1) {
$back = "class=\"forum_odd_row\"";
} else {
$back = "class=\"forum_topic_sig\"";
}
$content = $content . "<tr><td $back>" . $ban['ip'] . "</td>\n";
$content = $content . "<td $back><a href=\"admin.php?do=del_ban&amp;ban=" . $ban['ip'] . "\">{$_PWNDATA['admin']['forms']['ban_lift']}</a></td>\n";
$content = $content . "</tr>\n";
}
$content = $content . "</table>";
drawBlock($_PWNDATA['admin']['forms']['bans'],"",$content);

$content = "<div style=\"display: inline;\" id=\"cut_log\"><table class=\"forum_base\" width=\"100%\">";
$odd = 1;
$result = mysql_query("SELECT * FROM security LIMIT 10", $db);
while ($row = mysql_fetch_array($result)) {
$odd = 1 - $odd;
if ($odd == 1) {
$back = "class=\"forum_odd_row\"";
} else {
$back = "class=\"forum_topic_sig\"";
}
$content = $content . "<tr><td $back>" .  $row['where'] . " " . date("F j, Y (g:ia T)", $row['time']) . "</td><td $back>Password used: " . $row['passused'] . "</td><td $back>IP: " . $row['ip'] . "</td></tr>\n";
}


$content = $content . "</table></div>\n<div style=\"display: none;\" id=\"extra_log\"><table class=\"forum_base\" width=\"100%\">";
$odd = 1;
$result = mysql_query("SELECT * FROM security", $db);
while ($row = mysql_fetch_array($result)) {
$odd = 1 - $odd;
if ($odd == 1) {
$back = "class=\"forum_odd_row\"";
} else {
$back = "class=\"forum_topic_sig\"";
}
$content = $content . "<tr><td $back>" .  $row['where'] . " " . date("F j, Y (g:ia T)", $row['time']) . "</td><td $back>Password used: " . $row['passused'] . "</td><td $back>IP: " . $row['ip'] . "</td></tr>\n";
}
$content = $content . <<<END
</table></div>
<script type="text/javascript">
//<![CDATA[
function showlog() {
document.getElementById('cut_log').style.display = "none"
document.getElementById('extra_log').style.display = "inline"
}
//]]>
</script>
<a href="javascript:showlog()">{$_PWNDATA['admin']['forms']['si_log_show']}</a>
<form action="admin.php" method="post">
<input type="hidden" name="action" value="clear_security" />
<input type="hidden" name="pw" value="
END;
//"
$content = $content . $_GET['pw'];
$content = $content . "\" /><input type=\"submit\" value=\"{$_PWNDATA['admin']['forms']['si_log_clear']}\" /></form>";
drawBlock($_PWNDATA['admin']['forms']['si_log'],"",$content);
}

if ($_GET['view'] == "promo") {
if ($user['level'] < $site_info['admin_rank']) { messageBack($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['only_moderators_promote']); }
$content = "<table class=\"forum_base\" width=\"100%\"><tr><td class=\"forum_thread_title\" colspan=\"4\"><b>{$_PWNDATA['admin']['forms']['ranks_custom']}:</b></td></tr>";
// List ranks
$results = mysql_query("SELECT * FROM `ranks`");
while ($rank = mysql_fetch_array($results)) {
$content = $content . "<tr><td class=\"forum_topic_sig\">" . $rank['name'] . "</td><td class=\"forum_topic_sig\">{$_PWNDATA['admin']['forms']['ranks_level']}: " . $rank['value'] . "</td><td class=\"forum_topic_sig\">{$_PWNDATA['admin']['forms']['ranks_posts']}: " . $rank['posts'] . "</td><td class=\"forum_topic_sig\">[<a href=\"admin.php?do=delrank&amp;rank=" . $rank['id'] . "\">{$_PWNDATA['admin']['forms']['delete']}</a>]</td></tr>";
}
$content = $content . "</table>";
$modrank = $site_info['mod_rank'];
$admrank = $site_info['admin_rank'];
$content = $content . <<<END
<form action="admin.php" method="post">
<input type="hidden" name="action" value="addrank" />
<table class="forum_base" width="100%">
<tr><td class="forum_thread_title" colspan="2"><b>{$_PWNDATA['admin']['forms']['ranks_add']}:</b></td></tr>
<tr><td class="forum_topic_sig" width="300">{$_PWNDATA['admin']['forms']['ranks_level']}</td><td class="forum_topic_sig"><input type="text" name="level" value="-1" /> {$_PWNDATA['admin']['forms']['ranks_ignore']}</td></tr>
<tr><td class="forum_topic_sig">{$_PWNDATA['admin']['forms']['ranks_posts']}</td><td class="forum_topic_sig"><input type="text" name="posts" value="-1" /> {$_PWNDATA['admin']['forms']['ranks_ignore']}</td></tr>
<tr><td class="forum_topic_sig">{$_PWNDATA['admin']['forms']['ranks_name']}</td><td class="forum_topic_sig"><input type="text" name="name" value="" /></td></tr>
<tr><td class="forum_topic_sig" colspan="2"><input type="submit" value="Add Rank" /></td></tr>
</table>
</form>
<form action="admin.php" method="post" name="form">
<input type="hidden" name="action" value="setranks" />
<input type="hidden" name="mod_old" value="$modrank" />
<input type="hidden" name="adm_old" value="$admrank" />
<table class="forum_base" width="100%">
<tr><td class="forum_thread_title" colspan="2"><b>{$_PWNDATA['admin']['forms']['ranks_set']}</b> <i>{$_PWNDATA['admin']['forms']['ranks_set_warn']}</i></td></tr>
<tr><td class="forum_topic_sig" width="300">{$_PWNDATA['admin']['forms']['ranks_mod']}</td><td class="forum_topic_sig"><input type="text" name="mod" value="$modrank" /></td></tr>
<tr><td class="forum_topic_sig">{$_PWNDATA['admin']['forms']['ranks_adm']}</td><td class="forum_topic_sig"><input type="text" name="adm" value="$admrank" /></td></tr>
<tr><td class="forum_topic_sig" colspan="2"><input type="submit" value="{$_PWNDATA['admin']['forms']['ranks_save']}" /></td></tr>
</table>
</form>
END;
drawBlock($_PWNDATA['admin']['forms']['ranks'],"",$content);
$content = "";
$content = $content . "<table class=\"forum_base\" width=\"100%\">";
$members_result = mysql_query("SELECT * FROM `users` WHERE `level`<" . $site_info['mod_rank'] . " ORDER BY `level`, `name`");
$content = $content . "<tr><td class=\"forum_thread_title\" colspan=\"2\"> --- {$_PWNDATA['admin']['forms']['ranks_users']} --- </td></tr>";
$odd = 1;
while ($member = mysql_fetch_array($members_result))
{
$odd = 1 - $odd;
if ($odd == 1) {
$back = "class=\"forum_odd_row\"";
} else {
$back = "class=\"forum_topic_sig\"";
}
$add = "";
if ($member['level'] < 1) {
$add = " {$_PWNDATA['admin']['forms']['ranks_banned']}";
}
$content = $content . "<tr><td $back><a href=\"forum.php?do=viewprofile&amp;id=" . $member['id'] . "\">" . $member['name'] . "</a> - " . $member['level'] . $add . "</td>";
$content = $content . "<td $back><a href=\"admin.php?do=promote&amp;id=" . $member['id'] . "\">{$_PWNDATA['admin']['forms']['ranks_promote']}</a> | <a href=\"admin.php?do=demote&amp;id=" . $member['id'] . "\">{$_PWNDATA['admin']['forms']['ranks_demote']}</a></td>";
$content = $content . "</tr>";
}
$odd = 1;
$members_result = mysql_query("SELECT * FROM `users` WHERE `level`<" . $site_info['admin_rank'] . " AND `level`>=" . $site_info['mod_rank'] . " ORDER BY `level`, `name`");
$content = $content . "<tr><td class=\"forum_thread_title\" colspan=\"2\"> --- <font class='mod_name'>{$_PWNDATA['admin']['forms']['ranks_mod_a']}</font> --- </td></tr>";
while ($member = mysql_fetch_array($members_result))
{
$odd = 1 - $odd;
if ($odd == 1) {
$back = "class=\"forum_odd_row\"";
} else {
$back = "class=\"forum_topic_sig\"";
}
$content = $content . "<tr><td $back><a href=\"forum.php?do=viewprofile&amp;id=" . $member['id'] . "\">" . $member['name'] . "</a> - " . $member['level'] . "</td>";
$content = $content . "<td $back><a href=\"admin.php?do=promote&amp;id=" . $member['id'] . "\">{$_PWNDATA['admin']['forms']['ranks_promote']}</a> | <a href=\"admin.php?do=demote&amp;id=" . $member['id'] . "\">{$_PWNDATA['admin']['forms']['ranks_demote']}</a></td>";
$content = $content . "</tr>";
}
$odd = 1;
$members_result = mysql_query("SELECT * FROM `users` WHERE `level`>=" . $site_info['admin_rank'] . " ORDER BY `level`, `name`");
$content = $content . "<tr><td class=\"forum_thread_title\" colspan=\"2\"> --- <font class='adm_name'>{$_PWNDATA['admin']['forms']['ranks_adm_a']}</font> --- </td></tr>";
while ($member = mysql_fetch_array($members_result))
{
$odd = 1 - $odd;
if ($odd == 1) {
$back = "class=\"forum_odd_row\"";
} else {
$back = "class=\"forum_topic_sig\"";
}
$content = $content . "<tr><td $back><a href=\"forum.php?do=viewprofile&amp;id=" . $member['id'] . "\">" . $member['name'] . "</a> - " . $member['level'] . "</td>";
$content = $content . "<td $back><a href=\"admin.php?do=promote&amp;id=" . $member['id'] . "\">{$_PWNDATA['admin']['forms']['ranks_promote']}</a> | <a href=\"admin.php?do=demote&amp;id=" . $member['id'] . "\">{$_PWNDATA['admin']['forms']['ranks_demote']}</a></td>";
$content = $content . "</tr>";
}
$content = $content . "</table>";
drawBlock($_PWNDATA['admin']['forms']['members'],"",$content);
}

// Retreive update information from O-G
// This is a short bit of text.
if (!isset($_GET['do']) || $_GET['do'] == "") {
    $pwnversion = $_PWNVERSION['major'] . "_" . $_PWNVERSION['minor'] . $_PWNVERSION['extra'];
    $update_data = file_get_contents("http://oasis-games.com/pwn/updates_{$pwnversion}");
    // Make sure this is valid. If it has <body, it's not, because it's either
    // O-G's 404 page or someone else with a man-in-the-middle (Not necessarily intentional
    // it could be a proxy page for an internet access registration or something)
    if ($update_data && !stristr($update_data,"<body")) {
        $content = $update_data;
    } else {
        // In which case, we just give the dump message.
        $content = $_PWNDATA['admin']['update_failed'];
    }
    drawBlock($_PWNDATA['admin_page_title'],$_PWNDATA['admin']['og_updates'],$content);
}

$content = <<<END
<table class="borderless_table" width="100%">
  <tr>
    <td width="10%" height="1" align="center">
    <a href="admin.php?view=news">{$_PWNICONS['admin']['news']}</a><br />
    <a href="admin.php?view=news">{$_PWNDATA['admin']['groups']['news']}</a></td>
    <td width="10%" height="1" align="center">
    <a href="admin.php?view=forum">{$_PWNICONS['admin']['forums']}</a><br />
    <a href="admin.php?view=forum">{$_PWNDATA['admin']['groups']['forums']}</a></td>
    <td width="10%" height="1" align="center">
    <a href="admin.php?view=blocks">{$_PWNICONS['admin']['blocks']}</a><br />
    <a href="admin.php?view=blocks">{$_PWNDATA['admin']['groups']['blocks']}</a></td>
    <td width="10%" height="1" align="center">    <a href="admin.php?view=members">{$_PWNICONS['admin']['members']}</a><br />
    <a href="admin.php?view=members">{$_PWNDATA['admin']['groups']['members']}</a></td>
    <td width="10%" height="1" align="center">
    <a href="admin.php?view=pages">{$_PWNICONS['admin']['pages']}</a><br />
    <a href="admin.php?view=pages">{$_PWNDATA['admin']['groups']['pages']}</a></td>
END;
if ($user['level'] >= $site_info['admin_rank']) {
$content = $content . <<<END
    <td width="10%" height="1" align="center">
    <a href="admin.php?view=site_info">{$_PWNICONS['admin']['siteinfo']}</a><br />
    <a href="admin.php?view=site_info">{$_PWNDATA['admin']['groups']['site_info']}</a></td>
    <td width="10%" height="1" align="center">
    <a href="admin.php?view=promo">{$_PWNICONS['admin']['promos']}</a><br />
    <a href="admin.php?view=promo">{$_PWNDATA['admin']['groups']['promo']}</a></td>
    <td width="10%" height="1" align="center">
    <a href="admin.php?view=bans">{$_PWNICONS['admin']['security']}</a><br />
    <a href="admin.php?view=bans">{$_PWNDATA['admin']['groups']['bans']}</a></td>
END;
}
$content = $content . <<<END
    <td width="10%" height="1" align="center">
    <a href="forum.php?do=logoff">{$_PWNICONS['admin']['logout']}</a><br />
    <a href="forum.php?do=logoff">{$_PWNDATA['admin']['groups']['logout']}</a></td>
</tr></table>
END;
drawBlock($_PWNDATA['admin_page_title'],"",$content);
print <<<END
	</table>
END;
require 'footer.php';
?>
