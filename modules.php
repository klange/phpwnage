<?php
/*
	This file is part of PHPwnage (Module Loader)

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
require 'config.php';require 'includes.php';
require "modules/" . $_GET['m'] . ".php";

print <<<END

<html>

<head>
<title>
END;
print $site_info['name'] . " :: Modules :: " . $mod['title'];
print "</title>\n";
require 'css.php';
require 'header.php';

print <<<END
<table class="borderless_table" width="100%">
  <tr>
    <td class="sub_left"></td>
    <td class="sub_mid"><font class="sub_body_text">
END;
print "<a href=\"index.php\">" . $site_info['name'] . "</a> :: {$_PWNDATA['modules_page_title']} :: " . $mod['title'];
print <<<END
    </font></td>
    <td class="sub_mid">

    <p align="right"><font class="sub_body_text">
END;
print $mod['right'];
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
drawBlocK($mod['title'],$mod['right_inner'],mod_print());
print <<<END
	</table>
        </td>
  </tr>
</table>
END;
require 'footer.php';
?>
