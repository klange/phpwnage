<?php
function smarty_function_drawbutton($params, &$smarty) {
    echo <<<END
<td style="border: 0px">
	<table class="forum_button">
	<tr>
    <td class="but_left"></td>
    <td class="but_mid"><span class="forum_button_text"><a href="{$params['action']}">{$params['button']}{$params['title']}</a></span></td>
    <td class="but_right"></td>
  </tr>
</table>
</td>
END;
}
