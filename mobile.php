<?php
/*
	This file is part of PHPwnage (Mobile Format News Reader)

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
require 'config.php';
require 'includes.php';


print <<<END
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
<head>
<title>
END;
print $site_info['name'] . " :: Mobile";
print <<<END
</title>
<meta http-equiv="Content-type" content="text/html;charset=windows-1252" />
<style type="text/css">
img {width:120;}
body {font-family: sans; font-size: 12px;}
</style>
</head>

<body>
<b>{$site_info['name']}</b><br /><br />
END;
$result = mysql_query("SELECT * FROM news ORDER BY id DESC LIMIT 5", $db);
while ($row = mysql_fetch_array($result)) {
print <<<END
<b>{$row['title']}</b><br />
END;
print BBDecode($row['content'],true);
print "<br /><br />";
}
print <<<END
<i>{$site_info['copyright']}</i><br />
END;
$mtime = microtime();
$mtime = explode(" ",$mtime);
$mtime = $mtime[1] + $mtime[0];
$endtime = $mtime;
$totaltime = ($endtime - $starttime); 
print "{$_PWNDATA['exec_a']}$totaltime{$_PWNDATA['exec_b']}";
print <<<END
</body>
</html>
END;
die('');
?>
