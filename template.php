<?php
require_once('includes.php');
require_once('smarty/libs/Smarty.class.php');
$smarty = new Smarty();
$smarty->template_dir = 'themes/templates/classic/';
$smarty->compile_dir  = 'smarty/compile/';
$smarty->config_dir   = 'smarty/config/';
$smarty->cache_dir    = 'smarty/cache/';
$smarty->plugins_dir  = 'smarty/plugins/';
$sidebar = array();
$result = mysql_query("SELECT * FROM `{$_PREFIX}blocks` ORDER BY `id`", $db);
while ($row = mysql_fetch_array($result)) {
    $sidebar[] = $row;
}
$myDirectory = opendir("blocks"); // Open 'blocks'
while($entryName = readdir($myDirectory)) {
    $dirArray[] = $entryName; // Get our list of files
}
closedir($myDirectory); // Close the directory
sort($dirArray); // Sort the array (names should be changed for order, adding 01, etc)
$indexCount	= count($dirArray); // Count...
for($index=0; $index < $indexCount; $index++) {
    if (substr("$dirArray[$index]", 0, 1) != "."){ 
        if (substr("$dirArray[$index]", strlen($dirArray[$index]) - 4, 4) == ".php") {
            $block_title = "";
            $block_content = "";
            require "blocks/" . $dirArray[$index];
            $sidebar[] = array("title" => $block_title, "content" => $block_content);
        }
    }
}


$news = array();
$result = mysql_query("SELECT `id`,`title`,`time_code`,`user`,`content` FROM `{$_PREFIX}news`", $db);
while ($row = mysql_fetch_array($result)) {
    $news[] = $row;
}

/*
$res_topic = mysql_query("SELECT * FROM `topics` WHERE `id`={$_GET['id']}");
$topic = mysql_fetch_array($res_topic);
$posts = Array();
$users = Array();
$res_user = mysql_query("SELECT * FROM `users` WHERE `id`={$topic['authorid']}");
$users[$topic['authorid']] = mysql_fetch_array($res_user);
$res_posts = mysql_query("SELECT * FROM `posts` WHERE `topicid`={$_GET['id']}");

while ($post = mysql_fetch_array($res_posts)) {
    if (!array_key_exists($post['authorid'],$users)) {
        $res_user = mysql_query("SELECT * FROM `users` WHERE `id`={$post['authorid']}");
        $users[$post['authorid']] = mysql_fetch_array($res_user);
    }
    $posts[$post['id']] = $post;
}
*/
$smarty->assign('sidebar',$sidebar);
$smarty->assign('news',$news);
$smarty->assign('theme',$theme);
$smarty->assign('imageroot',$imageroot);
$smarty->assign('title',$site_info['name']);
$smarty->assign('site',$site_info);
$smarty->assign('user',$user);
$smarty->assign('_PWNICONS',$_PWNICONS);
$smarty->assign('_PWNDATA',$_PWNDATA);
//$smarty->assign('topic',$topic);
//$smarty->assign('posts',$posts);
//$smarty->assign('users',$users)

$smarty->display('index.tpl');
