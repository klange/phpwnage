{include file='header.tpl' full=true title="`$site.name` :: `$_PWNDATA.gallery_page_title`"}
{include file='subbar.tpl' subbar_right=$site.right_data subbar_left="<a href=\"index.php\">`$site.name`</a> &gt; <a href=\"gallery.php\">`$_PWNDATA.gallery_page_title`</a>"}
{include file='sidebar.tpl'}
<td valign="top">
<table class="borderless_table" width="100%">
      <tr>
        <td width="100%"><div class="panel">
    <table class="borderless_table" width="100%">
      <tr>
        <td class="pan_ul">&nbsp;</td>
        <td class="pan_um">
        <span class="pan_title_text">{$_PWNDATA.gallery.gallery_index}</span></td>
        <td class="pan_ur">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_ml">&nbsp;</td>
        <td class="pan_body" valign="top">
        <table class="forum_base" width="100%">
{foreach item=gallery from=$galleries}
        <tr>
            <td width="32" class="forum_topic_content" align="center" valign="middle">
{if $gallery.thumb != 0}
                <a href="gallery.php?do=view&amp;id={$gallery.id}"><img src="gallery.php?do=img&amp;type=thumb&amp;i={$galler.thumb}" alt="" /></a>
{else}
                <a href="gallery.php?do=view&amp;id={$gallery.id}">{$_PWNICONS.admin.images}</a>
{/if}
            </td>
            <td class="forum_topic_content"><a href="gallery.php?do=view&amp;id={$gallery.id}">{$gallery.name}</a><br />
            <i>{$gallery.desc}</i></td>
            <td width="50" class="forum_topic_content" align="center" valign="middle">{$img_counts[$gallery.id]}</td></tr>
{/foreach}
        </table>
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
</table>
</td></tr></table>
{include file='footer.tpl'}
