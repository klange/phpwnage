<?php
/*
	This file is part of PHPwnage (Main Index, News Listing)

	Copyright 2009 Kevin Lange <klange@oasis-games.com>

	PHPwnage is free software: you can redistribute it and/or modify
	it under the terms of the GNU Generald Public License as published by
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
$news = array();

$news_arguments = "";
if (isset($_GET['show']) && $_GET['show'] == 'all') { 
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
$result = $_SQL->query("SELECT COUNT(*) FROM `{$_PREFIX}news`");
$tmp = $result->fetch_array();
$total = $tmp['COUNT(*)'];
$result = $_SQL->query("SELECT `id`,`title`,`time_code`,`user`,`content` FROM `{$_PREFIX}news` ORDER BY id $news_arguments");
while ($row = $result->fetch_array()) {
    $news[] = $row;
}

$smarty->assign('news',$news);
$smarty->assign('title',$site_info['name']);
$smarty->assign('page_num',$page);
$smarty->assign('page_total',(int)(($total - 1) / 10 + 1));

$smarty->display('index.tpl');
