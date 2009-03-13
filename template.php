<?php
require_once('includes.php');
require_once('sidebar.php');
$news = array();

$news_arguments = "";
if ($_GET['show'] == 'all') { 
    $news_arguments = "DESC";
    $page = 1;
} else if (isset($_GET['page'])) {
    $page = intval($_GET['page']);
    $start = ($page - 1) * 10;
    $news_arguments = "DESC LIMIT {$start},10";
} else {
    $news_arguments = "DESC LIMIT 10";
    $page = 1;
}
$result = mysql_query("SELECT COUNT(*) FROM `{$_PREFIX}news`");
$tmp = mysql_fetch_array($result);
$total = $tmp['COUNT(*)'];
$result = mysql_query("SELECT `id`,`title`,`time_code`,`user`,`content` FROM `{$_PREFIX}news` ORDER BY id $news_arguments", $db);
while ($row = mysql_fetch_array($result)) {
    $news[] = $row;
}

$smarty->assign('news',$news);
$smarty->assign('title',$site_info['name']);
$smarty->assign('page_num',$page);
$smarty->assign('page_total',(int)(($total - 1) / 10 + 1));

$smarty->display('index.tpl');
