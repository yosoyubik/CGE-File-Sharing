<?php #! /usr/bin/php5 -q
################################################################################
#                             CGE Isolate MANAGER                              #
################################################################################
/* This is the user manager, where the user can create, see, edit and delete
   his/her user details:
 *  'create'      -> Allows a new users to create a profile
 *  'edit'        -> Allows the users to edit and delete their profile
 *  'show'        -> Allows the users to see their profile details
 *  'pend_create' -> Waiting page while the user activates the profile
 *  'pend_delete' -> Waiting page while the user confirms the profile deletion
 *  'pend_delete' -> Waiting page while the user confirms the profile deletion
 */

$domain = 'https://cge.cbs.dtu.dk';
$serviceRoot = "/srv/www/htdocs/services/";

# STANDARD CBS PAGE TEMPLATES, always include this file
include_once('/srv/www/php-lib/cge_std-2.0.php'); // Including CGE_std clases and functions
// Load the CGE class (title, meta_tags, banner_path, css_paths, js_paths) '' is default
$CGE = new CGE('Isolate Overview', '<base href="'.$domain.'" target="_blank">', '', '/cge/user/isolate/css/isolate_manager.css', '/cge/user/isolate/js/isolate_manager.js');

# CGE MENU
# Format is: ServerName, "(Link/Path.html, 'NameOfLink'),(Link/Path.html, 'NameOfLink')"
$CGE->std_header("Isolate Overview", "(/cge/user/login/user_manager.php?action=show,'User Home'),(/services/,'Services'),(/services/cge/batch.php,'Batch Upload'),(/services/cge/map.php,'MapViewer')", FALSE); // Print the Menu

//function runLink($service, $version, $runid, $name){
//	$str = "<tr><td valign='middle' height='5'><form action='../show_result.php' method='POST'>";
//	$str .= " <input type='hidden' name='UID' value='$runid'>";
//	$str .= " <input type='hidden' name='SERVICE' value='$service'>";
//   $str .= " <input type='hidden' name='VERSION' value='$version'>";
//	$str .= " <input type='submit' value='$name'>";
//	$str .= "</form></td></tr>\n";
//   return $str;
//}

$ACTION = _INPUT("action");
if ($ACTION == "edit"){
	// SHOW EDITING FORM
   $iid = _INPUT("iid");
   ?><!-- START OF CONTENT -->
   <div id='edit'>
	   <h1>Edit Isolate</h1> <button onclick="delete_isolate(<?php echo $iid; ?>);">Delete isolate</button>
	   <form name='profile'>
	   	<p>
	   		<input type='hidden' name='iid'>
	   		Isolate name: <input type='text' id='name' name='name'><br>
	   		Description: <textarea name='note'></textarea><br>
	   		Sampling Date: <input type='text' name='mdate' onchange='check_date(this.value, "date_check");'> <span id='date_check' style='font-weight: bold;'></span><br>
            Sequencing Technology:
            <select style="width:300px;" name="technology">
               <option value="Assembled_Genome">Assembled Genome/Contigs*</option>
               <option value="454">454 - single end reads</option>
               <option value="454_Paired_End_Reads">454 - paired end reads</option>
               <option value="Illumina">Illumina - single end reads</option>
               <option value="Paired_End_Reads">Illumina - paired end reads</option>
               <option value="Ion_Torrent">Ion Torrent</option>
               <option value="Solid">SOLiD - single end reads</option>
               <option value="S_Paired_End_Reads">SOLiD - paired end reads</option>
               <option value="S_Mate_Paired_Reads">SOLiD - mate pair reads</option>
               <!--<option value="PAC_Bio">PacBio</option>-->
            </select><br>
	   		Public Available? <input type='checkbox' value='checked' name='ipublic'><br>
	   	</p>
	   	<p>
	   		Country: <input type='text' name='country' onchange='check_name(this.value, "country_check");'> <span id='country_check' style='font-weight: bold;'></span><br>
	   		Region: <input type='text' name='region'><br>
	   		City: <input type='text' name='city' onchange='check_name(this.value, "city_check");'> <span id='city_check' style='font-weight: bold;'></span><br>
	   		Zip code: <input type='text' name='zip'><br>
	   		Longitude: <input type='text' name='lon' onchange='check_float(this.value, "lon_check");'> <span id='lon_check' style='font-weight: bold;'></span><br>
	   		Latitude: <input type='text' name='lat' onchange='check_float(this.value, "lat_check");'> <span id='lat_check' style='font-weight: bold;'></span><br>
            Location note: <input type='text' name='lnote'><br>
	   	</p>
	   	<p>
	   		Origin:
               <select name="origin" style="width:80px;">
				      <option value="Human">Human</option>
				      <option value="Animal">Animal</option>
				      <option value="Food">Food</option>
				      <option value="Water">Water</option>
				      <option value="Other">Other</option>
			      </select><br>
	   		Origin note: <input type='text' name='onote'><br>
	   		Pathogenicity:
               <select name="pathogenicity" style="width:80px;">
				      <option value="yes">Yes</option>
				      <option value="no">No</option>
				      <option value="unknown">Unknown</option>
			      </select><br>
	   		Pathogenicity note: <input type='text' name='pnote'><br>
	   	</p>
         <input type='button' value='Save changes' onclick="update_isolate(this.form);"><br>
	   </form>
   </div>
   <script type="text/javascript">
      // Retrieve Isolate details from the database and populate the fields
      populate_isolate_form(get_isolate(<?php echo $iid; ?>));
   </script>
   <!-- END OF CONTENT --><?php

}else{
	// SHOW OVERVIEW TABLE
   ?><!-- START OF CONTENT -->
   <table id='isoview'>
      <tr><th>&nbsp;</th><th>Name</th><th>Date</th><th>Country</th><th>City</th><th>Origin</th><th>Action</th></tr>
   </table>
   <br>
   <a href='/services/CGE/mapviewer.php'>Watch Your Isolates on Google Maps.</a>
   <!-- END OF CONTENT --><?php
   
   if(isset($_SESSION['USERNAME'],$_SESSION['SESSIONID'])){
      // GET USER ISOLATE DATA AND POPULATE ABOVE LIST
      echo "<script type='text/javascript'>".
           "populate_isolate_table(get_isolate_data_all());\n".
           "</script>";
   }
}

$CGE->Piwik(15); // Printing Piwik codes!!

# STANDARD FOOTER
# First a simple headline like: "Support"
# Then a list of emails like this: "('Scientific problems','foo','foo@cbs.dtu.dk'),('Technical problems','bar','bar@cbs.dtu.dk')"
$CGE->standard_foot("Support","('Technical problems','Martin Thomsen','mcft@cbs.dtu.dk')");
?>