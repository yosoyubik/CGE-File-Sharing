<?php
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
include_once('/srv/www/php-lib/cge_std-2.0.php'); // Including CGE_std clases and functions

// FUNCTIONS

////////////////////////  MAIN  /////////////////////////
// CHECK FOR CORRECT INPUTS
if (count($_POST)+count($_GET)>0 and isset($_SESSION['SESSIONID']) and isset($_SESSION['USERNAME'])){ //
   // VALIDATE INPUTS
   $SESSIONID = $_SESSION['SESSIONID'];
   $USERNAME = $_SESSION['USERNAME'];
   $iid = _INPUT('IID');
   //$date = _INPUT('DATE');
   $sid = _INPUT('SID');
   //$service = _INPUT('SERVICE');
   //$version = _INPUT('VERSION');
	if ($iid == ''){ respond("NOIID", '', true); }
	//if ($date == ''){ respond("NODATE", '', true); }
	//if ($sid == ''){ respond("NOSID", '', true); }
	//if ($service == ''){ respond("NOSERVICE", '', true); }
	//if ($version == ''){ respond("NOVERSION", '', true); }
	if (preg_match("/[^A-Za-z0-9\_\-\.\@\,]/", $USERNAME)){ respond("BADUSER", '', true); }
	if (preg_match("/[^A-fa-f0-9]/", $SESSIONID)){ respond("BADSESSION", '', true); }
	if (preg_match("/[^0-9]/", $iid)){ respond("BADIID", '', true); }
	//if (!preg_match("/\d{4}-\d{2}-\d{2}/", $date)){ respond("BADDATE", '', true); }
	if (preg_match("/[^0-9]/", $sid)){ respond("BADSERVICEID", '', true); }
	//if (preg_match("/[^A-Za-z]/", $service)){ respond("BADSERVICE", '', true); }
	//if (preg_match("/[^0-9A-Za-z\.]/", $version)){ respond("BADVERSION", '', true); }
   
   // Process inputs
   //$date = split('-', $date);
   //$year = $date[0];
   //$month = $date[1];
   //$day = $date[2];
   
   // Set global variables
   //$htdocs = "/srv/www/htdocs/services/";
   $wwwroot = "http://cge.cbs.dtu.dk/services/";
   
   // CONNECT TO THE DATABASE
   $mysqli = new mysqli('cge', 'cgeclient', 'www', 'cge');
   // CHECK CONNECTION
   if (mysqli_connect_errno()) { respond("Connect failed: %s\n", mysqli_connect_error(), '', '', true); }
   // CHECK USERNAME AND SESSIONID
   $stmt = $mysqli->prepare("SELECT id, session_id FROM users WHERE usr = ?");
   $stmt->bind_param('s', $USERNAME);
   // EXECUTE AND GET RESULTS
   $stmt->execute();
   $stmt->bind_result($uid, $sesid);
   
   // CHECK IF SESSIONID MATCHES
   $status = 'INVALIDUSER';
   while($stmt->fetch()){
      if ($SESSIONID == $sesid){
      	$status = "ACCEPTED";
      }
   }
   // CLOSE STATEMENT
   $stmt->close();
   
   if($status=="ACCEPTED" AND $sid != null){
      // RETRIEVE FILE PATH FROM DATABASE
      $stmt = $mysqli->prepare("SELECT s.isolate_id, s.service, s.folder 
                                FROM services s 
                                WHERE s.owner = ?
                                AND s.id = ?
                                ;");
      $stmt->bind_param('ss', $uid, $sid);
      // EXECUTE AND GET RESULTS
      $stmt->execute();
      $stmt->bind_result($iid, $service, $service_folder);
      // FETCH RESULTS
      $count = 0;
      while($stmt->fetch()){ $count++; }
      // CLOSE STATEMENT
      $stmt->close();
      $title   = $service;
      $service = split('-', $service);
      $version = $service[1];
      $service = $service[0];
      $wwwroot .= "$service-$version/";
      $service_folder = "/services/$service_folder/outputs/$service.out";
   }
   if($status=="ACCEPTED" and $iid != null){	
      // RETRIEVE FILE PATH FROM DATABASE
      $stmt = $mysqli->prepare("SELECT i.isolate_path 
                                FROM isolates i 
                                WHERE i.owner = ?
                                AND i.id = ?
                                ;");
      $stmt->bind_param('ss', $uid, $iid);
      // EXECUTE AND GET RESULTS
      $stmt->execute();
      $stmt->bind_result($fullpath);
      // FETCH RESULTS
      $count = 0;
      while($stmt->fetch()){ $count++; }
      // CLOSE STATEMENT
      $stmt->close();
   }elseif($status=="ACCEPTED"){
      $status="NOACCESS";
   }
   //CLOSING DATABASE
   $mysqli->close();
   
   if($status=="ACCEPTED" AND $sid != null){
      $fullpath .= $service_folder;
   }elseif($status=="ACCEPTED" and $iid != null){
      $fullpath .= "/logs/pipeline.out";
      $title = 'Pipeline';
   }else{
      $fullpath = null;
   }
   
	// Load the CGE class (title, meta_tags, banner_path, css_paths, js_paths) '' is default
   $CGE = new CGE("$title Results", '', '', '', ''); 

	# INCLUDE CGE MENU
	# Format is: ServerName, "(Link/Path.html, 'NameOfLink'),(Link/Path.html, 'NameOfLink')"
	$CGE->std_header('', "(".$wwwroot."instructions.php,'Instructions'),(".$wwwroot."output.php,'Output'),(".$wwwroot."abstract.php,'Article abstract')"); //"$service-$version Results"
   echo "\n\n<!-- START CONTENT -->\n\n";
   
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
            respond("NOFILE", $msg, true);
         }
      }
   }else{
      // Output does not exist, or user has no access or similar
      $msg = array();
      $msg['FULLPATH'] = $fullpath;
      $msg['MESSAGE'] = "OBS: Either the requested file does not exist, you don't have access to the file or an error occured! If you are the owner of this file, and the file should exist, please contact the cgehelp@cbs.dtu.dk...";
      respond($status, $msg, true);
   }
	
	$CGE->Piwik(15); // Printing Piwik codes!!

	# INCLUDE STANDARD FOOTER
	# First a simple headline like: "Support"
	# Then a list of emails like this: "('Scientific problems','foo','foo@cbs.dtu.dk'),('Technical problems','bar','bar@cbs.dtu.dk')"
	$CGE->standard_foot("Support","('Technical problems','CGE Support','cgehelp')");
   //include $htdocs.$service.$version."/footer.html"; # Footer file
} else {
   #HTML form to submit UID and pick service from list
// 	echo "<html><body>";
//	echo "<form action='show_result.php' method='POST'>";
//	echo "<b>Unique folder ID</b>: <input type='text' name='UID'>";
//	echo '<p><b>Select Logo type: </b><select name="SERVICE">';
// 	echo ' <option value="CGE">CGE</option>';
// 	echo ' <option value="MLST">MLST</option>';
// 	echo ' <option value="pMLST">pMLST</option>';
// 	echo ' <option value="ResFinder">ResFinder</option>';
// 	echo ' <option value="SpeciesFinder">SpeciesFinder</option>';
// 	echo ' <option value="TaxonomyFinder">TaxonomyFinder</option>';
// 	echo ' <option value="PlasmidFinder">PlasmidFinder</option>';
// 	echo ' <option value="snpTree">snpTree</option>';
// 	echo ' <option value="Assembler">Assembler</option>';
// 	echo ' <option value="VirulenceFinder">VirulenceFinder</option>';
// 	echo "</select></p>";
//   echo "<b>Version:</b> <input type='text' name='VERSION'><br>";
//	echo "<input type='submit' value='Show results'>";
//	echo "</form>";
// 	echo "</body></html>";

 	echo "<html><body>";
 	echo "This area is restricted!";
 	echo "</body></html>";

// Use following URL to see results manually: edit "test" to folder ID, "ResFinder" to Service name, and "1.3" to correct version
// http://cge.cbs.dtu.dk/cge/show_result.php?UID=test&SERVICE=ResFinder&VERSION=1.3
}

?>