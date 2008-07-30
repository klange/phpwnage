<?php
/*
	This file is part of PHPwnage (Footer)

	Copyright 2008 Kevin Lange <klange@ogunderground.com>

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
print <<<END
<table class="borderless_table" width="100%">
  <tr>
    <td class="sub_left"></td>

    <td class="sub_mid">
    <p align="center"><font class="sub_body_text">
END;
print $site_info['copyright'] . " <a href=\"rss.php\"><img align=absmiddle border=\"0\" src=\"smiles/rss20.png\"></a><a href=\"psp.php\"><img align=absmiddle border=\"0\" src=\"smiles/mobile.png\"></a><a href=\"http://oasis-games.com/\"><img align=absmiddle border=\"0\" src=\"smiles/pwn.png\"></a><a href=\"http://php.net\"><img align=absmiddle border=\"0\" src=\"smiles/php5.png\"></a> <a href=\"javascript:MinimumFontSize()\">{$_PWNDATA['increase_font']}</a>";
print <<<END
    <td class="sub_right"></td>
  </tr>
  
</table>
<script>
function MinimumFontSize() {
tags = new Array ('body', 'div', 'a', 'td', 'th', 'p', 'span', 'h1', 'h2', 'h3', 'font', 'tr', 'table');
for (j = 0; j < tags.length; j ++) {
for (i = 0; i < document.getElementsByTagName(tags[j]).length - 1; i ++) {
var getbody = document.getElementsByTagName(tags[j]).item(i);
if (getbody) {
var fsize = getbody.style.fontSize.substr(0,getbody.style.fontSize.length-2);
if (fsize == "") {
fsize = "12pt";
} else {
var tempsize = parseInt(fsize) + 1;
fsize = tempsize.toString() + "pt";
}
getbody.style.fontSize = fsize;
}
}
}
}
function FWithEverything() {
tags = new Array ('body', 'div', 'a', 'td', 'th', 'p', 'span', 'h1', 'h2', 'h3', 'font', 'tr', 'table');
var itemcount = 0;
for (j = 0; j < tags.length; j ++) {
for (i = 0; i < document.getElementsByTagName(tags[j]).length - 1; i ++) {
var getbody = document.getElementsByTagName(tags[j]).item(i);
if (getbody) {
getbody.style.position = "fixed";
getbody.style.width = "10";
getbody.style.height = "10";
getbody.style.left = i * 10;
getbody.style.top = j * 20;
itemcount = itemcount + 1;
}
}
}
}
</script>
END;
require 'buddy.php';
print <<<END
<p align="center"><font class="sub_body_text"><font size="1">
END;
   $mtime = microtime();
   $mtime = explode(" ",$mtime);
   $mtime = $mtime[1] + $mtime[0];
   $endtime = $mtime;
   $totaltime = ($endtime - $starttime); 
print "{$_PWNDATA['exec_a']}$totaltime{$_PWNDATA['exec_b']}";
print <<<END
</font></font></p>
</body>
</html>
END;
?>
