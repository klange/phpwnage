<?php
/*
	This file is part of PHPwnage (Custom Page Module)

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

if ($_POST[action]){
$pagename = $_GET['page'];
mysql_query("UPDATE `{$_PREFIX}pages` SET `content` = '" . $_POST['content'] . "' WHERE `{$_PREFIX}pages`.`name`='" . $pagename . "'", $db);
messageRedirect($_PWNDATA['admin']['forms']['pages'],$_PWNDATA['articles']['edit_page'],"");
}
$result = mysql_query("SELECT * FROM `{$_PREFIX}pages` WHERE name='" . $_GET['page'] . "'", $db);
$page = mysql_fetch_array($result);

standardHeaders($site_info['name'] . " :: " . $page['display_name'],true);
drawSubbar("<a href=\"index.php\">" . $site_info['name'] . "</a> > <a href=\"custompages.php\">{$_PWNDATA['admin']['forms']['pages']}</a> > " . $page['display_name'],$site_info['right_data']);

if ($page['showsidebar'] == "true") {
    require 'sidebar.php';
} else {
    print "<table class=\"borderless_table\" width=\"100%\"><tr>";
}

print <<<END
<td height="269" valign="top">
<table class="borderless_table" width="100%">
END;
drawBlock($page['display_name'],$page['author'],$page['content']);
if ($user['level'] >= $site_info['mod_rank']) {
$content_temp = str_replace(">","&gt;",str_replace("<","&lt;",$page['content']));
$content = <<<END
<form action="pages.php?page={$page['name']}" method="post">
<input type="hidden" name="action" value="true" />
<textarea rows="8" name="content" style="width:100%;" cols="80">$content_temp</textarea>
<br /><input type="submit" value="{$_PWNDATA['articles']['save_page']}" /></form>
END;
drawBlock("{$_PWNDATA['articles']['edita']} " . $page['title'], $page['author'], $content);
}


print <<<END
	</table>
        </td>
  </tr>
</table>
END;
require 'footer.php';
?>
