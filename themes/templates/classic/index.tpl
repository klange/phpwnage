{include file='header.tpl' full=true}
{assign var=last_update value=$site.last_updated|date_format:"%B %e, %Y (%l:%M%P %Z)"}
{include file='subbar.tpl' subbar_right=$site.right_data subbar_left="`$_PWNDATA.last_updated` `$last_update`"}
{include file='sidebar.tpl'}
<td valign="top">
<table class="borderless_table" width="100%">
{foreach item=article from=$news}
      <tr>
        <td width="100%"><div class="panel">
    <table class="borderless_table" width="100%">
      <tr>
        <td class="pan_ul">&nbsp;</td>
        <td class="pan_um">
        <span class="pan_title_text"><a href="article.php?id={$article.id}">{$article.title}</a></span></td>
        <td class="pan_um" align="right">
        <span class="pan_title_text">{$article.time_code|date_format:"%h %e, %Y"} - {$article.user}</span></td>
        <td class="pan_ur">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_ml">&nbsp;</td>
        <td class="pan_body" valign="top" colspan="2">{$article.content|bbdecodehtml}</td>
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
<tr><td><table><tr>{pager url="index.php?page=" page=$page_num total=$page_total}</tr></table></td></tr>
</table>
</td></tr></table>
{include file='footer.tpl'}
