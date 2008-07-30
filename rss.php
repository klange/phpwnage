<?php
/*
	This file is part of PHPwnage (RSS Syndication Module)

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
require 'config.php';
$no_login = true;
require 'includes.php';
header("Content-type: application/xhtml+xml");

print "<?xml version=\"1.0\" encoding=\"iso-8859-1\" ?>\n";
print "<rss version=\"2.0\">\n";
print "<channel>\n";
print "	<title>" . $site_info['name'] . "</title>\n";
print "	<description>Powered by PHPwnage</description>\n";
print "	<link>" . $site_info['url'] . "</link>\n";
$result = mysql_query("SELECT * FROM news ORDER BY id DESC LIMIT 10", $db);
while ($row = mysql_fetch_array($result)) {
print "	<item>\n		<title>" . htmlspecialchars($row['title']) . "</title>\n";
print "     <pubDate>" . date("D, d M Y H:i:s T", $row['time_code']) . "</pubDate>\n";
print "		<link>" . $site_info['url'] . "article.php?id=" . $row['id'] . "</link>\n";
$rowtemp = trim($row['content'], "\n");
print "		<description><![CDATA[" . $rowtemp . "]]></description>\n";
print "	</item>\n";
}
print "</channel>\n</rss>";
?>
