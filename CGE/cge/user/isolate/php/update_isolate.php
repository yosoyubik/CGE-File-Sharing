<?php
################################################################################
#                              CGE Update Isolate                              #
################################################################################
// This is the script which:
//   -> Validates and checks user session details (AUTHORIZATION)
//   -> Validates and check availability of requested isolate changes
//   -> Updates the isolate details
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
   if($DATA !== ''){
      arr2xml($DATA, $xml);
   }
   print $xml->asXML();
   if($EXIT == true) exit();
}

// MAIN
if (count($_POST)>0 and isset($_SESSION['SESSIONID']) and isset($_SESSION['USERNAME'])){ #  or count($_GET)>0
	// VALIDATE SESSION
   $SESSIONID = $_SESSION['SESSIONID'];
   $USERNAME = $_SESSION['USERNAME'];
	if (preg_match("/[^A-Za-z0-9\_\-\.\@\,]/", $USERNAME)){ respond("BADUSER", '', true); }
	if (preg_match("/[^A-fa-f0-9]/", $SESSIONID)){ respond("BADSESSION", '', true); }
   // VALIDATE INPUTS
   $iid = _INPUT('IID');
   $name = _INPUT('NAME');
   $note = _INPUT('NOTE');
   $mdate = _INPUT('MDATE');
   $technology = _INPUT('TECHNOLOGY');
   $ipublic = _INPUT('IPUBLIC');
   $country = _INPUT('COUNTRY');
   $region = _INPUT('REGION');
   $city = _INPUT('CITY');
   $zip = _INPUT('ZIP');
   $lon = _INPUT('LON');
   $lat = _INPUT('LAT');
   $lnote = _INPUT('LOCNOTE');
   $origin = _INPUT('ORIGIN');
   $onote = _INPUT('ONOTE');
   $pathogenicity = _INPUT('PATHOGENICITY');
   $pnote = _INPUT('PNOTE');
   //if (_INPUT('IPUBLIC')){ $ipublic = true; }else{ $ipublic = false; }
	if (preg_match("/[^0-9]/", $iid)){ respond("BADID", array('VALUE' => $iid), true); }
	if (preg_match("/[^0-9\.]/", $lon)){ respond("BADLON", array('VALUE' => $lon), true); }
	if (preg_match("/[^0-9\.]/", $lat)){ respond("BADLAT", array('VALUE' => $lat), true); }
	if (preg_match("/[^01]/", $ipublic)){ respond("BADPUBLIC", array('VALUE' => $ipublic), true); }
	if (!preg_match("/^(yes|no|unknown)$/", $pathogenicity)){ respond("BADPATHOGEN", array('VALUE' => $pathogenicity), true); }
	if ($country==''){ respond("NOCOUNTRY", '', true); }
	if ($city==''){ respond("NOCITY", '', true); }
   
	// CONNECT TO THE DATABASE
	$mysqli = new mysqli('cge', 'cgeclient', 'www', 'cge');
	// CHECK CONNECTION
	if (mysqli_connect_errno()) { respond("MYSQLERROR", array('MESSAGE' => "Connect failed: %s\n", mysqli_connect_error())); }
   
   // CHECK USERNAME, SESSIONID AND ISOLATE
	$stmt = $mysqli->prepare("SELECT u.id, u.session_id
                             FROM isolates i
                             INNER JOIN users u ON u.id = i.owner
                             WHERE u.usr = ?
                             AND i.id = ?
                             ;");
	$stmt->bind_param('ss', $USERNAME, $iid);
	// EXECUTE AND GET RESULTS
	$stmt->execute();
	$stmt->bind_result($uid, $sid);

	// CHECK IF SESSIONID MATCHES
	$status = 'NOISOLATE';
	while($stmt->fetch()){
      if ($SESSIONID == $sid){
      	$status = "ACCEPTED";
      }else{
      	$status = "REJECTED";
      }
   }
   
	// CLOSE STATEMENT
	$stmt->close();
   
	if($status=="ACCEPTED"){
      // UPDATE THE ISOLATE DETAILS
		$stmt = $mysqli->prepare("UPDATE isolates i, meta m
                                SET i.sample_name = ?,
                                    i.sequencing_platform = ?,
                                    i.public = ?,
                                    i.shared = '',
                                    m.country = ?,
                                    m.region = ?,
                                    m.city = ?,
                                    m.zip_code = ?,
                                    m.longitude = ?,
                                    m.latitude = ?,
                                    m.location_note = ?,
                                    m.collection_date = ?,
                                    m.isolation_source = ?,
                                    m.source_note = ?,
                                    m.pathogenic = ?,
                                    m.pathogenicity_note = ?,
                                    m.notes = ?
                                WHERE m.id = i.id
                                  AND i.id = ?
                                  AND i.owner = ?
                                ;");
		$stmt->bind_param('ssssssssssssssssss', $name, $technology, $ipublic, $country, $region, $city, $zip, $lon, $lat, $lnote, $mdate, $origin, $onote, $pathogenicity, $pnote, $note, $iid, $uid);
      // EXECUTE AND CLOSE STATEMENT
	 	$stmt->execute();
		$stmt->close();	 
	   
      if ($mysqli->error) {
         respond("MYSQLERROR", array('MESSAGE' => $mysqli->error));
      }else{
         // RESPOND WITH SUCCES
         respond($status, '');
      }
	}else{
		// RESPOND WITH REJECTION
		respond($status, '');
	}
	// CLOSING CONNECTION
	$mysqli->close();
} else { echo "<html><head><title>Unauthorized Usage!</title></head><body>Get Lost!!!</body></html>"; }
?>
