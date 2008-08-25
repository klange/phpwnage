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
            if (!generateThumbnail($_FILES['image']['tmp_name'],$_FILES['image']['type'])) {
                messageBack("Image Gallery","Upload failed. File may be too large or of an unknown type.");    
            }
            $fname = $_FILES['image']['tmp_name'];
            $file = fopen($fname,"rb");
            $image = addslashes(fread($file,$_FILES['image']['size']));
            $fname = $_FILES['image']['tmp_name'] . "_th";
            $file = fopen($fname,"rb");
            $thumb = addslashes(fread($file,filesize($fname)));
            $query = <<<END
INSERT INTO `images` VALUES (
NULL, '{$_POST['name']}', '{$_POST['desc']}', {$user['id']},   
'{$_FILES['image']['name']}', {$_POST['gallery']}, {$_FILES['image']['size']},
'{$_FILES['image']['type']}', 1, "{$image}", "{$thumb}");
END;
            mysql_query($query);
            $result = mysql_query("SELECT `id` FROM `images` ORDER BY `id` DESC LIMIT 1");
            $newimage = mysql_fetch_array($result);
            unlink($_FILES['image']['tmp_name']);
            unlink($_FILES['image']['tmp_name'] . "_th");
            messageRedirect("Image Gallery","Image uploaded!","gallery.php?do=image&amp;id={$newimage['id']}");
        } else {
            messageBack("Image Gallery","No image specified");
        }
    } elseif ($_POST['action'] == "edit_image") {
        $request = mysql_query("SELECT `id`,`uid` FROM `images` WHERE `id`={$_POST['id']}");
        $image = mysql_fetch_array($request);
        if (!$image) {
            messageBack("Image Gallery","Invalid image specified.");
        }
        if ($user['level'] < $site_info['mod_rank'] && $user['id'] != $image['uid']) {
            messageBack("Image Gallery","This is not your image, only moderators can edit other users' images.");
        }
        mysql_query("UPDATE `images` SET `name`='{$_POST['name']}' WHERE `id`={$_POST['id']}");
        mysql_query("UPDATE `images` SET `desc`='{$_POST['desc']}' WHERE `id`={$_POST['id']}");
        messageRedirect("Image Gallery","Image edited.","gallery.php?do=image&amp;id={$image['id']}");
    }
}

if ($_GET['do'] != "img") {

    if (!isset($_GET['do']) || ($_GET['do'] == "")) {
        $content = "<table class=\"forum_base\" width=\"100%\">";
        $request = mysql_query("SELECT * FROM `galleries`");
        while ($gal = mysql_fetch_array($request)) {
            if ($gal['view'] <= $user['level']) {
                if ($gal['thumb'] != 0) {
                    $gal_thumb = "<img src=\"gallery.php?do=img&amp;type=thumb&amp;i={$gal['thumb']}\" alt=\"\" />";
                } else {
                    $gal_thumb = "<img src=\"tango/admin/images.png\" alt=\"\" />";
                }
                $request = mysql_query("SELECT COUNT(*) FROM `images` WHERE `gid`={$gal['id']}");
                $count = mysql_fetch_array($request);
                $content = $content . "<tr><td width=\"1\" class=\"forum_topic_content\" align=\"center\" valign=\"middle\"><a href=\"gallery.php?do=view&amp;id={$gal['id']}\">{$gal_thumb}</a></td><td class=\"forum_topic_content\"><a href=\"gallery.php?do=view&amp;id={$gal['id']}\">{$gal['name']}</a><br /><i>{$gal['desc']}</i></td><td width=\"50\" class=\"forum_topic_content\" align=\"center\" valign=\"middle\">{$count['COUNT(*)']}</td></tr>\n";
            }
        }
        $content = $content . "</table>";
        $page_contents = makeBlock("Image Gallery", "Gallery Index", $content);
        $page_location = "<a href=\"gallery.php\">Image Gallery</a>";
        $page_loctitle = " :: Index";
    } elseif ($_GET['do'] == "upload_form") {
        $request = mysql_query("SELECT * FROM `galleries` WHERE `id`={$_GET['id']}");
        $gal = mysql_fetch_array($request);
        if ($gal['upload'] > $user['level']) {
            messageBack("Image Gallery","You can not upload to this gallery.");
        }
        $poster = printPoster('desc');
        $content = <<<END
        <form enctype="multipart/form-data" action="gallery.php" name="form" method="post">
            <input type="hidden" name="action" value="upload" />
            <input type="hidden" name="gallery" value="{$_GET['gal']}" />
            <input type="hidden" name="MAX_FILE_SIZE" value="20000000" />
            <table class="forum_base" width="100%">
                <tr><td class="forum_topic_content" width="200">Name</td><td class="forum_topic_content"><input type="text" name="name" style="width: 100%" /></td></tr>
                <tr><td class="forum_topic_sig" colspan="2">{$poster}<textarea name="desc" style="width: 100%" rows="5" cols="80"></textarea></td></tr>
                <tr><td class="forum_topic_sig">Image</td><td class="forum_topic_sig"><input type="file" name="image" /></td></tr>
                <tr><td class="forum_topic_sig" colspan="2"><input type="submit" value="Upload" /></td></tr>
            </table>
        </form>
END;
        $page_contents = makeBlock("Image Gallery", "Upload Image", $content);
        $page_location = "<a href=\"gallery.php\">Image Gallery</a> > Upload Image";
        $page_loctitle = " :: Upload Image";
    } elseif ($_GET['do'] == "view") {
        $request = mysql_query("SELECT * FROM `galleries` WHERE `id`={$_GET['id']}");
        $gal = mysql_fetch_array($request);
        if ($gal['view'] > $user['level']) {
            messageBack("Image Gallery","You can not view this gallery.");
        }
        $content = "<table class=\"mod_set\">";
        if ($user['level'] >= $gal['upload']) {
            $content = $content . drawButton("gallery.php?do=upload_form&amp;gal={$gal['id']}","Upload");
        }
        $content = $content . "</table>";
        $content = $content . "<table class=\"forum_base\" width=\"100%\">";
        $request = mysql_query("SELECT `id`,`name`,`desc`,`uid`,`fname`,`publ` FROM `images` WHERE `gid`={$gal['id']} ORDER BY `id` DESC");
        while ($image = mysql_fetch_array($request)) {
            $content = $content . "<tr><td class=\"forum_topic_content\" width=\"1\" rowspan=\"2\" align=\"center\" valign=\"middle\"><a href=\"gallery.php?do=image&amp;id={$image['id']}\"><img src=\"gallery.php?do=img&amp;type=thumb&amp;i={$image['id']}\" alt=\"\" /></a></td>";
            $content = $content . "<td class=\"forum_topic_content\"><b><a href=\"gallery.php?do=image&amp;id={$image['id']}\">{$image['name']}</a></b></td>";
            $results = mysql_query("SELECT `name` FROM `users` WHERE `id`={$image['uid']}");
            $uploader = mysql_fetch_array($results);
            $content = $content . "<td class=\"forum_topic_content\" width=\"200\">Uploaded by <a href=\"forum.php?do=viewprofile&amp;id={$image['uid']}\">{$uploader['name']}</a></td>";
            $description = bbDecode($image['desc']);
            $content = $content . "</tr><tr><td class=\"forum_topic_sig\" colspan=\"2\">{$description}</td></tr>";
        }
        $content = $content . "</table>";
        $page_contents = makeBlock("Image Gallery","Viewing Gallery", $content);
        $page_location = "<a href=\"gallery.php\">Image Gallery</a> > " . $gal['name'];
        $page_loctitle = " :: " . $gal['name'];
    } elseif ($_GET['do'] == "image") {
        $request = mysql_query("SELECT `id`,`name`,`desc`,`uid`,`fname`,`publ`,`gid` FROM `images` WHERE `id`={$_GET['id']}");
        $image = mysql_fetch_array($request);
        $results = mysql_query("SELECT `id`, `name` FROM `users` WHERE `id`={$image['uid']}");
        $uploader = mysql_fetch_array($results);
        $results = mysql_query("SELECT * FROM `galleries` WHERE `id`={$image['gid']}");
        $gal = mysql_fetch_array($results);
        if ($gal['view'] > $user['level']) {
            messageBack("Image Gallery","You can not view this image's details because it is in a gallery you are not permitted to view.");
        }
        $desc = bbDecode($image['desc']);
        $content = "<table class=\"mod_set\">";
        if ($user['level'] >= $site_info['mod_rank'] || $user['id'] == $image['uid']) {
            $content = $content . drawButton("gallery.php?do=delete_image&amp;id={$image['id']}","Delete");
            $content = $content . drawButton("gallery.php?do=edit_image&amp;id={$image['id']}","Edit");
        }
        $content = $content . "</table>";
        $content = $content . <<<END
<table class="forum_base" width="100%">        
<tr><td class="forum_topic_content" align="center"><b>{$image['name']}</b></td></tr>
<tr><td class="forum_topic_sig" align="center">Uploaded by <a href="forum.php?do=viewprofile&amp;id={$uploader['id']}">{$uploader['name']}</a></td></tr>
<tr><td class="forum_topic_sig" align="center"><img src="gallery.php?do=img&amp;i={$_GET['id']}" alt="{$image['name']}" /></tr></td>
<tr><td class="forum_topic_sig" align="center">{$desc}</td></tr>
</table>
END;
        $page_contents = makeBlock("Image Gallery",$image['name'], $content);
        $page_location = "<a href=\"gallery.php\">Image Gallery</a> > <a href=\"gallery.php?do=view&amp;id={$gal['id']}\">" . $gal['name'] . "</a> > " . $image['name'];
        $page_loctitle = " :: " . $gal['name'] . " :: " . $image['name'];
    } elseif ($_GET['do'] == "delete_image") {
        $request = mysql_query("SELECT `id`,`name`,`desc`,`uid`,`fname`,`publ`,`gid` FROM `images` WHERE `id`={$_GET['id']}");
        $image = mysql_fetch_array($request);
        if (!$image) {
            messageBack("Image Gallery","Invalid image specified.");
        }
        if ($user['level'] < $site_info['mod_rank'] && $user['id'] != $image['uid']) {
            messageBack("Image Gallery","This is not your image, only moderators can delete other users' images.");
        }
        mysql_query("DELETE FROM `images` WHERE `id`={$_GET['id']}");
        messageRedirect("Image Gallery","Image deleted","gallery.php?do=view&amp;id={$image['gid']}");
    } elseif ($_GET['do'] == "edit_image") {
        $request = mysql_query("SELECT `id`,`name`,`desc`,`uid`,`fname`,`publ`,`gid` FROM `images` WHERE `id`={$_GET['id']}");
        $image = mysql_fetch_array($request);
        if (!$image) {
            messageBack("Image Gallery","Invalid image specified.");
        }
        if ($user['level'] < $site_info['mod_rank'] && $user['id'] != $image['uid']) {
            messageBack("Image Gallery","This is not your image, only moderators can edit other users' images.");
        }
        $poster = printPoster('desc');
        $content = <<<END
<form action="gallery.php" name="form" method="post">
    <input type="hidden" name="action" value="edit_image" />
    <input type="hidden" name="id" value="{$image['id']}" />
    <table class="forum_base" width="100%">
        <tr><td class="forum_topic_content" width="200">Name</td><td class="forum_topic_content"><input type="text" name="name" style="width: 100%" value="{$image['name']}"/></td></tr>
        <tr><td class="forum_topic_sig" colspan="2">{$poster}<textarea name="desc" style="width: 100%" rows="5" cols="80">{$image['desc']}</textarea></td></tr>
        <tr><td class="forum_topic_sig" colspan="2"><input type="submit" value="Save" /></td></tr>
    </table>
</form>
END;
        $page_contents = makeBlock("Image Gallery","Editing " . $image['name'], $content);
        $page_location = "<a href=\"gallery.php\">Image Gallery</a> > Editing '" . $image['name'] . "'";
        $page_loctitle = " :: Editing '" . $image['name'] . "'";
    }



    standardHeaders($site_info['name'] . " :: " . "Image Gallery" . $page_loctitle,true);
    drawSubbar("<a href=\"index.php\">" . $site_info['name'] . "</a> > $page_location","Gallery");
    require 'sidebar.php';    print <<<END
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
        $results = mysql_query("SELECT `type`, `data` FROM `images` WHERE `id`={$_GET['i']}");
        $image = mysql_fetch_array($results);
        header("Content-type: " . $image['type']);
        die($image['data']);
    } elseif ($_GET['type'] == "thumb") {
        $results = mysql_query("SELECT `thumb` FROM `images` WHERE `id`={$_GET['i']}");
        $image = mysql_fetch_array($results);
        header("Content-type: image/png");
        die($image['thumb']);
    }
}
?>
