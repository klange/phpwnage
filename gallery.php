<?php
/*
	This file is part of PHPwnage (Image Gallery)

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
            $temp_query = $_SQL->query("SELECT `id`,`upload` FROM `{$_PREFIX}galleries` WHERE `id`={$_POST['gallery']}");
            $temp = $temp_query->fetch_array();
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
            $_SQL->query($query);
            $result = $_SQL->query("SELECT `id` FROM `{$_PREFIX}images` ORDER BY `id` DESC LIMIT 1");
            $newimage = $result->fetch_array();
            unlink($_FILES['image']['tmp_name']);
            unlink($_FILES['image']['tmp_name'] . "_th");
            messageRedirect($_PWNDATA['gallery_page_title'],$_PWNDATA['gallery']['image_uploaded'],"gallery.php?do=image&amp;id={$newimage['id']}");
        } else {
            messageBack($_PWNDATA['gallery_page_title'],$_PWNDATA['gallery']['no_image_specified']);
        }
    } elseif ($_POST['action'] == "edit_image") {
        $request = $_SQL->query("SELECT `id`,`uid` FROM `{$_PREFIX}images` WHERE `id`={$_POST['id']}");
        $image = $request->fetch_array();
        if (!$image) {
            messageBack($_PWNDATA['gallery_page_title'],"Invalid image specified.");
        }
        if ($user['level'] < $site_info['mod_rank'] && $user['id'] != $image['uid']) {
            messageBack($_PWNDATA['gallery_page_title'],$_PWNDATA['gallery']['not_yours_edit']);
        }
        $_SQL->query("UPDATE `{$_PREFIX}images` SET `name`='{$_POST['name']}' WHERE `id`={$_POST['id']}");
        $_SQL->query("UPDATE `{$_PREFIX}images` SET `desc`='{$_POST['desc']}' WHERE `id`={$_POST['id']}");
        messageRedirect($_PWNDATA['gallery_page_title'],$_PWNDATA['gallery']['image_edited'],"gallery.php?do=image&amp;id={$image['id']}");
    } elseif ($_POST['action'] == "move_image") {
        if ($user['level'] < $site_info['mod_rank']) {
            messageBack($_PWNDATA['gallery_page_title'],$_PWNDATA['gallery']['only_mods_move']);
        }
        $_SQL->query("UPDATE `{$_PREFIX}images` SET `gid`={$_POST['gallery']} WHERE `id`={$_POST['id']}");
        messageRedirect($_PWNDATA['gallery_page_title'],$_PWNDATA['gallery']['image_moved'],"gallery.php?do=image&amp;id={$_POST['id']}");
    }
}

if (!isset($_GET['do'])) {
    $_GET['do'] = "";
}

if ($_GET['do'] != "img") {
    if (!isset($_GET['do']) || ($_GET['do'] == "")) {
        $galleries = array();
        $img_counts = array();
        $request = $_SQL->query("SELECT * FROM `{$_PREFIX}galleries`");
        while ($gal = $request->fetch_array()) {
            if ($gal['view'] <= $user['level']) {
                $results = $_SQL->query("SELECT COUNT(*) FROM `{$_PREFIX}images` WHERE `gid`={$gal['id']}");
                $count = $results->fetch_array();
                $galleries[$gal['id']] = $gal;
                $img_counts[$gal['id']] = $count['COUNT(*)'];
            }
        }
        $smarty->assign('galleries',$galleries);
        $smarty->assign('img_counts',$img_counts);
        $smarty->display('gallery/index.tpl');
    } elseif ($_GET['do'] == "view") {
        $id = intval($_GET['id']);
        $request = $_SQL->query("SELECT * FROM `{$_PREFIX}galleries` WHERE `id`={$id}");
        $gal = $request->fetch_array();
        if ($gal['view'] > $user['level']) {
            messageBack($_PWNDATA['gallery_page_title'],$_PWNDATA['gallery']['can_not_view']);
        }
        $images = array();
        $users  = array();
        if (!isset($_GET['p'])) {
            $start = 0;
            $page = 1;
        } else {
            $start = ($_GET['p'] - 1) * $_IMAGESPERPAGE;
            $page = $_GET['p'];
        }
        $request = $_SQL->query("SELECT COUNT(*) FROM `{$_PREFIX}images` WHERE `gid`={$gal['id']}");
        $temp = $request->fetch_array();
        $totalImages = $temp['COUNT(*)'];
        $totalPages = (int)(($totalImages - 1) / $_IMAGESPERPAGE + 1);
        
        $request = $_SQL->query("SELECT `id`,`name`,`desc`,`uid`,`fname`,`publ` FROM `{$_PREFIX}images` WHERE `gid`={$gal['id']} ORDER BY `id` DESC LIMIT {$start}, {$_IMAGESPERPAGE}");
        while ($image = $request->fetch_array()) {
            $images[] = $image;
            if (!array_key_exists($image['uid'],$users)) {
                $results_b = $_SQL->query("SELECT * FROM `{$_PREFIX}users` WHERE `id`={$image['uid']}");
                $tmp = $results_b->fetch_array();
                $users[$tmp['id']] = $tmp;
            }
        }
        
        $smarty->assign('gallery',$gal);
        $smarty->assign('images',$images);
        $smarty->assign('users',$users);
        $smarty->assign('page',$page);
        $smarty->assign('totalImages',$totalImages);
        $smarty->assign('totalPages',$totalPages);
        $smarty->display('gallery/viewgallery.tpl');
    } elseif ($_GET['do'] == "upload_form") {
        $request = $_SQL->query("SELECT * FROM `{$_PREFIX}galleries` WHERE `id`={$_GET['gal']}");
        $gal = $request->fetch_array();
        
        if ($gal['upload'] > $user['level']) {
            messageBack($_PWNDATA['gallery_page_title'],$_PWNDATA['gallery']['can_not_upload']);
        }
        
        $smarty->assign('gallery',$gal);
        $smarty->display('gallery/uploadform.tpl');
    } elseif ($_GET['do'] == "image") {
        $request = $_SQL->query("SELECT `id`,`name`,`desc`,`uid`,`fname`,`publ`,`gid` FROM `{$_PREFIX}images` WHERE `id`={$_GET['id']}");
        $image = $request->fetch_array();
        $results = $_SQL->query("SELECT `id`, `name` FROM `{$_PREFIX}users` WHERE `id`={$image['uid']}");
        $uploader = $results->fetch_array();
        $results = $_SQL->query("SELECT * FROM `{$_PREFIX}galleries` WHERE `id`={$image['gid']}");
        $gal = $results->fetch_array();
        
        if ($gal['view'] > $user['level']) {
            messageBack($_PWNDATA['gallery_page_title'],$_PWNDATA['gallery']['cannot_view_image']);
        }
        
        if ($user['level'] >= $site_info['mod_rank']) {
            $galleries = array();
            $results = $_SQL->query("SELECT `id`,`name` FROM `{$_PREFIX}galleries`");
            while ($tmp = $results->fetch_array()) {
                $galleries[] = $tmp;
            }
            $smarty->assign('galleries',$galleries);
        }
        
        $smarty->assign('image',$image);
        $smarty->assign('gallery',$gal);
        $smarty->assign('uploader',$uploader);
        $smarty->display('gallery/image.tpl');
    } elseif ($_GET['do'] == "delete_image") {
        $request = $_SQL->query("SELECT `id`,`name`,`desc`,`uid`,`fname`,`publ`,`gid` FROM `{$_PREFIX}images` WHERE `id`={$_GET['id']}");
        $image = $request->fetch_array();
        if (!$image) {
            messageBack($_PWNDATA['gallery_page_title'],$_PWNDATA['gallery']['no_image_specified']);
        }
        if ($user['level'] < $site_info['mod_rank'] && $user['id'] != $image['uid']) {
            messageBack($_PWNDATA['gallery_page_title'],$_PWNDATA['gallery']['not_yours_delete']);
        }
        $_SQL->query("DELETE FROM `{$_PREFIX}images` WHERE `id`={$_GET['id']}");
        messageRedirect($_PWNDATA['gallery_page_title'],"Image deleted","gallery.php?do=view&amp;id={$image['gid']}");
    } elseif ($_GET['do'] == "edit_image") {
        $request = $_SQL->query("SELECT `id`,`name`,`desc`,`uid`,`fname`,`publ`,`gid` FROM `{$_PREFIX}images` WHERE `id`={$_GET['id']}");
        $image = $request->fetch_array();
        if (!$image) {
            messageBack($_PWNDATA['gallery_page_title'],$_PWNDATA['gallery']['no_image_specified']);
        }
        if ($user['level'] < $site_info['mod_rank'] && $user['id'] != $image['uid']) {
            messageBack($_PWNDATA['gallery_page_title'],$_PWNDATA['gallery']['not_yours_edit']);
        }
        $request = $_SQL->query("SELECT `id`,`name` FROM `{$_PREFIX}galleries` WHERE `id`={$image['gid']}");
        $gal = $request->fetch_array();
        $smarty->assign('image',$image);
        $smarty->assign('gallery',$gal);
        $smarty->display('gallery/edit.tpl');
    }
} else {
    // We're procesing image requests here.
    if (!isset($_GET['type']) || $_GET['type'] == "img") {
        $results = $_SQL->query("SELECT `type`, `data` FROM `{$_PREFIX}images` WHERE `id`={$_GET['i']}");
        $image = $results->fetch_array();
        header("Content-type: " . $image['type']);
        die($image['data']);
    } elseif ($_GET['type'] == "thumb") {
        $results = $_SQL->query("SELECT `thumb` FROM `{$_PREFIX}images` WHERE `id`={$_GET['i']}");
        $image = $results->fetch_array();
        header("Content-type: image/png");
        die($image['thumb']);
    }
}
?>
