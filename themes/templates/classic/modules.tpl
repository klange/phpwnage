{include file='header.tpl' full=true title="`$site.name` :: Modules :: `$mod.title`"}
{include file='subbar.tpl' subbar_right=$mod.right subbar_left="<a href=\"index.php\">`$site.name`</a> :: `$_PWNDATA.modules_page_title` :: `$mod.title`"}
{include file='sidebar.tpl'}
<td valign="top">
<table class="borderless_table" width="100%">
      <tr>
        <td width="100%"><div class="panel">
    <table class="borderless_table" width="100%">
      <tr>
        <td class="pan_ul">&nbsp;</td>
        <td class="pan_um">
        <span class="pan_title_text">{$mod.title}</span></td>
        <td class="pan_um" align="right">
        <span class="pan_title_text">{$mod.right_inner}</span></td>
        <td class="pan_ur">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_ml">&nbsp;</td>
        <td class="pan_body" valign="top" colspan="2">{$page_content}</td>
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
</table>
</td></tr></table>
{include file='footer.tpl'}
