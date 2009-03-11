<?php
require_once('includes.php');
function smarty_modifier_bbdecode($string) {
    return bbDecode($string);
}
?>
