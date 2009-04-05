{include file='header.tpl' full=true title="`$site.name` :: `$_PWNDATA.cal.name` :: `$date_formatted`"}
{include file='subbar.tpl' subbar_right=$_PWNDATA.cal.name subbar_left="<a href=\"index.php\">`$site.name`</a> &gt; <a href=\"calendar.php\">`$_PWNDATA.cal.name`</a> &gt; <a href=\"calendar.php?view=viewmonth&amp;mon=`$month`&amp;y=`$year`\">`$month_name`</a> &gt; `$date_formatted`"}
{include file='sidebar.tpl'}
<td valign="top">
<table class="borderless_table" width="100%">
      <tr>
        <td width="100%"><div class="panel">
    <table class="borderless_table" width="100%">
      <tr>
        <td class="pan_ul">&nbsp;</td>
        <td class="pan_um">
        <span class="pan_title_text">{$date_formatted}</span></td>
        <td class="pan_ur">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_ml">&nbsp;</td>
        <td class="pan_body" valign="top">
            <span style="font-size: 18px">{$date_formatted}</span><br /><br />
            <table class="forum_base" width="100%">
{foreach item=event from=$events}
{assign var=poster value=$users[$event.user]}
                <tr><td colspan="2" class="forum_thread_title">
                    <b>{$event.title}</b> {$_PWNDATA.posted_by} <a href="forum.php?do=viewprofile&amp;id={$poster.id}">{$poster.name}</a>
                </td></tr>
                <tr>
                    <td class="forum_topic_content">{$event.content|bbdecode}</td>
                    <td class="forum_topic_content" width="200">{if $user.level > $site.mod_rank}
[<a href="calendar.php?view=edit&amp;e={$event.id}">{$_PWNDATA.admin.forms.edit}</a>] [<a href="calendar.php?view=del_event&amp;e={$event.id}">{$_PWNDATA.admin.forms.delete}</a>]{/if}</td>
                </tr>
{/foreach}
{if count($events) < 1}
                <tr><td class="forum_topic_content">
                    {$_PWNDATA.cal.no_events}
                    <a href="calendar.php?view=add&amp;day={$day_code}">{$_PWNDATA.cal.add_one}</a>
                </td></tr>
{/if}
            </table>
{if $user.level > $site.mod_rank}
            <br /><a href="calendar.php?view=add&amp;day={$day_code}">{$_PWNDATA.cal.event_add}</a>
{/if}
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
