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
session_start(); // ENABLE PHP SESSION

function respond($status, $SESSIONID, $USERNAME, $EXIT=false){
	echo "<?xml version='1.0' encoding='UTF-8'?><SESSION><STATUS>$status</STATUS><SESSIONID>$SESSIONID</SESSIONID><USERNAME>$USERNAME</USERNAME></SESSION>";
   if($EXIT == true) exit();
}

// MAIN
if (isset($_SESSION['SESSIONID']) and isset($_SESSION['USERNAME'])) {
   // VALIDATE INPUT
   $SESSIONID = $_SESSION['SESSIONID'];
   $USERNAME = $_SESSION['USERNAME'];
	if (preg_match("/[^A-Za-z0-9\_\-\.\@\,]/", $USERNAME)){ respond("BADUSER", '', '', true); }
	if (preg_match("/[^A-fa-f0-9]/", $SESSIONID)){ respond("BADSESSION", '', '', true); }
   
	// CONNECT TO THE DATABASE
	$mysqli = new mysqli('cge', 'cgeclient', 'www', 'cge');
   
	// CHECK CONNECTION
	if (mysqli_connect_errno()) { respond("Connect failed: %s\n", mysqli_connect_error(), '', '', true); }

   // CHECK USERNAME AND SESSIONID
	$stmt = $mysqli->prepare("SELECT session_id, status, email FROM users WHERE usr = ?");
	$stmt->bind_param('s', $USERNAME);
	// EXECUTE AND GET RESULTS
	$stmt->execute();
	$stmt->bind_result($sid, $stat, $EMAIL);

	// VALIDATE IF SESSIONID MATCHES AND STATUS IS ACCEPTED
   $status = "REJECTED";
	while($stmt->fetch()){
      if ($sid == $SESSIONID and $stat == 'ACCEPTED'){
      	$status = "ACCEPTED";
      }elseif($stat != 'CREATED'){
      	$status = "NOACTIVATION";
      }elseif($stat != 'DELETE'){
      	$status = "PREDELETE";
      }elseif($stat != 'RESET'){
      	$status = "ACCEPTED";
      }
   }
   
	// CLOSE USERCHECK STATEMENT
	$stmt->close();
   
	if($status=="ACCEPTED"){
		// CREATE DELETE HASH VARS
		$key1 = "g8/d2o8R452gius1h¤(729dp9Thdlbdt";
		$key2 = "86%G7d2ius875HVD167%/%Û2p+10g/&f";
		$dateTimeNow = date('d/m-Y H:i:s');
		$user_ip = $_SERVER['REMOTE_ADDR'];
      
      // MARK USER FOR DELETION
      $stmt = $mysqli->prepare(" UPDATE users".
                               " SET status = 'DELETE',".
                               "     tmp = ?".
                               " WHERE usr = ?".
                               " ;");
		$stmt->bind_param('ss', $TMP, $USERNAME);
		$TMP = sha1($key1.$dateTimeNow.$user_ip.$key2);
      // EXECUTE AND CLOSE STATEMENT
	 	$stmt->execute();
		$stmt->close();
      
      // SEND EMAIL // smtp => 'mail.cbs.dtu.dk'
      $to = $EMAIL;
      $subject = "Deletion of CGE User Account";
      $message = "Dear $USERNAME,\r\n".
                 "We have registered a request for your account deletion.\r\n".
                 "\r\n".
                 "The account to be deleted is:\r\n".
                 "Username: $USERNAME\r\n".
                 "\r\n".
                 "To confirm the user account deletion, and the deletion of ".
                 "all its non-public data, please follow the following ".
                 "link:\r\n".
                 "https://cge.cbs.dtu.dk/cge/user/login/user_manager.php?action=".
                 "confirm_delete&uid=$USERNAME&tmp=$TMP\r\n".
                 "\r\n".
                 "When deleted, your user account and all of its non-public ".
                 "data will be completely removed, and the username can be ".
                 "reused by a new user.\r\n".
                 "\r\n".
                 "\r\n".
                 "If you did not apply for this user account deletion, please ".
                 "follow the following link to cancel the process:\r\n".
                 "https://cge.cbs.dtu.dk/cge/user/login/user_manager.php?action=".
                 "cancel_delete&uid=$USERNAME&tmp=$TMP\r\n".
                 "\r\n".
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
