<?php
function smarty_function_pager($params, &$smarty ) {
    if ($params.table) {
        echo printPagerNonTabular($params['url'],$params['page'],$params['total']);
    } else {
        echo printPager($params['url'],$params['page'],$params['total']);
    }
}
// Print a paging device (use everywhere!)
function printPager($url,$page,$total) {
    // Magic number 7: First, last, current, plus two on each side = 7 total
    /*  1 2 3 4 5 6 7 */
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
