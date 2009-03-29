<?php
function smarty_function_loaderrors($params, &$smarty ) {
    global $_ERRORS, $_SQL, $_DEBUG_MODE;
    pwnErrorStackAppend(1337,"Total of " . count($_ERRORS) . " errors and warnings.<br /><b>MySQL Status:</b><br />" . $_SQL->stat(),'',0);
    $smarty->assign('show_errors',$_DEBUG_MODE);
    $smarty->assign('errors',$_ERRORS);
}
