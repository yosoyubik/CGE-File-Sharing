<?php
session_start();

function respond($status, $SESSIONID, $USERNAME, $MESSAGE){
	//Return function which prints the xml
	echo "<?xml version='1.0' encoding='UTF-8'?><SESSION><STATUS>$status</STATUS><SESSIONID>$SESSIONID</SESSIONID><USERNAME>$USERNAME</USERNAME><MESSAGE>$MESSAGE</MESSAGE></SESSION>";
}

if (isset($_SESSION['SESSIONID']) or isset($_SESSION['USERNAME']))
{
	$status = '';

	//Checking if username is invalid
	if (preg_match("/[^A-Za-z0-9\_\-\.\@]/", $_SESSION['USERNAME'])){
		respond("BADSESSION", '', '');
		exit();
	}
	
	//Checking if SESSIONID is invalid
	if (preg_match("/[^A-Za-z0-9]/", $_SESSION['SESSIONID'])){
		respond("BADSESSION", '', '');
		exit();
	}
	
	// REMOVING THE SESSIONID FROM THE DATABASE AND RESETTING THE TIMEOUT
	// CONNECT TO THE DATABASE
	$mysqli = new mysqli('cge', 'cgeclient', 'www', 'cge');
   
	// CHECK CONNECTION
	if (mysqli_connect_errno()) {
		respond("Connect failed: %s\n", mysqli_connect_error(), '', '');
		exit();
	}

	//CHECK FOR CORRECT USERNAME AND PASSWORD
	$stmt = $mysqli->prepare("SELECT id FROM login WHERE user = ? AND session_id = ?");
	$stmt->bind_param('ss', $USERNAME, $SESSIONID);

	$USERNAME = $_SESSION['USERNAME'];
	$SESSIONID = $_SESSION['SESSIONID'];

	//EXECUTE PREPARED STATEMENT
	$stmt->execute();

	// BIND RESULT VARIABLES
	$stmt->bind_result($id);

	// FETCH VALUES
	$count = 0;
	while($stmt->fetch()){$count++;}

	if ($count == 1){
		$status = "ACCEPTED";
	}else{
		$status = "REJECTED";
	}

	// CLOSE STATEMENT
	$stmt->close();
	
	// CREATE SESSION
	if($status=="ACCEPTED"){
		// KILL SESSION

		//PREPARE SQL STATEMENT for RESETTING DB
		$stmt = $mysqli->prepare("UPDATE login SET session_id = '' WHERE user=?");
		$stmt->bind_param('s', $USERNAME);
		$USERNAME = $_SESSION['USERNAME'];

	 	//EXECUTE PREPARED STATEMENT
	 	$stmt->execute();

		// CLOSE CREATEUSER STATEMENT
		$stmt->close();

		// DELETING THE SESSION COOKIE
		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 1,
				$params["path"], $params["domain"],
				$params["secure"], $params["httponly"]
			);
		}
	
		// ENDING THE SESSION
		session_destroy();
		
		// RETURN XML WITH $status
		respond($status, '', '', '');
	}else{
		// RETURN XML EXCEPTION ERROR
		respond($status, '', '', $USERNAME +" "+ $count);
	}

	//CLOSING DATABASE
	$mysqli->close();
} else { echo "<html><head><title>Unauthorized Usage!</title></head><body>Get Lost!!!</body></html>"; }
?>