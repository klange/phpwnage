<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
<head>
<title>{$title}</title>
<meta http-equiv="Content-Language" content="en-us" />
<meta name="GENERATOR" content="PHPwnage" />
<meta http-equiv="Content-Type" content="text/html; charset=windows-1252" />
<meta name="keywords" content="PHPwnage, PHP, php, CMS, forum, Forum, news, calendar, Oasis-Games" />
{if file_exists("favicon.ico")}
<link rel="icon" href="favicon.ico" />
{/if}
<link rel="alternate" type="application/rss+xml" title="{$site.name}" href="/rss.php" />
{if $user.level < 1 || $user.rich_edit}
<script type="text/javascript" src="tiny_mce/tiny_mce.js"></script>
<script type="text/javascript">
//<![CDATA[{literal}
tinyMCE.init({
	theme : "advanced",
	mode : "textareas",
	plugins : "bbcode",
	editor_selector : "content_editor",
	theme_advanced_toolbar_location : "external",
	theme_advanced_buttons1 : "",
	theme_advanced_buttons2 : "",
	theme_advanced_buttons3 : "",
	theme_advanced_resize_horizontal : false,
	theme_advanced_resizing : true,
	theme_advanced_resizing_use_cookie : false,
	theme_advanced_path : false,
	theme_advanced_statusbar_location : "bottom",
	entity_encoding : "raw",
	add_unload_trigger : false,
	remove_linebreaks : false,
	inline_styles : false,
	relative_urls : false,
	convert_fonts_to_spans : false
});
{/literal}//]]>
</script>
<style type="text/css">
.mceExternalToolbar {ldelim}
    display: none !important;
{rdelim}
{else}
<style type="text/css">
{/if}
body {ldelim}
    background-image: url(themes/backgrounds/{$imageroot}.gif);
{rdelim}
</style>
<link rel="stylesheet" type="text/css" href="themes/styles/{$theme}/theme.css" />
</head>

<body>

<table class="borderless_table" width="100%">
  <tr>
    <td class="head_left">{if strlen($site.pheader) > 0}<a href="index.php"><img src="{$site.pheader}" alt="{$site.name}"/></a>{else}&nbsp;{/if}</td>
    <td class="head_mid">&nbsp;</td>
    <td class="head_right">&nbsp;</td>
  </tr>
</table>
<div class="main_body">
