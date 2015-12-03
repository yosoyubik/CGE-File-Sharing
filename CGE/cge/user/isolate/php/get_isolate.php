<?php
################################################################################
#                             CGE Get Isolate Data                             #
################################################################################
// This is the script which:
//   -> Validates and checks user session details (AUTHORIZATION)
//   -> Extract isolate data
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
if ((count($_POST)>0) and isset($_SESSION['SESSIONID']) and isset($_SESSION['USERNAME'])){ #+count($_GET)
   // VALIDATE SESSION
   $USERNAME = $_SESSION['USERNAME'];
   $SESSIONID = $_SESSION['SESSIONID'];
	if (preg_match("/[^A-Za-z0-9\_\-\.\@\,]/", $USERNAME)){ respond("BADUSER", '', '', true); }
	if (preg_match("/[^A-fa-f0-9]/", $SESSIONID)){ respond("BADSESSION", '', '', true); }
   // VALIDATE INPUTS
   $IID = _INPUT('ISOLATEID');
	if (preg_match("/[^0-9]/", $IID)){ respond("BADISOLATE", '', '', true); }
   
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
      $stmt = $mysqli->prepare("SELECT i.id, i.sample_name, i.date, i.sequencing_platform, i.sequencing_type, i.pre_assembled, i.public, i.shared, 
                                       m.country, m.region, m.city, m.zip_code, m.longitude, m.latitude, m.location_note, m.collection_date, m.isolation_source, m.source_note, m.pathogenic, m.pathogenicity_note, m.notes, m.organism, m.strain, m.subtype, m.collected_by
                                FROM isolates i 
                                LEFT OUTER JOIN meta m ON m.id = i.id
                                WHERE i.owner = ?
                                AND i.id = ?
                                AND i.removed = false
                                ;");
      $stmt->bind_param('ss', $uid, $IID);
   	// EXECUTE AND GET RESULTS
      $stmt->execute();
      $stmt->bind_result($iid, $name, $idate, $technology, $sequencing_type, $pre_assembled, $ipublic, $shared,
                         $country, $region, $city, $zip, $lon, $lat, $lnote, $mdate, $origin, $onote, $pathogenicity, $pnote, $note, $organism, $strain, $subtype, $collected_by
                         );
      
      // FETCH RESULTS
      $DATA = array();
      while($stmt->fetch()){
         # Handling unset variables
         if(!isset($iid)){ $iid = ''; }
         if(!isset($name)){ $name = ''; }
         if(!isset($idate)){ $idate = ''; }
         if(!isset($technology)){ $technology = ''; }
         if(!isset($sequencing_type)){ $sequencing_type = ''; }
         if(!isset($pre_assembled)){ $pre_assembled = ''; }
         if(!isset($ipublic)){ $ipublic = ''; }
         if(!isset($shared)){ $shared = ''; }
         if(!isset($country)){ $country = ''; }
         if(!isset($region)){ $region = ''; }
         if(!isset($city)){ $city = ''; }
         if(!isset($zip)){ $zip = ''; }
         if(!isset($lon)){ $lon = ''; }
         if(!isset($lat)){ $lat = ''; }
         if(!isset($lnote)){ $lnote = ''; }
         if(!isset($mdate)){ $mdate = ''; }
         if(!isset($origin)){ $origin = ''; }
         if(!isset($onote)){ $onote = ''; }
         if(!isset($pathogenicity)){ $pathogenicity = ''; }
         if(!isset($pnote)){ $pnote = ''; }
         if(!isset($note)){ $note = ''; }
         if(!isset($organism)){ $organism = ''; }
         if(!isset($strain)){ $strain = ''; }
         if(!isset($subtype)){ $subtype = ''; }
         if(!isset($collected_by)){ $collected_by = ''; }

         # Creating Isolate Array
         $isolate = array(
            'id' => $iid,
            'name' => $name,
            'date' => $idate,
            'technology' => $technology,
            'sequencing_type' => $sequencing_type,
            'pre_assembled' => $pre_assembled,
            'ipublic' => $ipublic,
            'shared' => $shared,
            'metadata' => array(),
            'services' => array()
         );
         if($country !== ''){
            $isolate['metadata'] = array(
               'country' => $country,
               'region' => $region,
               'city' => $city,
               'zip' => $zip,
               'lon' => $lon,
               'lat' => $lat,
               'lnote' => $lnote,
               'date' => $mdate,
               'origin' => $origin,
               'onote' => $onote,
               'pathogenicity' => $pathogenicity,
               'pnote' => $pnote,
               'note' => $note,
               'organism' => $organism,
               'strain' => $strain,
               'subtype' => $subtype,
               'collected_by' => $collected_by
            );
         }
         # Update Data
         $DATA = $isolate;
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
