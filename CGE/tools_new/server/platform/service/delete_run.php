<?php
################################################################################
#                            CGE Delete Service Run                            #
################################################################################
/* This is the script which:
      -> Validates and checks user session details (AUTHORIZATION)
      -> Validates and check availability of requested run
      -> Deletes the isolate details
      -> Deletes the isolate folder, if non-public
      -> Returns the status of the transaction
 */

// IMPORT PHP LIBRARIES
include_once('/srv/www/php-lib/cge_std_tools.php'); // Including CGE_std clases and functions

////////////////////////  MAIN  /////////////////////////
if ((count($_POST)+count($_GET)>0) and isset($_SESSION['SESSIONID']) and isset($_SESSION['USERNAME'])){
	// VALIDATE SESSION
   $SESSIONID = $_SESSION['SESSIONID'];
   $USERNAME = $_SESSION['USERNAME'];
	if (preg_match("/[^A-Za-z0-9\_\-\.\@\,]/", $USERNAME)){ respond("BADUSER", '', true); }
	if (preg_match("/[^A-fa-f0-9]/", $SESSIONID)){ respond("BADSESSION", '', true); }
	// VALIDATE INPUTS
   $sid = _INPUT("SID");
	if ($sid == '' or $sid == null){ respond("NOSID", '', true); }
   if (preg_match("/[^0-9]/", $sid)){ respond("BADSID", array('VALUE' => $sid), true); }
   
	// CONNECT TO THE DATABASE
	$mysqli = new mysqli('cge', 'cgeclient', 'www', 'cge');
	// CHECK CONNECTION
	if (mysqli_connect_errno()) { respond("MYSQLERROR", array('MESSAGE' => "Connect failed: %s\n", mysqli_connect_error()), true); }
   // VALIDATE USER AND EXTRACT UID  => $status = ACCEPTED or NOUSER or INVALIDSESSION
	list ($status, $uid) = ValidateUser($mysqli, $USERNAME, $SESSIONID);
   // VERIFY SERVICE ACCESS PERMISSIONS => $status = ACCEPTED or NOACCESS or NOSERVICE
   if($status=="ACCEPTED"){ list( $status, $sfolder, $service ) = ValidateServiceAccess($mysqli, $uid, $sid); }
   
	if($status=="ACCEPTED"){
      // DELETE ISOLATE FROM DATABASE
      $stmt = $mysqli->prepare("DELETE s, f, e
                                FROM services s
                                LEFT OUTER JOIN files f ON s.id = f.service_id
                                LEFT OUTER JOIN evergreen e ON s.id = e.service_id
                                WHERE s.id = ?
                                AND s.owner = ?
                                ;");
      $stmt->bind_param('ss', $sid, $uid);
      
      // EXECUTE AND CLOSE STATEMENT
	 	$stmt->execute();
		$stmt->close();	 
      
      $msg = array();
      // CHECK FILE EXISTS
      if($sfolder != null AND is_dir($sfolder)){
         // DELETE FOLDER AND FILES
         $cmd = "rm -rf ".escapeshellarg($sfolder);
         system($cmd);
         $msg['CMD'] = $cmd;
      }else{
         $status = 'NOSFOLDER';
         $msg['folder'] = $sfolder;
         $msg['serviceID'] = $sid;
      }
      
      if ($mysqli->error) {
         respond("MYSQLERROR", array('MESSAGE' => $mysqli->error));
      }else{
         // RESPOND WITH SUCCES
         respond($status, $msg);
      }
	}else{
		// RESPOND WITH REJECTION
      if ($mysqli->error) {
         respond("MYSQLERROR", array('MESSAGE' => $mysqli->error));
      }else{
         respond($status, '');
      }
	}
	// CLOSING CONNECTION
	$mysqli->close();
}else{
   echo  "<html>".
            "<head><title>Unauthorized Usage!</title></head>".
            "<body>Get Lost!!!</body>".
         "</html>";
}
?>
