<?php
################################################################################
#                              CGE Delete Isolate                              #
################################################################################
// This is the script which:
//   -> Validates and checks user session details (AUTHORIZATION)
//   -> Validates and check availability of requested isolate
//   -> Deletes the isolate details
//   -> Deletes the isolate folder, if non-public
//   -> Returns the status of the transaction

// ERROR LOGGING
error_reporting(E_ALL);
ini_set('error_log','/srv/www/htdocs/services/error_log');
ini_set('log_errors','true');

// DEPENDENCIES
session_start(); // ENABLE PHP SESSION

// FUNCTIONS
function _INPUT($name){
   // QUERY HANDLER: Used to get form elements and queries in a simple manner
   // AUTHOR: Martin Thomsen
   // USAGE: $form_text = _INPUT('form_text_name');
   if ($_SERVER['REQUEST_METHOD'] == 'POST' and isset($_POST[$name]))
      return strip_tags($_POST[$name]);
   elseif ($_SERVER['REQUEST_METHOD'] == 'GET' and isset($_GET[$name]))
      return strip_tags($_GET[$name]);
   else return NULL;
}

function arr2xml(&$arr, &$xml){
   /* WALKING THROUGH THE ARRAY AND ITS CHILDREN TO COPY/CONVERT IT TO XML FORMAT */
   foreach ($arr as $key => $value){
      $value = $value == NULL ? 'NULL' :$value; // converting NULL to 'NULL'
      $type = gettype($value);
      if($type == 'array'){
         arr2xml($arr[$key], $xml->addChild($key));
      }elseif($type=="boolean" or $type=="integer" or $type=="double" or $type=="string"){ 
         $xml->addChild($key, $value);
      }
   }
}

function respond($status, $DATA, $EXIT=false){
   /* PRINTING THE XML FORMATTED DATA */
   $xml = new SimpleXMLElement('<XML/>');
   $xml->addChild('STATUS', $status);
   arr2xml($DATA, $xml);
   print $xml->asXML();
   if($EXIT == true) exit();
}

// MAIN
if (count($_POST)>0  and isset($_SESSION['SESSIONID']) and isset($_SESSION['USERNAME'])){ # count($_POST)>0 or count($_GET)>0
	// VALIDATE SESSION
   $SESSIONID = $_SESSION['SESSIONID'];
   $USERNAME = $_SESSION['USERNAME'];
	if (preg_match("/[^A-Za-z0-9\_\-\.\@\,]/", $USERNAME)){ respond("BADUSER", '', true); }
	if (preg_match("/[^A-fa-f0-9]/", $SESSIONID)){ respond("BADSESSION", '', true); }
	// VALIDATE INPUTS
   $IID = _INPUT("IID");
	if (preg_match("/[^0-9]/", $IID)){ respond("BADID", '', true); }
	if ($IID == ''){ respond("NOID", '', true); }

	// CONNECT TO THE DATABASE
	$mysqli = new mysqli('cge', 'cgeclient', 'www', 'cge');
   
	// CHECK CONNECTION
	if (mysqli_connect_errno()) { respond("Connect failed: %s\n", mysqli_connect_error(), '', true); }

   // CHECK USERNAME, SESSIONID AND ISOLATE
	$stmt = $mysqli->prepare("SELECT u.id, u.session_id, i.public, i.isolate_path
                             FROM isolates i
                             INNER JOIN users u ON u.id = i.owner
                             WHERE u.usr = ?
                             AND i.id = ?
                             ;");
	$stmt->bind_param('ss', $USERNAME, $IID);
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
         $stmt = $mysqli->prepare("DELETE i, m, s, f
                                   FROM isolates i
                                   LEFT OUTER JOIN meta m ON i.id = m.id
                                   LEFT OUTER JOIN services s ON i.id = s.isolate_id
                                   LEFT OUTER JOIN files f ON i.id = f.isolate_id
                                   WHERE i.id = ?
                                   AND i.owner = ?
                                   ;");
      }
      $stmt->bind_param('ss', $IID, $uid);
      
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
            $status = 'NOFOLDER';
            $msg['FOLDER'] = $path;
         }
      }
      
		// RESPOND WITH SUCCES
		respond($status, $msg);
	}else{
		// RESPOND WITH REJECTION
		respond($status, '');
	}
	// CLOSING CONNECTION
	$mysqli->close();
} else { echo "<html><head><title>Unauthorized Usage!</title></head><body>Get Lost!!!</body></html>"; }
?>
