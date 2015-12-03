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

// ERROR LOGGING
error_reporting(E_ALL);
ini_set('error_log','/srv/www/htdocs/services/error_log');
ini_set('log_errors','true');

// DEPENDENCIES
require_once 'is_email.php';
require_once '/var/www/html/cge/user/securimage/securimage.php';


// FUNCTIONS
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

// MAIN
if (count($_POST)>0) { # or count($_GET)>0
	$securimage = new Securimage();
   $key = ")gIs56bi%";

	// VALIDATE INPUTS
   $USERNAME = _INPUT("USERNAME");
   $PASSWORD = sha1($key.$USERNAME._INPUT("PASSWORD").$key);
   $EMAIL = _INPUT("EMAIL");
	$CAPTCHA = preg_replace("/[^A-Za-z0-9]/", '', _INPUT("CAPTCHA"));
	if (preg_match("/[^A-Za-z0-9\_\-\.\@\,]/", $USERNAME)){ respond("BADUSER", '', '', true); }
   if (!is_email($EMAIL)){ respond("BADEMAIL", $EMAIL, '', true); }
	//if ($securimage->check($CAPTCHA) == false) { respond("BADIMAGE", '', '', true); }

	// CONNECT TO THE DATABASE
	$mysqli = new mysqli('cge', 'cgeclient', 'www', 'cge');

	// CHECK CONNECTION
	if (mysqli_connect_errno()) { respond("Connect failed: %s\n", mysqli_connect_error(), '', '', true); }

	// CHECK USERNAME AND EMAIL AVAILABILITY
	$stmt = $mysqli->prepare("SELECT usr FROM users WHERE usr = ? OR email = ?");
	$stmt->bind_param('ss', $USERNAME, $EMAIL);
	// EXECUTE AND GET RESULTS
	$stmt->execute();
	$stmt->bind_result($usr);

	// CHECK IF USERNAME OR EMAIL IS TAKEN
	$status = 'ACCEPTED';
	while($stmt->fetch()){
      if ($USERNAME == $usr){
      	$status = "USERTAKEN";
      }else{
      	$status = "EMAILTAKEN";
      }
   }

	// CLOSE STATEMENT
	$stmt->close();

	if($status=="ACCEPTED"){
      // CREATE USER
		// CREATE ACTIVATION HASH VARS
		$key1 = "g8/d2o8R452gius1h�(729dp9Thdlbdt";
		$key2 = "86%G7d2ius875HVD167%/%�2p+10g/&f";
		$dateTimeNow = date('d/m-Y H:i:s');
		$user_ip = $_SERVER['REMOTE_ADDR'];

      // ADD USER TO DATABASE
		$stmt = $mysqli->prepare("INSERT INTO users (usr, pwd, email, tmp, status) VALUES (?,?,?,?,'CREATED')");
		$stmt->bind_param('ssss', $USERNAME, $PASSWORD, $EMAIL, $TMP);
		$TMP = sha1($key1.$dateTimeNow.$user_ip.$key2);
      // EXECUTE AND CLOSE STATEMENT
	 	$stmt->execute();
		$stmt->close();

      // SEND EMAIL // smtp => 'mail.cbs.dtu.dk'
      $to = $EMAIL;
      $subject = "Welcome to Center for Genomic Epidemiology";
      $message = "Dear $USERNAME,\r\n".
                 "Welcome to Center for Genomic Epidemiology.\r\n".
                 "\r\n".
                 "A user account has been created for this email, but it has ".
                 "yet to be activated.\r\n".
                 "Username: $USERNAME\r\n".
                 "\r\n".
                 "To activate the user account please follow the following ".
                 "link:\r\n".
                 "https://cge.cbs.dtu.dk/cge/user/login/user_manager.php?action=".
                 "confirm_create&uid=$USERNAME&tmp=$TMP\r\n".
                 "\r\n".
                 "When activated you will be able to login to your user account".
                 " in the top right corner of our service pages, and all your ".
                 "data will be available to you for later viewing, managing ".
                 "and running of new/other services.\r\n".
                 "\r\n".
                 "\r\n".
                 "If you did not apply for this user account, please follow the".
                 " following link to cancel:\r\n".
                 "https://cge.cbs.dtu.dk/cge/user/login/user_manager.php?action=".
                 "cancel_create&uid=$USERNAME&tmp=$TMP\r\n".
                 "\r\n".
                 "\r\n".
                 "Thank you for registering.\r\n".
                 "\r\n".
					  "Yours sincerely,\r\n".
                 "	The CGE group\r\n\r\n\r\n";
      $from = 'CGE-group@cbs.dtu.dk';
      $replyto = 'cgehelp@cbs.dtu.dk';
      mail($to,$subject,$message,"From: $from\r\nReply-To: $replyto");

		// RESPOND WITH SUCCES
		respond($status, $TMP, $USERNAME);
	}else{
		// RESPOND WITH REJECTION
		respond($status, '', '');
	}
	// CLOSING CONNECTION
	$mysqli->close();
} else { echo "<html><head><title>Unauthorized Usage!</title></head><body>Get Lost!!!</body></html>"; }
?>
