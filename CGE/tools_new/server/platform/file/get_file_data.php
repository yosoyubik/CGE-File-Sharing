<?php
################################################################################
#                             CGE Get Isolate Data                             #
################################################################################
/* This is the script which:
      -> Validates and checks user session details (AUTHORIZATION)
      -> Extract isolate data
      -> Returns the status, and any found data
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
   $iid = _INPUT('IID');
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
      // GET ISOLATE DATA FROM DATABASE
      $stmt = $mysqli->prepare("SELECT i.sample_name, i.date, i.sequencing_platform, i.sequencing_type, i.pre_assembled
                                FROM isolates i 
                                WHERE i.id = ?
                                AND i.removed = false
                                ;");
      $stmt->bind_param('s', $iid);
   	// EXECUTE AND GET RESULTS
      $stmt->execute();
      $stmt->bind_result($name, $date, $sequencing_platform, $sequencing_type, $pre_assembled);
      
      // FETCH RESULTS
      $DATA = array();
      $DATA['files'] = array();
      $file_counter = 0;
      while($stmt->fetch()){
         # Handling unset variables
         if(!isset($iid)){ $iid = ''; }
         if(!isset($name)){ $name = ''; }
         if(!isset($date)){ $date = ''; }
         if(!isset($sequencing_platform)){ $sequencing_platform = ''; }
         if(!isset($sequencing_type)){ $sequencing_type = ''; }
         if(!isset($pre_assembled)){ $pre_assembled = ''; }
         # Handle defaults
         if($sequencing_type == 'paired'){ $sequencing_type = 'Paired End'; }
         elseif($sequencing_type == 'mate'){ $sequencing_type = 'Mate Paired'; }
         else{ $sequencing_type = 'Single End'; }
         
         # Parsing Data
         $DATA['sample_id'] = $iid;
         $DATA['sample_name'] = $name;
         if($pre_assembled == 0){ # CONTIGS
            $file = array(
                  'id' => '0',
                  'service' => 'Uploaded Data',
                  'date' => $date,
                  'name' => 'fastq',
                  'description' => "$sequencing_platform $sequencing_type #1",
               );
            $DATA['files']["f$file_counter"] = $file;
            $file_counter += 1;
            if($sequencing_type != 'Single End'){ # CONTIGS
               $file = array(
                     'id' => '1',
                     'service' => 'Uploaded Data',
                     'date' => $date,
                     'name' => 'fastq',
                     'description' => "$sequencing_platform $sequencing_type #2",
                  );
               $DATA['files']["f$file_counter"] = $file;
               $file_counter += 1;
            }
         }else{
            $file = array(
                  'id' => '0',
                  'service' => 'Uploaded Data',
                  'date' => $date,
                  'name' => 'contigs',
                  'description' => "Contigs / draft genome / assembled genome",
               );
            $DATA['files']["f$file_counter"] = $file;
            $file_counter += 1;
         }
      }
      // CLOSE STATEMENT
	   $stmt->close();
	   
      // GET FILE DATA FROM DATABASE
      $stmt = $mysqli->prepare("SELECT f.id, s.service, f.date, f.name, f.description
                                FROM files f
                                INNER JOIN services s ON f.service_id = s.id
                                WHERE f.isolate_id = ?
                                ;");
      $stmt->bind_param('s', $iid);
   	// EXECUTE AND GET RESULTS
      $stmt->execute();
      $stmt->bind_result($fid, $service, $date, $name, $description);
      
      // FETCH RESULTS
      while($stmt->fetch()){
         $file = array(
               'id' => "$fid",
               'service' => $service,
               'date' => $date,
               'name' => $name,
               'description' => $description,
            );
         $DATA['files']["f$file_counter"] = $file;
         $file_counter += 1;
      }
      
      // CLOSE STATEMENT
	   $stmt->close();
      
      // RESPOND
      //echo var_dump($DATA);
      if ($mysqli->error) {
         respond("MYSQLERROR", array('MESSAGE' => $mysqli->error));
      }elseif (count($DATA) >= 1){
         // RESPOND WITH SUCCES
         respond($status, $DATA);
      }else{
         // RESPOND WITH NO DATA FOUND
         respond('NODATA', '');
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
