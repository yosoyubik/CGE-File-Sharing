<?php
################################################################################
#                                  CGE LOGIN                                   #
################################################################################
// This is the Login script which:
//   -> Checks and authorize the recieved user login against the SQL db
//   -> Starts a session
//   -> Returns the status session id and username

// ERROR LOGGING
error_reporting(E_ALL);
ini_set('error_log','/srv/www/htdocs/services/error_log');
ini_set('log_errors','true');

$user= $_ENV['DB_ENV_MYSQL_USER'];
$passwd = $_ENV['DB_ENV_MYSQL_PASSWORD'];
$db = $_ENV['DB_ENV_MYSQL_DATABASE'];
$port= $_ENV['DB_PORT_3306_TCP_PORT'];
$host= $_ENV['DB_PORT_'. $port .'_TCP_ADDR'];

function _INPUT($name){
   // QUERY HANDLER: Used to get form elements and queries in a simple manner
   // AUTHOR: Martin Thomsen
   // USAGE: $form_text = _INPUT('form_text_name');
   if ($_SERVER['REQUEST_METHOD'] == 'POST' and isset($_POST[$name]))
      return strip_tags($_POST[$name]);
   //elseif ($_SERVER['REQUEST_METHOD'] == 'GET' and isset($_GET[$name]))
   //   return strip_tags($_GET[$name]);
   else return NULL;
}

function respond($status, $SESSIONID, $USERNAME, $EXIT=false){
	echo "<?xml version='1.0' encoding='UTF-8'?><SESSION><STATUS>$status</STATUS><SESSIONID>$SESSIONID</SESSIONID><USERNAME>$USERNAME</USERNAME></SESSION>";
   if($EXIT == true) exit();
}


if (count($_POST)>0){ #  or count($_GET)>0
	$key = ")gIs56bi%";

	// VALIDATE INPUTS
   $USERNAME = _INPUT("USERNAME");
   $PASSWORD = sha1($key.$USERNAME._INPUT("PASSWORD").$key);
	if (preg_match("/[^A-Za-z0-9\_\-\.\@\,]/", $USERNAME)){ respond("BADUSER", '', '', true); }

	// CONNECT TO THE DATABASE
	$mysqli = new mysqli($host, $user, $passwd, $db, $port);

	// CHECK CONNECTION
	if (mysqli_connect_errno()) { respond("Connect failed: %s\n", mysqli_connect_error(), '', '', true); }

	// CHECK FOR CORRECT USERNAME AND PASSWORD
	$stmt = $mysqli->prepare("SELECT status FROM users WHERE usr = ? AND pwd = ?");
	$stmt->bind_param('ss', $USERNAME, $PASSWORD);
	// EXECUTE AND GET RESULTS
	$stmt->execute();
	$stmt->bind_result($stat);

	// CHECK THE STATUS
   $status = 'REJECTED';
	while($stmt->fetch()){
      if($stat == 'ACCEPTED'){
         $status = "ACCEPTED";
      }elseif($stat == 'RESET'){
         $status = "ENDRESET"; // Completion of reset
      }elseif($stat == 'CREATED'){
         $status = "NOACTIVATION";
      }elseif($stat == 'DELETE'){
         $status = "CANCELDELETE";
      }
   }

	// CLOSE STATEMENT
	$stmt->close();

	if($status=="ACCEPTED" or $status=="ENDRESET"){
   	// CREATE SESSION
		// CREATE SESSIONID
		$key1 = "biasgkBJHGAdk2%/2g177=(maj12s&7h";
		$key2 = ")/6jb8HilkjWdu&R/2gjHG(&nbkgkP)2";
		$IP = $_SERVER['REMOTE_ADDR'];
		$DATE = date('d-m-Y H:i:s');
		$SESSIONID = sha1($key1.$DATE.$IP.$key2);

		//PREPARE SQL STATEMENT
		$stmt = $mysqli->prepare(" UPDATE users".
                               " SET session_id = ?,".
                               "     last_login = ?,".
                               "     ip = ?".
                               " WHERE usr = ?".
                               " ;");
		$stmt->bind_param('ssss', $SESSIONID, $DATE, $IP, $USERNAME);
      // EXECUTE AND CLOSE STATEMENT
	 	$stmt->execute();
		$stmt->close();

      if($status=="ENDRESET"){
         // FINALIZE RESET
         $stmt = $mysqli->prepare("UPDATE users ".
                                  "SET status = 'ACCEPTED',".
                                  "    tmp = ''".
                                  "WHERE usr = ?".
                                  ";");
         $stmt->bind_param('s', $USERNAME);
         // EXECUTE AND CLOSE STATEMENT
         $stmt->execute();
         $stmt->close();
      }

		// REGISTER $SESSIONID AND $USERNAME
		session_start(); // START SESSION
		$_SESSION = array(); // UNSET ALL SESSION VARIABLES
		$_SESSION['SESSIONID'] = $SESSIONID;
		$_SESSION['USERNAME']  = $USERNAME;

		// RESPOND WITH SUCCES
		respond($status, $SESSIONID, $USERNAME);
	}else{
		// RESPOND WITH REJECTION
		respond($status, '', '');
	}
	// CLOSING CONNECTION
	$mysqli->close();
} else { echo "<html><head><title>Unauthorized Usage!</title></head><body>Get Lost!!!</body></html>"; }
?>
