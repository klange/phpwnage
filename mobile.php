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
</style>
</head>

<body>

<table border="1" cellpadding="0" cellspacing="0" style="border-collapse: collapse">
  <tr>
    <td width="100%"  height="19"><center>
END;
print $site_info['name'];
print <<<END
</center></td>
  </tr>
  <tr>
    <td width="100%" height="331" valign="top"><table border="1" cellpadding="0" cellspacing="0" style="border-collapse: collapse" width="100%">
END;
$result = mysql_query("SELECT * FROM news ORDER BY id DESC LIMIT 10", $db);
while ($row = mysql_fetch_array($result)) {
print <<<END
      <tr>
        <td width="100%" bgcolor="#C0C0C0">
END;
print $row['title'];
print <<<END
	</td>
      </tr>
      <tr>
        <td width="100%">
END;
print BBDecode($row['content'],true);
print "</td></tr>";
}
print <<<END
</table></td>
  </tr>
    <tr>
        <td width="100%"><center>
END;
print $site_info['copyright'];
print <<<END
	</center></td>
      </tr>
</table>
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
