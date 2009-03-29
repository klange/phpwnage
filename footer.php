<?php
/*
	This file is part of PHPwnage (Footer)

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
print <<<END
</div>
<table class="borderless_table" width="100%">
  <tr>
    <td class="foot_left"></td>

    <td class="foot_mid" align="center"><font class="foot_body_text">
END;
print $site_info['copyright'] . " <a href=\"rss.php\">{$_PWNICONS['tags']['rss']}</a><a href=\"mobile.php\">{$_PWNICONS['tags']['mobile']}</a><a href=\"https://launchpad.net/phpwnage\">{$_PWNICONS['tags']['phpwnage']}</a><a href=\"http://php.net\">{$_PWNICONS['tags']['php']}</a>";
print <<<END
    </font></td>
    <td class="foot_right"></td>
  </tr>
  
</table>
END;
require 'buddy.php';
print <<<END
<div class="footer_body_text" style="font-size: 10px;" align="center">
END;
$mtime = microtime();
$mtime = explode(" ",$mtime);
$mtime = $mtime[1] + $mtime[0];
$endtime = $mtime;
$totaltime = ($endtime - $starttime); 
print "{$_PWNDATA['exec_a']}$totaltime{$_PWNDATA['exec_b']}";
print "<br />{$_PWNICONS['notice']}";
print $_TRACKER;
// XXX: The following is hacked in from a compiled footer.tpl because I'm lazy.
$bt = debug_backtrace();
$bt = $bt['0'];
pwnErrorStackAppend(1,"File not converted to Templates!",$bt['file'],$bt['line']);
pwnErrorStackAppend(1337,"Total of " . count($_ERRORS) . " errors and warnings.<br /><b>MySQL Status:</b><br />" . $_SQL->stat(),'',0);
foreach ($_ERRORS as $error) { ?>
<?php if ($error['type'] == 1) {?>
<div style="padding: 2px; margin: 4px; width: 800px; border: 1px solid #FF4B4B; background-color: #FFE1E1; text-align: left;">
<?php } elseif ($error['type'] == 2 || $error['type'] == 512) { ?>
<div style="padding: 2px; margin: 4px; width: 800px; border: 1px solid #FFBB65; background-color: #FFEFDA; text-align: left;">
<?php } elseif ($error['type'] == 8) { ?>
<div style="padding: 2px; margin: 4px; width: 800px; border: 1px solid #71FF69; background-color: #D7FFD5; text-align: left;">
<?php } else {?>
<div style="padding: 2px; margin: 4px; width: 800px; border: 1px solid #7DCBFF; background-color: #D9F0FF; text-align: left;">
<?php } ?>
<?php if ($error['line'] > 0)  { ?><span style="font-family: monospace;">[<?php echo $error['time']; ?>
]</span> <?php } ?><b><?php echo $error['name']; ?>
:</b> <?php echo $error['str']; ?>

<?php if ($error['line'] > 0) {?><br /><span style="font-family: monospace;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
Line <b><?php echo $error['line']; ?>
</b> in <i><?php echo $error['file']; ?>
</i><?php } ?>
</div>
<?
}
print <<<END
</div>
</body>
</html>
END;
?>
