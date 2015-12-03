<?php
################################################################################
#                               CGE Update User                                #
################################################################################
// This is the script which:
//   -> Validates and checks user session details (AUTHORIZATION)
//   -> Validates and check availability of requested user changes
//   -> Updates the user details
//   -> Sends emails to the user informing about the changes made to the account
//   -> Returns the status of the transaction

// ERROR LOGGING
error_reporting(E_ALL);
ini_set('error_log','/srv/www/htdocs/services/error_log');
ini_set('log_errors','true');

// DEPENDENCIES
require_once 'is_email.php';
session_start(); // ENABLE PHP SESSION

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
if ((count($_POST)>0) and isset($_SESSION['SESSIONID']) and isset($_SESSION['USERNAME'])) { # or count($_GET)>0
   $key = ")gIs56bi%";
   
	// VALIDATE INPUTS
   $SESSIONID = $_SESSION['SESSIONID'];
   $USERNAME = $_SESSION['USERNAME'];
	if (preg_match("/[^A-Za-z0-9\_\-\.\@\,]/", $USERNAME)){ respond("BADUSER", '', '', true); }
	if (preg_match("/[^A-fa-f0-9]/", $SESSIONID)){ respond("BADSESSION", '', '', true); }
   $NEW_USERNAME = _INPUT("USERNAME");
   $NEW_EMAIL = _INPUT("EMAIL");
   $NEW_PASSWORD = sha1($key.$NEW_USERNAME._INPUT("PASSWORD").$key);
	if (preg_match("/[^A-Za-z0-9\_\-\.\@\,]/", $NEW_USERNAME)){ respond("BADUSER2", '', '', true); }
   if (!is_email($NEW_EMAIL)){ respond("BADEMAIL", '', '', true); }
	
	// CONNECT TO THE DATABASE
	$mysqli = new mysqli('cge', 'cgeclient', 'www', 'cge');
	// CHECK CONNECTION
	if (mysqli_connect_errno()) { respond("Connect failed: %s\n", mysqli_connect_error(), '', '', true); }
   
   // CHECK USERNAME AND SESSIONID
	$stmt = $mysqli->prepare("SELECT session_id, email FROM users WHERE usr = ?");
	$stmt->bind_param('s', $USERNAME);
	// EXECUTE AND GET RESULTS
	$stmt->execute();
	$stmt->bind_result($sid, $EMAIL);

	// CHECK IF SESSIONID MATCHES
	$status = 'REJECTED';
	while($stmt->fetch()){
      if ($SESSIONID == $sid){
      	$status = "ACCEPTED";
      }
   }
   
	// CLOSE STATEMENT
	$stmt->close();
   
	if($status=="ACCEPTED"){
      // CHECK NEW USERNAME AND EMAIL FOR AVAILABILITY
      $stmt = $mysqli->prepare("SELECT usr FROM users WHERE (usr = ? OR email = ?) AND usr <> ?;");
      $stmt->bind_param('sss', $NEW_USERNAME, $NEW_EMAIL, $USERNAME);
      // EXECUTE AND GET RESULTS
      $stmt->execute();
      $stmt->bind_result($usr);
   
      // CHECK IF USERNAME OR EMAIL IS TAKEN
      while($stmt->fetch()){
         if ($NEW_USERNAME == $usr){
            $status = "USERTAKEN";
         }else{
            $status = "EMAILTAKEN";
         }
      }
      
      // CLOSE STATEMENT
      $stmt->close();
   }
   
	if($status=="ACCEPTED"){
      // UPDATE USER
		$stmt = $mysqli->prepare(" UPDATE users".
                               " SET usr = ?,".
                               "     pwd = ?,".
                               "     email = ?,".
                               "     status = 'ACCEPTED',".
                               "     tmp = NULL".
                               " WHERE usr = ?".
                               " ;");
      $stmt->bind_param('ssss', $NEW_USERNAME, $NEW_PASSWORD, $NEW_EMAIL, $USERNAME);
      // EXECUTE AND CLOSE STATEMENT
	 	$stmt->execute();
		$stmt->close();
      
      // UPDATE SESSION USERNAME
      $_SESSION['USERNAME'] = $NEW_USERNAME;
		
      // SEND EMAILS
      if($EMAIL !== $NEW_EMAIL){
         // SEND NOTIFICATION TO OLD EMAIL ADDRESS
         $to = $EMAIL;
         $subject = "Your CGE Profile Was Updated";
         $message = "Dear $USERNAME,\r\n".
                    "\r\n".
                    "We have registered a request to update to your user profile.".
                    "\r\n".
                    "\r\n".
                    "Your new details are as follows:\r\n".
                    "Username: $NEW_USERNAME\r\n".
                    "Email: $NEW_EMAIL\r\n".
                    "\r\n".
                    "As you can see, you have changed your email adress, and ".
                    "this will be the last email you receive from us to this ".
                    "email adress, unless you change it back.\r\n".
                    "\r\n".
                    "Thank you for using our services.\r\n".
                    "\r\n".
         			  "Yours sincerely,\r\n".
                    "	The CGE group\r\n\r\n\r\n";
         $from = 'CGE-group@cbs.dtu.dk';
         $replyto = 'cgehelp@cbs.dtu.dk';
         mail($to,$subject,$message,"From: $from\r\nReply-To: $replyto");
         
         // SEND NOTIFICATION TO NEW EMAIL ADDRESS
         $to = $NEW_EMAIL;
         $subject = "Your CGE Profile Was Updated";
         $message = "Dear $USERNAME,\r\n".
                    "\r\n".
                    "We have registered a request to update to your user profile.".
                    "\r\n".
                    "This will now be the new email recipient address for your ".
                    "account.\r\n".
                    "\r\n".
                    "Your details are as follows:\r\n".
                    "Username: $NEW_USERNAME\r\n".
                    "Email: $NEW_EMAIL\r\n".
                    "\r\n".
                    "\r\n".
                    "Thank you for using our services.\r\n".
                    "\r\n".
         			  "Yours sincerely,\r\n".
                    "	The CGE group\r\n\r\n\r\n";
         $from = 'CGE-group@cbs.dtu.dk';
         $replyto = 'cgehelp@cbs.dtu.dk';
         mail($to,$subject,$message,"From: $from\r\nReply-To: $replyto");
      }else{
         // SEND NOTIFICATION EMAIL ABOUT CHANGES
         $to = $EMAIL;
         $subject = "Your CGE Profile Was Updated";
         $message = "Dear $USERNAME,\r\n".
                    "\r\n".
                    "We have registered a request to update to your user profile.".
                    "\r\n".
                    "\r\n".
                    "Your new details are as follows:\r\n".
                    "Username: $NEW_USERNAME\r\n".
                    "\r\n".
                    "\r\n".
                    "Thank you for using our services.\r\n".
                    "\r\n".
         			  "Yours sincerely,\r\n".
                    "	The CGE group\r\n\r\n\r\n";
         $from = 'CGE-group@cbs.dtu.dk';
         $replyto = 'cgehelp@cbs.dtu.dk';
         mail($to,$subject,$message,"From: $from\r\nReply-To: $replyto");
      }
      
		// RESPOND WITH SUCCES
		respond($status, '', '');
	}else{
		// RESPOND WITH REJECTION
		respond($status, '', '');
	}
	// CLOSING CONNECTION
	$mysqli->close();
} else { echo "<html><head><title>Unauthorized Usage!</title></head><body>Get Lost!!!</body></html>"; }
?>
