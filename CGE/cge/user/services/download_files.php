<?php
###############################################################
#               CGE SERVICES DOWNLOAD RESULTS                 #
###############################################################

// ERROR LOGGING
error_reporting(E_ALL);
ini_set('error_log','/srv/www/htdocs/services/error_log');
ini_set('log_errors','true');

// DEPENDENCIES
session_start(); // ENABLE PHP SESSION

////////////////////////  FUNCTIONS  ////////////////////////
function _INPUT($name){
    if ($_SERVER['REQUEST_METHOD'] == 'GET')
        return isset($_GET[$name])? strip_tags($_GET[$name]): '';
    if ($_SERVER['REQUEST_METHOD'] == 'POST')
        return isset($_POST[$name])? strip_tags($_POST[$name]): '';
}

function arr2xml(&$arr, &$xml){
   /* WALKING THROUGH THE ARRAY AND ITS CHILDREN TO COPY/CONVERT IT TO XML FORMAT */
   if (is_array($arr)){
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
}

function respond($status, $DATA, $EXIT=false){
   /* PRINTING THE XML FORMATTED DATA */
   $xml = new SimpleXMLElement('<XML/>');
   $xml->addChild('STATUS', $status);
   arr2xml($DATA, $xml);
   print_r($xml);
   //print $xml->asXML();
   if($EXIT == true) exit();
}

function downloadFile( $filePath, $filename ){
   /* // function for force downloading files
   */
   
   // Must be fresh start
   if( headers_sent() )
      die('Headers Sent');

   // Required for some browsers
   if(ini_get('zlib.output_compression'))
      ini_set('zlib.output_compression', 'Off');
   
   // PREPARE FILEPATH (removing gz extension)
   $path_parts = pathinfo($filename);
   if($path_parts['extension'] == 'gz'){ $filename = $path_parts['filename']; }
   
   // CHECK IF THE FILE EXISTS
   $fullPath = "$filePath/$filename";
   if(file_exists($fullPath)){
      // Read File to standard out
      header("Pragma: public"); // required
      header("Expires: 0");
      header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
      header("Cache-Control: private",false); // required for certain browsers
      header("Content-Type: application/force-download");
      header("Content-Disposition: attachment; filename=$filename;");
      header("Content-Transfer-Encoding: binary");
      header("Content-Length: ".filesize($fullPath));
      ob_clean();
      flush();
      readfile($fullPath);
   }else{
      // Check if file is gzipped
      $fullPath .= ".gz";
      if(file_exists($fullPath)){
         // Read File to standard out
         header("Pragma: public"); // required
         header("Expires: 0");
         header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
         header("Cache-Control: private",false); // required for certain browsers
         header("Content-Type: application/force-download");
         header("Content-Disposition: attachment; filename=".$filename.";" );
         header("Content-Transfer-Encoding: binary");
         ob_clean();
         flush();
         readgzfile($fullPath);
      }else{
         // Output does not exist, maybe the run failed, or is still running?
         $msg = array();
         $msg['FULLPATH'] = $fullPath;
         $msg['MESSAGE'] = "Error: No file was found, either the job has not finished, or the job failed during execution!";
         respond("NOFILE", $msg, true);
      }
   }
}#END FUNCTION 

////////////////////////  MAIN  ////////////////////////
// CHECK FOR CORRECT INPUTS
if (count($_POST)+count($_GET)>0 and isset($_SESSION['SESSIONID']) and isset($_SESSION['USERNAME'])){
   // INITIALIZE
   $filePath = '';
   $filename = '';
   
   // VALIDATE SESSION VARIABLES
   $SESSIONID = $_SESSION['SESSIONID'];
   $USERNAME = $_SESSION['USERNAME'];
   if (preg_match("/[^A-Za-z0-9\_\-\.\@\,]/", $USERNAME)){ respond("BADUSER", '', true); }
   if (preg_match("/[^A-fa-f0-9]/", $SESSIONID)){ respond("BADSESSION", '', true); }
   // CHECK FOR FILE ID INPUT
	$fid = _INPUT('FID');
   if ($fid != null){
      // VALIDATE INPUTS
      if (preg_match("/[^0-9]/", $fid)){ respond("BADFID", '', true); }
      // PROCESS INPUTS
      
      // CONNECT TO THE DATABASE
      $mysqli = new mysqli('cge', 'cgeclient', 'www', 'cge');
      // CHECK CONNECTION
      if (mysqli_connect_errno()) { respond("Connect failed: %s\n", mysqli_connect_error(), '', '', true); }
      // CHECK USERNAME AND SESSIONID
      $stmt = $mysqli->prepare("SELECT id, session_id FROM users WHERE usr = ?");
      $stmt->bind_param('s', $USERNAME);
      // EXECUTE AND GET RESULTS
      $stmt->execute();
      $stmt->bind_result($uid, $sesid);
      
      // CHECK IF SESSIONID MATCHES
      $status = 'REJECTED';
      while($stmt->fetch()){
         if ($SESSIONID == $sesid){
         	$status = "ACCEPTED";
         }
      }
      // CLOSE STATEMENT
      $stmt->close();
      
      if($status=="ACCEPTED"){	
         // RETRIEVE FILE PATH FROM DATABASE
         $stmt = $mysqli->prepare("SELECT f.path 
                                   FROM files f 
                                   WHERE f.owner = ?
                                   AND f.id = ?
                                   ;");
         $stmt->bind_param('ss', $uid, $fid);
         // EXECUTE AND GET RESULTS
         $stmt->execute();
         $stmt->bind_result($fullpath);
         // FETCH RESULTS
         $count = 0;
         while($stmt->fetch()){ $count++; }
         // CLOSE STATEMENT
   	   $stmt->close();
         
         // RESPOND
         if ($count >= 1){
            // GET filePath and fileName from fullpath
            $path_parts = pathinfo($fullpath);
            $filePath = $path_parts['dirname'];
            $filename = $path_parts['basename'];
         }else{
            // RESPOND WITH NO DATA FOUND
            respond('NODATA', '');
         }
      }else{
      	// RESPOND WITH REJECTION
      	respond($status, '', '');
      }
      //CLOSING DATABASE
      $mysqli->close();
   }else{
      // VALIDATE INPUTS
      $filename = _INPUT('FILENAME');
      $iid = _INPUT('IID');
      $date = _INPUT('DATE');
      $sid = _INPUT('SID');
      $service = _INPUT('SERVICE');
      $version = _INPUT('VERSION');
      if ($filename == ''){ respond("NOFILENAME", '', true); }
      if ($iid == ''){ respond("NOIID", '', true); }
      if ($date == ''){ respond("NODATE", '', true); }
      if ($sid == ''){ respond("NOSID", '', true); }
      if ($service == ''){ respond("NOSERVICE", '', true); }
      if ($version == ''){ respond("NOVERSION", '', true); }
      if (preg_match("/[^\w\.\-]/", $filename)){ respond("BADFILENAME", '', true); }
      if (preg_match("/[^0-9]/", $iid)){ respond("BADIID", '', true); }
      if (!preg_match("/\d{4}-\d{2}-\d{2}/", $date)){ respond("BADDATE", '', true); }
      if (preg_match("/[^0-9]/", $sid)){ respond("BADSERVICEID", '', true); }
      if (preg_match("/[^A-Za-z]/", $service)){ respond("BADSERVICE", '', true); }
      if (preg_match("/[^0-9A-Za-z\.]/", $version)){ respond("BADVERSION", '', true); }
      // PROCESS INPUTS
      $date = split('-', $date);
      $year = $date[0];
      $month = $date[1];
      $day = $date[2];
      $filePath = "/home/data1/isolates/$year/$month/$day/$iid/services/".$service."_$sid/downloads/";
   }
   // TRY TO UPLOAD THE DOWNLOADABLE FILE TO THE USER
   if($filePath != '' and $filename != ''){
      downloadFile($filePath, $filename);
   }
}else{
	echo "<html><body>Missing input</body></html>";
}
?>