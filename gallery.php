<?php
/*
	This file is part of PHPwnage (Image Gallery)

	Copyright 2008 Kevin Lange <klange@oasis-games.com>

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

function generateThumbnail($file, $type) {
    $_SIZE = 120;
    switch ($type) {
    case "image/png":
        $src = imagecreatefrompng($file);
        break;
    case "image/jpeg":
        $src = imagecreatefromjpeg($file);
        break;
    case "image/gif":
        $src = imagecreatefromgif($file);
        break;
    default:
        return false;
    }
    $im = imagecreatetruecolor($_SIZE,$_SIZE);
    imagesavealpha($im, true);
    $trans = imagecolorallocatealpha($im, 0, 0, 0, 127);
    imagefill($im, 0, 0, $trans);
    $wid = imagesx($src);
    $hei = imagesy($src);
    if ($wid < $_SIZE && $hei < $_SIZE) {
        imagesavealpha($src, true);
        imagepng($src, $file . "_th");
    } else {
        $ratio = $wid / $hei;
        if ($ratio > 1) {
            $newsize = $_SIZE / $ratio;
            $offset = ($_SIZE - $newsize) / 2;
            imagecopyresampled($im, $src, 0, $offset, 0, 0, $_SIZE, $_SIZE / $ratio, $wid, $hei);
        } else {
            $newsize = $_SIZE * $ratio;
            $offset = ($_SIZE - $newsize) / 2;
            imagecopyresampled($im, $src, $offset, 0, 0, 0, $_SIZE * $ratio, $_SIZE, $wid, $hei);
        }
        imagesavealpha($im, true);
        imagepng($im, $file . "_th");
    }
    imagedestroy($im);
    return true;
}

if (isset($_POST['action'])) {
    if ($_POST['action'] == "upload") {
        if (isset($_FILES['image'])) {
            $_POST['gallery'] = (int)$_POST['gallery'];
            $temp_query = mysql_query("SELECT `id`,`upload` FROM `galleries` WHERE `id`={$_POST['gallery']}");
            $temp = mysql_fetch_array($temp_query);
            if (!isset($temp['id']) || $temp['upload'] > $user['level']) {
                 messageBack($_PWNDATA['post_attack'],$_PWNDATA['not_permitted']);
            }
            if (!generateThumbnail($_FILES['image']['tmp_name'],$_FILES['image']['type'])) {
                messageBack($_PWNDATA['gallery_page_title'],$_PWNDATA['gallery']['upload_failed']);    
            }
            $fname = $_FILES['image']['tmp_name'];
            $file = fopen($fname,"rb");
            $image = addslashes(fread($file,$_FILES['image']['size']));
            $fname = $_FILES['image']['tmp_name'] . "_th";
            $file = fopen($fname,"rb");
            $thumb = addslashes(fread($file,filesize($fname)));
            $name = mse($_FILES['image']['name']);
            $title = mse($_POST['name']);
            $desc = mse($_POST['desc']);
            $query = <<<END
INSERT INTO `{$_PREFIX}images` VALUES (
NULL, '$title', '$desc', {$user['id']},   
'$name', {$_POST['gallery']}, {$_FILES['image']['size']},
'{$_FILES['image']['type']}', 1, "{$image}", "{$thumb}");
END;
            mysql_query($query);
            $result = mysql_query("SELECT `id` FROM `{$_PREFIX}images` ORDER BY `id` DESC LIMIT 1");
            $newimage = mysql_fetch_array($result);
            unlink($_FILES['image']['tmp_name']);
            unlink($_FILES['image']['tmp_name'] . "_th");
            messageRedirect($_PWNDATA['gallery_page_title'],$_PWNDATA['gallery']['image_uploaded'],"gallery.php?do=image&amp;id={$newimage['id']}");
        } else {
            messageBack($_PWNDATA['gallery_page_title'],$_PWNDATA['gallery']['no_image_specified']);
        }
    } elseif ($_POST['action'] == "edit_image") {
        $request = mysql_query("SELECT `id`,`uid` FROM `{$_PREFIX}images` WHERE `id`={$_POST['id']}");
        $image = mysql_fetch_array($request);
        if (!$image) {
            messageBack($_PWNDATA['gallery_page_title'],"Invalid image specified.");
        }
        if ($user['level'] < $site_info['mod_rank'] && $user['id'] != $image['uid']) {
            messageBack($_PWNDATA['gallery_page_title'],$_PWNDATA['gallery']['not_yours_edit']);
        }
        mysql_query("UPDATE `{$_PREFIX}images` SET `name`='{$_POST['name']}' WHERE `id`={$_POST['id']}");
        mysql_query("UPDATE `{$_PREFIX}images` SET `desc`='{$_POST['desc']}' WHERE `id`={$_POST['id']}");
        messageRedirect($_PWNDATA['gallery_page_title'],$_PWNDATA['gallery']['image_edited'],"gallery.php?do=image&amp;id={$image['id']}");
    } elseif ($_POST['action'] == "move_image") {
        if ($user['level'] < $site_info['mod_rank']) {
            messageBack($_PWNDATA['gallery_page_title'],$_PWNDATA['gallery']['only_mods_move']);
        }
        mysql_query("UPDATE `{$_PREFIX}images` SET `gid`={$_POST['gallery']} WHERE `id`={$_POST['id']}");
        messageRedirect($_PWNDATA['gallery_page_title'],$_PWNDATA['gallery']['image_moved'],"gallery.php?do=image&amp;id={$_POST['id']}");
    }
}

if ($_GET['do'] != "img") {

    if (!isset($_GET['do']) || ($_GET['do'] == "")) {
        $content = "<table class=\"forum_base\" width=\"100%\">";
        $request = mysql_query("SELECT * FROM `{$_PREFIX}galleries`");
        while ($gal = mysql_fetch_array($request)) {
            if ($gal['view'] <= $user['level']) {
                if ($gal['thumb'] != 0) {
                    $gal_thumb = "<img src=\"gallery.php?do=img&amp;type=thumb&amp;i={$gal['thumb']}\" alt=\"\" />";
                } else {
                    $gal_thumb = "<img src=\"tango/admin/images.png\" alt=\"\" />";
                }
                $results = mysql_query("SELECT COUNT(*) FROM `{$_PREFIX}images` WHERE `gid`={$gal['id']}");
                $count = mysql_fetch_array($results);
                $content = $content . "<tr><td width=\"1\" class=\"forum_topic_content\" align=\"center\" valign=\"middle\"><a href=\"gallery.php?do=view&amp;id={$gal['id']}\">{$gal_thumb}</a></td><td class=\"forum_topic_content\"><a href=\"gallery.php?do=view&amp;id={$gal['id']}\">{$gal['name']}</a><br /><i>{$gal['desc']}</i></td><td width=\"50\" class=\"forum_topic_content\" align=\"center\" valign=\"middle\">{$count['COUNT(*)']}</td></tr>\n";
            }
        }
        $content = $content . "</table>";
        $page_contents = makeBlock($_PWNDATA['gallery_page_title'], $_PWNDATA['gallery']['gallery_index'], $content);
        $page_location = "<a href=\"gallery.php\">{$_PWNDATA['gallery_page_title']}</a>";
        $page_loctitle = " :: {$_PWNDATA['gallery']['gallery_index']}";
    } elseif ($_GET['do'] == "upload_form") {
        $request = mysql_query("SELECT * FROM `{$_PREFIX}galleries` WHERE `id`={$_GET['gal']}");
        $gal = mysql_fetch_array($request);
        if ($gal['upload'] > $user['level']) {
            messageBack($_PWNDATA['gallery_page_title'],$_PWNDATA['gallery']['can_not_upload']);
        }
        $poster = printPoster('desc');
        $content = <<<END
        <form enctype="multipart/form-data" action="gallery.php" name="form" method="post">
            <input type="hidden" name="action" value="upload" />
            <input type="hidden" name="gallery" value="{$_GET['gal']}" />
            <input type="hidden" name="MAX_FILE_SIZE" value="20000000" />
            <table class="forum_base" width="100%">
                <tr><td class="forum_topic_content" width="200">{$_PWNDATA['gallery']['image_name']}</td><td class="forum_topic_content"><input type="text" name="name" style="width: 100%" /></td></tr>
                <tr><td class="forum_topic_sig" colspan="2">{$poster}<textarea name="desc" style="width: 100%" rows="5" cols="80" class="content_editor"></textarea></td></tr>
                <tr><td class="forum_topic_sig">{$_PWNDATA['gallery']['image_file']}</td><td class="forum_topic_sig"><input type="file" name="image" /></td></tr>
                <tr><td class="forum_topic_sig" colspan="2"><input type="submit" value="{$_PWNDATA['gallery']['upload_button']}" /></td></tr>
            </table>
        </form>
END;
        $page_contents = makeBlock($_PWNDATA['gallery_page_title'], $_PWNDATA['gallery']['upload_panel'], $content);
        $page_location = "<a href=\"gallery.php\">{$_PWNDATA['gallery_page_title']}</a> > {$_PWNDATA['gallery']['upload_panel']}";
        $page_loctitle = " :: {$_PWNDATA['gallery']['upload_panel']}";
    } elseif ($_GET['do'] == "view") {
        $request = mysql_query("SELECT * FROM `{$_PREFIX}galleries` WHERE `id`={$_GET['id']}");
        $gal = mysql_fetch_array($request);
        if ($gal['view'] > $user['level']) {
            messageBack($_PWNDATA['gallery_page_title'],$_PWNDATA['gallery']['can_not_view']);
        }
        $content = "<table class=\"mod_set\"><tr>";
        if ($user['level'] >= $gal['upload']) {
            $content = $content . drawButton("gallery.php?do=upload_form&amp;gal={$gal['id']}",$_PWNDATA['gallery']['upload_button'],$_PWNICONS['buttons']['img_upload']);
        }
        if (!isset($_GET['p'])) {
            $start = 0;
            $page = 1;
        } else {
            $start = ($_GET['p'] - 1) * $_IMAGESPERPAGE;
            $page = $_GET['p'];
        }
        $request = mysql_query("SELECT COUNT(*) FROM `{$_PREFIX}images` WHERE `gid`={$gal['id']}");
        $temp = mysql_fetch_array($request);
        $totalImages = $temp['COUNT(*)'];
        $totalPages = (int)(($totalImages - 1) / $_IMAGESPERPAGE + 1);
        if ($page > 1) {
            $content = $content . drawButton("gallery.php?do=view&amp;id={$gal['id']}&amp;p=" . ($page - 1), $_PWNDATA['forum']['previous_page'],$_PWNICONS['buttons']['previous']);
        }
        if ($totalPages > 1) {
            $content = $content . printPager("gallery.php?do=view&amp;id={$gal['id']}&amp;p=",$page,$totalPages);
        }
        if ($page < $totalPages) {
            $content = $content . drawButton("gallery.php?do=view&amp;id={$gal['id']}&amp;p=" . ($page + 1), $_PWNDATA['forum']['next_page'],$_PWNICONS['buttons']['next']);
        }
        $content = $content . "</tr></table>";
        $content = $content . "<table class=\"forum_base\" width=\"100%\">";
        $request = mysql_query("SELECT `id`,`name`,`desc`,`uid`,`fname`,`publ` FROM `{$_PREFIX}images` WHERE `gid`={$gal['id']} ORDER BY `id` DESC LIMIT {$start}, {$_IMAGESPERPAGE}");
        while ($image = mysql_fetch_array($request)) {
            $content = $content . "<tr><td class=\"forum_topic_content\" width=\"1\" rowspan=\"2\" align=\"center\" valign=\"middle\"><a href=\"gallery.php?do=image&amp;id={$image['id']}\"><img src=\"gallery.php?do=img&amp;type=thumb&amp;i={$image['id']}\" alt=\"\" /></a></td>";
            $content = $content . "<td class=\"forum_topic_content\"><b><a href=\"gallery.php?do=image&amp;id={$image['id']}\">{$image['name']}</a></b></td>";
            $results = mysql_query("SELECT `name` FROM `{$_PREFIX}users` WHERE `id`={$image['uid']}");
            $uploader = mysql_fetch_array($results);
            $content = $content . "<td class=\"forum_topic_content\" width=\"200\">{$_PWNDATA['gallery']['uploaded_by']}<a href=\"forum.php?do=viewprofile&amp;id={$image['uid']}\">{$uploader['name']}</a></td>";
            $description = bbDecode($image['desc']);
            $content = $content . "</tr><tr><td class=\"forum_topic_sig\" colspan=\"2\">{$description}</td></tr>";
        }
        $content = $content . "</table>";
        $page_contents = makeBlock($_PWNDATA['gallery_page_title'],$_PWNDATA['gallery']['viewing_gallery'], $content);
        $page_location = "<a href=\"gallery.php\">{$_PWNDATA['gallery_page_title']}</a> > " . $gal['name'];
        $page_loctitle = " :: " . $gal['name'];
    } elseif ($_GET['do'] == "image") {
        $request = mysql_query("SELECT `id`,`name`,`desc`,`uid`,`fname`,`publ`,`gid` FROM `{$_PREFIX}images` WHERE `id`={$_GET['id']}");
        $image = mysql_fetch_array($request);
        $results = mysql_query("SELECT `id`, `name` FROM `{$_PREFIX}users` WHERE `id`={$image['uid']}");
        $uploader = mysql_fetch_array($results);
        $results = mysql_query("SELECT * FROM `{$_PREFIX}galleries` WHERE `id`={$image['gid']}");
        $gal = mysql_fetch_array($results);
        $extra = "";
        if ($gal['view'] > $user['level']) {
            messageBack($_PWNDATA['gallery_page_title'],$_PWNDATA['gallery']['cannot_view_image']);
        }
        $desc = bbDecode($image['desc']);
        $content = "<table class=\"mod_set\"><tr>";
        if ($user['level'] >= $site_info['mod_rank'] || $user['id'] == $image['uid']) {
            $content = $content . drawButton("gallery.php?do=delete_image&amp;id={$image['id']}",$_PWNDATA['gallery']['delete'],$_PWNICONS['buttons']['del_img']);
            $content = $content . drawButton("gallery.php?do=edit_image&amp;id={$image['id']}",$_PWNDATA['gallery']['edit'],$_PWNICONS['buttons']['edit_img']);
        }
        if ($user['level'] >= $site_info['mod_rank']) {
            $content = $content . drawButton("javascript:flipVisibility('movebox');",$_PWNDATA['gallery']['move_image'],$_PWNICONS['buttons']['move']);
            $extra = <<<END
    <script type="text/javascript">
    //<![CDATA[
function flipVisibility(what) {
    if (document.getElementById(what).style.display != "none") {
        document.getElementById(what).style.display = "none"
    } else {
        document.getElementById(what).style.display = "inline"
    }
}
    //]]>
    </script>
END;
            $content = $content . <<<END
<td style="border: 0px"><div id="movebox" style="display:none;">
<form action="gallery.php" method="post" style="display:inline;">
<input type="hidden" name="action" value="move_image" />
<input type="hidden" name="id" value="{$image['id']}" />
<select name="gallery">
END;
            $request = mysql_query("SELECT `id`,`name` FROM `{$_PREFIX}galleries`");
            while ($gallery = mysql_fetch_array($request)) {
                $content = $content . "<option label=\"{$gallery['name']}\" value=\"{$gallery['id']}\">{$gallery['name']}</option>\n";
            }
            $content = $content . <<<END
</select>
<input type="submit" value="{$_PWNDATA['gallery']['move_image']}" />
</form></div></td>
END;
        }
        
        $content = $content . "</tr></table>";
        $content = $content . <<<END
<table class="forum_base" width="100%">        
<tr><td class="forum_topic_content" align="center"><b>{$image['name']}</b></td></tr>
<tr><td class="forum_topic_sig" align="center">{$_PWNDATA['gallery']['uploaded_by']}<a href="forum.php?do=viewprofile&amp;id={$uploader['id']}">{$uploader['name']}</a></td></tr>
<tr><td class="forum_topic_sig" align="center"><img src="gallery.php?do=img&amp;i={$_GET['id']}" alt="{$image['name']}" /></td></tr>
<tr><td class="forum_topic_sig" align="center">{$desc}</td></tr>
</table>
END;
        $page_contents = makeBlock($_PWNDATA['gallery_page_title'],$image['name'], $extra . $content);
        $page_location = "<a href=\"gallery.php\">{$_PWNDATA['gallery_page_title']}</a> > <a href=\"gallery.php?do=view&amp;id={$gal['id']}\">" . $gal['name'] . "</a> > " . $image['name'];
        $page_loctitle = " :: " . $gal['name'] . " :: " . $image['name'];
    } elseif ($_GET['do'] == "delete_image") {
        $request = mysql_query("SELECT `id`,`name`,`desc`,`uid`,`fname`,`publ`,`gid` FROM `{$_PREFIX}images` WHERE `id`={$_GET['id']}");
        $image = mysql_fetch_array($request);
        if (!$image) {
            messageBack($_PWNDATA['gallery_page_title'],$_PWNDATA['gallery']['no_image_specified']);
        }
        if ($user['level'] < $site_info['mod_rank'] && $user['id'] != $image['uid']) {
            messageBack($_PWNDATA['gallery_page_title'],$_PWNDATA['gallery']['not_yours_delete']);
        }
        mysql_query("DELETE FROM `{$_PREFIX}images` WHERE `id`={$_GET['id']}");
        messageRedirect($_PWNDATA['gallery_page_title'],"Image deleted","gallery.php?do=view&amp;id={$image['gid']}");
    } elseif ($_GET['do'] == "edit_image") {
        $request = mysql_query("SELECT `id`,`name`,`desc`,`uid`,`fname`,`publ`,`gid` FROM `{$_PREFIX}images` WHERE `id`={$_GET['id']}");
        $image = mysql_fetch_array($request);
        if (!$image) {
            messageBack($_PWNDATA['gallery_page_title'],$_PWNDATA['gallery']['no_image_specified']);
        }
        if ($user['level'] < $site_info['mod_rank'] && $user['id'] != $image['uid']) {
            messageBack($_PWNDATA['gallery_page_title'],$_PWNDATA['gallery']['not_yours_edit']);
        }
        $poster = printPoster('desc');
        $content = <<<END
<form action="gallery.php" name="form" method="post">
    <input type="hidden" name="action" value="edit_image" />
    <input type="hidden" name="id" value="{$image['id']}" />
    <table class="forum_base" width="100%">
        <tr><td class="forum_topic_content" width="200">{$_PWNDATA['gallery']['image_name']}</td><td class="forum_topic_content"><input type="text" name="name" style="width: 100%" value="{$image['name']}"/></td></tr>
        <tr><td class="forum_topic_sig" colspan="2">{$poster}<textarea name="desc" style="width: 100%" rows="5" cols="80" class="content_editor">{$image['desc']}</textarea></td></tr>
        <tr><td class="forum_topic_sig" colspan="2"><input type="submit" value="{$_PWNDATA['gallery']['save_image']}" /></td></tr>
    </table>
</form>
END;
        $page_contents = makeBlock($_PWNDATA['gallery_page_title'],$_PWNDATA['gallery']['editing'] . $image['name'], $content);
        $page_location = "<a href=\"gallery.php\">{$_PWNDATA['gallery_page_title']}</a> > {$_PWNDATA['gallery']['editing']}'" . $image['name'] . "'";
        $page_loctitle = " :: {$_PWNDATA['gallery']['editing']}'" . $image['name'] . "'";
    }



    standardHeaders($site_info['name'] . " :: " . $_PWNDATA['gallery_page_title'] . $page_loctitle,true);
    drawSubbar("<a href=\"index.php\">" . $site_info['name'] . "</a> > $page_location",$_PWNDATA['gallery_page_title']);
    require 'sidebar.php';
    print <<<END
        <td valign="top">
            <table class="borderless_table" width="100%">
                {$page_contents}
	        </table>
        </td>
  </tr>
</table>
END;
    require 'footer.php';
} else {
    // We're procesing image requests here.
    if (!isset($_GET['type']) || $_GET['type'] == "img") {
        $results = mysql_query("SELECT `type`, `data` FROM `{$_PREFIX}images` WHERE `id`={$_GET['i']}");
        $image = mysql_fetch_array($results);
        header("Content-type: " . $image['type']);
        die($image['data']);
    } elseif ($_GET['type'] == "thumb") {
        $results = mysql_query("SELECT `thumb` FROM `{$_PREFIX}images` WHERE `id`={$_GET['i']}");
        $image = mysql_fetch_array($results);
        header("Content-type: image/png");
        die($image['thumb']);
    }
}
?>
