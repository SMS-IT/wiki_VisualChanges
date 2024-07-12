<?php
include_once "MysqlConnector.class.php";
/*
Mysql connector
*/
class RedmineSqlProvider {
    var $dbserver = "";
    var $dbuser = "";
    var $dbpassword = "";
    var $dbencoding = "utf8";
    var $database = "";

    var $mysql = null;

    public function __construct()
    {
        $this->Connect();
    }

    public function Connect() {
        $connector = new MysqlConnector($this->dbserver, $this->dbuser, $this->database, $this->dbpassword, $this->dbencoding);
        $connector->connect();
        $connector->execute( "USE '". $this->database ."';" );
        $this->mysql = $connector;
    }

    public function SearchByTasks($tasks) {
        $tasks_str = join(",", $tasks);
        $sql_query =
"SELECT child.id,
	child.subject,
	child.status_id,
	status_child.`name` AS status_name,
	tracker_child.`name` AS tracker_name,
	parent.id AS parent_id,
	parent.subject AS parent_subject,
	parent.status_id AS parent_status_id,
	status_parent.`name` AS parent_status_name,
	tracker_parent.`name` AS parent_tracker_name
FROM issues AS child
LEFT JOIN issues AS parent ON child.root_id = parent.id
INNER JOIN issue_statuses AS status_child ON child.status_id = status_child.id
INNER JOIN issue_statuses AS status_parent ON parent.status_id = status_parent.id
INNER JOIN trackers AS tracker_child ON child.tracker_id = tracker_child.id
INNER JOIN trackers AS tracker_parent ON parent.tracker_id = tracker_parent.id
WHERE child.id IN ($tasks_str)";

        return $this->mysql->execute($sql_query);
    }

	// ret all subtasks for specified task
	public function GetSubtasks($task_id) {
        $sql_query = "SELECT root_id, lft, rgt FROM issues WHERE id = " . $task_id;
        $dbres = $this->mysql->execute($sql_query);
		if ($dbres) {
			while ($row = $this->fetch($dbres)) {
				$root_id = $row['root_id'];
				$left  = $row['lft'];
				$right = $row['rgt'];
			}
		}

        $sql_query = "SELECT i.id FROM issues i WHERE i.root_id = $root_id AND i.lft >= $left AND i.rgt <= $right";
        return $this->mysql->execute($sql_query);
    }

    public function fetch($dbres) {
        return $this->mysql->fetch($dbres);
    }
}


?>
