<?php
################################################################################
#                              CGE Delete Isolate                              #
################################################################################
/* This is the script which:
      -> Validates and checks user session details (AUTHORIZATION)
      -> Validates and check availability of requested isolate
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
   $iid = _INPUT("IID");
	if ($iid == '' or $iid == null){ respond("NOIID", '', true); }
   if (preg_match("/[^0-9]/", $iid)){ respond("BADIID", array('VALUE' => $iid), true); }
   
	// CONNECT TO THE DATABASE
	$mysqli = new mysqli('cge', 'cgeclient', 'www', 'cge');
	// CHECK CONNECTION
	if (mysqli_connect_errno()) { respond("MYSQLERROR", array('MESSAGE' => "Connect failed: %s\n", mysqli_connect_error()), true); }
   // VALIDATE USER AND EXTRACT UID  => $status = ACCEPTED or NOUSER or INVALIDSESSION
	list ($status, $uid) = ValidateUser($mysqli, $USERNAME, $SESSIONID);
   // VERIFY ISOLATE ACCESS PERMISSIONS => $status = ACCEPTED or NOACCESS or NOISOLATE
   if($status=="ACCEPTED"){ list( $status, $ifolder ) = ValidateIsolateAccess($mysqli, $uid, $iid); }
   
   if($status=="ACCEPTED"){ 
      $stmt = $mysqli->prepare("SELECT u.id, u.session_id, i.public, i.isolate_path
                              FROM isolates i
                              INNER JOIN users u ON u.id = i.owner
                              WHERE u.usr = ?
                              AND i.id = ?
                              ;");
      $stmt->bind_param('ss', $USERNAME, $iid);
      // EXECUTE AND GET RESULTS
      $stmt->execute();
      $stmt->bind_result($uid, $sesid, $public, $ifolder);
   
      // CHECK IF SESSIONID MATCHES
      $status = 'NOISOLATE';
      while($stmt->fetch()){
         if ($SESSIONID == $sesid){
            $status = "ACCEPTED";
         }else{
            $status = "REJECTED";
         }
      }
      
      // CLOSE STATEMENT
      $stmt->close();
      
      if($status=="ACCEPTED"){
         if($public==1 and 2+2==5){
            // UPDATE ISOLATE - Remove from owner
            $stmt = $mysqli->prepare("UPDATE isolates
                                    SET removed = true,
                                          owner = 1,
                                          shared = ''
                                    WHERE id = ?
                                    ;");
         }else{
            // DELETE ISOLATE FROM DATABASE
            $stmt = $mysqli->prepare("DELETE i, m, s, f, e
                                      FROM isolates i
                                      LEFT OUTER JOIN meta m ON i.id = m.id
                                      LEFT OUTER JOIN services s ON i.id = s.isolate_id
                                      LEFT OUTER JOIN files f ON i.id = f.isolate_id
                                      LEFT OUTER JOIN evergreen e ON i.id = e.isolate_id
                                      WHERE i.id = ?
                                      AND i.owner = ?
                                      ;");
         }
         $stmt->bind_param('ss', $iid, $uid);
         
         // EXECUTE AND CLOSE STATEMENT
         $stmt->execute();
         $stmt->close();	 
         
         $msg = array();
         if(strlen($ifolder) > 3){ // $public==0 and 
            $path = $ifolder;
            // CHECK FILE EXISTS
            if(is_dir($path)){
               // DELETE FOLDER AND FILES
               $cmd = "rm -rf ".escapeshellarg($path);
               system($cmd);
               $msg['CMD'] = $cmd;
            }else{
               $status = 'NOIFOLDER';
               $msg['folder'] = $path;
               $msg['isolateID'] = $iid;
            }
         }
         
         // RESPOND WITH SUCCES
         if ($mysqli->error) {
            respond("MYSQLERROR", array('MESSAGE' => $mysqli->error));
         }else{
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
