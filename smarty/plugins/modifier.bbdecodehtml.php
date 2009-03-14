<?php
require_once('includes.php');
function smarty_modifier_bbdecodehtml($string) {
    return bbDecode($string,true);
}
?>
