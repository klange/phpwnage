<?php
/*
	This file is part of PHPwnage (Global Includes, general functions)

	Copyright 2008 Kevin Lange <klange@oasis-games.com>

	PHPwnage is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	PHPwnage is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with PHPwnage. If not, see <http://www.gnu.org/licenses/>.

*/
session_start(); // Always ensure a session.
require "lang/enUS.php";
//require "icons.php"; // TODO: Move this to be theme-safe.

$_PWNVERSION['major'] = 1;
$_PWNVERSION['minor'] = 8;
$_PWNVERSION['extra'] = "b3";

$result = mysql_query("SELECT * FROM info", $db);
$site_info = mysql_fetch_array($result); // Get the site info, called by all pages, so why not?
function mse($source) {
	// Do we return the Real Escape String or the source?
	//return mysql_real_escape_string($source);
	return $source;
}
function isReadable($userLevel, $board) {
	$result = mysql_query("SELECT * FROM `boards` WHERE `id`=" .  $board);
	$brd = mysql_fetch_array($result);
	if ((int)$userLevel < (int)$brd['vis_level']) {
		return false;
	} else {
		return true;
	}
}
function isWriteableTopic($userLevel, $board) {
	$result = mysql_query("SELECT * FROM `boards` WHERE `id`=" .  $board);
	$brd = mysql_fetch_array($result);
	if ((int)$userLevel < (int)$brd['topic_level']) {
		return false;
	} else {
		return true;
	}
}
function isWriteable($userLevel, $board) {
	$result = mysql_query("SELECT * FROM `boards` WHERE `id`=" .  $board);
	$brd = mysql_fetch_array($result);
	if ((int)$userLevel < (int)$brd['post_level']) {
		return false;
	} else {
		return true;
	}
}
function getBoardName($bid) {
	$result = mysql_query("SELECT * FROM `boards` WHERE `id`=" .  $bid);
	$brd = mysql_fetch_array($result);
	return $brd['title'];
}
function getPostsInBoard($bid) {
	$result = mysql_query("SELECT `id` FROM `topics` WHERE `board`=" .  $bid);
	$total = 0;
	while ($top = mysql_fetch_array($result)) {
		$result2 = mysql_query("SELECT COUNT(*) FROM `posts` WHERE `topicid`=" .  $top['id']);
		$pc = mysql_fetch_array($result2);
		$total = $total + $pc['COUNT(*)'];
	}
	return $total;
}
function setTheme()
{
	global $user, $imageroot, $theme, $_PWNICONS, $_PWNDATA;
	if (!isset($user['color']) || $user['color'] == "")
	{
		$imageroot = "crystal"; // Default background.
	} else {
		$imageroot = $user['color'];
	}
	$themes = explode(",",$user['theme']);
	if (!isset($themes[0]) || $themes[0] == "")
	{
		$theme = "crystal"; // Default theme.
	} else {
		$theme = $themes[0];
	}
	if (!isset($themes[1]) || $themes[1] == "")
	{
		$icons = "tango"; // Default theme.
	} else {
		$icons = $themes[1];
	}
	$theme_exists = @include "icon_themes/$icons.php";
    if (!$theme_exists) {
	    include "icon_themes/tango.php";
	}
}
function drawButton($dowhat, $title, $button = "") {
    $post_content = <<<END
<td style="border: 0px">
	<table class="forum_button">
	<tr>
    <td class="but_left"></td>
    <td class="but_mid"><font class="forum_button_text">
END;
    $post_content = $post_content . "<a href=\"$dowhat\">$button$title</a>";
    $post_content = $post_content . <<<END
</font></td>
    <td class="but_right"></td>
  </tr>
</table>
</td>
END;
    return $post_content;
}
function getRankName($level,$site_info,$posts) {
	// First we'll check if there is a custom rank available.
    $results = mysql_query("SELECT * FROM `ranks` WHERE `value`=$level AND `posts`=-1");
    if ($rank = mysql_fetch_array($results)) {
	    return $rank['name'];
    } else {
	    // Then, if our user has a post count within a specific range, use it.
	    $results2 = mysql_query("SELECT * FROM `ranks` WHERE `value`=-1 AND `posts`<=" . $posts);
	    if ($rank = mysql_fetch_array($results2)) {
	    return $rank['name'];
	    } else {
	    // Otherwise, just use the standard title for their rank.
	    if ($level < $site_info['mod_rank']) {
		    return "User"; }
	    if ($level >= $site_info['mod_rank'] && $level < $site_info['admin_rank']) {
		    return "Moderator"; }
	    if ($level >= $site_info['admin_rank']) {
		    return "Admin"; }
	    }
    }
}
function bbJava($stuff) {
    // Usage: Takes in Java code and spits out nicely highlighted code in a box
    $stuff = str_replace("<br />","\n",$stuff);
    $stuff = str_replace("\\\"", "\"",$stuff);
    $stuff = preg_replace("/(\/\/)(.*?)(\n+?)/si","<font style='color: #00AA00'>$1$2</font>$3",$stuff);
    $stuff = preg_replace("/(\")(.*?)(\")/si","<font style='color: #AA0000'>$1$2$3</font>",$stuff);
    $stuff = preg_replace("/(\/\*)([\s\S]*?)(\*\/)/si","<font style='color: #00AA00'>$1$2$3</font>",$stuff);
    $keywords = array("abstract","continue","for","new","switch","assert","default","goto",
    "package","synchronized","boolean","do","if","private","this","break",
    "double","implements","protected","throw","byte","else","import","public",
    "throws","case","enum","instanceof","return","transient","catch","extends",
    "int","short","try","char","final","interface","static","void","class",
    "finally","long","strictfp","volatile","const","float","native","super","while");
    $keywordsa = $keywords;
    foreach ($keywordsa as $word) {
        $word2 = "<font style=\"color: #0000AA\">" . $word . "</font>";
        $stuff = sim_rep2($word,$word2,$stuff);
    }
    unset($word);
    //$stuff = str_replace($keywords, $keywordsa, $stuff);
    $stuff = "<font style=\"font-family: monospaced;\"><b>Java:</b></font><div style=\"background-color: #FFFFFF; border: 1px #000000 solid; overflow-x: scroll;\"><font style=\"font-family: monospaced;\"><pre>" . $stuff . "</pre></font></div>";
    return $stuff;
}
function bbCSharp($stuff) {
    // Usage: Takes in C# code and spits out nicely highlighted code in a box
    $stuff = str_replace("<br />","\n",$stuff);
    $stuff = str_replace("\\\"", "\"",$stuff);
    $stuff = preg_replace("/(\/\/)(.*?)(\n+?)/si","<font style='color: #00AA00'>$1$2</font>$3",$stuff);
    $stuff = preg_replace("/(\")(.*?)(\")/si","<font style='color: #AA0000'>$1$2$3</font>",$stuff);
    $stuff = preg_replace("/(\/\*)([\s\S]*?)(\*\/)/si","<font style='color: #00AA00'>$1$2$3</font>",$stuff);
    $keywords = array("abstract", "as", "base", "bool", "break", "byte", "case", "catch", "char", "checked", "class",
    "const", "continue", "decimal", "default", "delegate ", "do", "double", "else", "enum", "event", "explicit",
    "extern", "false", "finally", "fixed", "float", "for", "foreach", "goto", "if", "implicit", "in", "int",
    "interface", "internal", "is", "lock", "long", "namespace", "new", "null", "object", "operator", "out",
    "override", "params", "private", "protected", "public", "readonly", "ref", "return", "sbyte", "sealed",
    "short", "sizeof", "stackalloc", "static", "string", "struct", "switch", "this", "throw", "true", "try",
    "typeof", "uint", "ulong", "unchecked", "unsafe", "ushort", "using", "virtual", "void", "volatile", "while",
    "add", "alias", "get", "global", "partial", "remove", "set", "value", "where", "yield");
    $keywordsa = $keywords;
    foreach ($keywordsa as $word) {
        $word2 = "<font style=\"color: #0000AA\">" . $word . "</font>";
        $stuff = sim_rep2($word,$word2,$stuff);
    }
    unset($word);
    //$stuff = str_replace($keywords, $keywordsa, $stuff);
    $stuff = "<font style=\"font-family: monospaced;\"><b>C#:</b></font><div style=\"background-color: #FFFFFF; border: 1px #000000 solid; overflow-x: scroll;\"><font style=\"font-family: monospaced;\"><pre>" . $stuff . "</pre></font></div>";
    return $stuff;
}
function genericCode($stuff) {
    $stuff = str_replace("<br />","\n",$stuff);
    $stuff = str_replace("\\\"", "\"",$stuff);
    $stuff = "<font style=\"font-family: monospaced;\"><b>Code:</b></font><div style=\"background-color: #FFFFFF; border: 1px #000000 solid; overflow-x: scroll;\"><font style=\"font-family: monospaced;\"><pre>" . $stuff . "</pre></font></div>";
    return $stuff;
}
function makeURL($link, $title) {
    $link = str_replace("&", "&amp;", $link);
    return "<a href=\"$link\">$title</a>";
}
function makeIMG($link) {
    $link = str_replace("&", "&amp;", $link);
    return "<img alt=\"forum image\" border=\"0\" src=\"$link\" />";
}
function pCount($topic) {
    global $_PWNDATA;
    $results = mysql_query("SELECT COUNT(*) FROM `posts` WHERE `topicid`=" . $topic);
    $res = mysql_fetch_array($results);
    $comments = $res['COUNT(*)'] - 1;
    if ($comments == 0) {
        return $_PWNDATA['articles']['no_comments'];
    } else if ($comments == 1) {
        return $_PWNDATA['articles']['one_comment'];
    } else {
        return $comments . " " . $_PWNDATA['articles']['comment_count'];
    }
}
function BBDecode($content,$allowhtml = false) {
    if (!$allowhtml) {
        $content = str_replace("<","&lt;",$content); // Kill HTML in posts
        $content = str_replace(">","&gt;",$content);
    } else {
        $content = str_replace("<br>","<br />",$content);
        $order   = array("\r\n<br />", "\n<br />", "\r<br />");
        $content = str_replace($order,"\n",$content); // line break
    }
    $order   = array("\r\n", "\n", "\r");
    $content = str_replace($order,"<br />",$content); // line break
    // Standard bbCode replacements follow
    $content = str_replace("[site_url]",$site_info['url'],$content);
    $content = preg_replace("/(\[url\])(.+?)(\[\/\])(.+?)(\[\/url\])/sie","makeURL('$2','$4')",$content);
    $content = preg_replace("/(\[url=)(.+?)(\])(.+?)(\[\/url\])/sie","makeURL('$2','$4')",$content);
    $content = preg_replace("/(\[so\])(.+?)(\[\/so\])/si","<s>$2</s>",$content);
    $content = preg_replace("/(\[urls\])(.+?)(\[\/urls\])/sie","makeURL('$2','<b>$2</b>')",$content);
    $content = preg_replace("/(\[pcount\])(.+?)(\[\/pcount\])/sie","pCount($2)",$content);
    $content = preg_replace("/(\[u\])(.+?)(\[\/u\])/si","<u>$2</u>",$content);
    $content = preg_replace("/(\[i\])(.+?)(\[\/i\])/si","<i>$2</i>",$content);
    $content = preg_replace("/(\[b\])(.+?)(\[\/b\])/si","<b>$2</b>",$content);
    $content = preg_replace("/(\[java\])(.+?)(\[\/java\])/sie","bbJava('$2')",$content);
    $content = preg_replace("/(\[csharp\])(.+?)(\[\/csharp\])/sie","bbCSharp('$2')",$content);
    $content = preg_replace("/(\[code\])(.+?)(\[\/code\])/sie","genericCode('$2')",$content);
    $content = preg_replace("/(\[url=)(.+?)(\])(.+?)(\[\/url\])/sie","makeURL('$2','$4')",$content);
    $content = preg_replace("/(\[url\])(.+?)(\[\/url\])/sie","makeURL('$2','$2')",$content);
    $content = preg_replace("/(\[img\])(.+?)(\[\/img\])/sie","makeIMG('$2')",$content);
    $content = preg_replace("/(\[list\])(.+?)(\[\/list\])/si","<ul>$2</ul>",$content);
    $content = preg_replace("/(\[num\])(.+?)(\[\/num\])/si","<ol>$2</ol>",$content);
    $content = str_replace("[*]","<li>",$content); // A list item
    $content = preg_replace("/(\[dict\])(.+?)(\[\/dict\])/si","<dl>$2</dl>",$content);
    $content = str_replace("[word]","<dt>",$content); // Definition word
    $content = str_replace("[def]","<dd>",$content); // Definition
    $content = preg_replace("/(\[size=)([0-9]+)(\])(.+?)(\[\/size\])/si","<font size=\"$2\">$4</font>",$content);
    $content = preg_replace("/(\[ptsize=)([0-9]+)(\])(.+?)(\[\/ptsize\])/si","<font style=\"font-size: $2pt\">$4</font>",$content);
    $content = preg_replace("/(\[pxsize=)([0-9]+)(\])(.+?)(\[\/pxsize\])/si","<font style=\"font-size: $2px\">$4</font>",$content);
    $content = preg_replace("/(\[scroll=)(.+?)(\])(.+?)(\[\/scroll\])/si","<marquee direction=\"$2\">$4</marquee>",$content);
    $content = preg_replace("/(\[scroll\])(.+?)(\[\/scroll\])/si","<marquee>$2</marquee>",$content);
    $content = preg_replace("/(\[quote\])(.+?)(\[\/quote\])/si","<div class=\"quote\"><font class=\"forum_quote\">$2</font></div>",$content);
    $content = preg_replace("/(\[hide\])(.+?)(\[\/hide\])/si","<div><div><input value=\"Show\" onclick=\"if (this.parentNode.parentNode.getElementsByTagName('div')[1].getElementsByTagName('div')[0].style.display != '') { this.parentNode.parentNode.getElementsByTagName('div')[1].getElementsByTagName('div')[0].style.display = '';this.innerText = ''; this.value = 'Hide'; } else { this.parentNode.parentNode.getElementsByTagName('div')[1].getElementsByTagName('div')[0].style.display = 'none'; this.innerText = ''; this.value = 'Show'; }\" type=\"button\"></div><div class=\"alt2\" style=\"border: 1px inset ; margin: 0px; padding: 6px;\"><div style=\"display: none;\">$2</div></div></div>",$content);
    $content = preg_replace("/(\[color=)(.+?)(\])(.+?)(\[\/color\])/si","<font style=\"color: #$2\">$4</font>",$content);
    $content = preg_replace("/(\[youtube\])(.+?)(\[\/youtube\])/si","<object width=\"425\" height=\"350\"><param name=\"movie\" value=\"http://www.youtube.com/v/$2\"></param><param name=\"wmode\" value=\"transparent\"></param><embed src=\"http://www.youtube.com/v/$2\" type=\"application/x-shockwave-flash\" wmode=\"transparent\" width=\"425\" height=\"350\"></embed></object>",$content);
    $content = preg_replace("/(\[gallery-thumb\])(.+?)(\[\/gallery-thumb\])/si","<a href=\"gallery.php?do=image&amp;id=$2\"><img src=\"gallery.php?do=img&amp;type=thumb&amp;i=$2\" alt=\"gallery image\" /></a>",$content);
    // Smiles are stored in MySQL
    $smilesSet = mysql_query("SELECT * FROM `smileys`");
    while ($smile = mysql_fetch_array($smilesSet)) {
        $content = str_replace($smile['code'],"<img alt=\"{$smile['name']}\" src=\"smiles/" . $smile['image'] . "\" />",$content);
    }
    // Censorship
    $censor_list = array("ass", "bitch", "bastard", "cunt", "cock", "shit", "damn", "fuck", "fucker", "fucking");
    // Edit this to censor other words. Uses a fairly nice system to ensure words like bass, etc aren't censored.
    // However, things like "---hole" won't be censored. If you really care, add these words.
    foreach ($censor_list as $cen) {
        $content = sim_rep($cen,"-censored-",$content);
        $content = sim_rep($cen . "es","-censored-",$content); // Plural forms (I have no experience in regular expressions
        $content = sim_rep($cen . "s","-censored-",$content); // So we'll do this the old fashioned way...
    }
    return $content;
}
function sim_rep($search, $replace, $subject) {
    return preg_replace('/[a-zA-Z]+/e', 'strtolower(\'\0\') == \'' . $search . '\' ? \'' . $replace . '\' : \'\0\';', $subject);
}
function sim_rep2($search, $replace, $subject) {
    return preg_replace('/[a-zA-Z]+/e', '\'\0\' == \'' . $search . '\' ? \'' . $replace . '\' : \'\0\';', $subject);
}
function postCount($userID) {
	// Get a user's post count by ID.
	$results = mysql_query("SELECT COUNT(*) FROM `posts` WHERE `authorid`=" . $userID);
	$counter = mysql_fetch_array($results);
	return $counter['COUNT(*)'];
}
function printPoster($where) {
	// Print the posting tool buttons
    global $_PWNDATA, $_PWNICONS;
    $return = <<<END
<script type="text/javascript">
//<![CDATA[

function addCode(code,codeclose) {
var IE = document.all?true:false;
if (IE) {
    var element = document.form.$where;
    if( document.selection ){
	    var range = document.selection.createRange();
	    var stored_range = range.duplicate();
	    stored_range.moveToElementText( element );
	    stored_range.setEndPoint( 'EndToEnd', range );
	    element.selectionStart = stored_range.text.length - range.text.length;
	    element.selectionEnd = element.selectionStart + range.text.length;
    }
}

var Text = document.form.$where.value;
var selectedText = Text.substring(document.form.$where.selectionStart, document.form.$where.selectionEnd);
var beforeSelected = Text.substring(0,document.form.$where.selectionStart);
var afterSelected = Text.substring(document.form.$where.selectionEnd,Text.length);
document.form.$where.value = beforeSelected + code + selectedText + codeclose + afterSelected;
}
function setPreview() {
var Text = document.form.$where.value;
Text = Text.replace(/\\n/g,"!NL!");
frames['previewbox'].location.href = 'forum.php?do=preview&a=' + Text;
}
function addSize(sizeToAdd) {
document.form.$where.rows = document.form.$where.rows + sizeToAdd;
}
//]]>
</script>
<iframe name="previewbox" width="100%" style="border: 0px;" height="0px" id="previewbox"></iframe>
END;
    $smilesSet = mysql_query("SELECT * FROM `smileys`");
    $return = $return . "<table class=\"mod_set\"><tr><td colspan=\"10\"><b>{$_PWNDATA['poster']['smileys']}:</b> ";
    while ($smile = mysql_fetch_array($smilesSet)) {
        $return = $return . "<img src=\"smiles/" . $smile['image'] . "\" alt=\"" . $smile['code'] . "\" onclick=\"addCode('" . $smile['code'] . "','')\" />";
    }
    $return = $return . "</td></tr><tr>";
    $return = $return . drawButton("javascript:addCode('[b]','[/b]')","<b>{$_PWNDATA['poster']['bold']}</b>",$_PWNICONS['buttons']['editor']['bold']) . "\n";
    $return = $return . drawButton("javascript:addCode('[u]','[/u]')","<u>{$_PWNDATA['poster']['underline']}</u>",$_PWNICONS['buttons']['editor']['underline']) . "\n";
    $return = $return . drawButton("javascript:addCode('[i]','[/i]')","<i>{$_PWNDATA['poster']['italic']}</i>",$_PWNICONS['buttons']['editor']['italic']) . "\n";
    $return = $return . drawButton("javascript:addCode('[so]','[/so]')","<s>{$_PWNDATA['poster']['strike']}</s>",$_PWNICONS['buttons']['editor']['strike']) . "\n";
    $return = $return . drawButton("javascript:addCode('[color='+prompt('{$_PWNDATA['poster']['hex']}:','RRGGBB')+']','[/color]')","{$_PWNDATA['poster']['color']}",$_PWNICONS['buttons']['editor']['color']) . "\n";
    $return = $return . drawButton("javascript:addCode('[img]'+prompt('{$_PWNDATA['poster']['img_url']}:','http://')+'[/img]','')","{$_PWNDATA['poster']['image']}",$_PWNICONS['buttons']['editor']['img']) . "\n";
    $return = $return . drawButton("javascript:addCode('[url='+prompt('{$_PWNDATA['poster']['link_url']}:','http://')+']'+prompt('Link Title:','')+'[/url]','')","{$_PWNDATA['poster']['link']}",$_PWNICONS['buttons']['editor']['link']) . "\n";
    $return = $return . drawButton("javascript:setPreview()","{$_PWNDATA['poster']['preview']}") . "\n";
    $return = $return . drawButton("javascript:addSize(2)","\/") . "\n";
    $return = $return . drawButton("javascript:addSize(-2)","/\\") . "\n";
    $return = $return . "</tr></table>";
    return $return;
}
function getDay($timecode) {
	// Get the current day for the Calendar from a timecode
    $daytime = date("d,m,y",$timecode);
    return $daytime;
}
function printPosterMini($where, $topID) {
	// Print the posting tools in a smaller package.
    global $_PWNDATA, $_PWNICONS;
    $return = <<<END
<script type="text/javascript">
//<![CDATA[
function addCode(code,codeclose) {
var IE = document.all?true:false;
if (IE) {
    var element = document.form.$where;
    if( document.selection ){
	    var range = document.selection.createRange();
	    var stored_range = range.duplicate();
	    stored_range.moveToElementText( element );
	    stored_range.setEndPoint( 'EndToEnd', range );
	    element.selectionStart = stored_range.text.length - range.text.length;
	    element.selectionEnd = element.selectionStart + range.text.length;
    }
}
var Text = document.form.$where.value;
var selectedText = Text.substring(document.form.$where.selectionStart, document.form.$where.selectionEnd);
var beforeSelected = Text.substring(0,document.form.$where.selectionStart);
var afterSelected = Text.substring(document.form.$where.selectionEnd,Text.length);
document.form.$where.value = beforeSelected+code+selectedText+codeclose+afterSelected;
}
function addSize(sizeToAdd) {
document.form.$where.rows = document.form.$where.rows + sizeToAdd;
}
//]]>
</script>
END;
    $smilesSet = mysql_query("SELECT * FROM `smileys`");
    $return = $return . "<table class=\"mod_set\"><tr><td colspan=\"10\"><b>{$_PWNDATA['poster']['smileys']}:</b> ";
    while ($smile = mysql_fetch_array($smilesSet)) {
        $return = $return . "<img src=\"smiles/" . $smile['image'] . "\" alt=\"" . $smile['code'] . "\" onclick=\"addCode('" . $smile['code'] . "','')\" />";
    }
    $return = $return . "</td></tr><tr>";
    $return = $return . drawButton("javascript:addCode('[b]','[/b]')","<b>{$_PWNDATA['poster']['bold']}</b>",$_PWNICONS['buttons']['editor']['bold']) . "\n";
    $return = $return . drawButton("javascript:addCode('[u]','[/u]')","<u>{$_PWNDATA['poster']['underline']}</u>",$_PWNICONS['buttons']['editor']['underline']) . "\n";
    $return = $return . drawButton("javascript:addCode('[i]','[/i]')","<i>{$_PWNDATA['poster']['italic']}</i>",$_PWNICONS['buttons']['editor']['italic']) . "\n";
    $return = $return . drawButton("javascript:addCode('[so]','[/so]')","<s>{$_PWNDATA['poster']['strike']}</s>",$_PWNICONS['buttons']['editor']['strike']) . "\n";
    $return = $return . drawButton("javascript:addCode('[color='+prompt('{$_PWNDATA['poster']['hex']}:','RRGGBB')+']','[/color]')","{$_PWNDATA['poster']['color']}",$_PWNICONS['buttons']['editor']['color']) . "\n";
    $return = $return . drawButton("javascript:addCode('[img]'+prompt('{$_PWNDATA['poster']['img_url']}:','http://')+'[/img]','')","{$_PWNDATA['poster']['image']}",$_PWNICONS['buttons']['editor']['img']) . "\n";
    $return = $return . drawButton("javascript:addCode('[url='+prompt('{$_PWNDATA['poster']['link_url']}:','http://')+']'+prompt('Link Title:','')+'[/url]','')","{$_PWNDATA['poster']['link']}",$_PWNICONS['buttons']['editor']['link']) . "\n";
    $return = $return . drawButton("javascript:addSize(2)","\/") . "\n";
    $return = $return . drawButton("javascript:addSize(-2)","/\\") . "\n";
    $return = $return . drawButton("forum.php?do=newreply&amp;id=" . $topID,$_PWNDATA['poster']['go_advanced']) . "\n";
    $return = $return . "</tr></table>";
    return $return;
}
function printPosterEditor($where, $pid) {
	// Print the posting tools in a smaller package.
	$what = "_" . $pid;
    global $_PWNDATA, $_PWNICONS;
    $return = <<<END
<script type="text/javascript">
//<![CDATA[
function addCode$what(code,codeclose) {
var IE = document.all?true:false;
if (IE) {
    var element = document.form.$where;
    if( document.selection ){
	    var range = document.selection.createRange();
	    var stored_range = range.duplicate();
	    stored_range.moveToElementText( element );
	    stored_range.setEndPoint( 'EndToEnd', range );
	    element.selectionStart = stored_range.text.length - range.text.length;
	    element.selectionEnd = element.selectionStart + range.text.length;
    }
}
var Text = document.form$what.$where.value;
var selectedText = Text.substring(document.form$what.$where.selectionStart, document.form$what.$where.selectionEnd);
var beforeSelected = Text.substring(0,document.form$what.$where.selectionStart);
var afterSelected = Text.substring(document.form$what.$where.selectionEnd,Text.length);
document.form$what.$where.value = beforeSelected+code+selectedText+codeclose+afterSelected;
}
function addSize$what(sizeToAdd) {
document.form$what.$where.rows = document.form$what.$where.rows + sizeToAdd;
}
//]]>
</script>
END;
    $smilesSet = mysql_query("SELECT * FROM `smileys`");
    $return = $return . "<table class=\"mod_set\"><tr><td colspan=\"10\"><b>{$_PWNDATA['poster']['smileys']}:</b> ";
    while ($smile = mysql_fetch_array($smilesSet)) {
        $return = $return . "<img src=\"smiles/" . $smile['image'] . "\" alt=\"" . $smile['code'] . "\" onclick=\"addCode$what('" . $smile['code'] . "','')\" />";
    }
    $return = $return . "</td></tr><tr>";
        $return = $return . drawButton("javascript:addCode$what('[b]','[/b]')","<b>{$_PWNDATA['poster']['bold']}</b>",$_PWNICONS['buttons']['editor']['bold']) . "\n";
    $return = $return . drawButton("javascript:addCode$what('[u]','[/u]')","<u>{$_PWNDATA['poster']['underline']}</u>",$_PWNICONS['buttons']['editor']['underline']) . "\n";
    $return = $return . drawButton("javascript:addCode$what('[i]','[/i]')","<i>{$_PWNDATA['poster']['italic']}</i>",$_PWNICONS['buttons']['editor']['italic']) . "\n";
    $return = $return . drawButton("javascript:addCode$what('[so]','[/so]')","<s>{$_PWNDATA['poster']['strike']}</s>",$_PWNICONS['buttons']['editor']['strike']) . "\n";
    $return = $return . drawButton("javascript:addCode$what('[color='+prompt('{$_PWNDATA['poster']['hex']}:','RRGGBB')+']','[/color]')","{$_PWNDATA['poster']['color']}",$_PWNICONS['buttons']['editor']['color']) . "\n";
    $return = $return . drawButton("javascript:addCode$what('[img]'+prompt('{$_PWNDATA['poster']['img_url']}:','http://')+'[/img]','')","{$_PWNDATA['poster']['image']}",$_PWNICONS['buttons']['editor']['img']) . "\n";
    $return = $return . drawButton("javascript:addCode$what('[url='+prompt('{$_PWNDATA['poster']['link_url']}:','http://')+']'+prompt('Link Title:','')+'[/url]','')","{$_PWNDATA['poster']['link']}",$_PWNICONS['buttons']['editor']['link']) . "\n";
    $return = $return . drawButton("javascript:addSize$what(2)","\/") . "\n";
    $return = $return . drawButton("javascript:addSize$what(-2)","/\\") . "\n";
    $return = $return . drawButton("forum.php?do=editreply&amp;id=" . $pid,$_PWNDATA['poster']['go_advanced']) . "\n";
    $return = $return . "</tr></table>";
    return $return;
}
function themeList($selected)
{
	$themeList = "<select name=\"theme\">";
	$myDirectory = opendir("."); // Open root
	while($entryName = readdir($myDirectory)) {
		$dirArray[] = $entryName; // Get our list of files
	}
	closedir($myDirectory); // Close the directory
	sort($dirArray); // Sort the array
	$indexCount	= count($dirArray); // Count...
	for($index=0; $index < $indexCount; $index++) {
		if (substr("$dirArray[$index]", 0, 1) != "."){
			if (strstr($dirArray[$index],".css")) {
				$themeName = str_replace(".css","",$dirArray[$index]);
				if ($themeName == $selected) {
					$themeList = $themeList . "\n<option value=\"" . $themeName . "\" selected=\"selected\">" . $themeName . "</option>";
				} else {
					$themeList = $themeList . "\n<option value=\"" . $themeName . "\">" . $themeName . "</option>";
				}
			}
		}
	}
	$themeList = $themeList . "</select>";
	return $themeList;
}
function iconsList($selected)
{
	$themeList = "<select name=\"icons\">";
	$myDirectory = opendir("icon_themes"); // Open root
	while($entryName = readdir($myDirectory)) {
		$dirArray[] = $entryName; // Get our list of files
	}
	closedir($myDirectory); // Close the directory
	sort($dirArray); // Sort the array
	$indexCount	= count($dirArray); // Count...
	for($index=0; $index < $indexCount; $index++) {
		if (substr("$dirArray[$index]", 0, 1) != "."){
			if (strstr($dirArray[$index],".php")) {
				$themeName = str_replace(".php","",$dirArray[$index]);
				if ($themeName == $selected) {
					$themeList = $themeList . "\n<option value=\"" . $themeName . "\" selected=\"selected\">" . $themeName . "</option>";
				} else {
					$themeList = $themeList . "\n<option value=\"" . $themeName . "\">" . $themeName . "</option>";
				}
			}
		}
	}
	$themeList = $themeList . "</select>";
	return $themeList;
}
function colorList($selected)
{
	$themeList = "<select name=\"color\" style=\"height: 3ex\">";
	$myDirectory = opendir("colors"); // Open colors folder
	while($entryName = readdir($myDirectory)) {
		$dirArray[] = $entryName; // Get our list of files
	}
	closedir($myDirectory); // Close the directory
	sort($dirArray); // Sort the array
	$indexCount	= count($dirArray); // Count...
	for($index=0; $index < $indexCount; $index++) {
		if (substr("$dirArray[$index]", 0, 1) != "."){
			if (strstr($dirArray[$index],".gif")) {
				$themeName = str_replace(".gif","",$dirArray[$index]);
				if ($themeName == $selected) {
					$themeList = $themeList . "\n<option style=\"height: 30; background: url('colors/" . $dirArray[$index] . "'); background-repeat: no-repeat;\" value=\"" . $themeName . "\" selected=\"selected\">" . $themeName . "</option>";
				} else {
					$themeList = $themeList . "\n<option style=\"height: 30; background: url('colors/" . $dirArray[$index] . "'); background-repeat: no-repeat;\" value=\"" . $themeName . "\">" . $themeName . "</option>";
				}
			}
		}
	}
	$themeList = $themeList . "</select>";
	return $themeList;
}

function drawBlock($functitle, $funcright, $funccont) {
    print makeBlock($functitle, $funcright, $funccont);
}
function makeBlock($functitle, $funcright, $funccont) {
    // Page Body
    $output = <<<END
      <tr>
        <td width="100%"><div class="panel">
    <table class="borderless_table" width="100%">
      <tr>
        <td class="pan_ul">&nbsp;</td>
        <td class="pan_um">
        <font class="pan_title_text">
END;
    $output = $output . $functitle;
    $output = $output . <<<END
	</font></td>
        <td class="pan_um" align="right">
        <font class="pan_title_text">
END;
    $output = $output . $funcright;
    $output = $output . <<<END
	</font></td>
        <td class="pan_ur">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_ml">&nbsp;</td>
        <td class="pan_body" valign="top" colspan="2">
END;
    $output = $output . $funccont;
    $output = $output . <<<END
	</td>
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
END;
    return $output;
}
function makeBlockSA($functitle, $funcright, $funccont) {
    // Page Body
    $output = <<<END
    <div class="panel">
    <table class="borderless_table" width="100%">
      <tr>
        <td class="pan_ul">&nbsp;</td>
        <td class="pan_um">
        <font class="pan_title_text">
END;
    $output = $output . $functitle;
    $output = $output . <<<END
	</font></td>
        <td class="pan_um" align="right">
        <font class="pan_title_text">
END;
    $output = $output . $funcright;
    $output = $output . <<<END
	</font></td>
        <td class="pan_ur">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_ml">&nbsp;</td>
        <td class="pan_body" valign="top" colspan="2">
END;
    $output = $output . $funccont;
    $output = $output . <<<END
	</td>
        <td class="pan_mr">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_bl"></td>
        <td class="pan_bm" colspan="2"></td>
        <td class="pan_br"></td>
      </tr>
    </table>
    </div>
END;
    return $output;
}
function makeBlockTrue($functitle, $funccont) {
    // Page Body
    $output = <<<END
      <tr>
        <td width="100%"><div class="block">
    <table class="borderless_table" width="100%">
      <tr>
        <td class="block_ul">&nbsp;</td>
        <td class="block_um">
        <font class="block_title_text">
END;
    $output = $output . $functitle;
    $output = $output . <<<END
	</font></td>
        <td class="block_ur">&nbsp;</td>
      </tr>
      <tr>
        <td class="block_ml">&nbsp;</td>
        <td class="block_body" valign="top">
END;
    $output = $output . $funccont;
    $output = $output . <<<END
	</td>
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
END;
    return $output;
}
function drawMessage($title, $message) {
	global $_PWNDATA, $site_info, $theme, $imageroot, $user;
	setTheme();
	$SITENAME = $site_info['name'];
	print <<<END
<html>
<head>
<title>$SITENAME</title>
END;
	require 'css.php';
	print <<<END
</head>
<body>
<table width="100%" class="borderless_table">
END;
	drawBlock($title, "", $message);
	print "</table><body></html>";
	die();
}
function messageRedirect($title, $message, $redirect) {
	global $_PWNDATA, $site_info;
	$content = $message . "<meta http-equiv=\"Refresh\" content=\"1;url=" . $redirect . "\" /><br />" . $_PWNDATA['redirecting'] . "...";
	drawMessage($title, $content);
}
function messageBack($title, $message) {
	global $_PWNDATA, $site_info;
	$content = $message . "<br /><a href=\"javascript:history.back()\">" . $_PWNDATA['go_back'] . "</a>";
	drawMessage($title, $content);
}
function messageRedirectLight($message,$redirect) {
    global $_PWNDATA, $site_info;
	$content = $message . "<meta http-equiv=\"Refresh\" content=\"1;url=" . $redirect . "\" /><br />" . $_PWNDATA['redirecting'] . "...";
	$content = $content . "<br /><a href=\"$redirect\">{$_PWNDATA['click_to_continue']}</a>";
	die("<div style=\"font-family: sans; font-size: 12px\">" . $content . "</div>");
}
function messageBackLight($title, $message) {
	global $_PWNDATA, $site_info;
	$content = $message . "<br /><a href=\"javascript:history.back()\">" . $_PWNDATA['go_back'] . "</a>";
	die("<div style=\"font-family: sans; font-size: 12px\">" . $content . "</div>");
}

function check_read($id,$userid) {
    $temp_res = mysql_query("SELECT * FROM `topics` WHERE id=$id");
    $topic = mysql_fetch_array($temp_res);
    $read_list = $topic['readby'];
    $split_list = explode(",",$read_list);
    if (in_array($userid, $split_list)) {
        $is_read = true;
    } else {
        $is_read = false;
    }
    return $is_read;
}

function findTopic($postnumber) {
    $temp = mysql_query("SELECT `topicid` FROM `posts` WHERE `id`=" . $postnumber);
    $post = mysql_fetch_array($temp);
    $topic = $post['topicid'];
    return $topic;
}

function findPage($postnumber, $topic = -1) {
    global $_POSTSPERPAGE;
    if ($topic == -1) {
        $topic = findTopic($postnumber);
    }
    $temp_res = mysql_query("SELECT `id` FROM `posts` WHERE `topicid`=" . $topic);
    $i = 0;
    while ($post = mysql_fetch_array($temp_res)) {
        $i++;
        if ($post['id'] >= $postnumber)
            break;
    }
    $page = (int)(($i - 1) / $_POSTSPERPAGE) + 1;
    return $page;
}

function check_read_forum($id,$userid) {
    $temp_res = mysql_query("SELECT * FROM `topics` WHERE board=$id");
    $was_read = true;
    while ($topic = mysql_fetch_array($temp_res)) {
        if (!check_read($topic['id'],$userid)) { $was_read = false; }
    }
    return $was_read;
}

function set_read($id,$userid) {
    $temp_res = mysql_query("SELECT * FROM `topics` WHERE id=$id");
    $topic = mysql_fetch_array($temp_res);
    $read_list = $topic['readby'];
    $split_list = explode(",",$read_list);
    if (!in_array($userid, $split_list)) {
        $read_list = $read_list . ",$userid";
        mysql_query("UPDATE `topics` SET `readby` = '" . mse($read_list) . "' WHERE `topics`.`id` =" . $id);
    }
}

function set_unread($id) {
    mysql_query("UPDATE `topics` SET `readby` = '' WHERE `topics`.`id` =" . $id);
}

function check_voted($id,$userid) {
    $temp_res = mysql_query("SELECT * FROM `polls` WHERE id=$id");
    $topic = mysql_fetch_array($temp_res);
    $read_list = $topic['voters'];
    $split_list = explode(",",$read_list);
    if (in_array($userid, $split_list)) {
        $is_read = true;
    } else {
        $is_read = false;
    }
    return $is_read;
}

function set_voted($id,$userid) {
    $temp_res = mysql_query("SELECT * FROM `polls` WHERE id=$id");
    $topic = mysql_fetch_array($temp_res);
    $read_list = $topic['voters'];
    $split_list = explode(",",$read_list);
    if (!in_array($userid, $split_list)) {
        $read_list = $read_list . ",$userid";
        mysql_query("UPDATE `polls` SET `voters` = '" . mse($read_list) . "' WHERE `polls`.`id` =" . $id);
    }
}


function drawSubbar($left, $right) {
    global $_PWNDATA, $site_info, $theme, $imageroot, $user;
    print <<<END
<table class="borderless_table" width="100%">
  <tr>
    <td class="sub_left"></td>
    <td class="sub_mid"><font class="sub_body_text">
END;
    print $left;
    print <<<END
    </font></td>
    <td class="sub_mid" align="right">

    <font class="sub_body_text">
END;
    print $right;
    print <<<END
    </font></td>
    <td class="sub_right"></td>
  </tr>
</table>
END;
}
function standardHeaders($title, $draw_header, $additionalHead = "") {
    global $_PWNDATA, $site_info, $theme, $imageroot, $user;
    print <<<END
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
<head>
<title>
END;
    print $title;
    print "</title>\n";
    require 'css.php';      // Setup the theme
    print $additionalHead;
    if ($draw_header == true) {
        require 'header.php';
    }
}


if ($no_login != true) {
    // Handle the current session
    if (!isset($_SESSION['sess_id']) and ($_COOKIE['rem_yes'] == "yes")) {
	    $userresult = mysql_query("SELECT * FROM users WHERE UCASE(name)=UCASE('" . $_COOKIE['rem_user'] . "')", $db);
	    $tempuser = mysql_fetch_array($userresult);
	    if (($_COOKIE['rem_user'] == $tempuser['name']) and ($_COOKIE['rem_pass'] == $tempuser['password'])) {
		    $_SESSION['user_name'] = $_COOKIE['rem_user'];
		    $_SESSION['user_pass'] = $_COOKIE['rem_pass'];
		    $_SESSION['sess_id'] = time();
		    $_SESSION['last_on'] = time();
		    mysql_query("DELETE FROM `sessions` WHERE `user`=" . $tempuser['id'] . "");
		    mysql_query("INSERT INTO `sessions` VALUES (" . $_SESSION['sess_id'] . ", " . $tempuser['id'] . ", " . $_SESSION['last_on'] . ");");
	    } else {
		    setcookie("rem_user", "_", time()+60*60*24*365*10); // This cookie will last for another 10 years (just in case)
		    setcookie("rem_pass", "_", time()+60*60*24*365*10); 
		    setcookie("rem_yes", "no", time()+60*60*24*365*10);
	    }
    }
    if (isset($_SESSION['sess_id'])) {
        $result = mysql_query("SELECT * FROM users WHERE UCASE(name)=UCASE('" . $_SESSION['user_name'] . "')", $db);
        $user = mysql_fetch_array($result);
        $temp_count = mysql_query("SELECT COUNT(`id`) FROM `sessions` WHERE `id`=" . $_SESSION['sess_id'], $db);
        $counter_session = mysql_fetch_array($temp_count);
        if ($counter_session['COUNT(`id`)'] == 0) {
	        $_SESSION['sess_id'] = time();
	        $_SESSION['last_on'] = time();
	        mysql_query("DELETE FROM `sessions` WHERE `user`=" . $user['id'] . "");
	        mysql_query("INSERT INTO `sessions` VALUES (" . $_SESSION['sess_id'] . ", " . $user['id'] . ", " . $_SESSION['last_on'] . ");");
        }
        $_SESSION['last_on'] = time();
        mysql_query("UPDATE `sessions` SET `last`=" . time() . " WHERE `id`=" . $_SESSION['sess_id']);
    }
    if ($_GET['do'] == 'logoff') {
        mysql_query("DELETE FROM `sessions` WHERE `id`=" . $_SESSION['sess_id'] . "");
        unset($_SESSION['sess_id']);
        session_destroy();
        setcookie("rem_user", "_", time()+60*60*24*365*10);
        setcookie("rem_pass", "_", time()+60*60*24*365*10);
        setcookie("rem_yes", "no", time()+60*60*24*365*10);
        messageRedirect($_PWNDATA['signedout'],$_PWNDATA['signedout'],"index.php");
    }
}
mysql_query("DELETE FROM `sessions` WHERE (`last` < (" . time() . " - 600))");
if (!isset($user['level'])) { $user['level'] = 0; } // And lastly, set our user to 0 if they aren't logged in.

setTheme();

?>
