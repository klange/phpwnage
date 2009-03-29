</div>
<table class="borderless_table" width="100%">
  <tr>
    <td class="foot_left"></td>

    <td class="foot_mid" align="center"><font class="foot_body_text">
        {$site.copyright} <a href="rss.php">{$_PWNICONS.tags.rss}</a><a href="mobile.php">{$_PWNICONS.tags.mobile}</a><a href="https://launchpad.net/phpwnage">{$_PWNICONS.tags.phpwnage}</a><a href="http://php.net">{$_PWNICONS.tags.php}</a>
    </font></td>
    <td class="foot_right"></td>
  </tr>
</table>
<div class="footer_body_text" style="font-size: 10px;" align="center">
{php}
    global $starttime, $smarty;
    $mtime = microtime();
    $mtime = explode(" ",$mtime);
    $mtime = $mtime[1] + $mtime[0];
    $endtime = $mtime;
    $totaltime = ($endtime - $starttime); 
    $smarty->assign('exectime',$totaltime);
{/php}
{$_PWNDATA.exec_a}{$exectime}{$_PWNDATA.exec_b}<br />
{$_PWNICONS.notice}
{loaderrors}
{if $show_errors}
{foreach item=error from=$errors}
{if $error.type == 1}
<div style="padding: 2px; margin: 4px; width: 800px; border: 1px solid #FF4B4B; background-color: #FFE1E1; text-align: left;">
{elseif $error.type == 2 or $error.type == 512}
<div style="padding: 2px; margin: 4px; width: 800px; border: 1px solid #FFBB65; background-color: #FFEFDA; text-align: left;">
{elseif $error.type == 8}
<div style="padding: 2px; margin: 4px; width: 800px; border: 1px solid #71FF69; background-color: #D7FFD5; text-align: left;">
{else}
<div style="padding: 2px; margin: 4px; width: 800px; border: 1px solid #7DCBFF; background-color: #D9F0FF; text-align: left;">
{/if}
{if $error.line > 0}<span style="font-family: monospace;">[{$error.time}]</span> {/if}<b>{$error.name}:</b> {$error.str}
{if $error.line > 0}<br /><span style="font-family: monospace;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
Line <b>{$error.line}</b> in <i>{$error.file}</i>{/if}
</div>
{/foreach}
{/if}
</div>
</body>
</html>
