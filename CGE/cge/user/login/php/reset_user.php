<?php
################################################################################
#                           CGE Reset User Login                               #
################################################################################
// This script does following:
//   -> Validates the recieved email as an email
//   -> Validates the recieved email exists in database
//   -> Validates the account of the recieved email is ACCEPTED
//   -> Prepares the reset of the account
//   -> Returns the status

// ERROR LOGGING
error_reporting(E_ALL);
ini_set('error_log','/srv/www/htdocs/services/error_log');
ini_set('log_errors','true');

// DEPENDENCIES
require_once 'is_email.php';

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
if (count($_POST)>0) { #  or count($_GET)>0
   // VALIDATE INPUT
   $EMAIL = _INPUT("EMAIL");
   if (!is_email($EMAIL)){ respond("BADEMAIL", '', '', true); }
   
	// CONNECT TO THE DATABASE
	$mysqli = new mysqli('cge', 'cgeclient', 'www', 'cge');
   
	// CHECK CONNECTION
	if (mysqli_connect_errno()) { respond("Connect failed: %s\n", mysqli_connect_error(), '', '', true); }

	// CHECK USERNAME AND EMAIL AVAILABILITY
	$stmt = $mysqli->prepare("SELECT status, usr, pwd FROM users WHERE email = ?");
	$stmt->bind_param('s', $EMAIL);
	// EXECUTE AND GET RESULTS
	$stmt->execute();
	$stmt->bind_result($stat, $usr, $pwd);

	// CHECK IF EMAIL EXISTS AND STATUS ACCEPTED
	$status = 'REJECTED';
	while($stmt->fetch()){
      if ($stat == 'ACCEPTED'){
      	$status = "ACCEPTED";
      }elseif($stat != 'CREATED'){
      	$status = "NOACTIVATION";
      }elseif($stat != 'RESET'){
      	$status = "ISRESET";
      }elseif($stat != 'DELETE'){
      	$status = "CANCELDELETE";
      }
   }
   
	// CLOSE USERCHECK STATEMENT
	$stmt->close();
   
	if($status=="ACCEPTED"){
      // CREATE TEMPORARY PASSWORD
      $alpha = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
		$key = ")gIs56bi%";
      $tmpPWD = '';
      for ($i = 0; $i < 8; $i++) { $tmpPWD .= $alpha[rand(0, 59)]; }
      $tmpPWD_hash = sha1($key.$usr.$tmpPWD.$key);
      
      // MARK USER FOR RESET
      $stmt = $mysqli->prepare(" UPDATE users ".
                               " SET status = 'RESET',".
                               "     pwd = ?,".
                               "     tmp = ?".
                               " WHERE usr = ?".
                               " ;");
		$stmt->bind_param('sss', $tmpPWD_hash, $pwd, $usr);
      // EXECUTE AND CLOSE STATEMENT
	 	$stmt->execute();
		$stmt->close();	 
      
      // SEND EMAIL // smtp => 'mail.cbs.dtu.dk'
      $to = $EMAIL;
      $subject = "Reset of CGE User Account Password";
      $message = "Dear $usr,\r\n".
                 "We have registered a request for resetting your password.\r\n".
                 "\r\n".
                 "The new login details are:\r\n".
                 "Username: $usr\r\n".
                 "Password: $tmpPWD\r\n".
                 "\r\n".
                 "The transfer of emails are not secure, so please change your".
                 " password immediately after log in.\r\n".
                 "https://cge.cbs.dtu.dk/services/\r\n".
                 "\r\n".
                 "\r\n".
                 "If you did not apply for resetting your user account, please ".
                 "follow the following link to cancel the process, and restore ".
                 "your previous password:\r\n".
                 "https://cge.cbs.dtu.dk/cge/user/login/user_manager.php?action=".
                 "cancel_reset&uid=$usr\r\n".
                 "\r\n".
                 "\r\n".
					  "Yours sincerely,\r\n".
                 "	The CGE group\r\n\r\n\r\n";
      $from = 'CGE-group@cbs.dtu.dk';
      $replyto = 'cgehelp@cbs.dtu.dk';
      mail($to,$subject,$message,"From: $from\r\nReply-To: $replyto");
      
		// RESPOND WITH SUCCES
		respond($status, $tmpPWD, '');
	}else{
		// RESPOND WITH REJECTION
		respond($status, '', '');
	}
	// CLOSING CONNECTION
	$mysqli->close();
} else { echo "<html><head><title>Unauthorized Usage!</title></head><body>Get Lost!!!</body></html>"; }
?>
