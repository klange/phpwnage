<?php
/*
	This file is part of PHPwnage (Image Gallery)

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

standardHeaders($site_info['name'],true);

drawSubbar("<a href=\"index.php\">" . $site_info['name'] . "</a> > IMAGE GALLERY","Gallery");

require 'sidebar.php';

print <<<END

<td valign="top">
<table class="borderless_table" width="100%">
END;


drawBlock("Image Gallery", "Gallery Index", "Image Gallery");

print <<<END
	</table>
        </td>
  </tr>
</table>
END;
require 'footer.php';
?>
