<?php
function smarty_function_loaderrors($params, &$smarty ) {
    global $_ERRORS, $_SQL;
    $tmp['name'] = "Information";
    $tmp['str'] = "Total of " . count($_ERRORS) . " errors and warnings.<br /><b>MySQL Status:</b><br />" . $_SQL->stat();
    $tmp['line'] = 0;
    $tmp['file'] = "";
    $tmp['type'] = 1024;
    $_ERRORS[] = $tmp;
    $smarty->assign('errors',$_ERRORS);
    $smarty->assign('error_count',count($_ERRORS));
}
