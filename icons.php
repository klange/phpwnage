<?php
/*
	This file is part of PHPwnage (Tango icon theme definitions)

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

function genI($url, $alt) {
    return "<img src=\"$url\" alt=\"$alt\" style=\"vertical-align: text-bottom\"/>";
}

$_PWNICONS['buttons']['new_topic'] = genI("tango/new.png","");
$_PWNICONS['buttons']['new_reply'] = genI("tango/new-reply.png","+");
$_PWNICONS['buttons']['new_pm'] = genI("tango/new-pm.png","");
$_PWNICONS['buttons']['pm_reply'] = genI("tango/reply-pm.png","");
$_PWNICONS['buttons']['del_topic'] = genI("tango/delete.png","");
$_PWNICONS['buttons']['del_reply'] = genI("tango/delete.png","");
$_PWNICONS['buttons']['move'] = genI("tango/move.png","");

$_PWNICONS['buttons']['editor']['bold'] = genI("tango/bold.png","");
$_PWNICONS['buttons']['editor']['italic'] = genI("tango/italic.png","");
$_PWNICONS['buttons']['editor']['underline'] = genI("tango/underline.png","");
$_PWNICONS['buttons']['editor']['strike'] = genI("tango/strike.png","");


?>
