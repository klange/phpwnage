<?php
/*
	This file is part of PHPwnage (Single Article View)

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

standardHeaders($site_info['name'] . " :: {$_PWNDATA['article']} #" . $_GET['id'] . " - " . $row['title'],true);

drawSubbar("<a href=\"index.php\">" . $site_info['name'] . "</a> > " . $row['title'],$site_info['right_data']);

if (!isset($row['id'])) {
    messageBack($_PWNDATA['articles']['title'],$_PWNDATA['articles']['not_found'],false);
}

require 'sidebar.php';

$result = mysql_query("SELECT * FROM `{$_PREFIX}news` WHERE id='" . $_GET['id'] . "'", $db);
$row = mysql_fetch_array($result);
print <<<END
<td valign="top">
<table class="borderless_table" width="100%">
END;


// News article
drawBlock($row['title'], date("F j, Y (g:ia T)", $row['time_code']) . ", {$_PWNDATA['posted_by']} " . $row['user'] . "; {$_PWNDATA['article']} #" . ($row['id']), BBDecode($row['content'],true));

if ($row['topicid'] != 0){
    $results = mysql_query("SELECT * FROM `{$_PREFIX}topics` WHERE `id`=" . $row['topicid']);
    $topic = mysql_fetch_array($results);
    if (isWriteable($user['level'], $topic['board'])) {
        $content = printPosterMini('content', $topic['id']) . <<<END
<form action="forum.php" method="post" name="form">
<input type="hidden" name="action" value="new_reply" />
END;
        $content = $content . "<input type=\"hidden\" name=\"topic\" value=\"" . $topic['id'] . "\" />";
        $content = $content . "<input type=\"hidden\" name=\"user\" value=\"" . $user['id'] . "\" />";
        $content = $content . <<<END
<table class="forum_base" width="100%">
<tr><td class="forum_topic_content">
<textarea name="content" style="width: 95%;" rows="5" cols="80" class="content_editor"></textarea></td></tr>
<tr><td class="forum_topic_sig"><input type="submit" name="sub" value="{$_PWNDATA['forum']['submit_post']}" /></td></tr>
</table>
</form>
END;
    }
    $resultz = mysql_query("SELECT * FROM `{$_PREFIX}posts` WHERE topicid='" . $row['topicid'] . "' ORDER BY `id` DESC LIMIT 10", $db);
    $content = $content . "<table class=\"forum_base\" width=\"100%\">\n";
    while ($rowz = mysql_fetch_array($resultz)) {
        $resultb = mysql_query("SELECT * FROM `{$_PREFIX}users` WHERE id='" .  $rowz['authorid'] . "'", $db);
        $post_author = mysql_fetch_array($resultb);
        if (!$post_author) {
            $auth_name = "Guest";
        } else {
            $auth_name = $post_author['name'];
        }
        $dec_post = BBDecode($rowz['content']);
        $content = $content . "<tr><td width=\"20%\" class=\"glow\" valign=\"top\">$auth_name</td><td class=\"forum_topic_content\">$dec_post</td></tr>\n";
    }
    $content = $content . "<tr><td colspan=\"2\" class=\"forum_topic_content\" align=\"center\"><a href=\"forum.php?do=viewtopic&amp;id=" . $row['topicid'] . "\">{$_PWNDATA['articles']['more_comments']}</a></td></tr>";
    $content = $content . "</table>";
    drawBlock($_PWNDATA['articles']['comments'], "", $content);
}
if ($user['level'] >= $site_info['mod_rank']) {
    $title = str_replace("\"","&quot;",$row['title']);
    $content = str_replace(">","&gt;",str_replace("<","&lt;",$row['content']));
    $content = <<<END
<form action="article.php?id={$row['id']}" method="post">
<input type="hidden" name="action" value="true" />
<table class="forum_base" width="100%">
<tr><td class="forum_topic_sig"><textarea rows="8" name="content" style="width:100%;" cols="80">$content</textarea></td></tr><tr><td class="forum_topic_sig"><input name="title" type="text" value="{$title}" style="width: 100%"/></td></tr><tr><td class="forum_topic_sig"><input type="submit" value="{$_PWNDATA['articles']['save']}" /></td></tr></table></form>
END;
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
