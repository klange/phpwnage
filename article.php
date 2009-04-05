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

$result = $_SQL->query("SELECT * FROM `{$_PREFIX}news` WHERE id='" . $_GET['id'] . "'");
$row = $result->fetch_array();

if (isset($_POST['action']) && $_POST['action'] == "edit"){
    $id = $_GET['id'];
    if (!isset($user['id']) || $user['level'] < $site_info['mod_rank']) {
        messageBack($_PWNDATA['post_attack'], $_PWNDATA['not_permitted']);
    }
    $_SQL->query("UPDATE `{$_PREFIX}news` SET `content` = '" . mse($_POST['content']) . "' WHERE `{$_PREFIX}news`.`id`='" . $id . "'");
    $_SQL->query("UPDATE `{$_PREFIX}news` SET `title` = '" . mse($_POST['title']) . "' WHERE `{$_PREFIX}news`.`id`='" . $id . "'");
    messageRedirect($_PWNDATA['article'],$_PWNDATA['articles']['edit'],"article.php?id=" . $_GET['id']);
}
if (!isset($row['id'])) {
    messageBack($_PWNDATA['articles']['title'],$_PWNDATA['articles']['not_found'],false);
}

$smarty->assign('article',$row);

if ($row['topicid'] != 0) {
    $smarty->assign('has_comments',true);
    $results = $_SQL->query("SELECT * FROM `{$_PREFIX}topics` WHERE `id`=" . $row['topicid']);
    $topic = $results->fetch_array();
    $smarty->assign('topic',$topic);
    $smarty->assign('showposter',isWriteable($user['level'], $topic['board']));
    $posts = array();
    $users = array();
    $results = $_SQL->query("SELECT * FROM `{$_PREFIX}posts` WHERE topicid={$row['topicid']} ORDER BY `id` DESC LIMIT 10");
    while ($post = $results->fetch_array()) {
        $posts[] = $post;
        if (!array_key_exists($post['authorid'],$users)) {
            $results_b = $_SQL->query("SELECT * FROM `{$_PREFIX}users` WHERE id={$post['authorid']}");
            $tmp = $results_b->fetch_array();
            $users[$tmp['id']] = $tmp;
        }
    }
    $smarty->assign('posts',$posts);
    $smarty->assign('users',$users);
} else {
    $smarty->assign('has_comments',false);
}

$smarty->display('article.tpl');
