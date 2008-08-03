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
mysql_query("UPDATE `pages` SET `content` = '" . $_POST['content'] . "' WHERE `pages`.`name`='" . $pagename . "'", $db);
messageRedirect($_PWNDATA['admin']['forms']['pages'],$_PWNDATA['articles']['edit_page'],"");
}
$result = mysql_query("SELECT * FROM pages WHERE name='" . $_GET['page'] . "'", $db);
$page = mysql_fetch_array($result);


print <<<END

<html>

<head>
<title>
END;
print $site_info['name'] . " :: " . $page['display_name'];
print "</title>\n";

require 'css.php';
require 'header.php';

print <<<END
<table class="borderless_table" width="100%">
  <tr>
    <td class="sub_left"></td>
    <td class="sub_mid"><font class="sub_body_text">
END;
print "<a href=\"index.php\">";
print $site_info['name'] . "</a> > <a href=\"custompages.php\">{$_PWNDATA['admin']['forms']['pages']}</a> > " . $page['display_name'];
print <<<END
    </font></td>
    <td class="sub_mid">

    <p align="right"><font class="sub_body_text">
END;
print $site_info['right_data'];
print <<<END
    </font></td>
    <td class="sub_right"></td>
  </tr>
</table>

END;

if ($page['showsidebar'] == "true")
{
require 'sidebar.php';
}
print <<<END
<td height="269" valign="top">
<table class="borderless_table" width="100%">
END;
drawBlock($page['display_name'],$page['author'],$page['content']);
if ($user['level'] >= $site_info['mod_rank']) {
$content = <<<END
<form action="pages.php?page={$page['name']}" method="post">
<input type="hidden" name="action" value="true">
<textarea rows="8" name="content" style="width:100%; font=Tahoma">{$page['content']}</textarea>
<br><input type="submit" value="{$_PWNDATA['articles']['save_page']}"></form>
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
