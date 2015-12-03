<?php
################################################################################
#                          CGE RETRIEVE USER DATA                              #
################################################################################
/* This script retrieves the users details from the database:
 *   -> Validate the session details
 *   -> get_user_info: Retrieve the users details
 */
// ERROR LOGGING
error_reporting(E_ALL);
ini_set('error_log','/srv/www/htdocs/services/error_log');
ini_set('log_errors','true');

// DEPENDENCIES
session_start(); // ENABLE PHP SESSION

// FUNCTIONS
function respond($status, $SESSIONID, $USERNAME, $EXIT=false){
	echo "<?xml version='1.0' encoding='UTF-8'?><SESSION><STATUS>$status</STATUS><SESSIONID>$SESSIONID</SESSIONID><USERNAME>$USERNAME</USERNAME></SESSION>";
   if($EXIT == true) exit();
}

// MAIN
if (isset($_SESSION['SESSIONID']) and isset($_SESSION['USERNAME'])){
   $DATE = date('d-m-Y H:i:s');
   $IP = $_SERVER['REMOTE_ADDR'];
   
	// VALIDATE INPUTS
   $SESSIONID = $_SESSION['SESSIONID'];
   $USERNAME = $_SESSION['USERNAME'];
	if (preg_match("/[^A-Za-z0-9\_\-\.\@\,]/", $USERNAME)){ respond("BADUSER", '', '', true); }
	if (preg_match("/[^A-fa-f0-9]/", $SESSIONID)){ respond("BADSESSION", '', '', true); }
	
	// CONNECT TO THE DATABASE
	$mysqli = new mysqli('cge', 'cgeclient', 'www', 'cge');
   
	// CHECK CONNECTION
	if (mysqli_connect_errno()) { respond("Connect failed: %s\n", mysqli_connect_error(), '', '', true); }
   
   // CHECK USERNAME AND SESSIONID
	$stmt = $mysqli->prepare("SELECT session_id, last_login, ip, status, email FROM users WHERE usr = ?");
	$stmt->bind_param('s', $USERNAME);
	// EXECUTE AND GET RESULTS
	$stmt->execute();
	$stmt->bind_result($sid, $sll, $sip, $stat, $email);
   
	// CHECK IF SESSIONID AND IP MATCHES, AND TIME SINCE LAST LOGIN IS LESS THAN 2 HOURS
	$status = 'REJECTED';
	while($stmt->fetch()){
      if ($SESSIONID == $sid){
         if ($IP == $sip){
            if ((strtotime($DATE)-strtotime($sll)) <= 7200){
               $status = "ACCEPTED";
            }else{
               $status = "TIMEOUT";
            }
         }else{
            $status = "NEWIP";
         }
      }
   }
	// CLOSE STATEMENT
	$stmt->close();
   
	if($status=="ACCEPTED"){
      // UPDATE LAST LOGIN AND IP
		$stmt = $mysqli->prepare(" UPDATE users".
                               " SET last_login = ?,".
                               "     ip = ?".
                               " WHERE usr = ?".
                               " ;");
      $stmt->bind_param('sss', $DATE, $IP, $USERNAME);
      // EXECUTE AND CLOSE STATEMENT
	 	$stmt->execute();
		$stmt->close();
		
		// RESPOND WITH SUCCES
		respond($status, $email, $USERNAME);
	}else{
		// RESPOND WITH REJECTION
		respond($status, '', '');
	}
	// CLOSING CONNECTION
	$mysqli->close();

} else {
   // RESPOND WITH NOT LOGGED IN
   respond('NOSESSION', '', '');
}
?>
