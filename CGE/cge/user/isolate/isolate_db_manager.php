<?php
################################################################################
#                             CGE Isolate Manager                              #
################################################################################
// This is the Isolate Manager script which:
//   -> Checks and authorize the user validity in the mySQL database
//   -> Check all arguments are valid
//   -> Execute the requested action
//   -> Returns the status of the execution and data if requested

// Set Error Logging Environment
error_reporting(E_ALL);
ini_set('error_log','/srv/www/htdocs/services/error_log');
ini_set('log_errors','true');

echo('hello');
// FUNCTIONS
function _INPUT($name){
   /* NAME:   _INPUT - Query Handler
    * DESC:   Handles requested input fields from either POST or GET and returns
    *         the tag-stripped input.
    * AUTHOR: Martin Thomsen
    * USAGE:  $form_text = _INPUT('form_text_name');
    */
   if ($_SERVER['REQUEST_METHOD'] == 'POST' and isset($_POST[$name]))
      return strip_tags($_POST[$name]);
   //elseif ($_SERVER['REQUEST_METHOD'] == 'GET' and isset($_GET[$name]))
   //   return strip_tags($_GET[$name]);
   else return NULL;
}

function arr2xml(&$arr, &$xml){
   /* NAME:   arr2xml - Associative Array to XML Converter
    * DESC:   Walking through the array and its children to copy/convert it to
    *         xml format.
    * AUTHOR: Martin Thomsen
    * USAGE:  $xml = new SimpleXMLElement('<XML/>');
    *         arr2xml($DATA, $xml);
    */
   foreach ($arr as $key => $value){
      $value = $value == NULL ? 'NULL' :$value; // converting NULL to 'NULL'
      $type = gettype($value);
      if($type == 'array'){ // Descending down the tree's sub branches
         arr2xml($arr[$key], $xml->addChild($key));
      }elseif($type=="boolean" or $type=="integer" or $type=="double" or $type=="string"){ 
         $xml->addChild($key, $value); // Adding a leaf to the tree
      }
   }
}

function respond($status, $DATA){
   /* NAME:   respond - XML Response Handler
    * DESC:   Creates and prints a XML document with a STATUS tag and a
    *         associative data array converted into XML format.
    * AUTHOR: Martin Thomsen
    * USAGE:  respond($status, $DATA);
    */
   $xml = new SimpleXMLElement('<XML/>');   // CREATE XML OBJECT
   $xml->addChild('STATUS', $status);       // ADD STATUS TO XML
   if($DATA != ''){ arr2xml($DATA, $xml); } // ADD DATA ARRAY TO XML
   exit($xml->asXML());                     // PRINT XML AND EXIT
}

function AuthUser($mysqli, $USERNAME, $SESSIONID){
   /* NAME:   AuthUser - mySQL database valid user check
    * DESC:   Check for username and sessionid validity, that the user IP is the
    *         same as at login and that the time since login is less than 2
    *         hours.
    * AUTHOR: Martin Thomsen
    * USAGE:  DBuserOK($mysqli, $USERNAME, $SESSIONID);
    * NOTE:   $USERNAME + $SESSIONID should be sanitized before calling DBuserOK.
    * DEPEND: This function is dependend on the User class.
    */
   // PREPARE AND EXECUTE STATEMENT
   $stmt = $mysqli->stmt_init();
   $stmt->prepare("SELECT session_id, last_login, ip
                           FROM login
                           WHERE user = ?
                           ;");
   $stmt->bind_param('s', $USERNAME);
   $USERNAME = "Martin";
   $stmt->execute();
   // GET RESULT AND AUTHENTICATE USER DETAILS
   $result = $stmt->get_result(); //$stmt->bind_result($sid, $ll, $ip);
   $count = 0;
   while($user = $result->fetch_object('User')){ $count++; }
   if ($count == 1){
      if (!$user->auth($SESSIONID)) respond('REJECTED', '');
   }else{ respond('BADDATABASE', ''); }
   $stmt->close();
}
//mysqli_stmt::get_result() in /srv/www/htdocs/cge/user/isolate_db_manager.php on line 82
// CANT USE get_result() unless, has to use mysqlnd in PHP5.3 which requires a PHP upgrade (sp¿rg John om dette er en god ide?)
// Else stick with $stmt->bind_result() and $stmt->fetch(), and read up on PHP classes


// $result->fetch_array(MYSQLI_NUM))   // Numerical array
// $result->fetch_array(MYSQLI_ASSOC)) // Associative array

function GetData($mysqli){
   /* NAME:   AuthUser - mySQL database valid user check
    * DESC:   Check for username and sessionid validity, that the user IP is the
    *         same as at login and that the time since login is less than 2
    *         hours.
    * AUTHOR: Martin Thomsen
    * USAGE:  DBuserOK($mysqli, $USERNAME, $SESSIONID);
    * NOTE:   $USERNAME + $SESSIONID should be sanitized before calling DBuserOK.
    * DEPEND: This function is dependend on the User class.
    */
   // PREPARE AND EXECUTE STATEMENT
   $stmt = $mysqli->prepare("SELECT i.id iid, i.name name,
                                    m.country country, m.region region, m.city city, m.zip zip, m.date sdate, m.origin origin, m.pathogenicity pat,
                                    r.id rid, r.run_id run_id, r.service service, r.date rdate,
                                    a.id aid, a.n50 n50,
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
   $stmt->execute();
   // GET RESULTS
   $result = $stmt->get_result();
   $DATA = array();
   while ($isolateObj = $result->fetch_object('Isolate')) {
      $isolate = $isolateObj->get(); // Retrieves DB entry as an isolate object
      if (isset($DATA[$isolate['id']])){ // Add the run to the existing isolate in DATA
         $DATA[$isolate['id']]['runs'][key($isolate['runs'])] = reset($isolate['runs']);
      }else{ // Add the isolate + run to DATA
         $DATA[$isolate['id']] = $isolate;
      }
   }
   // CLOSE STATEMENT AND RETURN THE RETRIEVED DATA
   $stmt->close();
   return $DATA;
}


// CLASSES
class User {
   /* NAME:   User - User Profile
    * DESC:   This class keeps track of the user data retrieved from the mySQL
    *         database, which is needed for the user authentication, and
    *         provides 3 public functions which confirms the athentication.
    * AUTHOR: Martin Thomsen
    * USAGE:  while ($user = $result->fetch_object('User')) {
    *            DO STUFF
    *         }
    */
   public $session_id;
   public $last_login;
   public $ip;
   public function auth($SESSIONID) { return sessionOK($SESSIONID) and ipOK(); print dateOK(); }
   private function sessionOK($SESSIONID) { return $SESSIONID == $session_id; }
   private function ipOK() { return $_SERVER['REMOTE_ADDR'] == $ip; }
   private function dateOK() { return date('d/m-Y H:i:s')->diff(DateTime($ll)); }
}

class Isolate {
   /* NAME:   User - User Profile
    * DESC:   This class keeps track of the user data retrieved from the mySQL
    *         database, which is needed for the user authentication, and
    *         provides 3 public functions which confirms the athentication.
    * AUTHOR: Martin Thomsen
    * USAGE:  while ($data = $result->fetch_object('Data')) {
    *            $data->getIsolate()
    *         }
    */
   public $iid;
   public $name;
   public $country;
   public $region;
   public $city;
   public $zip;
   public $sdate;
   public $origin;
   public $pat;
   public $rid;
   public $uid;
   public $service;
   public $rdate;
   public $aid;
   public $n50;
   public $m_id;
   public $pm_id;
   public $rf_id;
   public function get() {
      // SET ISOLATE DATA
      $isolate = array(
         'id' => $iid,
         'name' => $name,
         'metadata' => array(
            'country' => $country,
            'region' => $region,
            'city' => $city,
            'zip' => $zip,
            'date' => $sdate,
            'origin' => $origin,
            'pathogenicity' => $pat,
         ),
         'assembly' => array(
            'id' => $aid,
            'n50' => $n50
         ),
         'runs' => array(
            "$rid" => array(
               'uid' => $uid,
               'service' => $service,
               'date' => $rdate,
               'servicedata' => array()
            )
         )
      );
      // ADD SERVICE DATA
      if($m_id){
         $isolate['runs'][$rid]['servicedata']['m_id'] = $m_id; //"run$rid"
      }elseif($pm_id){
         $isolate['runs'][$rid]['servicedata']['pm_id'] = $pm_id;
      }elseif($rf_id){
         $isolate['runs'][$rid]['servicedata']['rf_id'] = $rf_id;
      }
      return $isolate;
   }
}


// MAIN
if (count($_POST)>0) {# or count($_GET)>0 // There is inputs
   // GET INPUTS
   $USERNAME = _INPUT('USERNAME');
   $SESSIONID = _INPUT('SESSIONID');
   $ACTION = _INPUT('ACTION');
   
	// Checking if username is invalid
	if (preg_match("/[^A-Za-z0-9\,\_\-\.\@]/", $USERNAME) or strlen($USERNAME) < 2){
		respond('BADUSER', '');
	}
	// Checking if session id is invalid
	if (preg_match("/[^A-Za-z0-9]/", $SESSIONID) or strlen($SESSIONID) < 40){
		respond('BADSESSIONID', '');
	}
   // Checking if action is invalid
	if (preg_match("/[^a-z]/", $ACTION) or strlen($ACTION) != 3){
		respond('BADACTION', '');
	}
	
	// CONNECT TO THE DATABASE
	$mysqli = new mysqli('cge', 'cgeclient', 'www', 'cge');

	// CHECK CONNECTION
	if (mysqli_connect_errno()) {
		respond("Connect failed: ".mysqli_connect_error()."\n", '');
	}
   
   // Authorize Usage of Database
   AuthUser($mysqli, $USERNAME, $SESSIONID);
   
   if ($ACTION == 'dat'){
      // GET DATA FROM DATABASE
      $DATA = GetData($mysqli);
      // CHECK IF ANY DATA WAS FOUND
      if (count($DATA) > 0){ respond('ACCEPTED', $DATA); }
      else{ respond('NODATA', ''); }
   
	}elseif($ACTION == 'del'){
      
	}elseif($ACTION == 'upd'){
      
   }else{
         respond('UNKNOWNACTION', '');
   }
      
      
      //$stmt = $mysqli->prepare("SELECT i.id iid, i.name name,
      //                                 m.country country, m.region region, m.city city, m.zip zip, m.date sdate, m.origin origin, m.pathogenicity pat,
      //                                 r.id rid, r.run_id run_id, r.service service, r.date rdate,
      //                                 a.id aid, a.n50 n50,
      //                                 mlst.id m_id,
      //                                 pmlst.id pm_id,
      //                                 rf.id rf_id
      //                        FROM isolates i 
      //                        LEFT OUTER JOIN meta m ON i.id = m.isolate_id
      //                        LEFT OUTER JOIN runs r ON i.id = r.isolate_id
      //                        LEFT OUTER JOIN assembly a ON i.id = a.isolate_id
      //                        LEFT OUTER JOIN mlst ON r.id = mlst.runid
      //                        LEFT OUTER JOIN pmlst ON r.id = pmlst.runid
      //                        LEFT OUTER JOIN resfinder rf ON r.id = rf.runid
      //                        WHERE r.user_id = ?
      //                        ;");
      //$stmt->bind_param('s', $USERNAME);
      ////$USERNAME = preg_replace('/[^A-Za-z0-9\_\-\.\@\,]/', '', _INPUT("USERNAME"));
      //// EXECUTE PREPARED STATEMENT
      //$stmt->execute();
      //// BIND RESULT VARIABLES
      //$result = $stmt->get_result();
      //
      //// FETCH RESULTS
      //$DATA = array();
      //$count = 0;
      //while ($isolateObj = $result->fetch_object('Isolate')) {
      //   $isolate = $isolateObj->get();
      //   if (isset($DATA[$isolate['id']])){
      //      $DATA[$isolate['id']]['runs'][key($isolate['runs'])] = reset($isolate['runs']);
      //   }else{
      //      $DATA[$isolate['id']] = $isolate;
      //   }
      //   $count++;
      //}
      //$stmt->bind_result($r_id, $r_name,
      //                  $r_country, $r_region, $r_city, $r_zip, $r_mdate, $r_origin, $r_pathogenicity,
      //                  $r_rid, $r_uid, $r_service, $r_rdate,
      //                  $r_aid, $r_n50,
      //                  $r_m_id,
      //                  $r_pm_id,
      //                  $r_rf_id);
      // FETCH RESULTS
      //$count = 0;
      //$DATA = array();
      //while($stmt->fetch()){
      //   $isolat = array(
      //      'id' => $r_id,
      //      'name' => $r_name,
      //      'metadata' => array(
      //         'country' => $r_country,
      //         'region' => $r_region,
      //         'city' => $r_city,
      //         'zip' => $r_zip,
      //         'date' => $r_mdate,
      //         'origin' => $r_origin,
      //         'pathogenicity' => $r_pathogenicity,
      //      ),
      //      'assembly' => array(
      //         'id' => $r_aid,
      //         'n50' => $r_n50
      //      ),
      //      'runs' => array(
      //         "run$r_rid" => array(
      //            'uid' => $r_uid,
      //            'service' => $r_service,
      //            'date' => $r_rdate,
      //            'servicedata' => array()
      //         )
      //      )
      //   );
      //   // ADD SERVICE DATA
      //   if($r_m_id){
      //      $isolat['runs']["run$r_rid"]['servicedata']['m_id'] = $r_m_id;
      //   }elseif($r_pm_id){
      //      $isolat['runs']["run$r_rid"]['servicedata']['pm_id'] = $r_pm_id;
      //   }elseif($r_rf_id){
      //      $isolat['runs']["run$r_rid"]['servicedata']['rf_id'] = $r_rf_id;
      //   }
      //   if (isset($DATA["I$r_id"])){
      //      $DATA["I$r_id"]['runs']["run$r_rid"] = $isolat['runs']["run$r_rid"];
      //   }else{
      //      $DATA["I$r_id"] = $isolat; //"<I$count><name>$r_name</name><city>$r_city,$r_country</city></I$count>";
      //   }
      //   //$r_id, $r_name, $r_country, $r_region, $r_city, $r_zip, $r_date, $r_origin, $r_pathogenicity, $r_service, $r_date, $r_n50, $r_m_id, $r_pm_id, $r_rf_id
      //   $count++;
      //}
      

   
	//CLOSING DATABASE
	$mysqli->close();
} else {
   session_start(); // START SESSION
   //phpinfo();
	//echo "<html><head><title>Unauthorized Usage!</title></head><body>Get Lost!!!</body></html>";
	echo "<html><head><title>Unauthorized Usage!</title></head><body><form action='/cge/user/isolate_db_manager.php' method='post'><input name='USERNAME' value='".$_SESSION['USERNAME']."'><input name='SESSIONID' value='".$_SESSION['SESSIONID']."'><input name='ACTION' value='dat'><input type='submit' value='submit'></form></body></html>";
}
?>
