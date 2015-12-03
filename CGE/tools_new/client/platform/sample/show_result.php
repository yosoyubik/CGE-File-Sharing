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

////////////////////////  MAIN  /////////////////////////
if ((count($_POST)+count($_GET)>0) and isset($_SESSION['SESSIONID']) and isset($_SESSION['USERNAME'])){
	// VALIDATE SESSION
   $SESSIONID = $_SESSION['SESSIONID'];
   $USERNAME = $_SESSION['USERNAME'];
	if (preg_match("/[^A-Za-z0-9\_\-\.\@\,]/", $USERNAME)){ respond("BADUSER", '', true, true, true); }
	if (preg_match("/[^A-fa-f0-9]/", $SESSIONID)){ respond("BADSESSION", '', true, true, true); }
   // VALIDATE INPUTS
   $iid = _INPUT('IID');
   $sid = _INPUT('SID');
   if (preg_match("/[^0-9]/", $iid)){ respond("BADIID", array('VALUE' => $iid), true, true, true); }
   if (preg_match("/[^0-9]/", $sid)){ respond("BADSID", array('VALUE' => $sid), true, true, true); }
   
   // Set global variables
   $htdocs = "/srv/www/htdocs/services/";
   $wwwroot = "https://cge.cbs.dtu.dk/services/";
   
	// CONNECT TO THE DATABASE
	$mysqli = new mysqli('cge', 'cgeclient', 'www', 'cge');
	// CHECK CONNECTION
	if (mysqli_connect_errno()) { respond("MYSQLERROR", array('MESSAGE' => "Connect failed: %s\n", mysqli_connect_error()), true, true, true); }
   // VALIDATE USER AND EXTRACT UID  => $status = ACCEPTED or NOUSER or INVALIDSESSION
	list ($status, $uid) = ValidateUser($mysqli, $USERNAME, $SESSIONID);
   // VERIFY ISOLATE ACCESS PERMISSIONS => $status = ACCEPTED or NOACCESS or NOISOLATE
   if($status=="ACCEPTED" AND $iid != null AND $iid != ''){ list( $status, $ifolder ) = ValidateIsolateAccess($mysqli, $uid, $iid); }
   // VERIFY SERVICE ACCESS PERMISSIONS => $status = ACCEPTED or NOACCESS or NOSERVICE
   if($status=="ACCEPTED" AND $sid != null AND $sid != ''){ list( $status, $sfolder, $service ) = ValidateServiceAccess($mysqli, $uid, $sid); }
   // CLOSING CONNECTION
   $mysqli->close();
   
   if($status=="ACCEPTED" AND $sid != null AND $sid != ''){
      $fullpath = "$sfolder/outputs/".$service[0].".out";
      $wwwroot .= $service[0]."-".$service[1]."/";
      $title    = join("-", $service);
   }elseif($status=="ACCEPTED" and $iid != null AND $iid != ''){
      $fullpath = "$ifolder/logs/pipeline.out";
      $wwwroot .= "CGEpipeline/";
      $title = 'Pipeline';
   }else{
      $fullpath = null;
      $title = 'CGE';
   }
   
	// Load the CGE class (title, meta_tags, banner_path, css_paths, js_paths) '' is default
   $CGE = new CGE("$title Results", '', '', '', ''); 

	# INCLUDE CGE MENU
	# Format is: ServerName, "(Link/Path.html, 'NameOfLink'),(Link/Path.html, 'NameOfLink')"
	$CGE->std_header('', "(".$wwwroot."instructions.php,'Instructions'),(".$wwwroot."output.php,'Output'),(".$wwwroot."abstract.php,'Article abstract')"); //"$service-$version Results"

//	// Set path to output file
//   if ($sid != ''){
//      $fullpath = "/home/data1/isolates/$year/$month/$day/$iid/services/".$service."_$sid/outputs/$service.out";
//   }else{
//      $fullpath = "/home/data1/isolates/$year/$month/$day/$iid/logs/pipeline.out";
//   }
   if($fullpath != null){
      // Check if file exists
      if(file_exists($fullpath)){
         // Read File to standard out
         //respond("TEST", array('FULLPATH' => $fullpath), true);
         readfile($fullpath);
      }else{
         // Check if file is gzipped
         $fullpath .= ".gz";
         if(file_exists($fullpath)){
            // Read File to standard out
            //respond("TEST", array('FULLPATH' => $fullpath), true);
            readgzfile($fullpath);
         }else{
            // Output does not exist, maybe the run failed, or is still running?
            $msg = array();
            $msg['FULLPATH'] = $fullpath;
            $msg['MESSAGE'] = "Error: No outputfile was found, either the job has not finished, or the job failed during execution!";
            respond("NOFILE", $msg, false, false, true);
         }
      }
   }else{
      // Output does not exist, or user has no access or similar
      $msg = array();
      $msg['FULLPATH'] = $fullpath;
      $msg['MESSAGE'] = "OBS: Either the requested file does not exist, you don't have access to the file, or an error occured!\nIf you are the owner of this file, and the file should exist, please contact the cgehelp@cbs.dtu.dk...";
      respond($status, $msg, false, false, true);
   }
	
	$CGE->Piwik(15); // Printing Piwik codes!!

	# INCLUDE STANDARD FOOTER
	# First a simple headline like: "Support"
	# Then a list of emails like this: "('Scientific problems','foo','foo@cbs.dtu.dk'),('Technical problems','bar','bar@cbs.dtu.dk')"
	$CGE->standard_foot("Support","('Technical problems','CGE Support','cgehelp')");
   //include $htdocs.$service.$version."/footer.html"; # Footer file
} else {
   respond('NOSESSION', '', true, true, true);
}

?>