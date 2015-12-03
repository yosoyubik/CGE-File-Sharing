<?php
################################################################################
#                           CGE Get Map Isolate Data                           #
################################################################################
// This is the script which:
//   -> Validates and checks user session details (AUTHORIZATION)
//   -> Extracts all available map data
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
      // GET ISOLATE MAP DATA FROM DATABASE
      $stmt = $mysqli->prepare("SELECT i.name,
                                       m.country, m.city, m.collection_date, m.longitude, m.latitude
                              FROM isolates i 
                              LEFT OUTER JOIN meta m ON i.id = m.id
                              WHERE i.owner = ?
                              ;");
      $stmt->bind_param('s', $uid);
   	// EXECUTE AND GET RESULTS
      $stmt->execute();
      $stmt->bind_result($INAME, $MCOUNTRY, $MCITY, $MDATE, $MLON, $MLAT);
      $stmt->bind_result($iid, $name,
                         $country, $region, $city, $zip, $lon, $lat, $mdate, $origin, $pathogenicity);
      
      // FETCH RESULTS
      $DATA = array();
      while($stmt->fetch()){
         $isolat = array(
            'id' => $iid,
            'name' => $name,
            'metadata' => array(
               'country' => $country,
               'region' => $region,
               'city' => $city,
               'zip' => $zip,
               'date' => $mdate,
               'longitude' => $lon,
               'latitude' => $lat,
               'origin' => $origin,
               'pathogenicity' => $pathogenicity
            )
         );
         // ADD THE ISOLATE TO THE DATA OBJECT
         $DATA[$iid] = $isolat;
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
