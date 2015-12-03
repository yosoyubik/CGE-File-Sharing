<?php
################################################################################
#                                  CGE LOGIN                                   #
################################################################################
// This is the Login script which:
//   -> Checks and authorize the recieved user login against the SQL db
//   -> Starts a session
//   -> Returns the status session id and username
error_reporting(E_ALL);
ini_set('error_log','/srv/www/htdocs/services/error_log');
ini_set('log_errors','true');

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

function respond($status, $DATA){
   /* PRINTING THE XML FORMATTED DATA */
   $xml = new SimpleXMLElement('<XML/>');
   $xml->addChild('STATUS', $status);
   arr2xml($DATA, $xml);
   print $xml->asXML();
}


if (count($_POST)>0) # or count($_GET)>0
{
	$status = '';

	//Checking if username is invalid
	if (preg_match("/[^A-Za-z0-9\,\_\-\.\@]/", _INPUT("USERNAME")) or strlen(_INPUT("USERNAME")) < 2){
		respond("BADUSER", '');
		exit();
	}
	//Checking if sessionid is invalid
	if (preg_match("/[^A-Za-z0-9]/", _INPUT("SESSIONID")) or strlen(_INPUT("SESSIONID")) < 40){
		respond("BADSESSIONID", '');
		exit();
	}
	
	// CONNECT TO THE DATABASE
	$mysqli = new mysqli('cge', 'cgeclient', 'www', 'cge');

	// CHECK CONNECTION
	if (mysqli_connect_errno()) {
		respond("Connect failed: %s\n", mysqli_connect_error(), '', '');
		exit();
	}

	//CHECK FOR CORRECT USERNAME AND SESSIONID             ### TODO ###
	
	//GET DATA FROM DATABASE
	$stmt = $mysqli->prepare("SELECT i.id, i.name,
                                    m.country, m.region, m.city, m.zip, m.date, m.origin, m.pathogenicity,
                                    r.id rid, r.run_id, r.service, r.date,
                                    a.id, a.n50,
                                    mlst.id m_id,
                                    pmlst.id pm_id,
                                    rf.id rf_id
                             FROM isolates i 
                             LEFT OUTER JOIN meta m ON i.id = m.isolate_id
                             LEFT OUTER JOIN runs r ON i.id = r.isolate_id
                             LEFT OUTER JOIN assembly a ON i.id = a.isolate_id
                             LEFT OUTER JOIN mlst ON r.id = mlst.runid
                             LEFT OUTER JOIN pmlst ON r.id = pmlst.runid
                             LEFT OUTER JOIN resfinder rf ON r.id = rf.runid
                             WHERE r.user_id = ?
                             ;");
	$stmt->bind_param('s', $USERNAME);

	$USERNAME = preg_replace('/[^A-Za-z0-9\_\-\.\@\,]/', '', _INPUT("USERNAME"));

	//EXECUTE PREPARED STATEMENT
	$stmt->execute();

	// BIND RESULT VARIABLES
	$stmt->bind_result($r_id, $r_name,
                      $r_country, $r_region, $r_city, $r_zip, $r_mdate, $r_origin, $r_pathogenicity,
                      $r_rid, $r_uid, $r_service, $r_rdate,
                      $r_aid, $r_n50,
                      $r_m_id,
                      $r_pm_id,
                      $r_rf_id);
	
	// FETCH RESULTS
	$count = 0;
	$DATA = array();
	while($stmt->fetch()){
      $isolat = array(
         'id' => $r_id,
         'name' => $r_name,
         'metadata' => array(
            'country' => $r_country,
            'region' => $r_region,
            'city' => $r_city,
            'zip' => $r_zip,
            'date' => $r_mdate,
            'origin' => $r_origin,
            'pathogenicity' => $r_pathogenicity,
         ),
         'assembly' => array(
            'id' => $r_aid,
            'n50' => $r_n50
         ),
         'runs' => array(
            "run$r_rid" => array(
               'uid' => $r_uid,
               'service' => $r_service,
               'date' => $r_rdate,
               'servicedata' => array()
            )
         )
      );
      
      // ADD SERVICE DATA
      if($r_m_id){
         $isolat['runs']["run$r_rid"]['servicedata']['m_id'] = $r_m_id;
      }elseif($r_pm_id){
         $isolat['runs']["run$r_rid"]['servicedata']['pm_id'] = $r_pm_id;
      }elseif($r_rf_id){
         $isolat['runs']["run$r_rid"]['servicedata']['rf_id'] = $r_rf_id;
      }
      
      if (isset($DATA["I$r_id"])){
         $DATA["I$r_id"]['runs']["run$r_rid"] = $isolat['runs']["run$r_rid"];
      }else{
   		$DATA["I$r_id"] = $isolat; //"<I$count><name>$r_name</name><city>$r_city,$r_country</city></I$count>";
      }
      //$r_id, $r_name, $r_country, $r_region, $r_city, $r_zip, $r_date, $r_origin, $r_pathogenicity, $r_service, $r_date, $r_n50, $r_m_id, $r_pm_id, $r_rf_id
		$count++;
	}

	if ($count >= 1){
		// RETURN XML WITH $DATA
		$status = "ACCEPTED";
		respond($status, $DATA);
	}else{
		// RETURN XML EXCEPTION ERROR  isolates.id, 
		respond('NODATA', "SELECT isolates.name, meta.city, meta.country FROM isolates inner join runs on isolates.id = runs.isolate_id inner join meta on isolates.id = meta.isolate_id where runs.user_id = ?");
	}

	// CLOSE STATEMENT
	$stmt->close();
	
	//CLOSING DATABASE
	$mysqli->close();
} else {
	//echo "<html><head><title>Unauthorized Usage!</title></head><body>Get Lost!!!</body></html>";
	echo "<html><head><title>Unauthorized Usage!</title></head><body><form action='/cge/user/getmetadata.php' method='post'><input name='USERNAME' value='Annonymous'><input name='SESSIONID' value='hgasiu1g2hgk1g2f3ku1f2jh3fjkg1f23h12jhg3'><input type='submit' value='submit'></form></body></html>";
}
?>
