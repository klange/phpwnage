<?php
/*
	This file is part of PHPwnage (Footer)

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
print <<<END
<table class="borderless_table" width="100%">
  <tr>
    <td class="sub_left"></td>

    <td class="sub_mid" align="center"><font class="sub_body_text">
END;
print $site_info['copyright'] . " <a href=\"rss.php\">{$_PWNICONS['tags']['rss']}</a><a href=\"mobile.php\">{$_PWNICONS['tags']['mobile']}</a><a href=\"https://launchpad.net/phpwnage\">{$_PWNICONS['tags']['phpwnage']}</a><a href=\"http://php.net\">{$_PWNICONS['tags']['php']}</a>";
print <<<END
    </font></td>
    <td class="sub_right"></td>
  </tr>
  
</table>
END;
require 'buddy.php';
print <<<END
<div class="sub_body_text" style="font-size: 8px;" align="center">
END;
   $mtime = microtime();
   $mtime = explode(" ",$mtime);
   $mtime = $mtime[1] + $mtime[0];
   $endtime = $mtime;
   $totaltime = ($endtime - $starttime); 
print "{$_PWNDATA['exec_a']}$totaltime{$_PWNDATA['exec_b']}";
print "<br />{$_PWNICONS['notice']}";
print $_TRACKER;
print <<<END
</div>
</body>
</html>
END;
?>
