<?php
include_once "MysqlConnector.class.php";
/*
Mysql connector
*/
class WikiSqlProvider {
    var $dbserver = "localhost";
    var $dbuser = "";
    var $dbpassword = "";
    var $dbencoding = "utf8";
    var $database = "";
    var $pages_request = "SELECT * 
	  FROM page p, revision r, text t
      WHERE p.page_latest = r.rev_id
      AND t.old_id = r.rev_text_id ";

    var $mysql = null;

	public function __construct()
	{
        $this->ConnectWiki();
	}

    public function ConnectWiki() {
        $connector = new MysqlConnector($this->dbserver, $this->dbuser, $this->database, $this->dbpassword, $this->dbencoding);
        $connector->connect();
        $connector->execute( "USE '". $this->database ."';" );
        $this->mysql = $connector;
    }

    //'YYYY-MM-DD HH:MM:SS'
    public function CreateTablesIfNotExists() {
        $c1 = $this->mysql->execute(
            "CREATE TABLE IF NOT EXISTS `ext_accept_changes_user_changes` (
            `id` CHAR(32) PRIMARY KEY,
            `date` DATETIME NOT NULL,
            `autor` VARCHAR(250) NOT NULL,
            `task` VARCHAR(10) NOT NULL,
            `page_title` TEXT NOT NULL,
            `change_before` TEXT NOT NULL,
            `change_after` TEXT NOT NULL,
            `wikitext_before` TEXT NOT NULL,
            `wikitext_after` TEXT NOT NULL,
            `html_before` TEXT NOT NULL,
            `html_after` TEXT NOT NULL)
            ENGINE = InnoDB;");
        $c2 = $this->mysql->execute(
            "CREATE TABLE IF NOT EXISTS `ext_accept_changes_history` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `autor` VARCHAR(250) NOT NULL,
            `date` DATETIME NOT NULL,
            `task` VARCHAR(10) NOT NULL,
            `need_fix` BOOLEAN NOT NULL,
            `page_title` TEXT NOT NULL,
            `wikitext_before` TEXT NOT NULL,
            `wikitext_after` TEXT NOT NULL,
            `html_before` TEXT NOT NULL,
            `html_after` TEXT NOT NULL)
            ENGINE = InnoDB;");
            return $c1 && $c2;
    }

    public function AddHistoryItem($autor, $wikitext_before, $wikitext_after, $html_before, $html_after, $page_title, $task, $need_fix) {
        $need_fix_reqest = $need_fix ? 1 : 0;
        $date = get_date_for_db();
        $query = "INSERT INTO ext_accept_changes_history (`autor`,`date`,`task`,`need_fix`,`page_title`,`wikitext_before`,`wikitext_after`,`html_before`,`html_after`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->mysql->db->prepare($query);
        $stmt->bind_param('sssdsssss', $autor,$date,$task,$need_fix_reqest,$page_title,$wikitext_before,$wikitext_after,$html_before,$html_after);
        return $stmt->execute();
    }

	// список принятых правок по 1 задаче
    public function GetHistoryItemsByTask($task) {
        $query = "SELECT * FROM ext_accept_changes_history WHERE task='$task' ORDER BY date DESC";
        return $this->mysql->execute($query);
    }

	// список принятых правок по массиву задач
    public function GetHistoryItemsBySubTasks($tasks) {
        $query = "SELECT * FROM ext_accept_changes_history WHERE task in (". implode(",", $tasks) . ") ORDER BY date DESC";
        return $this->mysql->execute($query);
    }

    public function GetHistoryItemsByPage($page_title) { //todo возможно потом нужно будет вводить пэйджинг
        $page_title_spaceless = str_replace(' ', '_', $page_title);

        $sql_query = "SELECT * FROM ext_accept_changes_history 
        WHERE page_title = '" . $page_title . "' 
        OR page_title = '" . $page_title_spaceless . "'
        ORDER BY date DESC";

        return $this->mysql->execute($sql_query);
    }

    public function ClearTmpHistoryForUser($autor) {
        $start_date = gmdate("Y-m-d 00:00:00");
        return $this->mysql->execute(
            "DELETE FROM ext_accept_changes_user_changes
            WHERE autor = '$autor' and `date` < '$start_date'");
    }

    public function InsertUserHistoryItem($id, $date, $autor, $wikitext_before, $wikitext_after, $html_before, $html_after, $page_title, $task, $change_before, $change_after) {
        $query = "INSERT INTO ext_accept_changes_user_changes (`id`,`date`,`autor`,`task`,`page_title`,`change_before`,`change_after`,`wikitext_before`,`wikitext_after`,`html_before`,`html_after`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->mysql->db->prepare($query);
        $stmt->bind_param('sssssssssss', $id, $date, $autor, $task, $page_title, $change_before, $change_after, $wikitext_before, $wikitext_after, $html_before, $html_after);
        return $stmt->execute();
    }

    public function GetUserById($userId) {
        $dbres = $this->mysql->execute("SELECT user_name FROM user WHERE user_id = '$userId'");
        if ($dbres) {
            $row = $this->mysql->fetch($dbres);
            return $row['user_name'];
        }
    }

    public function RemoveUserHistoryItem($id) {
        $this->mysql->execute("DELETE FROM ext_accept_changes_user_changes WHERE id = '$id'");
    }

    public function SearchByPage($main_request_string) {
        $sql_query = $this->pages_request;
        $main_request_string_spaceless = str_replace(' ', '_', $main_request_string);

        $sql_query .= "AND (p.page_title = '" . $main_request_string . "' 
        OR p.page_title = '" . $main_request_string_spaceless . "')";

        return $this->mysql->execute($sql_query);
    }

	// поиск спек с ченжами на одну задачу
    public function SearchByTask($task) {
        $sql_query = $this->pages_request;
        $sql_query .= "AND t.old_text like '%change%$task%'";
        return $this->mysql->execute($sql_query);
    }

	// поиск спек с ченжами на задачу и все ее подзадачи
    public function SearchBySubTasks($tasks) {
        $sql_query = $this->pages_request;
        $sql_query .= "AND ( (1=0) ";
		foreach ($tasks as $task) {
			$sql_query .= "	OR (t.old_text like '%change%$task%') ";
		}
		$sql_query .= " )";

        return $this->mysql->execute($sql_query);
    }

    public function GetAllWithChange($prefix = "") {
        $sql_query = $this->pages_request;
        if (isset($prefix) && $prefix != "") {
            $link = str_replace(' ', '_', $prefix);

            $link_encoded = urlencode($link);
            $link_spaces = str_replace(' ', '_', $link);
            $sql_query .= "AND (p.page_title like '". str_replace('%', '\%', $link) . "%'
            or p.page_title like '". str_replace('%', '\%', $link_spaces) . "%'
            or p.page_title like '". str_replace('%', '\%', $link_encoded) . "%') ";
        }
        $sql_query .= "AND t.old_text like '%change%'";

        return $this->mysql->execute($sql_query);
    }



    public function GetUsersHistoryItems($user, $date) {
        return $this->mysql->execute("SELECT * FROM ext_accept_changes_user_changes WHERE autor = '$user' and `date` = '$date'");
    }

    public function UpdatePageText($text_old_id, $newtext) {
        $sql_update = "UPDATE text
        SET text.old_text = ?
        WHERE text.old_id = " . $text_old_id;

        $stmt = $this->mysql->db->prepare($sql_update);
        $stmt->bind_param("s", $newtext);
        return $stmt->execute();
    }


    public function fetch($dbres) {
        return $this->mysql->fetch($dbres);
    }
}

function get_date_for_db() {
    return gmdate("Y-m-d H:i:s");
}

?>
