<?php
################################################################################
#                                  CGE LOGIN                                   #
################################################################################
// This is the Login script which:
//   -> Checks and authorize the recieved user login against the SQL db
//   -> Starts a session
//   -> Returns the status session id and username
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

function respond($status, $SESSIONID, $USERNAME){
	echo "<?xml version='1.0' encoding='UTF-8'?><SESSION><STATUS>$status</STATUS><SESSIONID>$SESSIONID</SESSIONID><USERNAME>$USERNAME</USERNAME></SESSION>";
}


if (count($_POST)>0) # or count($_GET)>0
{
	$status = '';

	//Checking if username is invalid
	if (preg_match("/[^A-Za-z0-9\,\_\-\.\@]/", _INPUT("USERNAME"))){
		respond("BADUSER", '', '');
		exit();
	}

	// CONNECT TO THE DATABASE
	$mysqli = new mysqli($host, $user, $passwd, $db, $port)
    echo $USERNAME;
    echo $PASSWORD;
	// CHECK CONNECTION
	if (mysqli_connect_errno()) {
		respond("Connect failed: %s\n", mysqli_connect_error(), '', '');
		exit();
	}

	//CHECK FOR CORRECT USERNAME AND PASSWORD
	$stmt = $mysqli->prepare("SELECT user FROM login WHERE user = ? AND password = ?");
	$stmt->bind_param('ss', $USERNAME, $PASSWORD);

	$key = ")gIs56bi%";
	$USERNAME = preg_replace('/[^A-Za-z0-9\_\-\.\@\,]/', '', _INPUT("USERNAME"));
	$PASSWORD = sha1($key.$USERNAME._INPUT("PASSWORD").$key);

	//EXECUTE PREPARED STATEMENT
	$stmt->execute();

	// BIND RESULT VARIABLES
	$stmt->bind_result($name);

	// FETCH RESULTS
	$count = 0;
	while($stmt->fetch()){$count++;}

	if ($name === $USERNAME and $count === 1){
		$status = "ACCEPTED";
	}else{
		// RETURN XML EXCEPTION ERROR
		respond('REJECTED', '', '');
	}

	// CLOSE STATEMENT
	$stmt->close();

	// CREATE SESSION
	if($status=="ACCEPTED"){
		// CREATE SESSIONID
		$key1 = "biasgkBJHGAdk2%/2g177=(maj12s&7h";
		$key2 = ")/6jb8HilkjWdu&R/2gjHG(&nbkgkP)2";
		$dateTimeNow = date('d/m-Y H:i:s');
		$user_ip = $_SERVER['REMOTE_ADDR'];

		//PREPARE SQL STATEMENT
		$stmt = $mysqli->prepare("UPDATE login SET session_id = ?, last_login=?, ip=? WHERE user=?");
		$stmt->bind_param('ssss', $SESSIONID, $DATE, $IP, $USERNAME);

		$SESSIONID = sha1($key1.$dateTimeNow.$user_ip.$key2);
		$DATE = $dateTimeNow;
		$IP = $user_ip;
		$USERNAME = $USERNAME;

	 	//EXECUTE PREPARED STATEMENT
	 	$stmt->execute();

		// CLOSE CREATEUSER STATEMENT
		$stmt->close();

		// REGISTER $SESSIONID AND $USERNAME
		session_start(); // START SESSION
		$_SESSION = array(); // UNSET ALL SESSION VARIABLES
		$_SESSION['SESSIONID'] = $SESSIONID;
		$_SESSION['USERNAME']  = $USERNAME;

		// RETURN XML WITH $SESSIONID AND $USERNAME
		respond($status, $SESSIONID, $USERNAME);
	}

	//CLOSING DATABASE
	$mysqli->close();
} else { echo "<html><head><title>Unauthorized Usage!</title></head><body>Get Lost!!!</body></html>"; }
?>
