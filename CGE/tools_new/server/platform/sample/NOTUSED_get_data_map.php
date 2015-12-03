<?php
################################################################################
#                           CGE Get Map Isolate Data                           #
################################################################################
// This is the script which:
//   -> Validates and checks user session details (AUTHORIZATION)
//   -> Extracts all available map data
//   -> Returns the status, and any found data

// IMPORT PHP LIBRARIES
include_once('/srv/www/php-lib/cge_std_tools.php'); // Including CGE_std clases and functions

// FUNCTIONS

////////////////////////  MAIN  /////////////////////////
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
   
   // VALIDATE USER AND EXTRACT UID  => $status = ACCEPTED or REJECTED
	list ($status, $uid) = ValidateUser($mysqli, $USERNAME);
   
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
