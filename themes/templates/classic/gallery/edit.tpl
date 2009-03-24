{include file='header.tpl' full=true title="`$site.name` :: `$_PWNDATA.gallery_page_title` :: `$gallery.name` :: `$_PWNDATA.gallery.editing`"}
{include file='subbar.tpl' subbar_right=$site.right_data subbar_left="<a href=\"index.php\">`$site.name`</a> &gt; <a href=\"gallery.php\">`$_PWNDATA.gallery_page_title`</a> &gt; <a href=\"gallery.php?do=view&amp;id=`$gallery.id`\">`$gallery.name`</a> &gt; `$_PWNDATA.gallery.editing`"}
{include file='sidebar.tpl'}
<td valign="top">
<table class="borderless_table" width="100%">
      <tr>
        <td width="100%"><div class="panel">
    <table class="borderless_table" width="100%">
      <tr>
        <td class="pan_ul">&nbsp;</td>
        <td class="pan_um">
        <span class="pan_title_text">{$_PWNDATA.gallery.editing}</span></td>
        <td class="pan_ur">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_ml">&nbsp;</td>
        <td class="pan_body" valign="top">
        <form action="gallery.php" name="form" method="post">
            <input type="hidden" name="action" value="edit_image" />
            <input type="hidden" name="id" value="{$image.id}" />
            <table class="forum_base" width="100%">
                <tr>
                    <td class="forum_topic_content" width="200">{$_PWNDATA.gallery.image_name}</td>
                    <td class="forum_topic_content"><input type="text" name="name" style="width: 100%" value="{$image.name|escape}" /></td>
                </tr>
                <tr><td class="forum_topic_sig" colspan="2">{editor name='desc' preview=true path='' advanced=false target=""}
                    <textarea name="desc" style="width: 100%" rows="5" cols="80" class="content_editor">{$image.desc}</textarea></td></tr>
                <tr><td class="forum_topic_sig" colspan="2"><input type="submit" value="{$_PWNDATA.gallery.save_image}" /></td></tr>
            </table>
        </form>
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