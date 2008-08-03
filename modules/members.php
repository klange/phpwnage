<?php
/*
	This file is part of PHPwnage (Simple Memberlist module)

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
$mod['title'] = "Member List";
$mod['right'] = "Viewing List";
$mod['right_inner'] = "Members of " . $site_info['name'];
function mod_print()
{
$members_result = mysql_query("SELECT * FROM `users` ORDER BY `name`");
$content = "";
while ($member = mysql_fetch_array($members_result))
{
$content = $content . "<a href=\"forum.php?do=viewprofile&id=" . $member['id'] . "\">" . $member['name'] . "</a>";
$content = $content . " | <a href=\"forum.php?do=newpm&to=" . $member['id'] . "\">Send a PM</a><br>";
}
return $content;
}
?>
