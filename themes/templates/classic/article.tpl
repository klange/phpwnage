{include file='header.tpl' full=true title="`$site.name` :: `$article.title`"}
{include file='subbar.tpl' subbar_right=$site.right_data subbar_left="<a href=\"index.php\">`$site.name`</a> > `$article.title`"}
{include file='sidebar.tpl'}
<td valign="top">
<table class="borderless_table" width="100%">
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
{if $has_comments}
  <tr>
    <td width="100%"><div class="panel">
        <table class="borderless_table" width="100%">
          <tr>
            <td class="pan_ul">&nbsp;</td>
            <td class="pan_um">
            <span class="pan_title_text">{$_PWNDATA.articles.comments}</span></td>
            <td class="pan_ur">&nbsp;</td>
          </tr>
          <tr>
            <td class="pan_ml">&nbsp;</td>
            <td class="pan_body" valign="top">
{if $showposter}
{editor name='content' path='' preview=false advanced=true target="forum.php?do=newreply&amp;id=`$topic.id`"}
            <form action="forum.php" method="post" name="form">
            <input type="hidden" name="action" value="new_reply" />
            <input type="hidden" name="topic" value="{$topic.id}" />
            <input type="hidden" name="user" value="{$user.id}" />
            <table class="forum_base" width="100%">
            <tr><td class="forum_topic_content">
            <textarea name="content" style="width: 95%;" rows="5" cols="80" class="content_editor"></textarea></td></tr>
            <tr><td class="forum_topic_sig"><input type="submit" name="sub" value="{$_PWNDATA.forum.submit_post}" /></td></tr>
            </table>
            </form>
{/if}
{if count($posts) > 0}
            <table class="forum_base" width="100%">
{foreach item=post from=$posts}
{assign var=author value=$users[$post.authorid]}
{if $author}
              <tr><td width="20%" class="glow" valign="top"><b>{$author.name}</b>
{if strlen($author.avatar) > 0}
<br /><img src="{$author.avatar|escape}">
{/if}
{else}
              <tr><td width="20%" class="glow" valign="top"><b>Guest</b>
{/if}
              </td><td class="forum_topic_content">{$post.content|bbdecode}</td></tr>
{/foreach}
              <tr><td colspan="2" class="forum_topic_content" align="center"><a href="forum.php?do=viewtopic&amp;id={$article.topicid}">{$_PWNDATA.articles.more_comments}</a></td></tr>
            </table>
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
{/if}
{if $user}{if $user.level > $site.mod_rank}
  <tr>
    <td width="100%"><div class="panel">
        <table class="borderless_table" width="100%">
          <tr>
            <td class="pan_ul">&nbsp;</td>
            <td class="pan_um">
            <span class="pan_title_text">{$_PWNDATA.articles.edita} {$article.title}</span></td>
            <td class="pan_ur">&nbsp;</td>
          </tr>
          <tr>
            <td class="pan_ml">&nbsp;</td>
            <td class="pan_body" valign="top">
            <form action="article.php?id={$article.id}" method="post">
            <input type="hidden" name="action" value="edit" />
            <table class="forum_base" width="100%">
            <tr><td class="forum_topic_sig"><textarea rows="8" name="content" style="width:100%;" cols="80">{$article.content|escape}</textarea></td></tr><tr>
            <td class="forum_topic_sig"><input name="title" type="text" value="{$article.title|escape}" style="width: 100%"/></td></tr>
            <tr><td class="forum_topic_sig"><input type="submit" value="{$_PWNDATA.articles.save}" /></td></tr></table></form>
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
