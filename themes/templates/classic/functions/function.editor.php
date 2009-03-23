<?php
function smarty_function_editor($params, &$smarty) {
    echo pwnEditor($params['name'],$params['path'],$params['preview'],
                     $params['advanced'], $params['target']);
}
function pwnEditor($name,$path,$preview,$advanced,$target) {
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
function addSize$path(sizeToAdd) {
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
        $return .= "<img src=\"smiles/" . $smile['image'] . "\" alt=\"" . $smile['code'] . "\" onclick=\"addCode$path('" . $smile['code'] . "','')\" />";
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
