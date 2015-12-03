<?php
################################################################################
#                          CGE Create User Login                               #
################################################################################
// This is the script which:
//   -> Checks the recieved user login input for malicious entries
//   -> Checks the recieved user login is not already taken
//   -> Creates a user with the user login info in the SQL db
//   -> Starts a session
//   -> Returns the status session id and username
error_reporting(E_ALL);
ini_set('error_log','/srv/www/htdocs/services/error_log');
ini_set('log_errors','true');

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
	if (preg_match("/[^A-Za-z0-9\_\-\.\@\,]/", _INPUT("USERNAME"))){
		respond("BADUSER", '', '');
		exit();
	}
	
	//Checking if captcha image was correctly filled out:
	require_once dirname(__FILE__) .'/securimage/securimage.php';
	$securimage = new Securimage();
	$captcha = preg_replace("/[^A-Za-z0-9]/",'',_INPUT("CAPTCHA"));
	if ($securimage->check($captcha) == false) {
		respond("BADIMAGE", '', '');
		exit();
	}
	
	// CONNECT TO THE DATABASE
	$mysqli = new mysqli('cge', 'cgeclient', 'www', 'cge');

	// CHECK CONNECTION
	if (mysqli_connect_errno()) {
		respond("Connect failed: %s\n", mysqli_connect_error(), '', '');
		exit();
	}

	//CHECK FOR CORRECT USERNAME AND PASSWORD
	$stmt = $mysqli->prepare("SELECT id FROM login WHERE user = ?");
	$stmt->bind_param('s', $USERNAME);

	$USERNAME = _INPUT("USERNAME");

	//EXECUTE PREPARED STATEMENT
	$stmt->execute();

	// BIND RESULT VARIABLES
	$stmt->bind_result($id);
	
	// FETCH RESULTS
	$count = 0;
	while($stmt->fetch()){$count++;}
	
	// CHECK IF USERNAME IS TAKEN
	if ($count >= 1){
		$status = "USERTAKEN";
	}else{
		$status = "ACCEPTED";
	}

	// CLOSE USERCHECK STATEMENT
	$stmt->close();

	if($status=="ACCEPTED"){ // CREATE USER
		// CREATE SESSIONID VARS
		$key1 = "biasgkBJHGAdk2%/2g177=(maj12s&7h";
		$key2 = ")/6jb8HilkjWdu&R/2gjHG(&nbkgkP)2";
		$dateTimeNow = date('d/m-Y H:i:s');
		$user_ip = $_SERVER['REMOTE_ADDR'];
		// CREATE PASSWORD VARS
		$key = ")gIs56bi%";

		$stmt = $mysqli->prepare("INSERT INTO login (user, password, session_id, last_login, ip) VALUES (?,?,?,?,?)");
		$stmt->bind_param('sssss', $USERNAME, $PASSWORD, $SESSIONID, $DATE, $IP);

		$USERNAME = _INPUT("USERNAME");
		$PASSWORD = sha1($key.$USERNAME._INPUT("PASSWORD").$key);
		$SESSIONID = sha1($key1.$dateTimeNow.$user_ip.$key2);
		$DATE = $dateTimeNow;
		$IP = $user_ip;

	 	//EXECUTE PREPARED STATEMENT
	 	$stmt->execute();

		// CLOSE CREATEUSER STATEMENT
		$stmt->close();	 
	 
	 	// CREATE SESSION
      if(!isset($_SESSION)) session_start(); // START SESSION
		$_SESSION = array(); // UNSET ALL SESSION VARIABLES
		
		// REGISTER $SESSIONID AND $USERNAME
		$_SESSION['SESSIONID'] = $SESSIONID;
		$_SESSION['USERNAME']  = $USERNAME;
		
		// RETURN XML WITH $SESSIONID AND $USERNAME
		respond($status, $SESSIONID, $USERNAME);
	}else{
		// RETURN XML EXCEPTION ERROR
		respond($status, '', '');
	}
	//CLOSING DATABASE
	$mysqli->close();
} else { echo "<html><head><title>Unauthorized Usage!</title></head><body>Get Lost!!!</body></html>"; }
?>
