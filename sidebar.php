<?php
require_once('includes.php');
$sidebar = array();
$result = mysql_query("SELECT * FROM `{$_PREFIX}blocks` ORDER BY `id`", $db);
while ($row = mysql_fetch_array($result)) {
    $sidebar[] = $row;
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
            $sidebar[] = array("title" => $block_title, "content" => $block_content);
        }
    }
}
$smarty->assign('sidebar',$sidebar);
?>
