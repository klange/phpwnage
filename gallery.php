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

if (isset($_POST['action'])) {
    if ($_POST['action'] == "upload") {
        if (isset($_FILES['image'])) {
            move_uploaded_file($_FILES['image']['tmp_name'],"/var/www/pwn/upload/" . $_FILES['image']['name']);
        }
    }
}


if (!$_GET['do'] == "img") {

    if (!isset($_GET['do']) || ($_GET['do'] == "")) {
        $content = "";
        $request = mysql_query("SELECT * FROM `galleries`");
        while ($gal = mysql_fetch_array($request)) {
            $content = $content . $gal['name'] . "<br /><i>" . $gal['desc'] . "</i><br /><br />";
        }
        $page_contents = makeBlock("Image Gallery", "Gallery Index", $content);
        $page_location = "Gallery Index";
        $page_loctitle = " :: Index";
    } elseif ($_GET['do'] == "upload_form") {
        $content = <<<END
        <form enctype="multipart/form-data" action="gallery.php" name="form" method="post">
            <input type="hidden" name="action" value="upload" />
            <input type="hidden" name="MAX_FILE_SIZE" value="200000" />
            <input type="file" name="image" />
            <input type="submit" value="Upload" />
        </form>
END;
        $page_contents = makeBlock("Image Gallery", "Upload Image", $content);
        $page_location = "Upload Image";
        $page_loctitle = " :: Upload Image";
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
}
?>
