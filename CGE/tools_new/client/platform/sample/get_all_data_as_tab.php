<?php
################################################################################
#                             CGE Show Result Page                             #
################################################################################
/* This script is used to access output files which are saved on the
   web-unaccessible servers.
 * It is limited to only accessing output files which follow the CGE pipeline
   file structure.
   This increases the security, so hackers won't be able to access files which
   are not permitted.
 * The script recieves a CGI form with several inputs and returns the
   corresponding HTML service output file.
 */

// IMPORT PHP LIBRARIES
include_once('/srv/www/php-lib/cge_std_tools.php'); // Including CGE_std clases and functions

////////////////////////  FUNCTIONS  /////////////////////////
function downloadOutput( $output ){
   /* // function for force downloading files
   */
   
   // Must be fresh start
   if( headers_sent() )
      die('Headers Sent');

   // Required for some browsers
   if(ini_get('zlib.output_compression'))
      ini_set('zlib.output_compression', 'Off');
   
   // CHECK IF THE output EXISTS
   if($output != ''){
      // Read output to standard out
      header("Pragma: public"); // required
      header("Expires: 0");
      header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
      header("Cache-Control: private",false); // required for certain browsers
      header("Content-Type: application/force-download");
      header("Content-Disposition: attachment; filename=user-result-summary.xls;");
      header("Content-Transfer-Encoding: binary");
      header("Content-Length: ".strlen($output));
      ob_clean();
      flush();
      echo $output;
   }else{
      // Output does not exist, maybe the run failed, or is still running?
      $msg = array();
      $msg['MESSAGE'] = "Error: No resuts was found for your user!";
      respond("NORESULTS", $msg, true);
   }
}

////////////////////////  MAIN  ////////////////////////
// CHECK FOR CORRECT INPUTS
if (isset($_SESSION['SESSIONID']) and isset($_SESSION['USERNAME'])){
	// VALIDATE SESSION
   $SESSIONID = $_SESSION['SESSIONID'];
   $USERNAME = $_SESSION['USERNAME'];
	if (preg_match("/[^A-Za-z0-9\_\-\.\@\,]/", $USERNAME)){ respond("BADUSER", '', true, true, true); }
	if (preg_match("/[^A-fa-f0-9]/", $SESSIONID)){ respond("BADSESSION", '', true, true, true); }
   if (_INPUT('view') == 'resistance'){
      $view = 'resistance';
   }else{
      $view = '';
   }
	// CONNECT TO THE DATABASE
	$mysqli = new mysqli('cge', 'cgeclient', 'www', 'cge');
	// CHECK CONNECTION
	if (mysqli_connect_errno()) { respond("MYSQLERROR", array('MESSAGE' => "Connect failed: %s\n", mysqli_connect_error()), true, true, true); }
   // VALIDATE USER AND EXTRACT UID  => $status = ACCEPTED or NOUSER or INVALIDSESSION
	list ($status, $uid) = ValidateUser($mysqli, $USERNAME, $SESSIONID);
   // CLOSING CONNECTION
   $mysqli->close();
   // Extract Result data
   if($status=="ACCEPTED"){
      $scriptpath = '/home/data1/services/CGEpipeline/CGEpipeline-1.1/AllMetadata2Tab.py';
	  //echo "$scriptpath ".escapeshellarg($USERNAME)." ".escapeshellarg($SESSIONID)." ".escapeshellarg($view);
      $output = shell_exec("$scriptpath ".escapeshellarg($USERNAME)." ".escapeshellarg($SESSIONID)." ".escapeshellarg($view));
      // TRY TO SEND THE OUTPUT TO THE USER
      downloadOutput($output);
   }else{
      $output = '';
      // RESPOND WITH REJECTION
      respond($status, '', '');
   }
}else{
	echo "<html><body>Missing input</body></html>";
}
?>