<?php
//$endPoint = "https://en.wikipedia.org/w/api.php";

$wiki_apiLogin = "";
$wiki_apiPassword = "";
/*
	Wiki API Provider
*/
class WikiProvider {
	var $login;
	var $password;
	var $endPoint = "http://127.0.0.1/api.php";
	var $cookiePath;

	public function __construct()
	{
		global $wiki_apiLogin;
		global $wiki_apiPassword;
		$this->cookiePath = '/tmp/wiki_changes.cookie.txt';
		$this->login = $wiki_apiLogin;
		$this->password = $wiki_apiPassword;
	}
	public function login( $login, $password )
	{
		$this->login = $login;
		$this->password = $password;
	}

	public function savePage($page, $text, $time, $summary, $first_launch = true) {
		$csrf = $this->getCsrfToken();
		$params_local = [
			"action" => "visualeditoredit",
			"wikitext" => $text,
			"paction" => "save",
			"page" => $page,
			"minor" => true,
			"token" => $csrf,
			"summary" => $summary,
			"format" => "json"
		];

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_URL, $this->endPoint );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $params_local ) );
		curl_setopt( $ch, CURLOPT_COOKIEJAR, $this->cookiePath );
		curl_setopt( $ch, CURLOPT_COOKIEFILE, $this->cookiePath );
		$output = curl_exec( $ch );
		curl_close( $ch );
		$result = json_to_array($output);
		// autologin to wiki
		if ($first_launch && $this->loginIfUnauthorized($result)) {
			return $this->savePage($page, $text, $time, $summary, false);
		}
		return $result;
	}

	public function convertWikitextToHTML($textToConvert, $first_launch = true) {
		$params_local = [
			"action" => "parse",
			"text" => $textToConvert,
			"redirects" => true,
			"format" => "json",
			"contentmodel" => "wikitext",
			"servedby" => true,
			"sectionpreview" => true,
			"disablepp" => true,
			"formatversion" => "2",
		];

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_URL, $this->endPoint );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $params_local ) );
		curl_setopt( $ch, CURLOPT_COOKIEJAR, $this->cookiePath );
		curl_setopt( $ch, CURLOPT_COOKIEFILE, $this->cookiePath );
		$output = curl_exec( $ch );
		curl_close( $ch );

		$result = json_to_array($output);

		// autologin to wiki
		if ($first_launch && $this->loginIfUnauthorized($result)) {
			return $this->convertWikitextToHTML($textToConvert, false);
		}
		return $result["parse"]["text"];//["*"];
	}

	protected function loginIfUnauthorized($response) {
		if (isset($response["error"])) {
			if ($response["error"]["code"] == "readapidenied" || $response["error"]["code"] == "writeapidenied") {
				$this->loginRequest();
				return true;
			} else {
				var_dump($response);
				return false;
			}
		}
		return false;
	}

	protected function getCsrfToken() {
		$params_csrf_token = [
			"action" => "query",
			"meta" => "tokens",
			"format" => "json"
		];
		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_URL, $this->endPoint );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $params_csrf_token ) );
		curl_setopt( $ch, CURLOPT_COOKIEJAR, $this->cookiePath );
		curl_setopt( $ch, CURLOPT_COOKIEFILE, $this->cookiePath );

		$output = curl_exec( $ch );
		curl_close( $ch );
		$result = json_to_array( $output );
		return $result["query"]["tokens"]["csrftoken"];
	}

	// =============== LOGIN ===============
	// Step 2: POST request to log in. Use of main account for login is not
	// supported. Obtain credentials via Special:BotPasswords
	// (https://www.mediawiki.org/wiki/Special:BotPasswords) for lgname & lgpassword
	protected function loginRequest() {
		$logintoken = $this->getLoginToken();
		$params_login = [
			"action" => "login",
			"lgname" => $this->login,
			"lgpassword" => $this->password,
			"lgtoken" => $logintoken,
			"format" => "json",
		];

		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_URL, $this->endPoint );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $params_login ) );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_COOKIEJAR, $this->cookiePath );
		curl_setopt( $ch, CURLOPT_COOKIEFILE, $this->cookiePath );

		$output = curl_exec( $ch );
		curl_close( $ch );
	}
	// Step 1: GET request to fetch login token
	protected function getLoginToken() {
		$params_get_login_token = [
			"action" => "query",
			"meta" => "tokens",
			"type" => "login",
			"format" => "json"
		];

		$url = $this->endPoint . "?" . http_build_query( $params_get_login_token );

		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_COOKIEJAR, $this->cookiePath );
		curl_setopt( $ch, CURLOPT_COOKIEFILE, $this->cookiePath );

		$output = curl_exec( $ch );
		curl_close( $ch );
		$result = json_to_array( $output, true );
		return $result["query"]["tokens"]["logintoken"];
	}
}

// перед выполнением json_decode мы выполняем уничтожение байтов маркеров
//https://stackoverflow.com/questions/13582930/json-decode-return-null-utf-8-bom
function json_to_array($input) {
	return json_decode( ltrim($input, chr(239).chr(187).chr(191)), true );
}
?>
