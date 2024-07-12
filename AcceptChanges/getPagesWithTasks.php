<style>
<?php include 'site.css'; ?>
</style>
<?php
header('Access-Control-Allow-Origin: *');
set_time_limit(3000);
date_default_timezone_set('Europe/Samara');

include "shared.php";
include "WikiSqlProvider.class.php";
include "RedmineSqlProvider.class.php";

$prefix = "";
$only_closed = false;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['prefix'])) {
        $prefix = $_POST['prefix'];
    }
    $prefix = MakeRightLink($prefix);
    if(isset($_POST['only_closed']) && $_POST['only_closed'] == "true"){
        $only_closed = true;
    }
}

echo ('<form method="post" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '" class="mainform">');
echo ('Страницы начинаются с:<br>');
echo ('<input type="text" name="prefix" value="' . $prefix . '" size=100>');
echo ('<br>');

echo ('<label>');
echo ('<input type="checkbox" name="only_closed" value="true" ' .  ($only_closed ? "checked" : "") . '> Показывать только закрытые задачи');
echo ('</label>');
echo ('<br>');
echo ('<input type="submit" name="submit" value="Выполнить">');
echo ('</form>');

if ($_SERVER["REQUEST_METHOD"] == "GET")
    return;

$mysql_wiki = new WikiSqlProvider();

$tasks_pages_dic = array();
$dbres = $mysql_wiki->GetAllWithChange($prefix);
if ($dbres) {
    while ($row = $mysql_wiki->fetch($dbres)) {
        $page_title = $row['page_title'];
        preg_match_all($pattern_simple_change, $row['old_text'], $matches_change, PREG_SET_ORDER);
        foreach ($matches_change as $match_change) {
            if (!(isset($match_change[1]) && $match_change[1] != '')) {
                continue;
            }
            $task = $match_change[1];
            if (!isset($tasks_pages_dic[$task])) {
                $tasks_pages_dic[$task] = array();
                $tasks_pages_dic[$task]['has_info'] = false;
                $tasks_pages_dic[$task]['pages'] = array();
            }
            if(!in_array($page_title, $tasks_pages_dic[$task]['pages'], true)){
                array_push($tasks_pages_dic[$task]['pages'], $page_title);
            }
        }
    }
}
$mysql_redmine = new RedmineSqlProvider();
if (!empty($tasks_pages_dic)) {
    $task_ids = array_keys($tasks_pages_dic);
    $dbres = $mysql_redmine->SearchByTasks($task_ids);
    if ($dbres) {
        while ($row = $mysql_redmine->fetch($dbres)) {
            $tasks_pages_dic[$row['id']]['has_info'] = true;
            $tasks_pages_dic[$row['id']]['subject'] = $row['subject'];
            $tasks_pages_dic[$row['id']]['status_id'] = $row['status_id'];
            $tasks_pages_dic[$row['id']]['status_name'] = $row['status_name'];
            $tasks_pages_dic[$row['id']]['tracker_name'] = $row['tracker_name'];
            if (isset($row['parent_id']) && $row['parent_id'] != '' && $row['parent_id'] != $row['id']) {
                $tasks_pages_dic[$row['id']]['has_parent'] = true;
                $tasks_pages_dic[$row['id']]['parent_id'] = $row['parent_id'];
                $tasks_pages_dic[$row['id']]['parent_subject'] = $row['parent_subject'];
                $tasks_pages_dic[$row['id']]['parent_status_id'] = $row['parent_status_id'];
                $tasks_pages_dic[$row['id']]['parent_status_name'] = $row['parent_status_name'];
                $tasks_pages_dic[$row['id']]['parent_tracker_name'] = $row['parent_tracker_name'];
            }
            else {
                $tasks_pages_dic[$row['id']]['has_parent'] = false;
            }
        }
    }
    echo "<ul>";
    foreach ($tasks_pages_dic as $task => $task_info) {
        if (!empty($task_info) && !empty($task_info['pages'])) {
            if ($task_info['has_info'] && $only_closed) {
                if ($task_info['status_id'] != '5' || $task_info['has_parent'] && $task_info['parent_status_id'] != '5')
                    continue;
            }
            echo "<li class='mt-2'>";
            echo "<div>";

            if ($task_info['has_info']) {
                $task_status = $task_info['status_name'];
                $task_name = $task_info['subject'];
                $tracker_name = $task_info['tracker_name'];
                echo "<div><a target='_blank' href='$redmine_url/redmine/issues/$task'>$tracker_name #$task</a>";
                echo "<span class='ml-1'>$task_name</span><span class='ml-1'>($task_status)</span>";
                if ($task_info['has_parent']) {
                    $parent_name = $task_info['parent_subject'];
                    $parent_id = $task_info['parent_id'];
                    $parent_status_name = $task_info['parent_status_name'];
                    $parent_tracker_name = $task_info['parent_tracker_name'];
                    echo "</div><div class='ml-3'>";
                    echo "<span>Родительская:</span>";
                    echo "<a class='ml-1' target='_blank' href='$redmine_url/redmine/issues/$parent_id'>$parent_tracker_name #$parent_id</a>";
                    echo "<span class='ml-1'>$parent_name</span><span class='ml-1'>($parent_status_name)</span>";
                }
            } else {
                echo "<div><a target='_blank' href='$redmine_url/redmine/issues/$task'>#$task</a>";
            }
            echo "</div>";
            echo "</div>";
            echo "<ul>";
            foreach ($task_info['pages'] as $page) {
                echo "<li><a target='_blank' href='$wiki_url/index.php/$page'>$page</a></li>";
            }
            echo "</ul>";
            echo "</li>";
        }
    }
    echo "</ul>";

    echo "<div>Всего задач " . count($tasks_pages_dic)  . "</div>";
}

// выправление русского названия страницы
function MakeRightLink($link) {
	$res = preg_replace_callback(
		'/(^ *.)|(\/.)/u',
		function($match) {
			return mb_convert_case($match[0], MB_CASE_UPPER);
		}, $link);
	return $res;
}
?>
