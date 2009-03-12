<?php
/*
	This file is part of PHPwnage (Module Loader)

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
require_once('includes.php');
if (strstr($_GET['m'],".") || strstr($_GET['m'],"/")) {
    messageBack($_PWNDATA['modules_page_title'], $_PWNDATA['module_invalid']);
}
$exists = @include 'modules/' . $_GET['m'] . '.php';

standardHeaders($site_info['name'] . " :: Modules :: " . $mod['title'],true);

drawSubbar("<a href=\"index.php\">" . $site_info['name'] . "</a> :: {$_PWNDATA['modules_page_title']} :: " . $mod['title'],$mod['right']);

if (!$exists) {
    messageBack($_PWNDATA['modules_page_title'], $_PWNDATA['module_does_not_exist'], false);
}

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
