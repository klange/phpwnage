<?php
/*
	This file is part of PHPwnage (Side Bar)

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

print <<<END
<script type="text/javascript">
//<![CDATA[
function hideSideBar() {
document.getElementById('sidebar').style.display = "none"
document.getElementById('sb').style.width = "0"
}
//]]>
</script>
<table class="borderless_table" width="100%">
  <tr>
    <td id="sb" valign="top" width="200px">
	<div id="sidebar" class="sidebar">
    <table class="borderless_table" width="100%">
END;

$result = mysql_query("SELECT * FROM `{$_PREFIX}blocks` ORDER BY `id`", $db);
while ($row = mysql_fetch_array($result)) {
    print makeBlockTrue($row['title'],$row['content']);
}
$myDirectory = opendir("blocks"); // Open 'blocks'
while($entryName = readdir($myDirectory)) {
    $dirArray[] = $entryName; // Get our list of files
}
closedir($myDirectory); // Close the directory
sort($dirArray); // Sort the array (names should be changed for order, adding 01, etc)
$indexCount	= count($dirArray); // Count...
for($index=0; $index < $indexCount; $index++) {
    if (substr("$dirArray[$index]", 0, 1) != "."){ 
        if (substr("$dirArray[$index]", strlen($dirArray[$index]) - 4, 4) == ".php") {
            $block_title = "";
            $block_content = "";
            require "blocks/" . $dirArray[$index];
            print makeBlockTrue($block_title, $block_content);
        }
    }
}
print <<<END
	<tr>
	<td> <span style="font-size: 10px;"><a href="javascript:hideSideBar()">{$_PWNDATA['hide_sidebar']}</a></span></td>
	</tr>
    </table></div>
</td>
END;
?>
