<?php

// ERROR LOGGING
error_reporting(E_ALL);
ini_set('error_log','/srv/www/htdocs/services/error_log');
ini_set('log_errors','true');

// DEPENDENCIES
session_start(); // ENABLE PHP SESSION

function respond($status, $SESSIONID, $USERNAME, $MESSAGE, $EXIT=false){
	//Return function which prints the xml
	echo "<?xml version='1.0' encoding='UTF-8'?><SESSION><STATUS>$status</STATUS><SESSIONID>$SESSIONID</SESSIONID><USERNAME>$USERNAME</USERNAME><MESSAGE>$MESSAGE</MESSAGE></SESSION>";
   if($EXIT == true) exit();
}

if (isset($_SESSION['SESSIONID']) and isset($_SESSION['USERNAME'])) {
   // VALIDATE INPUT
   $SESSIONID = $_SESSION['SESSIONID'];
   $USERNAME = $_SESSION['USERNAME'];
	if (preg_match("/[^A-Za-z0-9\_\-\.\@\,]/", $USERNAME)){ respond("BADUSER", '', '', '', true); }
	if (preg_match("/[^A-fa-f0-9]/", $SESSIONID)){ respond("BADSESSION", '', '', '', true); }

	// CONNECT TO THE DATABASE
	$user= $_ENV['DB_ENV_MYSQL_USER'];
	$passwd = $_ENV['DB_ENV_MYSQL_PASSWORD'];
	$db = $_ENV['DB_ENV_MYSQL_DATABASE'];
	$port= $_ENV['DB_PORT_3306_TCP_PORT'];
	$host= $_ENV['DB_PORT_'. $port .'_TCP_ADDR'];
	$mysqli = new mysqli($host, $user, $passwd, $db);

	// CHECK CONNECTION
	if (mysqli_connect_errno()) { respond("Connect failed: %s\n", mysqli_connect_error(), '', '', '', true); }

   // CHECK USERNAME AND SESSIONID
	$stmt = $mysqli->prepare("SELECT session_id, status FROM users WHERE usr = ?");
	$stmt->bind_param('s', $USERNAME);
	// EXECUTE AND GET RESULTS
	$stmt->execute();
	$stmt->bind_result($sid, $stat);

	// VALIDATE IF SESSIONID MATCHES AND STATUS IS ACCEPTED
   $status = 'REJECTED';
	while($stmt->fetch()){
      if ($sid == $SESSIONID and $stat == 'ACCEPTED'){
      	$status = "ACCEPTED";
      }
   }

	// CLOSE STATEMENT
	$stmt->close();

	if($status=="ACCEPTED"){
		// REMOVING THE SESSIONID FROM THE DATABASE AND RESETTING THE TIMEOUT
		$stmt = $mysqli->prepare(" UPDATE users ".
                               " SET session_id = '' ".
                               " WHERE usr = ?".
                               " ;");
		$stmt->bind_param('s', $USERNAME);
      // EXECUTE AND CLOSE STATEMENT
	 	$stmt->execute();
		$stmt->close();

		// DELETING THE SESSION COOKIE
		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 1,
				$params["path"], $params["domain"],
				$params["secure"], $params["httponly"]
			);
		}

		// ENDING THE PHP SESSION
		session_destroy();

		// RESPOND WITH SUCCES
		respond($status, '', '', '');
	}else{
		// RESPOND WITH REJECTION
		respond($status, '', '', '');
	}
	// CLOSING CONNECTION
	$mysqli->close();
} else { echo "<html><head><title>Unauthorized Usage!</title></head><body>Get Lost!!!</body></html>"; }
?>
