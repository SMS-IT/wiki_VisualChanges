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
<?php
set_time_limit(3000);
date_default_timezone_set('Europe/Samara');
echo '<body class="mediawiki ltr sitedir-ltr mw-hide-empty-elt ns-0 ns-subject skin-vector action-view common-background">';

include "shared.php";
include "WikiSqlProvider.class.php";

$mysql = new WikiSqlProvider();

$autor = GetUser($mysql);

if (!(isset($autor) && $autor != '')){
    echo "<div class='warn'>Я тебя не знаю, авторизируйся на <a href='/'>wiki</a> еще раз пожалуйста.</div>";
    return;
}

if (!$mysql->CreateTablesIfNotExists()) {
    echo "<div class='warn'>Что-то не так: не могу проверить существование нужных для меня таблиц в БД или создать их.</div>";
    return;
}

$dbres = null;
$task = null;
if (isset($_GET['page'])) {
    $main_request_string = $_GET['page'];
    $dbres = $mysql->GetHistoryItemsByPage($main_request_string);
    echo "<div class='h2-changes'><a target='_blank' href='/index.php/$main_request_string'>$main_request_string</a> - история принятия правок по задачам</div>";
} else if (isset($_GET['task'])) {
    $task = $_GET['task'];
    $dbres = $mysql->GetHistoryItemsByTask($task);
    echo "<div class='h2-changes'>История принятия правок по задаче <a target='_blank' href='$redmine_url/redmine/issues/$task'>#$task</a>
    <a class='ml-1' target='_blank' href='findRedmineTask.php?task=$task'>(найти страницы с участием задачи)</a></div>";
} else {
    echo "<div class='h2-changes'>Выполнение отменено.<br>Не задан ни номер задачи, ни название страницы.</div>";
    return;
}

// получаем страницу(ы) по запросу
if ($dbres) {
    while ($row = $mysql->fetch($dbres)) {
        $page_title = $row['page_title'];
        $html_before = $row['html_before'];
        $html_after = $row['html_after'];
        $initiator = $row['autor'];
        $row_task = $row['task'];
        $need_fix = $row['need_fix'] == 1 ? true : false;
        $date_db = $row['date'];
        $after_change = "";
        $date = DateTime::createFromFormat(
            "Y-m-d H:i:s",
            $date_db,
            new DateTimeZone('UTC')
        );
        $date->setTimeZone(new DateTimeZone('Europe/Samara'));

        echo "<hr class='chunk-divider'>";
        $change_info = "<span>" . $date->format('d.m.Y H:i') . "</span>&nbsp;<span>Участник: $initiator</span>";
        if (isset($task)) {
            $chunk_title = "<a target='_blank' href='/index.php/$page_title'>$page_title</a><a class='pl-1' href='history.php?page=$page_title'>(все по спецификации)</a>";
        } else {
            $chunk_title = "Задача <a target='_blank' href='$redmine_url/redmine/issues/$row_task'>#$row_task</a><a class='pl-1' href='history.php?task=$row_task'>(все по задаче)</a>
            <a class='ml-1' target='_blank' href='findRedmineTask.php?task=$row_task'>(найти страницы с участием задачи)</a>";
        }
        $check_box = "<input class='ml-3 d-none' type='checkbox' disabled " . ($need_fix ? "checked" : "") . "><label class='d-none'>Не забыть подправить вручную</label>";
        echo "<div class='d-inline-block'><div class='h2-changes d-inline-block'>$change_info $chunk_title</div>$check_box</div>";

        echo "<div class='h3-changes mt-0'>До принятия правок</div>";
        echo "<div class='chunk-preview'>";
        echo $html_before;
        echo "</div>";

        echo "<div class='h3-changes'>После принятия правок</div>";
        echo "<div class='chunk-preview'>";
        echo $html_after;
        echo "</div>";
    }
}
?>
