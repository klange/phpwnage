<?php
require 'config.php';
require 'includes.php';
if (!isset($site_info['ims'])) {
 mysql_query("ALTER TABLE `{$_PREFIX}info` ADD COLUMN `ims` TEXT  DEFAULT NULL AFTER `recap_priv`;");
 mysql_query("UPDATE `{$_PREFIX}info` SET `ims`='msn,yahoo,aim,icq,xfire,live' WHERE `id`=1;");
}
if (!isset($site_info['ims_title'])) {
 mysql_query("ALTER TABLE `{$_PREFIX}info` ADD COLUMN `ims_title` TEXT DEFAULT NULL AFTER `ims`;");
 mysql_query("UPDATE `{$_PREIFX}info` SET `ims_title`='MSN,Yahoo,AIM,ICQ,xFire,Live' WHERE `id`=1;");
}
$result = mysql_query("SELECT * FROM `{$_PREFIX}info`", $db);
$site_info = mysql_fetch_array($result);

$im_array = explode(",",$site_info['ims']);
$im_titles = explode(",",$site_info['ims_title']);
print_r($im_array);
print_r($im_titles);
$im_types = array_combine($im_array,$im_titles);
$temp = mysql_query("SELECT * FROM `{$_PREFIX}users`");
while ($row = mysql_fetch_array($temp)) {
    if (!isset($row['ims'])) {
     print "HELLO?";
     mysql_query("ALTER TABLE `{$_PREFIX}users` ADD COLUMN `ims` TEXT DEFAULT NULL;");
    }
	$ims = $row['msn'] . "," . $row['yahoo'] . "," . $row['aim'] . "," . $row['icq'] . "," . $row['xfire'] . "," . $row['live'];
	print $row['name'] . ": " . $ims . "<br>\n";
	mysql_query("UPDATE `users` SET `ims`='{$ims}' WHERE `id`={$row['id']}");
	print_r(explode(",",$row['ims']));
	$i = 0;
	$ims = explode(",",$row['ims']);
	print "<b>" . $row['name'] . "</b><br>\n";
	foreach ($im_types as $im => $title) {
		print $_PWNICONS['protocols'][$im] . " ({$title}): " . $ims[$i] . "<br>\n";
		$i += 1;
	}
}
mysql_query(<<<END
ALTER TABLE `{$_PREFIX}users` DROP COLUMN `msn`,
 DROP COLUMN `yahoo`,
  DROP COLUMN `aim`,
   DROP COLUMN `icq`,
    DROP COLUMN `xfire`,
     DROP COLUMN `live`;
END
);
?>
