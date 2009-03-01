<?php
require 'config.php';
require 'includes.php';
$im_array = explode(",",$site_info['ims']);
$im_titles = explode(",",$site_info['ims_title']);
$im_types = array_combine($im_array,$im_titles);
$temp = mysql_query("SELECT * FROM `users`");
while ($row = mysql_fetch_array($temp)) {
	//$ims = $row['msn'] . "," . $row['yahoo'] . "," . $row['aim'] . "," . $row['icq'] . "," . $row['xfire'] . "," . $row['live'] . "," . $row['pand'];
	//print $row['name'] . ": " . $ims . "<br>\n";
	//mysql_query("UPDATE `users` SET `ims`='{$ims}' WHERE `id`={$row['id']}");
	//print_r(explode(",",$row['ims']));
	$i = 0;
	$ims = explode(",",$row['ims']);
	print "<b>" . $row['name'] . "</b><br>\n";
	foreach ($im_types as $im => $title) {
		print $_PWNICONS['protocols'][$im] . " ({$title}): " . $ims[$i] . "<br>\n";
		$i += 1;
	}
}

?>
