<?php
/*
	This file is part of PHPwnage (Mobile Format News Reader)

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
require 'config.php';
require 'includes.php';


print <<<END
<html>
<head>
<title>
END;
print $site_info['name'] . " :: Mobile";
print <<<END
</title>
<style>
img {width:120;}
</style>
</head>

<body>

<table border="1" cellpadding="0" cellspacing="0" style="border-collapse: collapse" bordercolor="#111111" width="100%" height="351">
  <tr>
    <td width="100%"  height="19"><center>
END;
print $site_info['name'];
print <<<END
</center></td>
  </tr>
  <tr>
    <td width="100%" height="331" valign="top"><table border="1" cellpadding="0" cellspacing="0" style="border-collapse: collapse" bordercolor="#111111" width="100%">
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
print $row['content'];
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

</body>

</html>
END;


?>
