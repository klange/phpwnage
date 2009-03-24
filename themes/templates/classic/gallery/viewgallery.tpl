{include file='header.tpl' full=true title="`$site.name` :: `$_PWNDATA.gallery_page_title` :: `$gallery.name`"}
{include file='subbar.tpl' subbar_right=$site.right_data subbar_left="<a href=\"index.php\">`$site.name`</a> &gt; <a href=\"gallery.php\">`$_PWNDATA.gallery_page_title`</a> &gt; `$gallery.name`"}
{include file='sidebar.tpl'}
<td valign="top">
<table class="borderless_table" width="100%">
      <tr>
        <td width="100%"><div class="panel">
    <table class="borderless_table" width="100%">
      <tr>
        <td class="pan_ul">&nbsp;</td>
        <td class="pan_um">
        <span class="pan_title_text">{$_PWNDATA.gallery.viewing_gallery}</span></td>
        <td class="pan_ur">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_ml">&nbsp;</td>
        <td class="pan_body" valign="top">
        <table class="mod_set"><tr>
{if $user.level > $gallery.upload}
            {drawbutton action="gallery.php?do=upload_form&amp;gal=`$gallery.id`" button=$_PWNICONS.buttons.img_upload title=$_PWNDATA.gallery.upload_button}
{/if}
{if $page > 1}
            {drawbutton action="gallery.php?do=view&amp;id=`$gallery.id`&amp;p=`$page-1`" button=$_PWNICONS.buttons.img_upload title=$_PWNDATA.gallery.upload_button}
{/if}    
{if $totalPages > 1}
            {pager table=true url="gallery.php?do=view&amp;id=`$gallery.id`&amp;p=" page=$page total=$totalPages}
{/if}
{if $page < $totalPages}
            {drawbutton action="gallery.php?do=view&amp;id=`$gallery.id`&amp;p=`$page+1`" button=$_PWNICONS.buttons.img_upload title=$_PWNDATA.gallery.upload_button}
{/if}
        </tr></table>
        <table class="forum_base" width="100%">
{foreach item=image from=$images}
{assign var=uploader value=$users[$image.uid]}
        <tr>
            <td width="32" class="forum_topic_content" align="center" valign="middle" rowspan="2">
                <a href="image.php?do=view&amp;id={$image.id}"><img src="gallery.php?do=img&amp;type=thumb&amp;i={$image.id}" alt="" /></a>
            </td>
            <td class="forum_topic_content"><a href="gallery.php?do=image&amp;id={$image.id}"><b>{$image.name}</b></a></td>
            <td class="forum_topic_content" align="center" valign="middle" width="200">
                {$_PWNDATA.gallery.uploaded_by}<a href="forum.php?do=viewprofile&amp;id={$image.uid}">{$uploader.name}</a>
            </td>
        </tr>
        <tr>
            <td class="forum_topic_sig" colspan="2">{$image.desc|bbdecode}</td>
        </tr>
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