<?php
################################################################################
#                              CGE Update Isolate                              #
################################################################################
/* This is the script which:
      -> Validates and checks user session details (AUTHORIZATION)
      -> Validates and check availability of requested isolate changes
      -> Updates the isolate details
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
	if ($iid == '' or $iid == null){ respond("NOIID", '', true); }
	if ($country == '' or $country == null){ respond("NOCOUNTRY", '', true); }
   if (preg_match("/[^0-9]/", $iid)){ respond("BADIID", array('VALUE' => $iid), true); }
	if (preg_match("/[^0-9\.]/", $lon)){ respond("BADLON", array('VALUE' => $lon), true); }
	if (preg_match("/[^0-9\.]/", $lat)){ respond("BADLAT", array('VALUE' => $lat), true); }
	if (preg_match("/[^01]/", $ipublic)){ respond("BADPUBLIC", array('VALUE' => $ipublic), true); }
	if (!preg_match("/^(yes|no|unknown)$/", $pathogenicity)){ respond("BADPATHOGEN", array('VALUE' => $pathogenicity), true); }
   
	// CONNECT TO THE DATABASE
	$mysqli = new mysqli('cge', 'cgeclient', 'www', 'cge');
	// CHECK CONNECTION
	if (mysqli_connect_errno()) { respond("MYSQLERROR", array('MESSAGE' => "Connect failed: %s\n", mysqli_connect_error()), true); }
   // VALIDATE USER AND EXTRACT UID  => $status = ACCEPTED or NOUSER or INVALIDSESSION
	list ($status, $uid) = ValidateUser($mysqli, $USERNAME, $SESSIONID);
   // VERIFY ISOLATE ACCESS PERMISSIONS => $status = ACCEPTED or NOACCESS or NOISOLATE
   if($status=="ACCEPTED"){ list( $status, $ifolder ) = ValidateIsolateAccess($mysqli, $uid, $iid); }

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
		$stmt->bind_param('ssssssssssssssssss', $name, $technology, $ipublic,
                        $country, $region, $city, $zip, $lon, $lat, $lnote,
                        $mdate, $origin, $onote, $pathogenicity, $pnote, $note,
                        $iid, $uid);
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
