<?php
$wiki_url = '';
$accept_changes_url = "";
$redmine_url = '';
// 0 - весь change, 2 - task, 4 - tag, 6 - forpage, 7 - тело change
$pattern_change = '/<\s*change[^t]*(?:(?:(?:task\s*=\s*(\"?|\'?)\s*#?(\d*)\s*\1)|(?:tag\s*=\s*(\"?|\'?)(.*?)\3)|(?:forpage\s*=\s*(\"?|\'?)(.*?)\5))\s*){0,3}[^>]*>([\s\S]*?)<\s*\/change\s*>/mui';

//1 - task
$pattern_simple_change = '/<\s*change[^>]*task\s*=\s*\"?\'?\s*#?(\d*)\s*\'?\"?[^>]*>/mui';
$pattern_before_change = '((?:(?:^[^\n]*\n){3}|(?:^[^\n]*\n){2}|(?:^[^\n]*\n){1})[^\n]*)?';//'(?:(?:^.*\\n{2,})?((?:^.+$\\s)*(?:(?:^.*\\n{2,})(?:^.+$\\s)*)?^.*))';
$pattern_after_change = '((?:(?:[^\n]*\n)(?:(?:(?:^[^\n]*\n){3}|(?:^[^\n]*\n){2}|(?:^[^\n]*\n){1})[^\n]*))?)';//'(.*$(?:(?:(?!\\n{2,})(?:\\S|\\s))*\\n{2,}(?:(?!\\n{2,})(?:\\S|\\s))*\\n{2,}|[\\s\\S]*))';

$images_pattern = '/(?:<\s*mockup[^>]*>[^<]*<\/\s*mockup\s*>)|(?:\[\[\s*image\s*:[^]]*\]\])|(?:<\s*figma[^>]*>[^<]*<\/\s*figma\s*>)|(?:<\s*drawio[^>]*>[^<]*<\/\s*drawio\s*>)/mui';


function GetUser($mysql) {
    $cookie_name = "";

    $autor = null;
    // берем логин из куки вики, т.к. php лежит на том же хосте
    if (isset($_COOKIE[$cookie_name])) {
        $userId = $_COOKIE[$cookie_name];
        $autor = $mysql->GetUserById($userId);
    }
    return $autor;
}
?>
