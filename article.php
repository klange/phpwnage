<?php
/*
	This file is part of PHPwnage (Single Article View)

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

$result = mysql_query("SELECT * FROM `{$_PREFIX}news` WHERE id='" . $_GET['id'] . "'", $db);
$row = mysql_fetch_array($result);

if ($_POST[action]){
    $id = $_GET['id'];
    if (!isset($user['id']) || $user['level'] < $site_info['mod_rank']) {
        messageBack($_PWNDATA['post_attack'], $_PWNDATA['not_permitted']);
    }
    mysql_query("UPDATE `{$_PREFIX}news` SET `content` = '" . mse($_POST['content']) . "' WHERE `{$_PREFIX}news`.`id`='" . $id . "'", $db);
    mysql_query("UPDATE `{$_PREFIX}news` SET `title` = '" . mse($_POST['title']) . "' WHERE `{$_PREFIX}news`.`id`='" . $id . "'", $db);
    messageRedirect($_PWNDATA['article'],$_PWNDATA['articles']['edit'],"article.php?id=" . $_GET['id']);
}
if (!isset($row['id'])) {
    messageBack($_PWNDATA['articles']['title'],$_PWNDATA['articles']['not_found'],false);
}

$smarty->assign('article',$row);

if ($row['topicid'] != 0) {
    $smarty->assign('has_comments',true);
    $results = mysql_query("SELECT * FROM `{$_PREFIX}topics` WHERE `id`=" . $row['topicid']);
    $topic = mysql_fetch_array($results);
    $smarty->assign('topic',$topic);
    $smarty->assign('showposter',isWriteable($user['level'], $topic['board']));
    $posts = array();
    $users = array();
    $results = mysql_query("SELECT * FROM `{$_PREFIX}posts` WHERE topicid={$row['topicid']} ORDER BY `id` DESC LIMIT 10", $db);
    while ($post = mysql_fetch_array($results)) {
        $posts[] = $post;
        if (!array_key_exists($post['authorid'],$users)) {
            $results_b = mysql_query("SELECT * FROM `{$_PREFIX}users` WHERE id={$post['authorid']}", $db);
            $tmp = mysql_fetch_array($results_b);
            $users[$tmp['id']] = $tmp;
        }
    }
    $smarty->assign('posts',$posts);
    $smarty->assign('users',$users);
} else {
    $smarty->assign('has_comments',false);
}

$smarty->display('article.tpl');
