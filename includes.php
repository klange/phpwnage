<?php
/*
	This file is part of PHPwnage (Global Includes, general functions)

	Copyright 2009 Kevin Lange <klange@oasis-games.com>

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
$config_exists = @include 'config.php';
if (!$config_exists) {
    die("<meta http-equiv=\"Refresh\" content=\"1;url=install.php\" />Error: Not installed. Redirecting to installer.");
}
require_once("lang/{$_DEFAULT_LANG}.php"); // Default language before we've processed users.

$_PWNVERSION['major'] = 1;
$_PWNVERSION['minor'] = 9;
$_PWNVERSION['extra'] = "";

// DO NOT EDIT ANYTHING BELOW THIS LINE
// ------------------------------------------------------------------------------------------------------------

$mtime = microtime();
$mtime = explode(" ",$mtime);
$mtime = $mtime[1] + $mtime[0];
$starttime = $mtime;

$db_fail = false;
$db = mysql_connect($conf_server,$conf_user,$conf_password) or 
die ("<span style=\"font-family: Verdana, Tahoma, sans; color: #EE1111;\">We've experienced an internal error. Please contact " . $conf_email . ".<br />
(Failed to connect to SQL server.)</span>"); 
mysql_select_db($conf_database, $db) or $db_fail = true; 

putenv("TZ=America/New_York");

$banlist = mysql_query("SELECT * FROM `{$_PREFIX}banlist`");
while ($ban = mysql_fetch_array($banlist)) {
    if ($_SERVER['REMOTE_ADDR'] == $ban['ip']) {
        die ("<span style=\"font-family: Verdana, Tahoma, sans; color: #EE1111;\">You have been permanently banned from this site.</span>");
    }
}


$result = mysql_query("SELECT * FROM `{$_PREFIX}info`", $db);
$site_info = mysql_fetch_array($result); // Get the site info, called by all pages, so why not?
function mse($source) {
	// Do we return the Real Escape String or the source?
	//return mysql_real_escape_string($source);
	return $source;
}
function isReadable($userLevel, $board) {
    global $_PREFIX;
	$result = mysql_query("SELECT `vis_level` FROM `{$_PREFIX}boards` WHERE `id`=" .  $board);
	$brd = mysql_fetch_array($result);
	if ((int)$userLevel < (int)$brd['vis_level']) {
		return false;
	} else {
		return true;
	}
}
function isWriteableTopic($userLevel, $board) {
    global $_PREFIX;
	$result = mysql_query("SELECT `top_level` FROM `{$_PREFIX}boards` WHERE `id`=" .  $board);
	$brd = mysql_fetch_array($result);
	if ((int)$userLevel < (int)$brd['top_level']) {
		return false;
	} else {
		return true;
	}
}
function isWriteable($userLevel, $board) {
    global $_PREFIX;
	$result = mysql_query("SELECT `post_level` FROM `{$_PREFIX}boards` WHERE `id`=" .  $board);
	$brd = mysql_fetch_array($result);
	if ((int)$userLevel < (int)$brd['post_level']) {
		return false;
	} else {
		return true;
	}
}
function getBoardName($bid) {
    global $_PREFIX;
	$result = mysql_query("SELECT `title` FROM `{$_PREFIX}boards` WHERE `id`=" .  $bid);
	$brd = mysql_fetch_array($result);
	return $brd['title'];
}
function getPostsInBoard($bid) {
    global $_PREFIX;
	$result = mysql_query("SELECT `id` FROM `{$_PREFIX}topics` WHERE `board`=" .  $bid);
	$total = 0;
	while ($top = mysql_fetch_array($result)) {
		$result2 = mysql_query("SELECT COUNT(*) FROM `{$_PREFIX}posts` WHERE `topicid`=" .  $top['id']);
		$pc = mysql_fetch_array($result2);
		$total = $total + $pc['COUNT(*)'];
	}
	return $total;
}
// Not just themes. All things user-selectable are here.
function setTheme() {
	global $user, $imageroot, $theme, $icons, $language, $_PWNICONS, $_PWNDATA,
	       $_DEFAULT_THEME, $_DEFAULT_ICONS, $_DEFAULT_COLOR, $_DEFAULT_LANG,
	       $smarty;
	if (!isset($user['color']) || $user['color'] == "")
	{
		$imageroot = $_DEFAULT_COLOR; // Default background.
	} else {
		$imageroot = $user['color'];
	}
	$themes = explode(",",$user['theme']);
	if (!isset($themes[0]) || $themes[0] == "")
	{
		$theme = $_DEFAULT_THEME;
	} else {
		$theme = $themes[0];
	}
	if (!isset($themes[1]) || $themes[1] == "")
	{
		$icons = $_DEFAULT_ICONS;
	} else {
		$icons = $themes[1];
	}
	$theme_exists = @include "themes/icons/$icons/icons.php";
    if (!$theme_exists) {
	    include "themes/icons/{$_DEFAULT_ICONS}/icons.php";
	}
	if (!isset($themes[2]) || $themes[2] == "") {
	    $language = $_DEFAULT_LANG;
    } else {
        require_once "lang/{$themes[2]}.php";
        $language = $themes[2];
    }
    require_once('smarty/libs/Smarty.class.php');
    $smarty = new Smarty();
    $smarty->template_dir = 'themes/templates/classic/';
    $smarty->compile_dir  = 'smarty/compile/';
    $smarty->config_dir   = 'smarty/config/';
    $smarty->cache_dir    = 'smarty/cache/';
    $smarty->plugins_dir  = array('smarty/plugins/');
    $smarty->plugins_dir[]= 'themes/templates/classic/functions/';
    $smarty->assign('theme',$theme);
    $smarty->assign('imageroot',$imageroot);
    $smarty->assign('_PWNICONS',$_PWNICONS);
    $smarty->assign('_PWNDATA',$_PWNDATA);

}
function drawButton($dowhat, $title, $button = "") {
    return <<<END
<td style="border: 0px">
	<table class="forum_button">
	<tr>
    <td class="but_left"></td>
    <td class="but_mid"><span class="forum_button_text"><a href="{$dowhat}">{$button}{$title}</a></span></td>
    <td class="but_right"></td>
  </tr>
</table>
</td>
END;
}
/*
// Print a paging device (use everywhere!)
function printPager($url,$page,$total) {
    // Magic number 7: First, last, current, plus two on each side = 7 total
    //  1 2 3 4 5 6 7 
    $unlink = "\">";
    $return = "";
    if ($total < 8) {
        for ($i = 1; $i <= $total; $i++) {
            if ($i == $page) {
                $return .= drawPage("","<b>$i</b>");
            } else {
                $return .= drawPage("{$url}$i",$i);
            }
        }
    } else {
        if ($page < 5) {
            for ($i = 1; $i <= $page + 2; $i++) {
                if ($i == $page) {
                    $return .= drawPage("","<b>$i</b>");
                } else {
                    $return .= drawPage("{$url}$i",$i);
                }
            }
            $return .= drawPage("#","...") . drawPage("{$url}$total",$total);
        } else if ($page > $total - 4) {
            $return .= drawPage("{$url}1",1) . drawPage("#","...");
            for ($i = $page - 2; $i <= $total; $i++) {
                if ($i == $page) {
                    $return .= drawPage("","<b>$i</b>");
                } else {
                    $return .= drawPage("{$url}$i",$i);
                }
            }
        } else {
            $return .= drawPage("{$url}1",1) . drawPage("#","...");
            for ($i = $page - 2; $i <= $page + 2; $i++) {
                if ($i == $page) {
                    $return .= drawPage("","<b>$i</b>");
                } else {
                    $return .= drawPage("{$url}$i",$i);
                }
            }
            $return .= drawPage("#","...") . drawPage("{$url}$total",$total);
        }
    }
    return $return;
}
function printPagerNonTabular($url,$page,$total) {
    $unlink = "\">";
    $return = "";
    if ($total < 8) {
        for ($i = 1; $i <= $total; $i++) {
            if ($i == $page) {
                $return .= drawPageB("","<b>$i</b>");
            } else {
                $return .= drawPageB("{$url}$i",$i);
            }
        }
    } else {
        if ($page < 5) {
            for ($i = 1; $i <= $page + 2; $i++) {
                if ($i == $page) {
                    $return .= drawPageB("","<b>$i</b>");
                } else {
                    $return .= drawPageB("{$url}$i",$i);
                }
            }
            $return .= drawPageB("#","...") . drawPageB("{$url}$total",$total);
        } else if ($page > $total - 4) {
            $return .= drawPageB("{$url}1",1) . drawPageB("#","...");
            for ($i = $page - 2; $i <= $total; $i++) {
                if ($i == $page) {
                    $return .= drawPageB("","<b>$i</b>");
                } else {
                    $return .= drawPageB("{$url}$i",$i);
                }
            }
        } else {
            $return .= drawPageB("{$url}1",1) . drawPageB("#","...");
            for ($i = $page - 2; $i <= $page + 2; $i++) {
                if ($i == $page) {
                    $return .= drawPageB("","<b>$i</b>");
                } else {
                    $return .= drawPageB("{$url}$i",$i);
                }
            }
            $return .= drawPageB("#","...") . drawPageB("{$url}$total",$total);
        }
    }
    return $return;
}

function drawPage($link,$text) {
    return  <<<END
<td>
    <div class="page_spacer">
        <div class="forum_page"><span class="page_text"><a href="$link">$text</a></span></div>
    </div>
</td>
END;
}

function drawPageB($link,$text) {
    return  <<<END
    <div class="page_spacer" style="display:inline;">
        <div class="forum_page" style="display:inline;"><span class="page_text"><a href="$link">$text</a></span></div>
    </div>
END;
}
*/

function getRankName($level,$site_info,$posts) {
    global $_PREFIX, $_PWNDATA;
	// First we'll check if there is a custom rank available.
	$level = (int)$level;
	$posts = (int)$posts;
    $temp = mysql_query("SELECT COUNT(*) FROM `{$_PREFIX}ranks` WHERE `value`=$level AND `posts`=-1");
    $temp = mysql_fetch_array($temp);
    if ((int)$temp['COUNT(*)'] < 1) {
        // Then, if our user has a post count within a specific range, use it.
        $temp = mysql_query("SELECT COUNT(*) FROM `{$_PREFIX}ranks` WHERE `value`=-1 AND `posts`<=$posts");
        $temp = mysql_fetch_array($temp);
        if ((int)$temp['COUNT(*)'] < 1) {
            // Otherwise, just use the standard title for their rank.
            if ($level < $site_info['mod_rank']) {
	        return $_PWNDATA['rank']['user'];
	    } else if ($level >= $site_info['mod_rank'] && $level < $site_info['admin_rank']) {
	        return $_PWNDATA['rank']['moderator'];
	    } else if ($level >= $site_info['admin_rank']) {
	        return $_PWNDATA['rank']['admin'];
	    }
	} else {
	    $results2 = mysql_query("SELECT `name` FROM `{$_PREFIX}ranks` WHERE `value`=-1 AND `posts`<=" . $posts . " ORDER BY `posts` DESC");
            $rank = mysql_fetch_array($results2);
            return $rank['name'];
        }
    } else {
        $results = mysql_query("SELECT `name` FROM `{$_PREFIX}ranks` WHERE `value`=$level AND `posts`=-1 ORDER BY `value` DESC");
        $rank = mysql_fetch_array($results);
        return $rank['name'];
    }
}
function bbJava($stuff) {
    // Usage: Takes in Java code and spits out nicely highlighted code in a box
    $stuff = str_replace("<br />","\n",$stuff);
    $stuff = str_replace("\\\"", "\"",$stuff);
    $stuff = preg_replace("/(\/\/)(.*?)(\n+?)/si","<span style='color: #00AA00'>$1$2</span>$3",$stuff);
    $stuff = preg_replace("/(\")(.*?)(\")/si","<span style='color: #AA0000'>$1$2$3</span>",$stuff);
    $stuff = preg_replace("/(\/\*)([\s\S]*?)(\*\/)/si","<span style='color: #00AA00'>$1$2$3</span>",$stuff);
    $keywords = array("abstract","continue","for","new","switch","assert","default","goto",
    "package","synchronized","boolean","do","if","private","this","break",
    "double","implements","protected","throw","byte","else","import","public",
    "throws","case","enum","instanceof","return","transient","catch","extends",
    "int","short","try","char","final","interface","static","void","class",
    "finally","long","strictfp","volatile","const","float","native","super","while");
    $keywordsa = $keywords;
    foreach ($keywordsa as $word) {
        $word2 = "<span style=\"color: #0000AA\">" . $word . "</span>";
        $stuff = sim_rep2($word,$word2,$stuff);
    }
    unset($word);
    $stuff = "<div><div style=\"font-family: monospace;\"><b>Code:</b></div><pre style=\"background-color: #FFFFFF; border: 1px #000000 solid; overflow: auto; width: 640px; margin: 0px;\">" . $stuff . "</pre></div>";
    return $stuff;
}
function bbCSharp($stuff) {
    // Usage: Takes in C# code and spits out nicely highlighted code in a box
    $stuff = str_replace("<br />","\n",$stuff);
    $stuff = str_replace("\\\"", "\"",$stuff);
    $stuff = preg_replace("/(\/\/)(.*?)(\n+?)/si","<span style='color: #00AA00'>$1$2</span>$3",$stuff);
    $stuff = preg_replace("/(\")(.*?)(\")/si","<span style='color: #AA0000'>$1$2$3</span>",$stuff);
    $stuff = preg_replace("/(\/\*)([\s\S]*?)(\*\/)/si","<span style='color: #00AA00'>$1$2$3</span>",$stuff);
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
        $word2 = "<span style=\"color: #0000AA\">" . $word . "</span>";
        $stuff = sim_rep2($word,$word2,$stuff);
    }
    unset($word);
    $stuff = "<div><div style=\"font-family: monospace;\"><b>Code:</b></div><pre style=\"background-color: #FFFFFF; border: 1px #000000 solid; overflow: auto; width: 640px; margin: 0px;\">" . $stuff . "</pre></div>";
    return $stuff;
}
function genericCode($stuff) {
    $stuff = str_replace("\\\"", "\"",$stuff);
    $stuff = "<div><div style=\"font-family: monospace;\"><b>Code:</b></div><pre style=\"background-color: #FFFFFF; border: 1px #000000 solid; overflow: auto; width: 640px; margin: 0px;\">" . $stuff . "</pre></div>";
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
    global $_PWNDATA, $_PREFIX;
    $results = mysql_query("SELECT COUNT(*) FROM `{$_PREFIX}posts` WHERE `topicid`=" . $topic);
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
function quote($c) {
    return preg_replace("/(\[quote\])((?:.(?!\[quote\]))*?)(\[\/quote\])/si","<div class=\"quote\"><span class=\"forum_quote\">$2</span></div>",$c);
}
function BBDecode($content,$allowhtml = false) {
    global $_PREFIX;
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
    $content = preg_replace("/(\[size=)([0-9]+)(\])(.+?)(\[\/size\])/si","<span size=\"$2\">$4</span>",$content);
    $content = preg_replace("/(\[ptsize=)([0-9]+)(\])(.+?)(\[\/ptsize\])/si","<span style=\"font-size: $2pt\">$4</span>",$content);
    $content = preg_replace("/(\[pxsize=)([0-9]+)(\])(.+?)(\[\/pxsize\])/si","<span style=\"font-size: $2px\">$4</span>",$content);
    $content = preg_replace("/(\[scroll=)(.+?)(\])(.+?)(\[\/scroll\])/si","<marquee direction=\"$2\">$4</marquee>",$content);
    $content = preg_replace("/(\[scroll\])(.+?)(\[\/scroll\])/si","<marquee>$2</marquee>",$content);
    while ($flag_quote ==false) {
        $c_old = $content;
        $content = preg_replace("/(\[quote\])((?:.(?!\[quote\]))*?)(\[\/quote\])/si","<div class=\"quote\"><span class=\"forum_quote\">$2</span></div>",$content);
        $flag_quote = $c_old == $content?true:false;
    }
    while ($flag_hide ==false) {
        $c_old = $content;
        $content = preg_replace("/(\[hide\])((?:.(?!\[hide\]))*?)(\[\/hide\])/si","<div><div><input value=\"Show\" onclick=\"if (this.parentNode.parentNode.getElementsByTagName('div')[1].getElementsByTagName('div')[0].style.display != '') { this.parentNode.parentNode.getElementsByTagName('div')[1].getElementsByTagName('div')[0].style.display = '';this.innerText = ''; this.value = 'Hide'; } else { this.parentNode.parentNode.getElementsByTagName('div')[1].getElementsByTagName('div')[0].style.display = 'none'; this.innerText = ''; this.value = 'Show'; }\" type=\"button\"></div><div class=\"alt2\" style=\"border: 1px inset ; margin: 0px; padding: 6px;\"><div style=\"display: none;\">$2</div></div></div>",$content);
        $flag_hide = $c_old == $content?true:false;
    }
    $content = preg_replace("/(\[color=)(.+?)(\])(.+?)(\[\/color\])/si","<span style=\"color: #$2\">$4</span>",$content);
    $content = preg_replace("/(\[youtube\])(.+?)(\[\/youtube\])/si","<object width=\"425\" height=\"350\"><param name=\"movie\" value=\"http://www.youtube.com/v/$2\"></param><param name=\"wmode\" value=\"transparent\"></param><embed src=\"http://www.youtube.com/v/$2\" type=\"application/x-shockwave-flash\" wmode=\"transparent\" width=\"425\" height=\"350\"></embed></object>",$content);
    $content = preg_replace("/(\[gallery-thumb\])(.+?)(\[\/gallery-thumb\])/si","<a href=\"gallery.php?do=image&amp;id=$2\"><img src=\"gallery.php?do=img&amp;type=thumb&amp;i=$2\" alt=\"gallery image\" /></a>",$content);
    // Smiles are stored in MySQL
    $smilesSet = mysql_query("SELECT * FROM `{$_PREFIX}smileys`");
    while ($smile = mysql_fetch_array($smilesSet)) {
        $content = str_replace($smile['code'],"<img alt=\"{$smile['name']}\" src=\"smiles/" . $smile['image'] . "\" />",$content);
    }
    // Censorship
    $censor_list = array("ass", "bitch", "bastard", "cunt", "cock", "shit", "damn", "fuck", "fucker", "fucking");
    // Edit this to censor other words. Uses a fairly nice system to ensure words like bass, etc aren't censored.
    // However, things like "---hole" won't be censored. If you really care, add these words.
    foreach ($censor_list as $cen) {
        $content = sim_rep($cen,"****",$content);
        $content = sim_rep($cen . "es","****",$content); // Plural forms
        $content = sim_rep($cen . "s","****",$content);
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
    global $_PREFIX;
	// Get a user's post count by ID.
	$results = mysql_query("SELECT COUNT(*) FROM `{$_PREFIX}posts` WHERE `authorid`=" . $userID);
	if (!$results) {
	    return 0;
    } else {
	    $counter = mysql_fetch_array($results);
	    return $counter['COUNT(*)'];
	}
}
function makeEditor($name,$path,$preview,$advanced,$target) {
    global $_PWNDATA, $_PWNICONS, $_PREFIX, $user;
    $userich = ($user['level'] < 1 or $user['rich_edit']) ? "true" : "false";
    $return = <<<END
<script type="text/javascript">
//<![CDATA[
var mce_editing$path = $userich;
function addCode$path(code,codeclose) {
if (mce_editing$path) {
    var Text = tinyMCE.activeEditor.selection.getContent();
    tinyMCE.activeEditor.selection.setContent(code + Text + codeclose);
    tinyMCE.activeEditor.execCommand("mceCleanup");
} else {
    var IE = document.all?true:false;
    if (IE) {
        var element = document.form$path.$name;
        if( document.selection ){
	        var range = document.selection.createRange();
	        var stored_range = range.duplicate();
	        stored_range.moveToElementText( element );
	        stored_range.setEndPoint( 'EndToEnd', range );
	        element.selectionStart = stored_range.text.length - range.text.length;
	        element.selectionEnd = element.selectionStart + range.text.length;
        }
    }
    var Text = document.form$path.$name.value;
    var selectedText = Text.substring(document.form$path.$name.selectionStart, document.form$path.$name.selectionEnd);
    var beforeSelected = Text.substring(0,document.form$path.$name.selectionStart);
    var afterSelected = Text.substring(document.form$path.$name.selectionEnd,Text.length);
    document.form$path.$name.value = beforeSelected+code+selectedText+codeclose+afterSelected;
}
}
function setPreview$path() {
if (mce_editing$path) {
    var Text = tinyMCE.activeEditor.getContent();
    Text = Text.replace(/\\n/g,"!NL!");
    frames['previewbox$path'].location.href = 'forum.php?do=preview&a=' + Text;
} else {
    var Text = document.form$path.$name.value;
    Text = Text.replace(/\\n/g,"!NL!");
    frames['previewbox$path'].location.href = 'forum.php?do=preview&a=' + Text;
}
}
function toggleMCE$path() {
    if (!mce_editing$path) {
        tinyMCE.execCommand('mceAddControl',false,'$name$path');
        mce_editing$path = true;
    } else {
        tinyMCE.execCommand('mceRemoveControl',false,'$name$path');
        mce_editing$path = false;
    }
}
function addSize$what(sizeToAdd) {
document.form$path.$name.rows = document.form$path.$name.rows + sizeToAdd;
}
//]]>
</script>
END;
    if ($preview) {
        $return .= "<iframe name=\"previewbox$path\" width=\"100%\" style=\"border: 0px;\" height=\"0px\" id=\"previewbox\"></iframe>";
    }
    $smilesSet = mysql_query("SELECT `code`,`image` FROM `{$_PREFIX}smileys`");
    $return .= "<table class=\"mod_set\"><tr><td colspan=\"11\"><b>{$_PWNDATA['poster']['smileys']}:</b> ";
    while ($smile = mysql_fetch_array($smilesSet)) {
        $return .= "<img src=\"smiles/" . $smile['image'] . "\" alt=\"" . $smile['code'] . "\" onclick=\"addCode$what('" . $smile['code'] . "','')\" />";
    }
    $return .= "</td></tr><tr>";
    $return .= drawButton("javascript:addCode$path('[b]','[/b]')","<b>{$_PWNDATA['poster']['bold']}</b>",$_PWNICONS['buttons']['editor']['bold']) . "\n";
    $return .= drawButton("javascript:addCode$path('[u]','[/u]')","<u>{$_PWNDATA['poster']['underline']}</u>",$_PWNICONS['buttons']['editor']['underline']) . "\n";
    $return .= drawButton("javascript:addCode$path('[i]','[/i]')","<i>{$_PWNDATA['poster']['italic']}</i>",$_PWNICONS['buttons']['editor']['italic']) . "\n";
    $return .= drawButton("javascript:addCode$path('[so]','[/so]')","<s>{$_PWNDATA['poster']['strike']}</s>",$_PWNICONS['buttons']['editor']['strike']) . "\n";
    $return .= drawButton("javascript:addCode$path('[color='+prompt('{$_PWNDATA['poster']['hex']}:','RRGGBB')+']','[/color]')","{$_PWNDATA['poster']['color']}",$_PWNICONS['buttons']['editor']['color']) . "\n";
    $return .= drawButton("javascript:addCode$path('[img]'+prompt('{$_PWNDATA['poster']['img_url']}:','http://')+'[/img]','')","{$_PWNDATA['poster']['image']}",$_PWNICONS['buttons']['editor']['img']) . "\n";
    $return .= drawButton("javascript:addCode$path('[url='+prompt('{$_PWNDATA['poster']['link_url']}:','http://')+']'+prompt('Link Title:','')+'[/url]','')","{$_PWNDATA['poster']['link']}",$_PWNICONS['buttons']['editor']['link']) . "\n";
    $return .= drawButton("javascript:addSize$path(2)","\/") . "\n";
    $return .= drawButton("javascript:addSize$path(-2)","/\\") . "\n";
    if ($preview) {
        $return .= drawButton("javascript:setPreview$path()","{$_PWNDATA['poster']['preview']}") . "\n";
    }
    if ($advanced) {
        $return .= drawButton($target,$_PWNDATA['poster']['go_advanced']) . "\n";
    }
    $return .= drawButton("javascript:toggleMCE$path()","MCE") . "\n";
    $return .= "</tr></table>";
    return $return;
}
function printPoster($where) {
	// Print the posting tool buttons
    return makeEditor($where,"",true,false,"");
}
function printPosterMini($where, $topID) {
	// Print the posting tools in a smaller package.
    return makeEditor($where,"",false,true,"forum.php?do=newreply&amp;id=" . $topID);
}
function printPosterEditor($where, $pid) {
	// Print the posting tools in a smaller package.
    return makeEditor($where,"_" . $pid,false,true,"forum.php?do=editreply&amp;id=" . $pid);
}
function getDay($timecode) {
	// Get the current day for the Calendar from a timecode
    $daytime = date("d,m,y",$timecode);
    return $daytime;
}
function themeList($selected) {
    global $theme;
	$themeList = "<select name=\"theme\">";
	$myDirectory = opendir("themes/styles/"); // Open root
	while($entryName = readdir($myDirectory)) {
		$dirArray[] = $entryName; // Get our list of files
	}
	closedir($myDirectory); // Close the directory
	sort($dirArray); // Sort the array
	$indexCount	= count($dirArray); // Count...
	if (!isset($selected) || $selected == "") {
	    $selected = $theme;
    }
	for($index=0; $index < $indexCount; $index++) {
		if (substr("$dirArray[$index]", 0, 1) != "."){
			if (!strstr($dirArray[$index],".")) {
				$themeName = $dirArray[$index];
				if ($themeName == $selected) {
					$themeList .= "\n<option value=\"" . $themeName . "\" selected=\"selected\">" . $themeName . "</option>";
				} else {
					$themeList .= "\n<option value=\"" . $themeName . "\">" . $themeName . "</option>";
				}
			}
		}
	}
	$themeList .= "</select>";
	return $themeList;
}
function iconsList($selected) {
    global $icons;
	$themeList = "<select name=\"icons\">";
	$myDirectory = opendir("themes/icons/"); // Open root
	while($entryName = readdir($myDirectory)) {
		$dirArray[] = $entryName; // Get our list of files
	}
	closedir($myDirectory); // Close the directory
	sort($dirArray); // Sort the array
	$indexCount	= count($dirArray); // Count...
	if (!isset($selected) || $selected == "") {
	    $selected = $icons;
    }
	for($index=0; $index < $indexCount; $index++) {
		if (substr("$dirArray[$index]", 0, 1) != "."){
			if (!strstr($dirArray[$index],".")) {
				$themeName = $dirArray[$index];
				if ($themeName == $selected) {
					$themeList .= "\n<option value=\"" . $themeName . "\" selected=\"selected\">" . $themeName . "</option>";
				} else {
					$themeList .= "\n<option value=\"" . $themeName . "\">" . $themeName . "</option>";
				}
			}
		}
	}
	$themeList .= "</select>";
	return $themeList;
}
function langList($selected)
{
    global $language;
    $language_list = file("lang/languages.txt");
    while ($l = each(&$language_list)) {
        $lan = rtrim($l['value']);
        $temp = explode(",",$lan);
        $languages["{$temp[0]}"] = "{$temp[1]}";
    }
	$themeList = "<select name=\"lang\">";
	$myDirectory = opendir("lang"); // Open root
	while($entryName = readdir($myDirectory)) {
		$dirArray[] = $entryName; // Get our list of files
	}
	closedir($myDirectory); // Close the directory
	sort($dirArray); // Sort the array
	$indexCount	= count($dirArray); // Count...
	if (!isset($selected) || $selected == "") {
	    $selected = $language;
    }
	for($index=0; $index < $indexCount; $index++) {
		if (substr("$dirArray[$index]", 0, 1) != "."){
			if (strstr($dirArray[$index],".php")) {
				$themeName = str_replace(".php","",$dirArray[$index]);
				if ($themeName == $selected) {
					$themeList .= "\n<option value=\"" . $themeName . "\" selected=\"selected\">" . $languages[$themeName] . "</option>";
				} else {
					$themeList .= "\n<option value=\"" . $themeName . "\">" . $languages[$themeName] . "</option>";
				}
			}
		}
	}
	$themeList .= "</select>";
	return $themeList;
}
function colorList($selected) {
    global $imageroot;
	$themeList = "<select name=\"color\" style=\"height: 3ex\">";
	$myDirectory = opendir("themes/backgrounds/"); // Open colors folder
	while($entryName = readdir($myDirectory)) {
		$dirArray[] = $entryName; // Get our list of files
	}
	closedir($myDirectory); // Close the directory
	sort($dirArray); // Sort the array
	$indexCount	= count($dirArray); // Count...
	if (!isset($selected) || $selected == "") {
	    $selected = $imageroot;
    }
	for($index=0; $index < $indexCount; $index++) {
		if (substr("$dirArray[$index]", 0, 1) != "."){
			if (strstr($dirArray[$index],".gif")) {
				$themeName = str_replace(".gif","",$dirArray[$index]);
				if ($themeName == $selected) {
					$themeList .= "\n<option style=\"height: 30; background: url('themes/backgrounds/" . $dirArray[$index] . "'); background-repeat: no-repeat;\" value=\"" . $themeName . "\" selected=\"selected\">" . $themeName . "</option>";
				} else {
					$themeList .= "\n<option style=\"height: 30; background: url('themes/backgrounds/" . $dirArray[$index] . "'); background-repeat: no-repeat;\" value=\"" . $themeName . "\">" . $themeName . "</option>";
				}
			}
		}
	}
	$themeList .= "</select>";
	return $themeList;
}

function drawBlock($functitle, $funcright, $funccont) {
    print makeBlock($functitle, $funcright, $funccont);
}
function makeBlock($functitle, $funcright, $funccont) {
    // Page Body
    return <<<END
      <tr>
        <td width="100%"><div class="panel">
    <table class="borderless_table" width="100%">
      <tr>
        <td class="pan_ul">&nbsp;</td>
        <td class="pan_um">
        <span class="pan_title_text">{$functitle}</span></td>
        <td class="pan_um" align="right">
        <span class="pan_title_text">{$funcright}</span></td>
        <td class="pan_ur">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_ml">&nbsp;</td>
        <td class="pan_body" valign="top" colspan="2">{$funccont}</td>
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
}
function makeBlockSA($functitle, $funcright, $funccont) {
    // Page Body
    return <<<END
    <div class="panel">
    <table class="borderless_table" width="100%">
      <tr>
        <td class="pan_ul">&nbsp;</td>
        <td class="pan_um">
        <span class="pan_title_text">{$functitle}</span></td>
        <td class="pan_um" align="right">
        <span class="pan_title_text">{$funcright}</span></td>
        <td class="pan_ur">&nbsp;</td>
      </tr>
      <tr>
        <td class="pan_ml">&nbsp;</td>
        <td class="pan_body" valign="top" colspan="2">{$funccont}</td>
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
}
function makeBlockTrue($functitle, $funccont) {
    // Page Body
    return <<<END
      <tr>
        <td width="100%"><div class="block">
    <table class="borderless_table" width="100%">
      <tr>
        <td class="block_ul">&nbsp;</td>
        <td class="block_um">
        <span class="block_title_text">{$functitle}</span></td>
        <td class="block_ur">&nbsp;</td>
      </tr>
      <tr>
        <td class="block_ml">&nbsp;</td>
        <td class="block_body" valign="top">{$funccont}</td>
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
}
function drawMessage($title, $message, $headers = true) {
	global $_PWNDATA, $site_info, $theme, $imageroot, $user;
	setTheme();
	$SITENAME = $site_info['name'];
	if ($headers) {
	print <<<END
<html>
<head>
<title>$SITENAME</title>
END;
	require 'css.php';
	print <<<END
</head>
<body>
END;
}
print <<<END
<table width="100%" class="borderless_table">
END;
	drawBlock($title, "", $message);
	print "</table><body></html>";
    die();
}
function messageRedirect($title, $message, $redirect, $headers = true) {
	global $_PWNDATA, $site_info;
	$content = $message . "<meta http-equiv=\"Refresh\" content=\"1;url=" . $redirect . "\" /><br />" . $_PWNDATA['redirecting'] . "...";
	drawMessage($title, $content, $headers);
}
function messageBack($title, $message, $headers = true) {
	global $_PWNDATA, $site_info;
	$content = $message . "<br /><a href=\"javascript:history.back()\">" . $_PWNDATA['go_back'] . "</a>";
	drawMessage($title, $content, $headers);
}
function messageRedirectLight($message,$redirect) {
    global $_PWNDATA, $site_info;
	$content = $message . "<meta http-equiv=\"Refresh\" content=\"1;url=" . $redirect . "\" /><br />" . $_PWNDATA['redirecting'] . "...";
	$content .= "<br /><a href=\"$redirect\">{$_PWNDATA['click_to_continue']}</a>";
	die("<div style=\"font-family: sans; font-size: 12px\">" . $content . "</div>");
}
function messageBackLight($title, $message) {
	global $_PWNDATA, $site_info;
	$content = $message . "<br /><a href=\"javascript:history.back()\">" . $_PWNDATA['go_back'] . "</a>";
	die("<div style=\"font-family: sans; font-size: 12px\">" . $content . "</div>");
}

function check_read($id,$userid) {
    global $_PREFIX;
    $temp_res = mysql_query("SELECT `readby` FROM `{$_PREFIX}topics` WHERE id=$id");
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
    global $_PREFIX;
    $temp = mysql_query("SELECT `topicid` FROM `{$_PREFIX}posts` WHERE `id`=" . $postnumber);
    $post = mysql_fetch_array($temp);
    $topic = $post['topicid'];
    return $topic;
}

function findPage($postnumber, $topic = -1) {
    global $_POSTSPERPAGE, $_PREFIX;
    if ($topic == -1) {
        $topic = findTopic($postnumber);
    }
    $temp_res = mysql_query("SELECT `id` FROM `{$_PREFIX}posts` WHERE `topicid`=" . $topic);
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
    global $_PREFIX;
    $temp_res = mysql_query("SELECT `id` FROM `{$_PREFIX}topics` WHERE board=$id");
    $was_read = true;
    while ($topic = mysql_fetch_array($temp_res)) {
        if (!check_read($topic['id'],$userid)) { $was_read = false; }
    }
    return $was_read;
}

function set_read($id,$userid) {
    global $_PREFIX;
    $temp_res = mysql_query("SELECT `readby` FROM `{$_PREFIX}topics` WHERE id=$id");
    $topic = mysql_fetch_array($temp_res);
    $read_list = $topic['readby'];
    $split_list = explode(",",$read_list);
    if (!in_array($userid, $split_list)) {
        $read_list .= ",$userid";
        mysql_query("UPDATE `{$_PREFIX}topics` SET `readby` = '" . mse($read_list) . "' WHERE `{$_PREFIX}topics`.`id` =" . $id);
    }
}

function set_unread($id) {
    global $_PREFIX;
    mysql_query("UPDATE `{$_PREFIX}topics` SET `readby` = '' WHERE `{$_PREFIX}topics`.`id` =" . $id);
}

function check_voted($id,$userid) {
    global $_PREFIX;
    $temp_res = mysql_query("SELECT `voters` FROM `{$_PREFIX}polls` WHERE id=$id");
    $poll = mysql_fetch_array($temp_res);
    $read_list = $poll['voters'];
    $split_list = explode(",",$read_list);
    if (in_array($userid, $split_list)) {
        $is_read = true;
    } else {
        $is_read = false;
    }
    return $is_read;
}

function set_voted($id,$userid) {
    global $_PREFIX;
    $temp_res = mysql_query("SELECT `voters` FROM `{$_PREFIX}polls` WHERE id=$id");
    $poll = mysql_fetch_array($temp_res);
    $read_list = $poll['voters'];
    $split_list = explode(",",$read_list);
    if (!in_array($userid, $split_list)) {
        $read_list .= ",$userid";
        mysql_query("UPDATE `{$_PREFIX}polls` SET `voters` = '" . mse($read_list) . "' WHERE `{$_PREFIX}polls`.`id` =" . $id);
    }
}


function drawSubbar($left, $right) {
    print <<<END
<table class="borderless_table" width="100%">
  <tr>
    <td class="sub_left"></td>
    <td class="sub_mid"><span class="sub_body_text">{$left}</span></td>
    <td class="sub_mid" align="right">
    <span class="sub_body_text">{$right}</span></td>
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
<title>{$title}</title>

END;
    require 'css.php';      // Setup the theme
    print $additionalHead;
    if ($draw_header == true) {
        require 'header.php';
    }
}


if ($no_login != true) {
    // Handle the current session
    if (!isset($_SESSION['sess_id']) and ($_COOKIE['rem_yes'] == "yes")) {
	    $userresult = mysql_query("SELECT `name`,`password`,`id` FROM `{$_PREFIX}users` WHERE UCASE(name)=UCASE('" . $_COOKIE['rem_user'] . "')", $db);
	    $tempuser = mysql_fetch_array($userresult);
	    if (($_COOKIE['rem_user'] == $tempuser['name']) and ($_COOKIE['rem_pass'] == $tempuser['password'])) {
		    $_SESSION['user_name'] = $_COOKIE['rem_user'];
		    $_SESSION['user_pass'] = $_COOKIE['rem_pass'];
		    $_SESSION['sess_id'] = time();
		    $_SESSION['last_on'] = time();
		    mysql_query("DELETE FROM `{$_PREFIX}sessions` WHERE `user`=" . $tempuser['id'] . "");
		    mysql_query("INSERT INTO `{$_PREFIX}sessions` VALUES (" . $_SESSION['sess_id'] . ", " . $tempuser['id'] . ", " . $_SESSION['last_on'] . ");");
	    } else {
		    setcookie("rem_user", "_", time()+60*60*24*365*10); // This cookie will last for another 10 years (just in case)
		    setcookie("rem_pass", "_", time()+60*60*24*365*10); 
		    setcookie("rem_yes", "no", time()+60*60*24*365*10);
	    }
    }
    if (isset($_SESSION['sess_id'])) {
        $result = mysql_query("SELECT * FROM `{$_PREFIX}users` WHERE UCASE(name)=UCASE('" . $_SESSION['user_name'] . "')", $db);
        $user = mysql_fetch_array($result);
        $temp_count = mysql_query("SELECT COUNT(`id`) FROM `{$_PREFIX}sessions` WHERE `id`=" . $_SESSION['sess_id'], $db);
        $counter_session = mysql_fetch_array($temp_count);
        if ($counter_session['COUNT(`id`)'] == 0) {
	        $_SESSION['sess_id'] = time();
	        $_SESSION['last_on'] = time();
	        mysql_query("DELETE FROM `{$_PREFIX}sessions` WHERE `user`=" . $user['id'] . "");
	        mysql_query("INSERT INTO `{$_PREFIX}sessions` VALUES (" . $_SESSION['sess_id'] . ", " . $user['id'] . ", " . $_SESSION['last_on'] . ");");
        }
        $_SESSION['last_on'] = time();
        mysql_query("UPDATE `{$_PREFIX}sessions` SET `last`=" . time() . " WHERE `id`=" . $_SESSION['sess_id']);
    }
    if ($_GET['do'] == 'logoff') {
        mysql_query("DELETE FROM `{$_PREFIX}sessions` WHERE `id`=" . $_SESSION['sess_id'] . "");
        unset($_SESSION['sess_id']);
        session_destroy();
        setcookie("rem_user", "_", time()+60*60*24*365*10);
        setcookie("rem_pass", "_", time()+60*60*24*365*10);
        setcookie("rem_yes", "no", time()+60*60*24*365*10);
        messageRedirect($_PWNDATA['signedout'],$_PWNDATA['signedout'],"index.php");
    }
}
mysql_query("DELETE FROM `{$_PREFIX}sessions` WHERE (`last` < (" . time() . " - 600))");
if (!isset($user['level'])) { $user['level'] = 0; } // And lastly, set our user to 0 if they aren't logged in.

setTheme();

$smarty->assign('site',$site_info);
$smarty->assign('user',$user);


?>
