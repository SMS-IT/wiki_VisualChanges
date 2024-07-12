<style>
<?php include 'site.css'; ?>
</style>
<?php
header('Access-Control-Allow-Origin: *');
date_default_timezone_set('Europe/Samara');
echo '<body class="common-background info-text-color">';
include "shared.php";
include "WikiSqlProvider.class.php";
include "WikiProvider.class.php";
$mysql = new WikiSqlProvider();
$autor = GetUser($mysql);
$wiki_api = new WikiProvider();

if (!(isset($autor) && $autor != '')){
    echo "<h1>Я тебя не знаю, авторизируйся на <a href='/'>wiki</a> еще раз пожалуйста.</h1>";
    return;
}
if(isset($_POST['submit']) && isset($_POST['mode']) && isset($_POST['date'])) {//to run PHP script on submit
    $mode = $_POST['mode'];
    $date = $_POST['date'];
    $dbres = $mysql->GetUsersHistoryItems($autor, $date);
    $checked_to_remark = [];
    if(!empty($_POST['check_list'])){
        $checked_to_remark = $_POST['check_list'];
    }
}
$show_header = true;
if ($dbres) {
    $result_pages = array();
    while ($row = $mysql->fetch($dbres)) {
        $id = $row['id'];
        $task = $row['task'];
        $page = $row['page_title'];
        if ($show_header) {
            $show_header = false;
            $header = null;
            $list_title = null;
            if ($mode == "task") {
                $header = "задаче <a target='_blank' href='$redmine_url/redmine/issues/$task'>#$task</a><a class='ml-1' href='history.php?task=$task'>(к истории)</a>
                <a class='ml-1' target='_blank' href='findRedmineTask.php?task=$task'>(найти страницы с участием задачи)</a>";
                $list_title = "Список измененных спецификаций";
            } else if ($mode = "page") {
                $header = "спецификации <a target='_blank' href='/index.php/$page'>$page</a><a class='ml-1' href='history.php?page=$page'>(к истории)</a>";
                $list_title = "Список измененных задач по спецификации";
            } else {
                echo "Ошибка: Не определен мод.";
                return;
            }
            echo "<h2>Принятие правок по $header (результат)</h2>";
            echo "<h3>$list_title</h3>";
        }
        $check_to_remark = false;
        if (in_array($id, $checked_to_remark)) {
            $check_to_remark = true;

        }
        $key = null;
        if ($mode == "task") {
            $key = $page;
            if (!isset($result_pages[$page])) {
                $result_pages[$page] = array();
                $result_pages[$page]["link"]="<a target='_blank' href='/index.php/$page'>$page</a><a class='ml-1' href='history.php?page=$page'>(к истории)</a>";
                $result_pages[$key]["replaced_count"] = 0;
                $result_pages[$key]["predicted_count"] = 0;
            }
        } else if ($mode = "page") {
            $key = $task;
            if (!isset($result_pages[$task])) {
                $result_pages[$task] = array();
                $result_pages[$task]["link"]="<a target='_blank' href='$redmine_url/redmine/issues/$task'>#$task</a>
                <a class='ml-1' href='history.php?task=$task'>(к истории)</a>
                <a class='ml-1' target='_blank' href='findRedmineTask.php?task=$task'>(найти страницы с участием задачи)</a>";
                $result_pages[$key]["replaced_count"] = 0;
                $result_pages[$key]["predicted_count"] = 0;
            }
        } else {
            echo "Ошибка: Не определен мод.";
            return;
        }
        $dbres_page = $mysql->SearchByPage($page);
        if ($dbres_page) {
            $page_row = $mysql->fetch($dbres_page);
            if ($page_row) {
                $new_text = str_replace($row["change_before"], $row["change_after"], $page_row['old_text'], $count);
                if ($count > 0) {
                    $result_pages[$key]["replaced_count"] += $count;
                    $result_api = $wiki_api->savePage($page, $new_text, $date, "Изменение с помощью утилиты принятия изменений пользователем " . $autor);
                    $mysql->AddHistoryItem($autor, $row["wikitext_before"], $row["wikitext_after"], $row["html_before"], $row["html_after"], $page, $task, $check_to_remark);
                } else {
                }
            } else {
                $result_pages[$key]['error'] = "<span class='ml-2 text-error'>Невозможно найти страницу $page</span>";
            }
        } else {
            $result_pages[$key]['error'] = "<span class='ml-2 text-error'>Невозможно найти страницу $page</span>";
        }
        if ($check_to_remark) {
            $result_pages[$key]['check_to_remark'] = "<span class='ml-2 text-info'>Подправь вручную</span>";
        }
        $mysql->RemoveUserHistoryItem($id);
        $result_pages[$key]["predicted_count"] += 1;
    }
    foreach ($result_pages as $values) {
        echo "<div>";
        echo $values["link"];
        if ($values["predicted_count"] != $values["replaced_count"]) {
            echo "<span class='ml-2 text-error'>Невозможно найти изменение, которое было зарезервировано, возможно страница " . $values["link"] . " была изменена</span>";
        }
        foreach ($values as $key_item => $value_item) {
            if ($key_item == "link") continue;
            if ($key_item == "predicted_count") continue;
            if ($key_item == "replaced_count") continue;
            echo $value_item;
        }
        echo "</div>";
    }
}
?>
