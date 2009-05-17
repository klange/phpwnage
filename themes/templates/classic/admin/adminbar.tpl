  <tr>
    <td width="100%"><div class="panel">
        <table class="borderless_table" width="100%">
          <tr>
            <td class="pan_ul">&nbsp;</td>
            <td class="pan_um"><span class="pan_title_text">{$_PWNDATA.admin_page_title}</span></td>
            <td class="pan_ur">&nbsp;</td>
          </tr>
          <tr>
            <td class="pan_ml">&nbsp;</td>
            <td class="pan_body" valign="top">
            <table class="borderless_table" width="100%">
              <tr>
                <td width="10%" height="1" align="center">
                <a href="admin.php?view=news">{$_PWNICONS.admin.news}</a><br />
                <a href="admin.php?view=news">{$_PWNDATA.admin.groups.news}</a></td>
                <td width="10%" height="1" align="center">
                <a href="admin.php?view=forum">{$_PWNICONS.admin.forums}</a><br />
                <a href="admin.php?view=forum">{$_PWNDATA.admin.groups.forums}</a></td>
                <td width="10%" height="1" align="center">
                <a href="admin.php?view=blocks">{$_PWNICONS.admin.blocks}</a><br />
                <a href="admin.php?view=blocks">{$_PWNDATA.admin.groups.blocks}</a></td>
                <td width="10%" height="1" align="center">
                <a href="admin.php?view=members">{$_PWNICONS.admin.members}</a><br />
                <a href="admin.php?view=members">{$_PWNDATA.admin.groups.members}</a></td>
                <td width="10%" height="1" align="center">
                <a href="admin.php?view=pages">{$_PWNICONS.admin.pages}</a><br />
                <a href="admin.php?view=pages">{$_PWNDATA.admin.groups.pages}</a></td>
                <td width="10%" height="1" align="center">
                <a href="admin.php?view=images">{$_PWNICONS.admin.images}</a><br />
                <a href="admin.php?view=images">{$_PWNDATA.admin.groups.images}</a></td>
                {if $user.level >= $site.admin_rank}
                <td width="10%" height="1" align="center">
                <a href="admin.php?view=site_info">{$_PWNICONS.admin.siteinfo}</a><br />
                <a href="admin.php?view=site_info">{$_PWNDATA.admin.groups.site_info}</a></td>
                <td width="10%" height="1" align="center">
                <a href="admin.php?view=promo">{$_PWNICONS.admin.promos}</a><br />
                <a href="admin.php?view=promo">{$_PWNDATA.admin.groups.promo}</a></td>
                <td width="10%" height="1" align="center">
                <a href="admin.php?view=bans">{$_PWNICONS.admin.security}</a><br />
                <a href="admin.php?view=bans">{$_PWNDATA.admin.groups.bans}</a></td>
                {/if}
              </td>
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
