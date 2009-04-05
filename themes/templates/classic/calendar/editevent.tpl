{include file='header.tpl' full=true title="`$site.name` :: `$_PWNDATA.cal.name` :: `$event.title`"}
{include file='subbar.tpl' subbar_right=$_PWNDATA.cal.name subbar_left="<a href=\"index.php\">`$site.name`</a> &gt; <a href=\"calendar.php\">`$_PWNDATA.cal.name`</a> &gt; `$event.title`"}
{include file='sidebar.tpl'}
<td valign="top">
<table class="borderless_table" width="100%">
      <tr>
        <td width="100%"><div class="panel">
    <table class="borderless_table" width="100%">
      <tr>
        <td class="pan_ul">&nbsp;</td>
        <td class="pan_um">
        <span class="pan_title_text">{$event.title}</span></td>
        <td class="pan_ur">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_ml">&nbsp;</td>
        <td class="pan_body" valign="top">
            <form method="post" action="calendar.php" name="form">
            <input type="hidden" name="action" value="edit_event" />
            <input type="hidden" name="event" value="{$event.id}" />
            <input type="hidden" name="date" value="{$event.day}" />
            <table class="forum_base" width="100%">
            <tr><td class="forum_topic_content">{$_PWNDATA.cal.event_name}</td>
            <td class="forum_topic_content"><input type="text" name="subj" size="51" style="width:100%" value="{$event.title|escape}"/></td></tr>
            <tr><td class="forum_topic_sig" colspan="2">{$_PWNDATA.cal.event_desc}</td></tr>
            <tr><td class="forum_topic_sig" colspan="2">
            {editor name='content' path='' preview=true advanced=false target=""}
            <textarea rows="11" name="content" id="content" style="width:100%;" cols="20" class="content_editor">{$event.content|escape}</textarea></td></tr>
            <tr><td class="forum_topic_sig" colspan="2">
            <input type="submit" value="{$_PWNDATA.cal.event_save}" name="sub" /></td></tr>
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
