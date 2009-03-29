<?php
/*
	This file is part of PHPwnage (Custom Page Module)

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

if ($_POST['action']){
    if (!isset($user['id']) || $user['level'] < $site_info['mod_rank']) {
        messageBack($_PWNDATA['post_attack'], $_PWNDATA['not_permitted']);
    }
    $pagename = $_GET['page'];
    override_sql_query("UPDATE `{$_PREFIX}pages` SET `content` = '" . $_POST['content'] . "' WHERE `{$_PREFIX}pages`.`name`='" . $pagename . "'", $db);
    messageRedirect($_PWNDATA['admin']['forms']['pages'],$_PWNDATA['articles']['edit_page'],"");
}
$page_name = mse($_GET['page']);
$result = override_sql_query("SELECT * FROM `{$_PREFIX}pages` WHERE name='{$page_name}'", $db);
$page = mysql_fetch_array($result);
if (!isset($page['display_name'])) {
    messageBack($_PWNDATA['admin']['forms']['pages'],$_PWNDATA['pages_does_not_exist'],false);
}
$smarty->assign('page',$page);
$smarty->display('pages.tpl');
