{include file='header.tpl' full=true title="`$site.name` :: `$_PWNDATA.admin_page_title`"}
{include file='subbar.tpl' subbar_right=$site.right_data subbar_left="<a href=\"index.php\">`$site.name`</a> > `$_PWNDATA.admin_page_title`"}
<table class="borderless_table" width="100%">
  <tr>
    <td width="100%"><div class="panel">
        <table class="borderless_table" width="100%">
          <tr>
            <td class="pan_ul">&nbsp;</td>
            <td class="pan_um"><span class="pan_title_text">{$_PWNDATA.admin_page_title}</span></td>
            <td class="pan_ur">&nbsp;</td>
          </tr>
          <tr>
            <td class="pan_ml">&nbsp;</td>
            <td class="pan_body" valign="top">
                {$content}
            </td>
            <td class="pan_mr">&nbsp;</td>
          </tr>
          <tr>
            <td class="pan_bl"></td>
            <td class="pan_bm"></td>
            <td class="pan_br"></td>
          </tr>
        </table></div>
    </td>
  </tr>
  {include file='admin/adminbar.tpl'}
</table>
{include file='footer.tpl'}
