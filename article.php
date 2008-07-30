<?php
/*
	This file is part of PHPwnage (Single Article View)

	Copyright 2008 Kevin Lange <klange@ogunderground.com>

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

require 'config.php';
require 'includes.php';

$result = mysql_query("SELECT * FROM news WHERE id='" . $_GET['id'] . "'", $db);
$row = mysql_fetch_array($result);

if ($_POST[action]){
$id = $_GET['id'];
mysql_query("UPDATE `news` SET `content` = '" . $_POST['content'] . "' WHERE `news`.`id`='" . $id . "'", $db);
mysql_query("UPDATE `news` SET `title` = '" . $_POST['title'] . "' WHERE `news`.`id`='" . $id . "'", $db);
messageRedirect($_PWNDATA['article'],$_PWNDATA['articles']['edit'],"article.php?id=" . $_GET['id']);
}


print <<<END

<html>

<head>
<title>
END;
print $site_info['name'] . " :: Article #" . $_GET['id'] . " - " . $row['title'];
print "</title>\n";

require 'css.php';

require 'header.php';

print <<<END
<table class="borderless_table" width="100%">
  <tr>
    <td class="sub_left"></td>
    <td class="sub_mid"><font class="sub_body_text">
END;
print "<a href=\"index.php\">" . $site_info['name'] . "</a> > " . $row['title'];
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

require 'sidebar.php';

$result = mysql_query("SELECT * FROM news WHERE id='" . $_GET['id'] . "'", $db);
$row = mysql_fetch_array($result);
print <<<END
<td height="269" valign="top">
<table class="borderless_table" width="100%">
END;


// News article
drawBlock($row['title'], date("F j, Y (g:ia T)", $row['time_code']) . ", {$_PWNDATA['posted_by']} " . $row['user'] . "; {$_PWNDATA['article']} #" . ($row['id']), $row['content']);

if ($row['topicid'] != 0){
$results = mysql_query("SELECT * FROM `topics` WHERE `id`=" . $row['topicid']);
$topic = mysql_fetch_array($results);
if (isWriteable($user['level'], $topic['board'])) {
$content = printPosterMini('content', $topic['id']) . <<<END
<form action="forum.php" method="post" name="form">
<input type="hidden" name="action" value="new_reply">
END;
$content = $content . "<input type=\"hidden\" name=\"topic\" value=\"" . $topic['id'] . "\">";
$content = $content . "<input type=\"hidden\" name=\"user\" value=\"" . $user['id'] . "\">";
$content = $content . <<<END
<textarea name="content" style="width: 95%;" rows="5"></textarea><br />
<input type="submit" name="sub" value="Post"></form>
END;
}
$resultz = mysql_query("SELECT * FROM posts WHERE topicid='" . $row['topicid'] . "' ORDER BY `id` DESC", $db);
$content = $content . "<table class=\"forum_base\" width=\"100%\">\n";
while ($rowz = mysql_fetch_array($resultz)) {
$resultb = mysql_query("SELECT * FROM users WHERE id='" .  $rowz['authorid'] . "'", $db);
$post_author = mysql_fetch_array($resultb);
$auth_name = $post_author['name'];
$dec_post = BBDecode($rowz['content']);
$content = $content . "<tr><td width=\"20%\" valign=\"top\"><font size=\"2\">$auth_name</font></td><td><font size=\"2\">$dec_post</font></td></tr>\n";
}
$content = $content . "</table>";
drawBlock($_PWNDATA['articles']['comments'], "", $content);
}
if ($user['level'] >= $site_info['mod_rank']) {
$content = "<form action=\"article.php?id=" . $row['id'];
$content = $content . "&pw=" . $_GET['pw'];
$content = $content . <<<END
" method="post"><input type="hidden" name="action" value="true"><textarea rows="8" name="content" style="width:100%; font=Tahoma">
END;
$content = $content . $row['content'];
$content = $content . "</textarea><br><input name=\"title\" type=\"text\" value=\"" . $row['title'] . "\"><input type=\"submit\" value=\"{$_PWNDATA['articles']['save']}\"></form>";
drawBlock("{$_PWNDATA['articles']['edita']} " . $row['title'], date("F j, Y (g:ia T)", $row['time_code']) . ", {$_PWNDATA['posted_by']} " . $row['user'], $content);
}
print <<<END
</table>
</td>
</tr>
</table>
END;

require 'footer.php';
?>
