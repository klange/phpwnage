<?php
/*  PHPwnage Board Order Fixer
    This will later be put into the admin panel as part
    of the 1.8.1 update.
*/
require_once('../includes.php');

standardHeaders("Board Order Fixer",false);

print "<div style=\"font-family: Tahoma, sans; font-size: 12px\"><b>Fixing Board Orders</b><br />";
$j = 1;
$result = mysql_query("SELECT * FROM `{$_PREFIX}categories` ORDER BY `orderid`");
while ($cat = mysql_fetch_array($result)) {
    print "Correcting category '" . $cat['name'] . "'...<br />";
    $catid = $cat['id'];
    mysql_query("UPDATE `{$_PREFIX}categories` SET `orderid`=$j WHERE `id`=$catid");
    $resultb = mysql_query("SELECT * FROM `{$_PREFIX}boards` WHERE `catid`=$catid ORDER BY `orderid`");
    $i = 1;
    while ($board = mysql_fetch_array($resultb)) {
        print "&nbsp;&nbsp;&nbsp;Fixing '" . $board['title'] . "'...";
        mysql_query("UPDATE `{$_PREFIX}boards` SET `orderid`=$i WHERE `id`={$board['id']}");
        print " <span style=\"color: #337733\">Ok</span><br />";
        $i++;
    }
    $j++;
}
print "</div>";
