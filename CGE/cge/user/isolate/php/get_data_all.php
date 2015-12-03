<?php
################################################################################
#                           CGE Get All Isolate Data                           #
################################################################################
// This is the script which:
//   -> Validates and checks user session details (AUTHORIZATION)
//   -> Extracts all available isolate data
//   -> Returns the status, and any found data

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
   //elseif ($_SERVER['REQUEST_METHOD'] == 'GET' and isset($_GET[$name]))
   //   return strip_tags($_GET[$name]);
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
if (isset($_SESSION['SESSIONID']) and isset($_SESSION['USERNAME'])){ # count($_POST)>0 or count($_GET)>0
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
	$stmt = $mysqli->prepare("SELECT id, session_id FROM users WHERE usr = ?");
	$stmt->bind_param('s', $USERNAME);
	// EXECUTE AND GET RESULTS
	$stmt->execute();
	$stmt->bind_result($uid, $sid);

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
      // GET ISOLATE DATA FROM DATABASE
      $stmt = $mysqli->prepare("SELECT i.id, i.sample_name, i.date, i.sequencing_platform, i.sequencing_type, i.pre_assembled, i.isolate_path, i.public, i.sample_type,
                                       m.country, m.region, m.city, m.zip_code, m.longitude, m.latitude, m.collection_date, m.isolation_source, m.pathogenic, m.organism, m.strain, m.subtype, m.collected_by,
                                       s.id, s.service, s.date, s.folder, s.other, s.status
                                FROM isolates i 
                                LEFT OUTER JOIN meta m ON m.id = i.id
                                LEFT OUTER JOIN services s ON s.isolate_id = i.id
                                WHERE i.owner = ?
                                  AND NOT i.removed = 1
                                ORDER BY i.id DESC
                                ;");
      $stmt->bind_param('s', $uid);
   	// EXECUTE AND GET RESULTS
      $stmt->execute();
      $stmt->bind_result($iid, $sample_name, $date, $sequencing_platform, $sequencing_type, $pre_assembled, $isolate_path, $public, $sample_type,
                         $country, $region, $city, $zip_code, $longitude, $latitude, $collection_date, $isolation_source, $pathogenic, $organism, $strain, $sub_type, $collected_by,
                         $sid, $service, $sdate, $sfolder, $other, $sstatus);
      
      // FETCH RESULTS
      $DATA = array();
      while($stmt->fetch()){
         # Handling unset variables
         if(!isset($iid)){ $iid = ''; }
         if(!isset($sample_name)){ $sample_name = ''; }
         if(!isset($date)){ $date = ''; }
         if(!isset($sequencing_platform)){ $sequencing_platform = ''; }
         if(!isset($sequencing_type)){ $sequencing_type = ''; }
         if(!isset($pre_assembled)){ $pre_assembled = ''; }
         if(!isset($isolate_path)){ $isolate_path = ''; }
         if(!isset($public)){ $public = ''; }
         if(!isset($sample_type)){ $sample_type = ''; }
         if(!isset($country)){ $country = ''; }
         if(!isset($region)){ $region = ''; }
         if(!isset($city)){ $city = ''; }
         if(!isset($zip_code)){ $zip_code = ''; }
         if(!isset($longitude)){ $longitude = ''; }
         if(!isset($latitude)){ $latitude = ''; }
         if(!isset($collection_date)){ $collection_date = ''; }
         if(!isset($isolation_source)){ $isolation_source = ''; }
         if(!isset($pathogenic)){ $pathogenic = ''; }
         if(!isset($organism)){ $organism = ''; }
         if(!isset($strain)){ $strain = ''; }
         if(!isset($sub_type)){ $sub_type = ''; }
         if(!isset($collected_by)){ $collected_by = ''; }
         if(!isset($sid)){ $sid = ''; }
         if(!isset($service)){ $service = ''; }
         if(!isset($sdate)){ $sdate = ''; }
         if(!isset($sfolder)){ $sfolder = ''; }
         if(!isset($other)){ $other = ''; }
         if(!isset($sstatus)){ $sstatus = ''; }
         # Creating Isolate Array
         $isolate = array(
            'id' => $iid,
            'sample_name' => $sample_name,
            'date' => $date,
            'sequencing_platform' => $sequencing_platform,
            'sequencing_type' => $sequencing_type,
            'pre_assembled' => $pre_assembled,
            'isolate_path' => $isolate_path,
            'public' => $public,
            'sample_type' => $sample_type,
            'metadata' => array(),
            'services' => array()
         );
         if($country !== ''){
            $isolate['metadata'] = array(
               'country' => $country,
               'region' => $region,
               'city' => $city,
               'zip_code' => $zip_code,
               'longitude' => $longitude,
               'latitude' => $latitude,
               'collection_date' => $collection_date,
               'isolation_source' => $isolation_source,
               'pathogenic' => $pathogenic,
               'organism' => $organism,
               'strain' => $strain,
               'sub_type' => $sub_type,
               'collected_by' => $collected_by
            );
         }
         if($sid !== ''){
            $isolate['services']["S$sid"] = array(
                  'id' => $sid,
                  'service' => $service,
                  'date' => $sdate,
                  'folder' => $sfolder,
                  'servicedata' => $other,
                  'status' => $sstatus
            );
         }
         // UPDATE THE DATA OBJECT
         if (isset($DATA["I$iid"])){
            // ISOLATE EXIST -> ADD SERVICE TO THE ISOLATE
            $DATA["I$iid"]['services']["S$sid"] = $isolate['services']["S$sid"];
         }else{
            // ADD THE ISOLATE
            $DATA["I$iid"] = $isolate;
         }
      }
      // CLOSE STATEMENT
	   $stmt->close();
	   
      // RESPOND
      if (count($DATA) >= 1){
         // RESPOND WITH SUCCES
         respond($status, $DATA);
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
} else {
	echo "<html><head><title>Unauthorized Usage!</title></head><body>Get Lost!!!</body></html>";
}
?>
