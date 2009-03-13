{php}
    global $smarty, $site_info, $_PWNDATA;
    $smarty->display('header.tpl');
    $smarty->assign('subbar_left',"{$_PWNDATA['last_updated']} " . date("F j, Y (g:ia T)", $site_info['last_updated']) . " <a href=\"?show=all\">[{$_PWNDATA['show_all']}]</a>");
    $smarty->assign('subbar_right',$site_info['right_data']);
    $smarty->display('subbar.tpl');
    $smarty->display('sidebar.tpl');
{/php}
<td valign="top">
<table class="borderless_table" width="100%">
{foreach item=article from=$news}
      <tr>
        <td width="100%"><div class="panel">
    <table class="borderless_table" width="100%">
      <tr>
        <td class="pan_ul">&nbsp;</td>
        <td class="pan_um">
        <span class="pan_title_text">{$article.title}</span></td>
        <td class="pan_um" align="right">
        <span class="pan_title_text">{$article.user}</span></td>
        <td class="pan_ur">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_ml">&nbsp;</td>
        <td class="pan_body" valign="top" colspan="2">{$article.content|bbdecode}</td>
        <td class="pan_mr">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_bl"></td>
        <td class="pan_bm" colspan="2"></td>
        <td class="pan_br"></td>
      </tr>
    </table></div>
        </td>
      </tr>
{/foreach}
<tr><td>{pager url="index.php?page=" page=$page_num total=$page_total}</td></tr>
</table>
</td></tr></table>
{php}global $smarty; $smarty->display('footer.tpl');{/php}
