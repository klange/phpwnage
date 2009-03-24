<?php
/*
	This file is part of PHPwnage (Main Header)

	Copyright 2009 Kevin Lange <klange@oasis-games.com>

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
<link rel="alternate" type="application/rss+xml" title="$SITETITLE" href="/rss.php" />
END;
if ($user['level'] < 1 or $user['rich_edit']) {
print <<<END
<script type="text/javascript" src="tiny_mce/tiny_mce.js"></script>
<script type="text/javascript">
//<![CDATA[
tinyMCE.init({
	theme : "advanced",
	mode : "textareas",
	plugins : "bbcode",
	editor_selector : "content_editor",
	theme_advanced_toolbar_location : "external",
	theme_advanced_buttons1 : "",
	theme_advanced_buttons2 : "",
	theme_advanced_buttons3 : "",
	theme_advanced_resize_horizontal : false,
	theme_advanced_resizing : true,
	theme_advanced_resizing_use_cookie : false,
	theme_advanced_path : false,
	theme_advanced_statusbar_location : "bottom",
	entity_encoding : "raw",
	add_unload_trigger : false,
	remove_linebreaks : false,
	inline_styles : false,
	relative_urls : false,
	convert_fonts_to_spans : false
});
//]]>
</script>
<style type="text/css">
.mceExternalToolbar {
    display: none !important;
}
</style>
END;
}
print <<<END</head>

<body>

<table class="borderless_table" width="100%">
  <tr>
    <td class="head_left">$pheader</td>
    <td class="head_mid">&nbsp;</td>
    <td class="head_right">&nbsp;</td>
  </tr>
</table>
<div class="main_body">
END;
?>
