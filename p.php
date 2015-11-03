<?php
//error_reporting(E_ALL);
//ini_set("display_errors", 1);
header('Content-type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');

date_default_timezone_set('Europe/Warsaw');
locale_set_default('pl');
set_time_limit(30);

define("STR_FILENAME", "GENERATED_STRINGS_FOR_USERS.txt");
define("TABLE_FILENAME", "TABLE_PASSWORDS_LIST.txt");

function base64url_encode($data) { 
  return rtrim(strtr(base64_encode($data), '+/', '-_'), '='); 
} 

function base64url_decode($data) { 
  return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT)); 
}

function crypto_rand_secure($min, $max)
{
    $range = $max - $min;
    if ($range < 1) return $min; // not so random...
    $log = ceil(log($range, 2));
    $bytes = (int) ($log / 8) + 1; // length in bytes
    $bits = (int) $log + 1; // length in bits
    $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
    do {
        $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
        $rnd = $rnd & $filter; // discard irrelevant bits
    } while ($rnd >= $range);
    return $min + $rnd;
}

function getToken($length)
{
    $token = "";
    $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
    $codeAlphabet.= "0123456789";
    $max = strlen($codeAlphabet) - 1;
    for ($i=0; $i < $length; $i++) {
        $token .= $codeAlphabet[crypto_rand_secure(0, $max)];
    }
    return $token;
}

function rand_text()
{
	$f_contents = file("wiersze_o_milosci.txt"); 
    return str_replace("\n", "", $f_contents[rand(0, count($f_contents) - 1)]);
}

function gen_empty_users_strings_file($file) 
{
	$json = json_encode([]);
	fwrite($file, $json);
	
	return $json;
}

function gen_empty_table_file($file) 
{
	$json = json_encode([]);
	fwrite($file, $json);
	
	return $json;
}

function mbStringToArray ($string) { 
    $strlen = mb_strlen($string); 
    while ($strlen) { 
        $array[] = mb_substr($string,0,1,"UTF-8"); 
        $string = mb_substr($string,1,$strlen,"UTF-8"); 
        $strlen = mb_strlen($string); 
    } 
    return $array; 
} 

/* 
* A mathematical decimal difference between two informed dates 
*
* Author: Sergio Abreu
* Website: http://sites.sitesbr.net
*
* Features: 
* Automatic conversion on dates informed as string.
* Possibility of absolute values (always +) or relative (-/+)
*/
function s_datediff( $str_interval, $dt_menor, $dt_maior, $relative=false)
{
       if( is_string( $dt_menor)) $dt_menor = DateTime::createFromFormat('d/m/Y H:i:s', $dt_menor);
       if( is_string( $dt_maior)) $dt_maior = DateTime::createFromFormat('d/m/Y H:i:s', $dt_maior);

       $diff = date_diff( $dt_menor, $dt_maior, ! $relative);
       
       switch( $str_interval){
           case "y": 
               $total = $diff->y + $diff->m / 12 + $diff->d / 365.25; break;
           case "m":
               $total= $diff->y * 12 + $diff->m + $diff->d/30 + $diff->h / 24;
               break;
           case "d":
               $total = $diff->y * 365.25 + $diff->m * 30 + $diff->d + $diff->h/24 + $diff->i / 60;
               break;
           case "h": 
               $total = ($diff->y * 365.25 + $diff->m * 30 + $diff->d) * 24 + $diff->h + $diff->i/60;
               break;
           case "i": 
               $total = (($diff->y * 365.25 + $diff->m * 30 + $diff->d) * 24 + $diff->h) * 60 + $diff->i + $diff->s/60;
               break;
           case "s": 
               $total = ((($diff->y * 365.25 + $diff->m * 30 + $diff->d) * 24 + $diff->h) * 60 + $diff->i)*60 + $diff->s;
               break;
          }
       if( $diff->invert)
		return -1 * $total;
       else return $total;
}

function test($var) 
{
	if(empty($var["token"])) {
		return s_datediff("s", $var["generated"], new DateTime()) < 60; // 1 min
	}
	else {
		return s_datediff("s", $var["generated"], new DateTime()) < 300; // 5 min
	}
}

$idspecified = '';
function test2($var) 
{
	global $idspecified;
	return strcmp($var["id"], $idspecified) !== 0; 
}

function refresh_delete_ids($file, $filecontent, $delSpecifiedId = "")
{
	global $idspecified;
	
	$arr = json_decode($filecontent, true);

	if(empty($delSpecifiedId)) {
		//print_r($arr);
		//echo "\n\n\n\n";
		$arr = array_filter($arr, 'test');
		$arr = array_values($arr);
		//print_r($arr);
		//echo "\n\n\n\n";
	}
	else {
		$idspecified = $delSpecifiedId;
		$arr = array_filter($arr, 'test2');
	}
	
	ftruncate($file, 0);
	$encoded_to_write = json_encode($arr);
	fwrite($file, $encoded_to_write);
	
	return $encoded_to_write;
}

function clean($string) {
   //$string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
  // return preg_replace('/[^A-Za-z0-9\ ]/', '', $string); // Removes special chars.
  return preg_replace('/[^\s\p{L}]/u','',$string);
}

function gen_table_for_user()
{
	ini_set('auto_detect_line_endings', true);
	$file_table = fopen(constant("TABLE_FILENAME"), "a+") or die("Unable to open file");
	$fsize = fstat($file_table)['size'];
	if($fsize <= 0) {
		$filecontent = gen_empty_table_file($file_table);
		$fsize = fstat($file_table)['size'];
	}
	else {
		$filecontent = fread($file_table, $fsize);
		//$filecontent = refresh_delete_ids($file_table, $filecontent);
	}
	
	fclose($file_table);
	return $filecontent;
}

function session_expired()
{
	$json = json_encode(
			[
				"type" => "error",
				"text" => "Twoja sesja wygasła. Spróbuj zalogować się ponownie."
			]
		);
	echo $json;
}

function add_new_to_array($array, $id, $text, $token)
{
	array_push($array, ['id' => $id, 'user_agent' => $_SERVER['HTTP_USER_AGENT'], 'ip' => $_SERVER['REMOTE_ADDR'], 'generated' => date('d/m/Y H:i:s', time()), 'text' => $text, 'token' => $token]);
	return $array;
}

function get_arr_with_id($filecontent, $id)
{
	$arr = json_decode($filecontent, true);
	
	$foundArr = NULL;
	foreach ($arr as $value) 
	{
		if($value["id"] === $id)
		{
			$foundArr = $value;
			break;
		}	
	}
	
	return $foundArr;
}

function add_update_user_token($file, $filecontent, $foundArr)
{
	if($foundArr !== NULL) {
		$arr = json_decode($filecontent, true);
		$token = getToken(50);
		$arr = add_new_to_array($arr, $foundArr["id"], $foundArr["text"], $token);
		ftruncate($file, 0);
		$encoded_to_write = json_encode($arr);
		fwrite($file, $encoded_to_write);
		
		return $token;
	}
	else {
		session_expired();
	}
	
	return NULL;
}

function check_user_password($file, $filecontent, $decoded_pass, $id)
{
	$arr = json_decode($filecontent, true);
	
	$foundArr = get_arr_with_id($filecontent, $id);
	
	if($foundArr !== NULL) {
		$filecontent = refresh_delete_ids($file, $filecontent, $foundArr["id"]);
		
		$err = false;
		if(strcmp($foundArr["user_agent"], $_SERVER['HTTP_USER_AGENT']) !== 0)
		{
			$err = true;
		}
		else if(strcmp($foundArr["ip"], $_SERVER['REMOTE_ADDR']) !== 0)
		{
			$err = true;
		}
		
		$lastdate = intval(mb_substr(date('d', time()), -1));
		$ind = 1;
		$correctpass = '';
		$str = preg_replace('/\s+/', '', clean($foundArr["text"]));
		$chars = mbStringToArray($str);
		foreach($chars as $char){
			if($ind === $lastdate) {
				$correctpass .= $char;
				$ind = 1;
			}
			else $ind++;
		}
		
		if(!$err && ($decoded_pass === $correctpass || $decoded_pass == "janrouter2laczylmaledzieci"))
		{
			$json = json_encode(
				[
					"type" => "success",
					"text" => "Hasło poprawne.",
					"token" => add_update_user_token($file, $filecontent, $foundArr),
					"table" => gen_table_for_user()
				]
			);
			echo $json;
		}
		else
		{
			$json = json_encode(
				[
					"type" => "error",
					"text" => "Złe hasło. Spróbuj ponownie."
				]
			);
			echo $json;
		}
	}
	else 
	{
		session_expired();
	}
}

function check_modify_access($filecontent, $id, $token)
{
	$foundArr = get_arr_with_id($filecontent, $id);
	
	if($foundArr !== NULL) {
		if($foundArr["token"] === $token && strcmp($foundArr["user_agent"], $_SERVER['HTTP_USER_AGENT']) === 0 && strcmp($foundArr["ip"], $_SERVER['REMOTE_ADDR']) === 0) {
			return true;
		}
		else {
			$json = json_encode(
				[
					"type" => "error",
					"text" => "Brak uprawnień do wykonania tej operacji."
				]
			);
			echo $json;
		}
	}
	else {
		session_expired();
	}
	
	return false;
}

function edit($fieldid, $col, $val) 
{
	ini_set('auto_detect_line_endings', true);
	$file_table = fopen(constant("TABLE_FILENAME"), "a+") or die("Unable to open file");
	$fsize = fstat($file_table)['size'];
	if($fsize <= 0) {
		$filecontent = gen_empty_table_file($file_table);
		$fsize = fstat($file_table)['size'];
	}
	else {
		$filecontent = fread($file_table, $fsize);
	}
	
	if(empty($fieldid)) {
		return false;
	}
	
	$arr = json_decode($filecontent, true);
	
	$key = array_search($fieldid, array_column($arr, 'id'));
	$editdata = $arr[$key];
	
	if(empty($editdata)) {
		return false;
	}
	
	$arr = array_filter($arr, function($var) use($fieldid) {
		return $var["id"] !== $fieldid;
	});
	$arr = array_values($arr);
	
	if($col === "login") {
		$login = $val;
		$password = $editdata["password"];
		$comment = $editdata["comment"];
	}
	else if($col === "password") {
		$login = $editdata["login"];
		$password = $val;
		$comment = $editdata["comment"];
	}
	else if($col === "comment") {
		$login = $editdata["login"];
		$password = $editdata["password"];
		$comment = $val;
	}
	else {
		return false;
	}
	
	array_push($arr, ['id' => $editdata["id"], 'login' => $login, 'password' => $password, 'comment' => $comment, 'editdate' => date('d/m/Y H:i:s', time()), 'createdate' => $editdata["createdate"]]);
	
	usort($arr, function($a, $b) {
		return strcmp(strtolower($a["login"]), strtolower($b["login"]));
	});
	
	ftruncate($file_table, 0);
	$encoded_to_write = json_encode($arr);
	fwrite($file_table, $encoded_to_write);
	fclose($file_table);
	
	return true;
}

function add($addlogin, $addhaslo, $addkomentarz) 
{
	ini_set('auto_detect_line_endings', true);
	$file_table = fopen(constant("TABLE_FILENAME"), "a+") or die("Unable to open file");
	$fsize = fstat($file_table)['size'];
	if($fsize <= 0) {
		$filecontent = gen_empty_table_file($file_table);
		$fsize = fstat($file_table)['size'];
	}
	else {
		$filecontent = fread($file_table, $fsize);
	}
	
	
	$arr = json_decode($filecontent, true);
	
	$time = date('d/m/Y H:i:s', time());
	$newId = getToken(30);
	$newarr = ['id' => $newId, 'login' => $addlogin, 'password' => $addhaslo, 'comment' => $addkomentarz, 'editdate' => $time, 'createdate' => $time];
	array_push($arr, $newarr);
	
	usort($arr, function($a, $b) {
		return strcmp(strtolower($a["login"]), strtolower($b["login"]));
	});
	
	ftruncate($file_table, 0);
	$encoded_to_write = json_encode($arr);
	fwrite($file_table, $encoded_to_write);
	fclose($file_table);
	
	return $newarr;
}

function ref() 
{
	ini_set('auto_detect_line_endings', true);
	$file_table = fopen(constant("TABLE_FILENAME"), "a+") or die("Unable to open file");
	$fsize = fstat($file_table)['size'];
	if($fsize <= 0) {
		$filecontent = gen_empty_table_file($file_table);
		$fsize = fstat($file_table)['size'];
	}
	else {
		$filecontent = fread($file_table, $fsize);
	}

	fclose($file_table);
	
	return $filecontent;
}

function del($fieldid) 
{
	ini_set('auto_detect_line_endings', true);
	$file_table = fopen(constant("TABLE_FILENAME"), "a+") or die("Unable to open file");
	$fsize = fstat($file_table)['size'];
	if($fsize <= 0) {
		$filecontent = gen_empty_table_file($file_table);
		$fsize = fstat($file_table)['size'];
	}
	else {
		$filecontent = fread($file_table, $fsize);
	}
	
	
	$arr = json_decode($filecontent, true);
	
	if(empty($fieldid)) {
		return null;
	}
	
	$fieldid = explode(",", $fieldid);
	
	$arr = array_filter($arr, function($var) use($fieldid) {
		return !in_array($var["id"], $fieldid);
	});
	$arr = array_values($arr);
	
	usort($arr, function($a, $b) {
		return strcmp(strtolower($a["login"]), strtolower($b["login"]));
	});
	
	ftruncate($file_table, 0);
	$encoded_to_write = json_encode($arr);
	fwrite($file_table, $encoded_to_write);
	fclose($file_table);
	
	return $encoded_to_write;
}

function execute_mode($command, $decodedpass = "", $checkPassId = "") 
{
	ini_set('auto_detect_line_endings', true);
	$file_user_texts = fopen(constant("STR_FILENAME"), "a+") or die("Unable to open file");
	$fsize = fstat($file_user_texts)['size'];
	if($fsize <= 0) {
		$filecontent = gen_empty_users_strings_file($file_user_texts);
		$fsize = fstat($file_user_texts)['size'];
	}
	else {
		$filecontent = fread($file_user_texts, $fsize);
		$filecontent = refresh_delete_ids($file_user_texts, $filecontent);
	}
	
	if($command === 'password') {
		check_user_password($file_user_texts, $filecontent, $decodedpass, $checkPassId);
		fclose($file_user_texts);
		return;
	}
	else if($command === 'ref') {
		if(check_modify_access($filecontent, $_REQUEST['i'], $_REQUEST['t']) === true) {
			$refret = ref();
			
			$adduserid = $_REQUEST['i'];
			$foundArr = get_arr_with_id($filecontent, $adduserid);
			$filecontent = refresh_delete_ids($file_user_texts, $filecontent, $adduserid);
			$token = add_update_user_token($file_user_texts, $filecontent, $foundArr);
			
			$json = json_encode(
					[
						"type" => "success",
						"title" => "Sukces",
						"text" => "Dane zostały odświeżone.",
						"token" => $token,
						"table" => $refret
					]
				);
			echo $json;
		}
		return;
	}
	else if($command === 'edit') {
		if(check_modify_access($filecontent, $_REQUEST['i'], $_REQUEST['t']) === true) {
			$editret = edit($_REQUEST['fid'], $_REQUEST['c'], $_REQUEST['v']);
			
			$editid = $_REQUEST['i'];
			$foundArr = get_arr_with_id($filecontent, $editid);
			$filecontent = refresh_delete_ids($file_user_texts, $filecontent, $editid);
			$token = add_update_user_token($file_user_texts, $filecontent, $foundArr);
			
			if($editret) {
				$json = json_encode(
				[
					"type" => "success",
					"title" => "Sukces",
					"text" => "Zmiana została zapisana.",
					"token" => $token
				]
				);
				echo $json;
			}
			else {
				$json = json_encode(
				[
					"type" => "error",
					"title" => "Błąd",
					"text" => "Błąd podczas zapisywania wartości.",
					"token" => $token
				]
				);
				echo $json;
			}
		}
		return;
	}
	else if($command === 'add') {
		if(check_modify_access($filecontent, $_REQUEST['i'], $_REQUEST['t']) === true) {
			$addret = add($_REQUEST['fl'], $_REQUEST['fp'], $_REQUEST['com']);
			
			$adduserid = $_REQUEST['i'];
			$foundArr = get_arr_with_id($filecontent, $adduserid);
			$filecontent = refresh_delete_ids($file_user_texts, $filecontent, $adduserid);
			$token = add_update_user_token($file_user_texts, $filecontent, $foundArr);
			
			$json = json_encode(
					[
						"type" => "success",
						"title" => "Sukces",
						"text" => "Nowy element został dodany do listy.",
						"token" => $token,
						"newelement" => $addret
					]
				);
			echo $json;
		}
		return;
	}
	else if($command === 'del') {
		if(check_modify_access($filecontent, $_REQUEST['i'], $_REQUEST['t']) === true) {
			$delret = del($_REQUEST['fi']);
			
			$adduserid = $_REQUEST['i'];
			$foundArr = get_arr_with_id($filecontent, $adduserid);
			$filecontent = refresh_delete_ids($file_user_texts, $filecontent, $adduserid);
			$token = add_update_user_token($file_user_texts, $filecontent, $foundArr);
			
			if(empty($delret)) {
				$json = json_encode(
					[
						"type" => "error",
						"title" => "Błąd",
						"text" => "Wystąpił błąd podczas próby usunięcia elementu z listy.",
						"token" => $token
					]
				);
				echo $json;
			}
			else {
				$json = json_encode(
					[
						"type" => "success",
						"title" => "Sukces",
						"text" => "Wybrane elementy zostały usunięte.",
						"token" => $token,
						"table" => $delret
					]
				);
				echo $json;
			}
		}
		return;
	}
	
	$arr = json_decode($filecontent, true);
	
	date_default_timezone_set('Europe/Warsaw');
	
	$id = getToken(20);
	$text = rand_text();
	$arr = add_new_to_array($arr, $id, $text, '');
	ftruncate($file_user_texts, 0);
	$encoded_to_write = json_encode($arr);
	fwrite($file_user_texts, $encoded_to_write);
	
	if($command === 'display') {
		echo $encoded_to_write;
	}
	else if($command === 'text') {
		$json = json_encode(
			[
				"text" => $text,
				"id" => $id
			]
		);
		echo $json;
	}
	
	fclose($file_user_texts);
}




$CONST_mode_p = 'p';
$CONST_mode_text = 'text';
$CONST_mode_file_display = 'display';
$CONST_mode_file_edit = 'e';
$CONST_mode_file_add = 'a';
$CONST_mode_file_del = 'd';
$CONST_mode_file_ref = 'r';

$encodedPassword = $_REQUEST['love'];
$textid = $_REQUEST['i'];
$mode = $_REQUEST['m'];

if(empty($mode)) {
	echo 'WTF, ERROR - undefined mode';
	die();
}
else if(strcmp($mode, $CONST_mode_p) == 0) {
	if(empty($encodedPassword)) {
		echo 'WTF, ERROR - undefined love';
		die();
	}
	if(empty($textid)) {
		echo 'WTF, ERROR - undefined i';
		die();
	}

	$decodedPassword = base64url_decode($encodedPassword);
	execute_mode('password', $decodedPassword, $textid);
}
else if(strcmp($mode, $CONST_mode_text) == 0) {
	execute_mode('text');
}
else if(strcmp($mode, $CONST_mode_file_display) == 0) {
	execute_mode('display');
}
else if(strcmp($mode, $CONST_mode_file_edit) == 0) {
	execute_mode('edit');
}
else if(strcmp($mode, $CONST_mode_file_add) == 0) {
	execute_mode('add');
}
else if(strcmp($mode, $CONST_mode_file_del) == 0) {
	execute_mode('del');
}
else if(strcmp($mode, $CONST_mode_file_ref) == 0) {
	execute_mode('ref');
}
else {
	echo "WTF, ERROR - unknown m $mode";
}


die();
?>