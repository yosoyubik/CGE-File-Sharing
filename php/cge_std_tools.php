<?php
// This Is a PHP Module containing CGE specific classes and functions for
// Header, DirectoryPath, MenuBar, Section, Footer and JavaPowUpload applet tag.

// HOW TO USE!
//<?php
//
//include_once('CGE_std-2.0.php'); // Including CGE_std clases and functions
//
////                Title    META        BANNER IMG PATH         CSS JS
//$CGE = new CGE('CGE Server','','/images/cge_buttons/banner.gif','',''); //Load the applet
//
//$CGE->Applet(); // Print Applet!!
//
//$CGE->StartForm($config); // Start form (provide path to config file)
//
// ADD SERVICE SPECIFIC FORM STUFF HERE
//
//$CGE->EndForm(); // Print Applet!!
//$CGE->Javascripts(); // Print Applet!!
//$CGE->ReadTypeCheck(); // Print Applet!!
//$CGE->Piwik(); // Print Applet!!
//
//
//? >

// SET PHP ERROR LOGGING
error_reporting(E_ALL);
ini_set('error_log','/srv/www/cgeweb_maintenance/platform_error_log');
ini_set('log_errors','true');



// START USER SESSION
session_start();

class CGE {

   public $user_logged_in = FALSE;

   function __construct($title='CGE Server', $meta='', $banner='/images/cge_buttons/banner.gif', $css='', $js='') { //Our construct function

      // This will print when we define our class
		$imageroot = "/images/cge_buttons/";
      // SET DEFAULT ARGUMENTS
      if($banner==''){$banner=$imageroot.'banner.gif';}

		if (isset($_REQUEST['_SESSION'])) die("Get lost Muppet!");

		# if utf-8 fails as encoding, use ISO 8859-1  (or utf-8)
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="en">
<head>
<meta name="keywords" content="CGE Server">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<!--<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">-->
<meta http-equiv="Content-Style-Type" content="text/css">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<?php // Adding service specific meta tags and server name
   if($meta!=''){echo "$meta\n";}
	echo "<title>$title</title>\n";
?>


<!-- STYLESHEETS -->
<link href="/css/bootstrap.min.css" rel="stylesheet" media="screen">
<link href="/css/bootstrap-responsive.css" rel="stylesheet">
<link href="/css/login.css" rel="styleSheet" type="text/css" media="all">
<link href="/css/main.css" rel="styleSheet" type="text/css" media="all">
<style type="text/css">/* special */
   body{
      font-size: large;
   }
   .badge{
      font-size: large;
      margin:    1.4px;
      padding:   3px 5px 6px;
      vertical-align: middle;
   }
   a.badge:link      {color: #FFF}
   a.badge:visited   {color: #FFF}
   a.badge:active    {color: #BBB}
   a.badge:hover     {color: #BBB}
   .greyback{
      background-color: #f8f8f8;
      /*border: 1px solid black;*/
      margin: 10px 0px 0px 0px;
      padding: 0px 15px 0px 5px;
      min-height: 90%;
   }
   .normpad{
      padding: 0px 5px 0px 5px;
   }
</style>
<?php // Add service specific stylesheets
if($css != ''){
   $sheets = explode(",", $css);
   for($i=0;$i<count($sheets);$i++){
      echo "<link href='$sheets[$i]' rel='StyleSheet' type='text/css' media='all'>";
   }
}
?>
<!--[if IE 8]>
<style type="text/css">
	/* css for IE 8 */
	body{
		text-align: center;
	}
	div.body {
		text-align: left;
	}
</style>
<![endif]-->

<!-- JAVASCRIPTS -->
<?php // Add service specific javascripts
if($js != ''){
   $scripts = explode(",", $js);
   for($i=0;$i<count($scripts);$i++){
      echo "<script type='text/javascript' src='$scripts[$i]'></script>\n";
   }
}
?>
<script src='/js/jquery.js' type="text/javascript"></script>
<script type="text/javascript">
	<?php
      /* CHECKING IF THE USER IS LOGGED IN */
      // CHECK FOR SESSION DETAILS
		if(isset($_SESSION['SESSIONID']) and isset($_SESSION['USERNAME'])){
			// VALIDATING SESSION DETAILS
         $USERNAME = $_SESSION['USERNAME'];
         $SESSIONID = $_SESSION['SESSIONID'];
         if (!preg_match("/[^A-Za-z0-9\_\-\.\@\,]/", $USERNAME) AND !preg_match("/[^A-fa-f0-9]/", $SESSIONID)){
				// CONNECT TO THE DATABASE
            $user= $_ENV['DB_ENV_MYSQL_USER'];
            $passwd = $_ENV['DB_ENV_MYSQL_PASSWORD'];
            $db = $_ENV['DB_ENV_MYSQL_DATABASE'];
            $port= $_ENV['DB_PORT_3306_TCP_PORT'];
            $host= $_ENV['DB_PORT_'. $port .'_TCP_ADDR'];
            $mysqli = new mysqli($host, $user, $passwd, $db, $port);
            // CHECK CONNECTION
				if (!mysqli_connect_errno()) {
					// CHECK USERNAME AND SESSIONID
					$stmt = $mysqli->prepare("SELECT session_id, last_login, ip, status FROM users WHERE usr = ?");
					$stmt->bind_param('s', $USERNAME);
					// EXECUTE AND GET RESULTS
               $stmt->execute();
					$stmt->bind_result($sid, $sll, $sip, $stat);
					// CHECK IF SESSIONID AND IP MATCHES, AND TIME SINCE LAST LOGIN IS LESS THAN 2 HOURS
               $DATE = date('d-m-Y H:i:s');
               $IP = $_SERVER['REMOTE_ADDR'];
               $status = 'REJECTED';
               while($stmt->fetch()){
                  if ($SESSIONID == $sid){
                     if ($IP == $sip){
                        if ((strtotime($DATE)-strtotime($sll)) <= 7200){
                           $status = "ACCEPTED";
                        }else{
                           $status = "TIMEOUT";
                        }
                     }else{
                        $status = "NEWIP";
                     }
                  }
               }
               // CLOSE STATEMENT
               $stmt->close();

               if($status=="ACCEPTED"){
                  // UPDATE LAST LOGIN AND IP
                  $stmt = $mysqli->prepare(" UPDATE users".
                                           " SET last_login = ?,".
                                           "     ip = ?".
                                           " WHERE usr = ?".
                                           " ;");
                  $stmt->bind_param('sss', $DATE, $IP, $USERNAME);
                  $stmt->execute();
                  $stmt->close();

                  // SET LOGGED IN INDICATION VARIABLE
                  $this->user_logged_in = TRUE;

                  // SHOW USER MENU
						echo "	$(document).ready(function(){ if(!window.stop_default_login){ showLoggedin('$USERNAME', '$SESSIONID'); } });";

					}else{
						// SESSION ERROR - destroy session
                  $stmt = $mysqli->prepare(" UPDATE users".
                                           " SET session_id = ''".
                                           " WHERE usr = ?".
                                           " ;");
						$stmt->bind_param('s', $USERNAME);
						$stmt->execute();
						$stmt->close();

						// DELETING THE SESSION COOKIE
						if (ini_get("session.use_cookies")) {
							$params = session_get_cookie_params();
							setcookie(session_name(), '', time() - 1,
							$params["path"], $params["domain"],
							$params["secure"], $params["httponly"]
							);
						}
						// ENDING THE PHP SESSION
						session_destroy();

                  // SHOW LOG IN BOX
						echo "	$(document).ready(function(){ if(!window.stop_default_login){ showLogin(); } });
						console.log('error1');
						";
					}
					// CLOSE DATABASE
					$mysqli->close();

   			}else{ // SQL CONNECTION FAILED
					echo "	$(document).ready(function(){ if(!window.stop_default_login){ showLogin(); } });
					console.log('error2');";
				}
			}else{ // INVALID SESSION DETAILS
				echo "	$(document).ready(function(){ if(!window.stop_default_login){ showLogin(); } });
				console.log('error3');";
         }
		}else{ // Not logged in
			echo "	$(document).ready(function(){ if(!window.stop_default_login){ showLogin(); } });
			console.log('error4');";
		};
	?>
</script>
</head>
<body><div class='body'>
<!-- <img src='< ?php echo $imageroot?>banner.gif' alt='CGE Banner'> -->
   <div id="banner" style="height:60px;width:1024px;background-image:url('<?php echo $banner;?>');">
      <div id="login"></div>
   </div>
<?php
	}

   function std_header($servername, $buttons, $defaults=TRUE) { //
      // $buttons should have the following format:
      // (link/path1/,'Link Name1'),(link/path2/,'Link Name2'),...

		# ADDING HOME AND SERVICES TO BUTTONS
		if($defaults){
			if($buttons != ""){
				$buttons = "(./,'Home'),(/services/,'Services'),". $buttons;
			}else{
				$buttons = "(./,'Home'),(/services/,'Services')";
			}
		}

      // split buttons by '),(' and removing first and last parentheses
      $temp = explode("),(",substr($buttons,1,-1));
      $count_buttons = count($temp);
      $width = round(1000/$count_buttons);
      $last_width = 1000 - $width * ($count_buttons - 1);
		$currentPath = $_SERVER['SCRIPT_NAME'];
		$currentDir = substr($currentPath, 0, strrpos($currentPath, '/')+1); #getting the path to the current file
		if (substr($currentPath, -9, 9) === 'index.php'){ $currentPath = substr($currentPath, 0, -9); } #removing index.php

		// Create buttons
      echo "<br>\n",
           "<table class='mytab'>\n",
           "   <tr>\n",
           "      <td class='left'></td>\n";
      for ($i=0 ; $i<$count_buttons ; $i++){
         // Removing the last ' from the string and splitting the path and name into the array[$i]
         $button = explode(",'",substr($temp[$i], 0, -1));
         $selected = "";
			$buttonPath = $button[0];#substr($button[0], strrpos($button[0], '/'));
			if (substr($buttonPath, -9, 9) === 'index.php'){ $buttonPath = substr($buttonPath, 0, -9); } #removing index.php
			if ($buttonPath === "./"){ $buttonPath = $currentDir; } #changing ./ to current dir
			if (strpos($buttonPath, '/') === false and $currentDir !== '/'){ $buttonPath = $currentDir.$buttonPath; } #adding current dir to internal files
			if ($currentPath === ''){ $currentPath = '/';}
			//echo "<!-- $currentPath == $buttonPath -->\n";
         if ($currentPath == $buttonPath){ // checking whether it is the current page (To highlight it!)
            $selected = " class='myselected'";
			}
         if ($i<$count_buttons-1){
            echo "      <td $selected width='$width' align='center' onclick='window.location = &quot;", $button[0],"&quot;'><span class='button'>", $button[1], "</span></td>\n";
         }else{
            echo "      <td $selected width='$last_width' align='center' onclick='window.location = &quot;", $button[0], "&quot;'><span class='button'>", $button[1], "</span></td>\n";
         }
      }
      echo "      <td class='right'></td>\n",
           "   </tr>\n",
           "</table>\n";

		#ADD NEWS NOTE For all pages
		//echo "<div style='color:red;font-size:16;padding:20px 0px;'>NEWS: Dear all CGE service users, ";
		//$this->showhide("news");
		//echo "<br>\n",
		//		"<div id='news' class='hide'>\n",
		//		"Welcome to the new CGE pages.<br>\n",
		//		"We are glad to present to you the new and improved server pages.<br>\n",
		//		"Apart from the clear visual changes, here is a short list of some of the new changes:\n",
		//		"<ul>\n",
		//		"	<li style='color:red;font-size:16;'>Gzipped files are accepted as upload items, which reduces the upload time.</li>\n",
		//		"	<li style='color:red;font-size:16;'>The applet now compresses all files on the fly, reducing the upload time.</li>\n",
		//		"	<li style='color:red;font-size:16;'>There is no longer an upload button, when ready to upload and submit simply click the submit button, and the script will handle the rest.</li>\n",
		//		"</ul><br>\n",
		//		"Best regards,<br>\n",
		//		"The CGE group\n",
		//		"</div>\n",
		//		"</div>\n";
		//echo "<br>\n";

      # Server Maintenance Warning  Show after mm/dd/yyyy h:mmAM and untill mm/dd/yyyy h:mmAM
		if (time() > strtotime("28/11/2015 8:00AM") && time() < strtotime("4/11/2015 11:59PM")){
   		echo "<div style='color:red;font-size:16;padding:20px 0px;'>WARNING:<br>\n".
              "The CGE services are offline 4 December 2015 from 1PM to 5PM (DK time GMT+1), due to maintenance.<br>\n".
              "All jobs which are active or queued in this period will be lost.<br>\n".
              "<br>\nSorry for the inconvenience,<br>\nThe CGE administration\n".
              "</div>";
      }

      if($servername != ''){echo "<h1>$servername</h1>";}
      echo "\n\n<!-- START CONTENT -->\n\n";
   }


   function Applet($compress = TRUE) { // Applet function
      global $JavaPowUploadVersion;
?>
<applet code      = "com.elementit.JavaPowUpload.Manager"
        archive   = "/cge/JavaPowUpload/<?php echo $JavaPowUploadVersion;?>/lib/JavaPowUpload.jar,
                      /cge/JavaPowUpload/<?php echo $JavaPowUploadVersion;?>/lib/skinlf.jar,
                      /cge/JavaPowUpload/<?php echo $JavaPowUploadVersion;?>/lib/commons-httpclient.jar,
                      /cge/JavaPowUpload/<?php echo $JavaPowUploadVersion;?>/lib/commons-compress.jar"
        width     = "512"
        height    = "200"
        name      = "JavaPowUpload"
        id        = "JavaPowUpload"
        mayscript = "true"
        alt       = "JavaPowUpload by www.element-it.com"
        VIEWASTEXT
        >
   <!-- Java Plug-In Options -->
   <param name = "Common.SerialNumber" value = "0071222625276113617717111621491871110176">
   <param name = "progressbar"         value = "true">
   <param name = "boxmessage"          value = "Loading JavaPowUpload Applet ...">

   <!-- Limitation on file upload -->
   <param name = "Common.Filter.MaxFileSize"      value = "5000000000"><!-- Bytes -->
   <param name = "Common.Filter.MaxFileCount"     value = "5">
   <param name = "Common.Filter.MaxFileTotalSize" value = "15000000000"><!-- Bytes -->
   <!-- File filters  -->
   <param name = "Common.FileFilter.Multilocus"    value = "true">
   <param name = "Common.FileFilter.SelectionMode" value = "FILES">
   <!--Enable upload mode -->
   <param name = "Common.UploadMode"                            value = "true">
   <param name = "Common.UseLiveConnect"                        value = "true">
   <param name = "Common.RetryWhenConnectionLost"               value = "true">
   <param name = "Common.RetryWhenConnectionLost.CheckInterval" value = "1">
   <param name = "Common.RetryWhenConnectionLost.CheckTimeout"  value = "600">
   <param name = "Common.Language.AutoDetect"                   value = "true">
   <param name = "Common.InternationalFile"                     value = "/cge/JavaPowUpload/<?php echo $JavaPowUploadVersion;?>/Extra/Localization/Language_en.xml">
   <!--Set url to file processing script -->
   <!-- THIS IS SET LATER (BOTTOM OF PAGE) -->

   <!--Enable compression before upload feature -->
   <param name = "Upload.HttpUpload.ChunkedUpload.Enabled"      value = "true">
   <param name = "Upload.HttpUpload.ChunkedUpload.ChunkSize"    value = "-1">
   <param name = "Upload.HttpUpload.ChunkedUpload.MaxChunkSize" value = "2097152">
<?php
if ($compress){
   echo "\n   <!--Enable compression before upload feature -->".
   "   <param name = 'Upload.Compress.Enabled'           value = 'true'>\n".
   "   <param name = 'Upload.Compress.ArchiveFileName'   value = '#UNIQUEID#'> <!-- Name of archive #UNIQUEID# -->\n".
   "   <param name = 'Upload.Compress.Format'            value = 'ZIP'> <!-- Valid values: ZIP, JAR, TAR, TAR-GZIP, TAR-BZIP2 -->\n".
   "   <param name = 'Upload.Compress.Level='            value = 'DEFAULT'>\n".
   "   <param name = 'Upload.Compress.MaxFilesInArchive' value = ''>\n".
   "   <param name = 'Upload.Compress.IgnoreFileTypes'   value = ''>\n";
}
?>

   <!-- UI SETTINGS -->
   <param name = "Common.DetailsArea.Visible"        value = "false">

   <!-- UI THEME-->
   <param name = "Common.SkinLF.ThemepackURL"        value = "/cge/JavaPowUpload/<?php echo $JavaPowUploadVersion;?>/lib/themepack.zip">

   <!-- BUTTON LOCATION -->
   <param name = "Common.ProgressArea.DownloadButton.PlaceOnTopArea" value = "false">
   <!-- BUTTON SHOW/HIDE -->
   <param name = "Common.TopArea.DisplayDetailsButton.Visible"       value = "false">
   <param name = "Common.ProgressArea.DownloadButton.Visible"        value = "false">
   <!-- BUTTON SIZES -->
   <param name = "Common.TopArea.BrowseButton.Width"  value = "150">
   <param name = "Common.TopArea.BrowseButton.Height" value = "50">
   <param name = "Upload.TopArea.RemoveButton.Width"  value = "100">
   <param name = "Upload.TopArea.RemoveButton.Height" value = "50">
   <param name = "Upload.TopArea.ClearButton.Width"   value = "150">
   <param name = "Upload.TopArea.ClearButton.Height"  value = "50">

   <!-- This text will be shown if applet not working or Java not installed-->
   <span style = "border:        1px solid #F00;
                  display:       block;
                  padding:       5px;
                  margin-top:    10px;
                  margin-bottom: 10px;
                  text-align:    left;
                  background:    #FDF2F2;
                  color:         #000000;
                  ">
      You should <b>enable applets</b> running at browser and to have the
      <b>Java</b> (JRE) version &gt;= 1.5.<br>If applet is not displaying
      properly, please check
      <a target = "_blank"
         href   = "http://java.com/en/download/help/testvm.xml"
         title  = "Check Java applets"
         >additional configurations</a>.<br>
   </span>
</applet>
<div style="margin: auto; display: inline-block;">Problems with Java? <a href="/services/Java_help.php">Check our Java help</a> </div>
<?php

   }

   function StartForm($config) { // This function prints the start of the Form

?>
<!-- Form Begins -->
<form enctype = "multipart/form-data"
      action  = "/cgi-bin/webface.fcgi"
      method  = "POST"
      id      = "theform"
      >
<!-- Config Filepath -->
<input type   = 'hidden'
       name   = 'configfile'
       value  = '<?php echo $config ?>'
       >
<!-- UserID + SessionID + UserIP -->
<input type   = 'hidden'
       name   = 'userID'
       value  = 'Anonymous'
       >
<input type   = 'hidden'
       name   = 'usersession'
       value  = ''
       >
<input type   = "hidden"
       name   = "userip"
       value  = "<?php echo $_SERVER['REMOTE_ADDR'] ?>"
       >

<!-- Uploadpath -->
<input type  = "hidden"
       name  = "uploadpath"
       value = "notset"
       >
<br><br>
<?php

// Hvis der er problemer med lokale ipadresser, så brug denne?
//	function getIP() {
//$ip;
//if (getenv("HTTP_CLIENT_IP"))
//$ip = getenv("HTTP_CLIENT_IP");
//else if(getenv("HTTP_X_FORWARDED_FOR"))
//$ip = getenv("HTTP_X_FORWARDED_FOR");
//else if(getenv("REMOTE_ADDR"))
//$ip = getenv("REMOTE_ADDR");
//else
//$ip = "UNKNOWN";
//return $ip;
//
//}
// evt. brug følgende hvis der stadig er problemer... --> $ip = GetHostByName($ip);

   }

   function EndForm() { // This function prints the end of the Form

?>
<!-- Adding the submit and reset buttons -->
<input type    = "button"
       value   = "Submit"
       OnClick = "JavaPowUpload.clickDownload();"
       >
<input type    = "reset"
       value   = "Clear fields"
       >
</form>
<!-- The Form Ends -->
<?php

   }

   function Javascripts($service, $version) { // This function prints the Javascripts for the applet
      global $JavaPowUploadVersion;

?>
<script type="text/javascript">
   // function executed on applet initiation
   function JavaPowUpload_onAppletInit(){
      // PHPUPLOADPATH VARIABLE FOR JavaPowUpload APPLET
      var phpUploadPath = "/cge/JavaPowUpload/<?php echo $JavaPowUploadVersion;?>/Extra/FileProcessingScripts/PHP/resumableandlargeupload.php?dirName1=";
      // alert("phpUploadPath: "+phpUploadPath); //DEBUG

      // PATH VARIABLE FOR INPUT/OUTPUT DIRECTORY
      var service = "<?php Print($service); ?>";
      var version = "<?php Print($version); ?>";
      var storagePath = "/srv/www/htdocs/services/"+service+"-"+version+"/tmp/";
      // alert("storagePath: "+storagePath); //DEBUG

      // RUNROOT UNIQUE ID VARIABLE FOR INPUT/OUTPUT DIRECTORY
      var myDate = new Date();
      wDay = myDate.getDay() + 1;
      mDay = myDate.getDate();
      year = myDate.getFullYear();
      month = myDate.getMonth() + 1;
      hours = myDate.getHours();
      mins = myDate.getMinutes();
      millisecs = myDate.getMilliseconds();
      randNum = (Math.floor(Math.random()*1000000))+1;

      today = wDay + "_" + mDay + "_" + month + "_" + year + "_" + hours + mins + "_" + millisecs + "_";
      dirName = today + randNum;
      // alert("dirName: "+dirName); //DEBUG

      // SET PATH
      var JavaPowUpload = document.getElementById('JavaPowUpload');
      // alert("JavaPowUpload: "+JavaPowUpload); //DEBUG
      JavaPowUpload.setParam("Upload.UploadUrl", phpUploadPath + storagePath + dirName);
      // alert("UploadUrl: "+JavaPowUpload.getParam("Upload.UploadUrl") ); //DEBUG

      // ASSIGNING UID TO FORM.RUNROOT.VALUE
      document.forms["theform"].uploadpath.value = storagePath + dirName;

      window.err = false;
   }

   function JavaPowUpload_onAddFiles(files){
      /* This script check that the only valid characters which does not cause
       * problems are used.
       */
		for(var i=0; i < files.length; i++){
         if(files[i].getFileName().match(/[\s]/g)){
   			alert("A space was found in the input file "+ files[i].getFileName() +".\nPlease remove!");
            JavaPowUpload.removeFileById(files[i].getId());
         }
         else if(files[i].getFileName().match(/[^\w\-\.]/g)){
   			alert("One or more unwanted characters were found in the input file "+ files[i].getFileName() +".\nPlease, only use following characters in your file names: a-z A-Z 0-9 _ - .");
            JavaPowUpload.removeFileById(files[i].getId());
         }
      }
	}

   // function executed after the upload of a file
   function JavaPowUpload_onUploadFinish(){
   // Submitting the Form
      if(!window.err){
         document.forms["theform"].submit();
      }
   }

   function messageAlert(errmsg){ // Alert message function to avoid firefox freezing due to window/applet focus issues
      alert(errmsg);
   }
</script>
<?php

   }

   function ReadTypeCheck() { // This function prints the Javascripts for checking if the correct number of files were uploaded

?>
<script type="text/javascript">
   // function executed before uploade of the files begin
   function JavaPowUpload_onUploadStart(){
      var errmsg = "";
		window.err = false;
      //Checking if the correct number of files were uploaded
      var list = JavaPowUpload.getFiles();
      var files_uploaded = 0; // COUNTING FILES IN LIST
      for(i=list.size()-1;i>=0;i--){if(list.get(i).isFile()){files_uploaded++;}}
      switch(document.forms["theform"].seqReadType.value){
         case "Paired_End_Reads": // 2 files needed
         case "Solid":
            if(files_uploaded != 2){
               window.err = true;
               errmsg = files_uploaded+" files were uploaded, where 2 was required for the chosen technology";
            };
            break;
         case "S_Mate_Paired_Reads": // 4 files needed
            if(files_uploaded != 4){
               window.err = true;
               errmsg = files_uploaded+" files were uploaded, where 4 was required for the chosen technology";
            };
            break;
         case "Ion_Torrent": // 4 files needed
            if(files_uploaded < 1){
               window.err = true;
               errmsg = files_uploaded+" files were uploaded, where 4 was required for the chosen technology";
            };
            break;
         default: // Only 1 file needed
            if(files_uploaded != 1){
               window.err = true;
               errmsg = files_uploaded+" files were uploaded, where only 1 was allowed for the chosen technology";
            };
            //here should call the JavaPowUpload_onFileProgress uploadItem = list.get(0) JavaPowUpload_onFileProgress(uploadItem)
      }
      if(window.err){
         JavaPowUpload.clickStop();
         messageAlert(errmsg+"\nNothing was submitted!");
      };
   }
</script>

<?php

   }


   function Piwik($id = 2) { // This function prints the Javascripts for checking if the correct number of files were uploaded

?>
<!-- Piwik -->
<script type="text/javascript">
    try {
      var piwikTracker = Piwik.getTracker("/piwik/piwik.php", <?php echo $id; ?>);
      piwikTracker.trackPageView();
      piwikTracker.enableLinkTracking();
   }catch( err ){}
</script>
<!-- FOR PEOPLE WITHOUT JAVASCRIPT ENABLED! -->
<noscript>
   <img src = "/piwik/piwik.php?idsite=2" style = "border:0px;" alt = "">
</noscript>
<!-- End Piwik Tracking Code -->
<?php

   }

   function standard_foot ($headline,$persons) {
      # Usage:
      # standard_foot("GETTING HELP","('Technical assistance','Frank Foo','frank-foo@foo.net'),('Scientific assistance','Dr. Fu','DrFu@foo.net')")
      global $service, $version;

		// split buttons by '),(' and removing first and last parentheses
      $temp = explode("),(",substr($persons,1,-1));
		$barlength = 1000;
		$titlewidth = strlen($headline)*14;
      $count_buttons = count($temp);
      $width = round(($barlength - $titlewidth) / $count_buttons);
      $last_width = ($barlength - $titlewidth) - $width * ($count_buttons - 1);
      $currentPath = "";

		// Create buttons
      echo "<br>\n<br>\n",
           "<table class='mytab' style='clear:both;'>\n",
           "   <tr>\n",
           "      <td class='left'></td>\n",
           "      <td class='none' width='$titlewidth' align='left'><span class='button'>$headline</span></td>\n";
      for ($i=0 ; $i<$count_buttons ; $i++){
         // Removing the ' from the string ends and splitting the email and name into the array[$i]
         $button = explode("','",substr($temp[$i], 1, -1));

			# Setting standard topic of email
			$subject = $button[0];
			if (!is_array($service) && $service != ""){
				$subject .= " with $service-$version";
			}

			# Printing the Email buttons
			$sendemail = $this->js_email($button[2], $button[1], $subject);
         if ($i<$count_buttons-1){
            echo "      <td width='$width' align='center' onclick='$sendemail'><span class='button'>", $button[0], "</span></td>\n";
         }else{
            echo "      <td width='$last_width' align='center' onclick='$sendemail'><span class='button'>", $button[0], "</span></td>\n";
         }
      }
      echo "      <td class='right'></td>\n",
           "   </tr>\n",
           "</table>\n<br>\n",
           "<div class='nav'>\n<center>\n",
		     "Copyright DTU 2011 / All rights reserved<br>\nCenter for Genomic Epidemiology, DTU, Kemitorvet, Building 204, 2800 Kgs. Lyngby, Denmark<br>\nFunded by: The Danish Council for Strategic Research<br>\n";
      # We print the last modification date for the document that are displayed
      $filename = $_SERVER["SCRIPT_FILENAME"];
      if (file_exists($filename)) print "Last modified ".gmdate('F j, Y H:i:s', filemtime($filename))." GMT<br>";
		echo "</center>\n",
           "         </div>\n",
           "      </div>\n",
           "      <script type='text/javascript' src='/js/main.js'></script>\n",
           "      <script type='text/javascript' src='/tools_new/client/platform/scripts/login.js'></script>\n",
           "      <script type='text/javascript' src='/js/bootstrap.min.js'></script>\n",
           "      <script type='text/javascript' src='/js/jQuery.xml2json.js'></script>\n",
           "      <script type='text/javascript' src='/piwik/piwik.js'></script>\n", # Piwik
           "   </body>\n</html>\n";
   }

   # Calling this function produces a well disguised javascript
   # mailto aka 'document.location.href'.
   # Made to prevent harvesting email adresses from our web pages.
   # Name is optional, $email will be used if not given.
   function js_email($email, $name = "", $subject = "") {
      $pos = strpos($email, "@");
      if ($pos === false) {
         $login = $email;
         $site = "cbs.dtu.dk"; }
      else {
         $login = substr($email, 0, $pos);
         $site = substr($email, $pos+1); }
      $site = str_replace('.dk', '%2Edk', $site);
      $site = str_replace('.com', '%2Ecom', $site);
      $site = str_replace('.', "&#&quot;+(10+11+12+13)+&quot;;", $site);
      $body = 'Dear '.$name.',\n';
      $pos = rand(1,180);
      return "contact(&quot;$login&#&quot;+(".(1+$pos)."+2+3-$pos)+&quot;4;$site&quot;, &quot;$subject&quot;, &quot;$body&quot;);";
	}

	# Calling this function produces a well disguised mailto.
   # Made to prevent harvesting email adresses from our web pages.
   # Name is optional, $email will be used if not given.
   function protect_email($email, $name = "") {
      $pos = strpos($email, "@");
      if ($pos === false) {
         $login = $email;
         $site = "cbs.dtu.dk"; }
      else {
         $login = substr($email, 0, $pos);
         $site = substr($email, $pos+1); }
      $site = str_replace('.dk', '%2Edk', $site);
      $site = str_replace('.com', '%2Edk', $site);
      $site = str_replace('.', '&#46;', $site);
      $pos = rand(1,180);
      $txt = "<script type=\"text/javascript\">";
      $txt .= "document.write('<a href=\"mai' + 'lto:' + '$login&#' + (";
      $txt .= sprintf("%d-%d", 64+$pos, $pos) . ") + ';$site\">";
      if ($name == "") {
         $pos = rand(1,180);
         $txt .= "$login&#' + (" . sprintf("%d-%d", 64+$pos, $pos);
         $txt .= ") + ';$site</a>')</script>"; }
      else {
         $txt .= "$name</a>')</script>"; }
      return $txt;
   }

	function showhide($element, $show=TRUE) { // This function prints a show/hide button
		if($show){$value="Show";}else{$value="Hide";};
		echo "<input type='button' class='showhide' onclick='showhide(this, &quot;$element&quot;);' value='$value'>";
   }

	function switcher() { // This function prints a switch button
		$str = ''; $value = 1;
		foreach(func_get_args() as $element){
			if($value>0){$value-=1;}else{$value=$element[1];}
			$str .= '[&quot;'.$element[0].'&quot;,&quot;'.$element[1].'&quot;],';
		}
		if($str != ''){$str = substr_replace($str ,"",-1);} //Removing trailing ,
		echo "<input type='button' class='showhide' onclick='switcher(this, $str);' value='$value'>";
   }

   function AddOption($v, $t){
      /* NAME:       addOption - Prints HTML option tag to the screen
       * Example:    addOption('value', 'Option1');
       * <option value="value">Option1</option>
       */
      echo "   <option value='$v'>$t</option>\n";
   }

   function FetchOptionFromFile($filepath){
      /* NAME:       FetchOptionFromFile - Fetches data from tab separated text
       *                                   file and prints all the lines as
       *                                   option tags
       * Example:    FetchOptionFromFile('/path/to/options.txt');
       * <option value="Column1">Column2</option>
       */
      if (($f = fopen($filepath, 'r')) !== FALSE) {
         $schemes = array();
         while (($data = fgetcsv($f, 0,"\t")) !== FALSE) {
            if(substr($data[0],0,1) == '#'){ continue; }
            elseif(sizeof($data) == 2){ $schemes[$data[0]] = $data[1]; }
         }
         fclose($f);
         # SORTING AND PRINTING SCHEME OPTIONS
         asort($schemes);
         foreach($schemes as $scheme=>$name){ $this->AddOption($scheme, $name); }
      }else{
         echo "   <option value=''>None</option>\n";
      }
   }

   function user_is_logged_in(){
      /* Check if user is logged in
      * USAGE:
            if(user_is_logged_in()){
               // WRITE PAGE
            }
      */
/*
      $old_sessionid = $_SESSION['SESSIONID'];
      $test = "<script type='text/javascript'>
                  console.log('%s');
               </script>";

      echo sprintf($test, $old_sessionid);
*/
      if(!$this->user_logged_in){
         // PRINT PLEASE LOGIN SCREEN

         // ADD LOGIN MESSAGE TO THE USER
         echo "<br><h1>Please Login (top right corner)</h1>";
         // ADD PERMANENTLY ASSOCIATE ACCOUNTS OPTION - SO THE USER DON'T HAVE TO LOGIN IN THE FUTURE
         //$user_id = $_SESSION['METAJSON']['UserCreatedBy'];
         // TODO
         // ADD CONTINUE BUTTON (ON CLICK RELOAD PAGE WITGH NEW URL ATTRIBUTE ACTION 'CONTINUE')
         echo "<script type='text/javascript'>
                  function continue_pipeline(){
                     // Check if user is logged in
                     if (window.user_logged_in == true){
                        location.reload(true);
                     }else{
                        alert('Please login');
                     }
                  }
               </script>";
         echo "<input type='button' onclick='continue_pipeline()' value='Continue'><br>\n";
         return FALSE;
      }else{
         return TRUE;
      }
   }

} //END CLASS




//// FUNCTIONS ////

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
   if(is_array($arr)){
      foreach ($arr as $key => $value){
         $value = $value == NULL ? 'NULL' :$value; // converting NULL to 'NULL'
         $type = gettype($value);
         if($type == 'array'){
            $arr_child = $arr[$key];
            $xml_child = $xml->addChild($key);
            arr2xml($arr_child, $xml_child);
         }elseif($type=="boolean" or $type=="integer" or $type=="double" or $type=="string"){
            $xml->addChild($key, $value);
         }
      }
   }elseif(is_string($arr)){
      $xml->addChild('MESSAGE', $arr);
   }
}

function respond($status, $DATA, $EXIT=false, $template=false, $human=false){
   /* Tools Paths */
   //global $CGE;
   $domain = '';
   $toolspath = '/tools_new/client/platform';
   if($template == true){
      // Load the CGE Class :: ARGUMENTS=($title, $meta, $banner,$css,$js)
      $CGE = new CGE('CGE Server','<base href="'.$domain.'">','/images/cge_buttons/banner.gif','','');
      // CGE MENU
      $CGE->std_header("CGE Server", "($toolspath/user_settings.php,'User Home'),(/services/,'Services'),($toolspath/isolate_manager.php,'Sample Overview'),($toolspath/map.php,'Map')"); // Print the Menu
   }
   if($human == true){
      /* PRINTING THE HUMAN RESPONSE */
      // REQUIRE THE USER TO LOGIN
      if( isset($CGE) ){
         if($CGE->user_is_logged_in()){
            // Write Message
            echo "<br><table style='margin:auto;'><tr><th>Server&nbsp;Response</th><th>Message</th></tr><tr><td style='text-align:center;'>$status</td><td>". str_replace("\n", "<br>", $DATA['MESSAGE']) ."</td></tr></table>";
         }
      }else{
         echo "<br><table style='margin:auto;'><tr><th>Server&nbsp;Response</th><th>Message</th></tr><tr><td style='text-align:center;'>$status</td><td>". str_replace("\n", "<br>", $DATA['MESSAGE']) ."</td></tr></table>";
      }
   }else{
      /* PRINTING THE XML RESPONSE */
      $xml = new SimpleXMLElement('<XML/>');
      $xml->addChild('STATUS', $status);
      arr2xml($DATA, $xml);
      print $xml->asXML();
   }
   if($template == true){
      // TRACK USER TRAFIC
      $CGE->Piwik(14);
      # STANDARD FOOTER
      $CGE->standard_foot("Support","('Technical problems','CGE Support','cgehelp')");
   }
   if($EXIT == true) exit();
}



function ValidateUser($mysqli, $USERNAME, $SESSIONID){
   /* Validate user and extract user id.
      list ($status, $uid) = ValidateUser($mysqli, $USERNAME);
    */
   // CHECK USERNAME AND SESSIONID
	$stmt = $mysqli->prepare("SELECT id, session_id FROM users WHERE usr = ?");
	$stmt->bind_param('s', $USERNAME);
	// EXECUTE AND GET RESULTS
	$stmt->execute();
	$stmt->bind_result($uid, $sid);
	// CHECK IF SESSIONID MATCHES
	$status = 'NOUSER';
	while($stmt->fetch()){
      if ($SESSIONID == $sid){
      	$status = "ACCEPTED";
         break;
      }else{
         $status = 'INVALIDSESSION';
         break;
      }
   }
	// CLOSE STATEMENT
	$stmt->close();
   return array( $status, $uid );
}

function ValidateIsolateAccess($mysqli, $uid, $iid){
   /* Validate that the user is permitted access to the isolate.
      list( $status, $ifolder ) = ValidateIsolateAccess($mysqli, $uid, $iid);
    */
   // CHECK USERNAME AND SESSIONID
   $stmt = $mysqli->prepare("SELECT i.owner, i.public, i.isolate_path
                             FROM isolates i
                             WHERE i.id = ?
                             ;");
	$stmt->bind_param('s', $iid);
	// EXECUTE AND GET RESULTS
	$stmt->execute();
	$stmt->bind_result($owner, $public, $ifolder);
	// CHECK IF SESSIONID MATCHES
	$status = 'NOISOLATE';
	while($stmt->fetch()){
      if ($public == true or $owner == $uid){
      	$status = "ACCEPTED";
      }else{
      	$status = "NOACCESS";
      }
   }
	// CLOSE STATEMENT
	$stmt->close();
   return array( $status, $ifolder );
}

function ValidateServiceAccess($mysqli, $uid, $sid){
   /* Validate that the user is permitted access to the services.
      list( $status, $sfolder, $service ) = ValidateServiceAccess($mysqli, $uid, $sid);
    */
   // CHECK USERNAME AND SESSIONID
   $stmt = $mysqli->prepare("SELECT i.owner, i.isolate_path, s.folder, s.service
                             FROM isolates i
                             INNER JOIN services s ON s.isolate_id = i.id
                             WHERE s.id = ?
                             ;");
	$stmt->bind_param('s', $sid);
	// EXECUTE AND GET RESULTS
	$stmt->execute();
	$stmt->bind_result($owner, $ifolder, $sfolder, $service);
	// CHECK IF SESSIONID MATCHES
	$status = 'NOSERVICE';
	while($stmt->fetch()){
      if ($owner == $uid){
      	$status = "ACCEPTED";
      }else{
      	$status = "NOACCESS";
      }
   }
	// CLOSE STATEMENT
	$stmt->close();
   // Process Results
   if(strlen($ifolder) > 3 and strlen($sfolder) > 3){
         $sfolder = "$ifolder/services/$sfolder";
   }else{
      $sfolder = null;
   }
   return array( $status, $sfolder, explode('-', $service) );
}

?>
