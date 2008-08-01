<?php
/*
	This file is part of PHPwnage (Main Index, News Listing)

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


// Check for and merge the configuration file
$config_exists = @include 'config.php';
if (!$config_exists) {
    die("<meta http-equiv=\"Refresh\" content=\"1;url=fresh_install.php\">Error: Not installed. Redirecting to installer.");
    // @Daniel: I don't cheat headers. There are browsers that ignore this.
    // A meta-refresh is more universal, and as I use it ever where else
    // it feels appropriate here as well.
}


require 'includes.php'; // Important stuff.

// Begin printing the page, starting with the meta tags.
print <<<END
<html>
<head>
	<title>
END;
print $site_info['name']; // Print the name of the site into the title.
print "	</title>\n";

require 'css.php'; // Load theme data from the appropriate CSS file

require 'header.php'; // Print the header

print <<<END
<table class="borderless_table" width="100%">
  <tr>
    <td class="sub_left"></td>
    <td class="sub_mid"><font class="sub_body_text">
END;
print "{$_PWNDATA['last_updated']} " . date("F j, Y (g:ia T)", $site_info['last_updated']) . " <a href=\"?show=all\">[{$_PWNDATA['show_all']}]</a>";
print <<<END
    </font></td>
    <td class="sub_mid">

    <p align="right"><font class="sub_body_text">
END;
print $site_info['right_data'];
print <<<END
    </font></td>
    <td class="sub_right"></td>
  </tr>
</table>

END;

require 'sidebar.php';

print <<<END

<td valign="top">
<table class="borderless_table" width="100%">
END;

if ($_GET['show'] == 'all') { 
    $result = mysql_query("SELECT * FROM news ORDER BY id DESC", $db);
} else {
    $result = mysql_query("SELECT * FROM news ORDER BY id DESC LIMIT 10", $db);
}
while ($row = mysql_fetch_array($result)) {
	// News article
	drawBlock("<a href=\"article.php?id=" . $row['id'] . "\">" . $row['title'] . "</a>", date("F j, Y (g:ia T)", $row['time_code']) . ", {$_PWNDATA['posted_by']} " . $row['user'] . "; {$_PWNDATA['article']} #" . ($row['id']), $row['content']);
}
print <<<END
	</table>
        </td>
  </tr>
</table>
END;
require 'footer.php';
?>
