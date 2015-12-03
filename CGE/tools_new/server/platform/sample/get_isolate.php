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
      $stmt = $mysqli->prepare("SELECT i.sample_name, i.date, i.sequencing_platform, i.sequencing_type, i.pre_assembled, i.public, i.shared, file_names,
                                       m.country, m.region, m.city, m.zip_code, m.longitude, m.latitude, m.location_note, m.collection_date, m.isolation_source, m.source_note, m.pathogenic, m.pathogenicity_note, m.notes, m.organism, m.strain, m.subtype, m.collected_by
                                FROM isolates i 
                                LEFT OUTER JOIN meta m ON m.id = i.id
                                WHERE i.id = ?
                                AND i.removed = false
                                ;");
      $stmt->bind_param('s', $iid);
   	// EXECUTE AND GET RESULTS
      $stmt->execute();
      $stmt->bind_result($name, $idate, $technology, $sequencing_type, $pre_assembled, $ipublic, $shared, $file_names,
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
         if(!isset($file_names)){ $file_names = ''; }
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
            'files' => implode(", ",array_map(function($x) {return basename($x);}, explode(", ",$file_names))), # keep only basename
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
