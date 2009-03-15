{include file='header.tpl' full=true title="`$site.name` :: `$page.display_name`"}
{include file='subbar.tpl' subbar_right=$site.right_data subbar_left="<a href=\"index.php\">`$site.name`</a> &gt; `$_PWNDATA.admin.forms.pages` &gt; `$page.display_name`"}
{if $page.showsidebar}{include file='sidebar.tpl'}{/if}
<td valign="top">
<table class="borderless_table" width="100%">
      <tr>
        <td width="100%"><div class="panel">
    <table class="borderless_table" width="100%">
      <tr>
        <td class="pan_ul">&nbsp;</td>
        <td class="pan_um">
        <span class="pan_title_text">{$page.display_name}</span></td>
        <td class="pan_um" align="right">
        <span class="pan_title_text">{$page.author}</span></td>
        <td class="pan_ur">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_ml">&nbsp;</td>
        <td class="pan_body" valign="top" colspan="2">{$page.content}</td>
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
{if $user}{if $user.level > $site.mod_rank}
      <tr>
        <td width="100%"><div class="panel">
    <table class="borderless_table" width="100%">
      <tr>
        <td class="pan_ul">&nbsp;</td>
        <td class="pan_um">
        <span class="pan_title_text">{$_PWNDATA.articles.edita} {$page.display_name}</span></td>
        <td class="pan_ur">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_ml">&nbsp;</td>
        <td class="pan_body" valign="top">
            <form action="pages.php?page={$page.name}" method="post">
            <input type="hidden" name="action" value="true" />
            <textarea rows="8" name="content" style="width:100%;" cols="80">{$page.content|escape}</textarea>
            <br /><input type="submit" value="{$_PWNDATA.articles.save_page}" /></form>
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
{/if}{/if}
</table>
</td></tr></table>
{include file='footer.tpl'}
