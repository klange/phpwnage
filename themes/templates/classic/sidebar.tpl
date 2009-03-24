<script type="text/javascript">
//<![CDATA[
function hideSideBar() {ldelim}
document.getElementById('sidebar').style.display = "none"
document.getElementById('sb').style.width = "0"
{rdelim}
//]]>
</script>
<table class="borderless_table" width="100%">
  <tr>
    <td id="sb" valign="top" width="200px">
	<div id="sidebar" class="sidebar">
    <table class="borderless_table" width="100%">
{foreach item=block from=$sidebar}
      <tr>
        <td width="100%"><div class="block">
    <table class="borderless_table" width="100%">
      <tr>
        <td class="block_ul">&nbsp;</td>
        <td class="block_um">
        <span class="block_title_text">{$block.title}</span></td>
        <td class="block_ur">&nbsp;</td>
      </tr>
      <tr>
        <td class="block_ml">&nbsp;</td>
        <td class="block_body" valign="top">{$block.content}</td>
        <td class="block_mr">&nbsp;</td>
      </tr>
      <tr>
        <td class="block_bl"></td>
        <td class="block_bm"></td>
        <td class="block_br"></td>
      </tr>
    </table></div>
        </td>
      </tr>
{/foreach}
	<tr>
	<td> <span style="font-size: 10px;"><a href="javascript:hideSideBar()">{$_PWNDATA.hide_sidebar}</a></span></td>
	</tr>
    </table></div>
</td>
