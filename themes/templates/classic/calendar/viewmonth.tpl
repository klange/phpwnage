{include file='header.tpl' full=true title="`$site.name` :: `$_PWNDATA.cal.name`"}
{include file='subbar.tpl' subbar_right=$_PWNDATA.cal.name subbar_left="<a href=\"index.php\">`$site.name`</a> > `$_PWNDATA.cal.name`"}
{include file='sidebar.tpl'}
<td valign="top">
<table class="borderless_table" width="100%">
      <tr>
        <td width="100%"><div class="panel">
    <table class="borderless_table" width="100%">
      <tr>
        <td class="pan_ul">&nbsp;</td>
        <td class="pan_um">
        <span class="pan_title_text">{$_PWNDATA.cal.name}</span></td>
        <td class="pan_ur">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_ml">&nbsp;</td>
        <td class="pan_body" valign="top">
            <div align="center" style="padding: 8px;">
                <span style="font-size: 18px;">{$month_year}</span><br />
                <span style="font-size: 12px;"><a href="calendar.php?view=viewmonth&amp;mon={$month_int-1}&amp;y={$year}">{$month_previous}</a>
                | <a href="calendar.php?view=viewmonth&amp;mon={$month_int+1}&amp;y={$year}">{$month_next}</a>
                </span>
            </div>
            <table border="1" style="border-collapse: collapse; border-width: 1; table-layout: fixed; border-color: #000000;" width="100%">
            <tr>
                <td class="forum_thread_title" align="center">{$_PWNDATA.cal.sunday}</td>
                <td class="forum_thread_title" align="center">{$_PWNDATA.cal.monday}</td>
                <td class="forum_thread_title" align="center">{$_PWNDATA.cal.tuesday}</td>
                <td class="forum_thread_title" align="center">{$_PWNDATA.cal.wednesday}</td>
                <td class="forum_thread_title" align="center">{$_PWNDATA.cal.thursday}</td>
                <td class="forum_thread_title" align="center">{$_PWNDATA.cal.friday}</td>
                <td class="forum_thread_title" align="center">{$_PWNDATA.cal.saturday}</td>
            </tr>
{foreach item=week from=$weeks}
                <tr>
{foreach item=day from=$week}
                    <td class="forum_topic_content">
                        <table class="borderless_table" width="100%">
                          <tr>
                            <td width="75%" height="24" style="border-style: none; border-width: 0px;" align="center">{$day.cell}</td>
                            <td width="25%" height="24" class="calendar_day" align="center">{$day.number}</td>
                          </tr>
                          <tr>
                            <td width="100%" colspan="2" height="60" 
                            style="border-style: none; border-width: medium; padding: 1px 1px 1px 1px" valign="top">{$day.content}</td>
                          </tr>
                        </table>
                    </td>
{/foreach}
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
