<?php
/*
	This file is part of PHPwnage (Main Header)

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
if ($site_info['pheader'] != "") {
    $pheader = "<a href=\"index.php\"><img src=\"" . $site_info['pheader'] . "\" alt=\"" . $site_info['name'] . "\"/></a>";
} else {
    $pheader = "&nbsp;";
}
// Don't forget to add any META tags before the </head>!
$SITETITLE = $site_info['name'];
if (file_exists("favicon.ico")) {
    $tempmeta =  getimagesize("favicon.ico");
    $FAVMETA = "<link rel=\"icon\" type=\"" . $tempmeta['mime'] . "\" href=\"favicon.ico\" />";
} else {
    $FAVMETA = "";
}
print <<<END
<meta http-equiv="Content-Language" content="en-us" />
<meta name="GENERATOR" content="PHPwnage" />
<meta http-equiv="Content-Type" content="text/html; charset=windows-1252" />
<meta name="keywords" content="PHPwnage, PHP, php, CMS, forum, Forum, news, calendar, Oasis-Games" /> 
$FAVMETA
<link rel="alternate" type="application/rss+xml" title="$SITETITLE" href="/rss.php" /></head>

<body>

<table class="borderless_table" width="100%">
  <tr>
    <td class="head_left">$pheader</td>
    <td class="head_mid">&nbsp;</td>
    <td class="head_right">&nbsp;</td>
  </tr>
</table>
END;
?>
