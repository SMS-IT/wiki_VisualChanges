<style>
<?php
header('Access-Control-Allow-Origin: *');
include 'styles.css';
include dirname(__DIR__).'/../wiki/customizations/custom.css';
?>
</style>
<style>
<?php include 'site.css'; ?>
</style>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
<form action="submit.php" method="post">
<?php
set_time_limit(3000);
ini_set("pcre.backtrack_limit", "100000000");
echo '<body class="mediawiki ltr sitedir-ltr mw-hide-empty-elt ns-0 ns-subject skin-vector action-view common-background">';

include "shared.php";
include "WikiSqlProvider.class.php";
include "WikiProvider.class.php";

$wiki_api = new WikiProvider();

$pure_code = false;
if (isset($_GET['pure_code'])) {
    $pure_code = true;
}

$mysql = new WikiSqlProvider();
$eta = microtime(true) * -1;
$autor = GetUser($mysql);

if (!(isset($autor) && $autor != '')){
    echo "<div class='warn'>Авторизируйся на <a href='/'>wiki</a> еще раз пожалуйста.</div>";
    return;
}

if (!$mysql->CreateTablesIfNotExists()) {
    echo "<div class='warn'>Что-то не так: не могу проверить существование нужных для меня таблиц в БД или создать их.</div>";
    return;
}

if (!$mysql->ClearTmpHistoryForUser($autor)) {
    echo "<div class='warn'>Что-то не так: не могу взаимодействовать с моими таблицами в БД.</div>";
    return;
}

$dbres = null;
$task = null;
if (isset($_GET['page'])) {
    $main_request_string = $_GET['page'];
    $dbres = $mysql->SearchByPage($main_request_string);
    echo "<div class='h2-changes'>Принятие правок по странице <a target='_blank' href='/index.php/$main_request_string'>$main_request_string</a></div>";
} else if (isset($_GET['task'])) {
    $task = $_GET['task']; //findRedmineTask.php
    $dbres = $mysql->SearchByTask($task);
    echo "<div class='h2-changes'>Принятие правок по задаче <a target='_blank' href='$redmine_url/redmine/issues/$task'>#$task</a>
    <a class='ml-1' target='_blank' href='findRedmineTask.php?task=$task'>(найти страницы с участием задачи)</a></div>";
} else {
    echo "<div class='h2-changes'>Выполнение отменено.<br>Не задан ни номер задачи, ни название страницы.</div>";
    return;
}

$cur_date = get_date_for_db();
echo "<input class='d-none' type='text' name='mode' value='" . (isset($task) ? 'task' : 'page') . "'/>";
echo "<input class='d-none' type='text' name='date' value='$cur_date'/>";

$eta += microtime(true);
echo "Время выполнения запроса в бд " . round($eta, 2) . " сек";
$eta = microtime(true) * -1;
// получаем страницу(ы) по запросу
if ($dbres) {
    $count = 0;
    $count_change = 0;
    while ($row = $mysql->fetch($dbres)) {
        $offset = 0;
        // ищем внутри теги <change>
        $row_text = $row['old_text'];
        $preg_res = preg_match_all($pattern_change, $row_text, $matches_change, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        foreach ($matches_change as $match_change) {
            if (!(isset($match_change[2][0]) && $match_change[2][0] != '')) {
                continue;
            }
            if ($match_change[2][0] != $task) {
                continue;
            }
            $count++;
            $search_result = '';
            $pattern = '/' . $pattern_before_change . '(' . preg_quote($match_change[0][0], '/') . ')' . $pattern_after_change . '/mu';
            // для каждого тега вытаскиваем превью с абзацем до и после
            if (preg_match($pattern, $row_text, $match_preview, 0, $offset) ) {
                $count_change++;
                $page_title = $row['page_title'];
                $id = getGUID();
                $change_after_block = null;
                $change_preparsed_before = parse_change($match_change[7][0], $match_change[2][0], $match_change[4][0], $change_before_block);
                $change_preparsed_after = null;
                $after_change = "";
                $internal_change = preg_replace('/<s\s*>[^<]*<\s*\/s\s*>/mui', '', $match_change[7][0]);
                if (isset($match_change[4][0]) && $match_change[4][0] != '' || isset($match_change[6][0]) && $match_change[6][0] != '' ) {
                    $after_change = "<change tag=\"" . $match_change[4][0] . "\" forpage=\"" . $match_change[6][0] . "\">" . $internal_change . "</change>";
                    $change_preparsed_after = parse_change($internal_change, '', $match_change[4][0], $change_after_block);
                } else {
                    $after_change = $internal_change;
                    $change_preparsed_after = $internal_change;
                }
                echo "<hr class='chunk-divider'>";
                if (isset($task)) {
                    $chunk_title = "<a target='_blank' href='/index.php/$page_title'>$page_title</a>";
                } else {
                    $chunk_title = "Задача <a target='_blank' href='$redmine_url/redmine/issues/" . $match_change[2][0] . "'>#" . $match_change[2][0] . "</a>
                    <a class='ml-1' target='_blank' href='findRedmineTask.php?task=$task'>(найти страницы с участием задачи)</a>";
                }
                $check_box = "<input class='ml-3' type='checkbox' name='check_list[]' value='$id'><label>Не забыть подправить вручную</label>";
                echo "<div class='d-inline-block'><div class='h2-changes d-inline-block'>$chunk_title</div>$check_box</div>";
                echo "<div class='h3-changes'>До принятия правок</div>";
                $search_result = $match_preview[1] . $match_preview[2] . $match_preview[3];

                $tag_change = $change_before_block ? "div" : "span";
                $search_result_for_wiki_api = $match_preview[1] . "<$tag_change>_change_placement_for_temp_replace_</$tag_change>" . $match_preview[3];

                // исключим все картинки
                $search_result_for_wiki_api = preg_replace($images_pattern, "[Объект mockup, image, figma или drawio]", $search_result_for_wiki_api);
                $html_before = $wiki_api->convertWikitextToHTML($search_result_for_wiki_api);
                $html_before = str_replace("_change_placement_for_temp_replace_", $change_preparsed_before, $html_before);
                echo "<div class='chunk-preview'>";
                if ($pure_code) {
                    echo "<xmp class='ws-bs'>" . $match_preview[1] . "</xmp>";
                    echo "<div><xmp class='ws-bs pure-code-background'>" . $match_preview[2] . "</xmp></div>";
                    echo "<xmp class='ws-bs'>" . $match_preview[3] . "</xmp>";
                } else {
                    echo $html_before;
                }
                echo "</div>";

                echo "<div class='h3-changes'>После принятия правок</div>";
                $replace_result = $match_preview[1] . $after_change . $match_preview[3];

                // исключим все картинки
                $preview_first_part = preg_replace($images_pattern, "[Объект mockup, image, figma или drawio]", $match_preview[1]);
                $preview_third_part = preg_replace($images_pattern, "[Объект mockup, image, figma или drawio]", $match_preview[3]);

                if (isset($change_after_block)) {
                    $tag_change = $change_after_block ? "div" : "span";
                    $replace_result_for_wiki_api = $preview_first_part . "<$tag_change>_change_placement_for_temp_replace_</$tag_change>" . $preview_third_part;
                } else {
                    $replace_result_for_wiki_api = $preview_first_part . $change_preparsed_after . $preview_third_part;
                }
                $html_after = $wiki_api->convertWikitextToHTML($replace_result_for_wiki_api);
                if (isset($change_after_block)) {
                    $html_after = str_replace("_change_placement_for_temp_replace_", $change_preparsed_after, $html_after);
                }

                echo "<div class='chunk-preview'>";
                if ($pure_code) {
                    echo "<xmp class='ws-bs'>" . $match_preview[1] . "</xmp>";
                    echo "<div><xmp class='ws-bs pure-code-background'>" . $after_change . "</xmp></div>";
                    echo "<xmp class='ws-bs'>" . $match_preview[3] . "</xmp>";
                } else {
                    echo $html_after;
                }
                echo "</div>";
                $tmp_result = $mysql->InsertUserHistoryItem($id, $cur_date, $autor, $search_result, $replace_result, $html_before, $html_after, $page_title, $match_change[2][0], $match_change[0][0], $after_change);
                $offset = $match_change[0][1] + 1;
            }
        }
    }
    if ($count != $count_change) {
        echo "<div class='h2-changes text-error'>В процессе обработки что-то полшо не так, обратитесь к администратору!</div>";
    }
}
$eta+=microtime(true);
echo "Время анализа данных " . round($eta, 2) . " сек";

function getGUID(){
    if (function_exists('com_create_guid')){
        return com_create_guid();
    }
    else {
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = "";//chr(45);// "-"
        $uuid = ""//chr(123)// "{"
            .substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12)
            ;//.chr(125);// "}"
        return $uuid;
    }
}

function parse_change($internalout, $task, $tag, &$is_block) {
    global $wiki_api;
    global $redmine_url;
    $title = "";
    $tagClass = "";
    $taskClass = "";
    $commonClass = "changes";
    $tagName = "span";
    $is_block = false;
    if (strpos($internalout, "\n") !== false) {
        $tagName = "div";
        $commonClass = "changes-right";
        $is_block = true;
    }
    if ($tagName == "div") {
        if (isset($tag) && $tag != "") {
            $tagClass = "changes-tag-right";
        }
        if (isset($task) && $task  != "") {
            $commonClass .= " in-develop";
            $taskClass = "changes-task-right changes-margin-right";
        }
        else {
            $commonClass .= " in-release";
            if ($tagClass != "") {
                $tagClass .= " changes-margin-right";
            }
        }
    } else {
        if (isset($tag) && $tag != "") {
            $tagClass = "changes-tag";
        }
        if (isset($task) && $task  != "") {
            $commonClass .= " in-develop";
            $taskClass = "changes-task";
        }
        else {
            $commonClass .= " in-release";
        }
    }
    if ($tagClass != "") {
        $title .= "<span class='".$tagClass."'>" . $tag . "</span>";
    }
    if ($taskClass != "") {
        $title .= "<span class='" . $taskClass . "'><a href='$redmine_url/redmine/issues/" . str_replace("#", "", $task) . "'>" . "#" . str_replace("#", "", $task) . "</a></span>";
    }
    if (!$is_block) {
        $body = str_replace("div", "span", $wiki_api->convertWikitextToHTML($internalout));
        $body = str_replace("pre", "span", $body);
        $body = str_replace("p>", "span>", $body);
    } else {
        $body = $wiki_api->convertWikitextToHTML($internalout);
    }
    $out = "<" . $tagName . " class='" . $commonClass . "'>" . $title . "<" . $tagName . " class='changes-content'>" . $body . "</" . $tagName . "></" . $tagName . ">";

    return $out;
}
?>

<input class='accept-button mt-3' type="submit" name="submit" value="Принять"/>
</form>
