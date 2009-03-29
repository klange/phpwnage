{include file='header.tpl' full=true title="`$site.name` :: `$_PWNDATA.gallery_page_title` :: `$gallery.name` :: `$image.name`"}
{include file='subbar.tpl' subbar_right=$site.right_data subbar_left="<a href=\"index.php\">`$site.name`</a> &gt; <a href=\"gallery.php\">`$_PWNDATA.gallery_page_title`</a> &gt; <a href=\"gallery.php?do=view&amp;id=`$gallery.id`\">`$gallery.name`</a> &gt; `$image.name`"}
{include file='sidebar.tpl'}
<td valign="top">
<table class="borderless_table" width="100%">
      <tr>
        <td width="100%"><div class="panel">
    <table class="borderless_table" width="100%">
      <tr>
        <td class="pan_ul">&nbsp;</td>
        <td class="pan_um">
        <span class="pan_title_text">{$image.name}</span></td>
        <td class="pan_ur">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_ml">&nbsp;</td>
        <td class="pan_body" valign="top">
        <table class="mod_set"><tr>
{if $user.level >= $site.mod_rank || $user.id == $image.uid}
        {drawbutton action="javascript:if(confirm('`$_PWNDATA.forum.delete_confirm`')) window.location.href='gallery.php?do=delete_image&amp;id=`$image.id`';" button=$_PWNICONS.buttons.del_img title=$_PWNDATA.gallery.delete}
        {drawbutton action="gallery.php?do=edit_image&amp;id=`$image.id`" button=$_PWNICONS.buttons.edit_img title=$_PWNDATA.gallery.edit}
{/if}
{if $user.level >= $site.mod_rank}
        {drawbutton action="javascript:flipVisibility('movebox');" button=$_PWNICONS.buttons.move title=$_PWNDATA.gallery.move_image}
        <td style="border: 0px"><div id="movebox" style="display:none;">
                <script type="text/javascript">
                //<![CDATA[
{literal}
                function flipVisibility(what) {
                    if (document.getElementById(what).style.display != "none") {
                        document.getElementById(what).style.display = "none"
                    } else {
                        document.getElementById(what).style.display = "inline"
                    }
                }
{/literal}
                //]]>
                </script>
            <form action="gallery.php" method="post" style="display:inline;">
            <input type="hidden" name="action" value="move_image" />
            <input type="hidden" name="id" value="{$image.id}" />
            <select name="gallery">
{foreach item=gal from=$galleries}
                <option label="{$gal.name}" value="{$gal.id}">{$gal.name}</option>
{/foreach}
            </select>
            <input type="submit" value="{$_PWNDATA.gallery.move_image}" />
            </form>
        </div></td>
{/if}
        </tr></table>
        <table class="forum_base" width="100%">        
            <tr><td class="forum_topic_content" align="center"><b>{$image.name}</b></td></tr>
            <tr><td class="forum_topic_sig" align="center">{$_PWNDATA.gallery.uploaded_by}<a href="forum.php?do=viewprofile&amp;id={$uploader.id}">{$uploader.name}</a></td></tr>
            <tr><td class="forum_topic_sig" align="center"><img src="gallery.php?do=img&amp;i={$image.id}" alt="{$image.name}" /></td></tr>
            <tr><td class="forum_topic_sig" align="center">{$image.desc|bbdecode}</td></tr>
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