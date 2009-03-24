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
</div>
</body>
</html>
