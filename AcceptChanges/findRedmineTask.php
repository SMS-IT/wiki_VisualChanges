<!DOCTYPE HTML>
<html>
<head>
	<style>
	body { margin:0px; pading:10px; }

	.mainform {
		border-top: 10px solid #707070;
		border-bottom: 10px solid #707070;
		margin:0px;
		padding: 10px;
		width:calc(100% - 20px);
		background: #F0F0F0;
		display: block;
	}

	.error {color: #FF0000;}

	.card {
		border: 5px solid orange;
		display: block;
		margin: 10px;
		padding: 10px;
	}

	.step {
		background: #E0E0C0;
		padding: 5px;
		margin: -10px -10px 5px -10px;
	}

	.pagebox {
		border: dotted 1px #88db88;
		padding: 5px;
		margin: 2px 0px;
		background: #F7FFF7;
	}
	</style>
</head>

<body>

<?php
	// define variables and set to empty values
	$taskError = $prefix = $task = "";

	header('Access-Control-Allow-Origin: *');
	set_time_limit(3000);

	include "MysqlConnector.class.php";

	if ($_SERVER["REQUEST_METHOD"] == "GET") {
		if (isset($_GET['task']) && $_GET['task'] != "") {
			$task = $_GET['task'];
			StartSearch($task);
		} else {
			$taskError = "Необходим номер задачи";
		}
	}

	if ($_SERVER["REQUEST_METHOD"] == "POST") {
		if (isset($_POST['prefix'])) {
			$prefix = $_POST['prefix'];
		}
		$prefix = MakeRightLink($prefix);
		if (isset($_POST['task']) && $_POST['task'] != "") {
			$task = $_POST['task'];
			StartSearch($task, $prefix);
		} else {
			$taskError = "Необходим номер задачи";
		}
	}

	function StartSearch($task, $prefix = "") {
		$wiki_connector = ConnectWiki();
		$sql = "SELECT * FROM page p, revision r, text t
		WHERE p.page_latest = r.rev_id
		AND t.old_id = r.rev_text_id ";
		if (isset($prefix) && $prefix != "") {
			$link = str_replace(' ', '_', $prefix);

			$link_encoded = urlencode($link);
			$link_spaces = str_replace(' ', '_', $link);
			$sql .= "AND (p.page_title like '". str_replace('%', '\%', $link) . "%'
			or p.page_title like '". str_replace('%', '\%', $link_spaces) . "%'
			or p.page_title like '". str_replace('%', '\%', $link_encoded) . "%') ";
		}
		$sql .= "AND t.old_text like '%$task%'";

		$dbres = $wiki_connector->execute( $sql );
		if ($dbres) {
			while ($row = $wiki_connector->fetch($dbres)) {
				$page_title = $row['page_title'];

				$regexPrev = "/((?:\s|\S){0,40}(?:[^\d]|^))(" . preg_quote($task, '/') . ")((?:[^\d](?:\s|\S){0,40})|$)/mu";
				preg_match_all ($regexPrev, $row['old_text'], $outPreview, PREG_SET_ORDER);
				if (count($outPreview) > 0) {
					echo "<div class='pagebox'>" .
					"Страница: " . MakeWikiInfoLink($page_title, $page_title);
					foreach ($outPreview as $preview) {
						$echoStr = htmlspecialchars($preview[1]) . "<span style='background: lightgray'>". htmlspecialchars($preview[2]) . "</span>" . htmlspecialchars($preview[3]);
						echo "<li>...$echoStr...</li>";
					}
					echo "</div>";
				}
			}
		} else {
			echo '<br>НЕ ПОЛУЧЕНО ДАНЫХ ИЗ ВИКИ:' . $wiki_connector->getError();
		}
	}

	//========================================

	// выправление русского названия страницы
	function MakeRightLink($link) {
		$res = preg_replace_callback(
			'/(^ *.)|(\/.)/u',
			function($match) {
				return mb_convert_case($match[0], MB_CASE_UPPER);
			}, $link);
		return $res;
	}

	//
	function MakeWikiInfoLink($ref, $title, $sel = "") {
		$title = str_replace($sel, "<span style='background:#E0E0E0;'>$sel</span>", $title);
		return "<a target='_blank' href='/index.php/" . $ref . "'>[" . $title . "]</a>";
	}

	function ConnectWiki() {

		$wiki_dbserver = "";
		$wiki_dbuser = "";
		$wiki_dbpassword = "";
		$wiki_dbencoding = "utf8";
		$wiki_database = "";

		$connector = new MysqlConnector($wiki_dbserver, $wiki_dbuser, $wiki_database, $wiki_dbpassword, $wiki_dbencoding);
		$connector->connect();
		$connector->execute( "USE '". $wiki_database ."';" );
		return $connector;
	}
?>

<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" class="mainform">

  Найти все страницы, где участвует номер задачи:<br>
  <input type="text" name="task" value="<?php echo $task;?>" size=100>

  <span class="error">*</span>
  <div class="error"><?php echo $taskError;?></div>

  И страницы начинаются с:<br>
  <input type="text" name="prefix" value="<?php echo $prefix;?>" size=100>
  <br>

  <input type="submit" name="submit" value="Выполнить">
</form>

</body>
</html>
