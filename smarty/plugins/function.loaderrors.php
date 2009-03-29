<?php
function smarty_function_loaderrors($params, &$smarty ) {
    global $_ERRORS, $_SQL;
    pwnErrorStackAppend(1337,"Total of " . count($_ERRORS) . " errors and warnings.<br /><b>MySQL Status:</b><br />" . $_SQL->stat(),'',0);
    $smarty->assign('errors',$_ERRORS);
    $smarty->assign('error_count',count($_ERRORS));
}
