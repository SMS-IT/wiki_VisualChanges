<?php
header('Access-Control-Allow-Origin: *');
set_time_limit(3000);
date_default_timezone_set('Europe/Samara');

include "shared.php";
include "WikiSqlProvider.class.php";
include "RedmineSqlProvider.class.php";

$task = null;
if (isset($_GET['task'])) {
    $task = $_GET['task'];
} else {
    return;
}


// get subtasks list
$tasks = array();
$mysql_redmine = new RedmineSqlProvider();
{
	$dbres = $mysql_redmine->GetSubtasks($task);
	if ($dbres) {
		while ($row = $mysql_redmine->fetch($dbres)) {
			$subtask = $row['id'];
			$tasks[] = $subtask;
		}
	}
}

// (1) страницы с непринятыми правками
$mysql = new WikiSqlProvider();
$pages_with_task = array();
$dbres = $mysql->SearchBySubTasks($tasks);
if ($dbres) {
    while ($row = $mysql->fetch($dbres)) {
        $page_title = $row['page_title'];
        preg_match_all($pattern_simple_change, $row['old_text'], $matches_change, PREG_SET_ORDER);
        foreach ($matches_change as $match_change) {
            if (!(isset($match_change[1]) && $match_change[1] != '')) {
                continue;
            }
            if (! in_array($match_change[1], $tasks)) {
                continue;
            }
            if(!in_array($page_title, $pages_with_task, true)){
                array_push($pages_with_task, $page_title);
            }
        }
    }
}


// (2) получаем страницу(ы) по запросу из истории принятия правок - с принятыми правками

$history_with_task = array();
$dbres = $mysql->GetHistoryItemsBySubTasks($tasks);

if ($dbres) {
    while ($row = $mysql->fetch($dbres)) {
        $page_title = $row['page_title'];
        if(!in_array($page_title, $history_with_task, true)){
            array_push($history_with_task, $page_title);
        }
    }
}

if (!empty($pages_with_task)) {
    echo "<p><strong>Непринятые правки </strong><a target='_blank' href='$accept_changes_url?task=$task'>(принять)</a></p>";
    echo "<ul>";
    foreach ($pages_with_task as $page_with_task) {
        echo "<li><a target='_blank' href='$wiki_url/index.php/$page_with_task'>$page_with_task</a></li>";
    }
    echo "</ul>";
}

if (!empty($history_with_task)) {
    echo "<p><strong>Принятые правки</strong></p>";
    echo "<ul>";
    foreach ($history_with_task as $page_with_task) {
        echo "<li><a target='_blank' href='$wiki_url/index.php/$page_with_task'>$page_with_task</a></li>";
    }
    echo "</ul>";
}
?>
