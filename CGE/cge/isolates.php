<?php
# This script takes a form input with service and unique folder id and returns 
# the HTML output (results)
include_once('/srv/www/php-lib/cge_std.php'); // Including CGE_std clases and functions
$wwwroot = "http://cge.cbs.dtu.dk/services/";
$htdocs = "/srv/www/htdocs/services/";

function _INPUT($name){
    if ($_SERVER['REQUEST_METHOD'] == 'GET')
        return strip_tags($_GET[$name]);
    if ($_SERVER['REQUEST_METHOD'] == 'POST')
        return strip_tags($_POST[$name]);
}

if (count($_POST)>0)
{
	$uid = preg_replace('/[^0-9\_]/', '', _INPUT("UID"));
	$service = preg_replace('/[^A-Za-z]/', '', _INPUT("SERVICE"));
	$version = preg_replace('/[^0-9\.]/', '', _INPUT("VERSION"));
	if ($version != ''){ # Adding dash to version
      $version = '-'.$version;
	}
	$wwwroot .= $service.$version."/";
	//include $htdocs.$service.$version."/header.html"; # Header file
	# INCLUDE STANDARD HTML HEADER
	$CGE = new CGE; // Load the Class

	# INCLUDE CGE MENU
	# Format is: ServerName, "(Link/Path.html, 'NameOfLink'),(Link/Path.html, 'NameOfLink')"
	$CGE->std_header("", "(".$wwwroot."instructions.php,'Instructions'),(".$wwwroot."output.php,'Output'),(".$wwwroot."abstract.php,'Article abstract')"); // Print the Menu

	if ($uid != "" and $service != ""){
		$fullPath = "/panfs1/cge-servers/".$service."/".$service.$version."/IO/".$uid."/final_output/".$service.".out.gz";
		#echo $fullPath;
		readgzfile( $fullPath );
	}
	
	$CGE->Piwik(15); // Printing Piwik codes!!

	# INCLUDE STANDARD FOOTER
	# First a simple headline like: "Support"
	# Then a list of emails like this: "('Scientific problems','foo','foo@cbs.dtu.dk'),('Technical problems','bar','bar@cbs.dtu.dk')"
	$CGE->standard_foot("Support","('Technical problems','Martin Thomsen','mcft@cbs.dtu.dk')");
   //include $htdocs.$service.$version."/footer.html"; # Footer file
} else {
   #HTML form to submit UID and pick service from list
 	echo "<html><body>";
	echo "<form action='show_result.php' method='POST'>";
	echo "<b>Unique folder ID</b>: <input type='text' name='UID'>";
	echo '<p><b>Select Logo type: </b><select name="SERVICE">';
 	echo ' <option value="CGE">CGE</option>';
 	echo ' <option value="MLST">MLST</option>';
 	echo ' <option value="pMLST">pMLST</option>';
 	echo ' <option value="ResFinder">ResFinder</option>';
 	echo ' <option value="SpeciesFinder">SpeciesFinder</option>';
 	echo ' <option value="TaxonomyFinder">TaxonomyFinder</option>';
 	echo ' <option value="PlasmidFinder">PlasmidFinder</option>';
 	echo ' <option value="snpTree">snpTree</option>';
 	echo ' <option value="Assembler">Assembler</option>';
 	echo ' <option value="VirulenceFinder">VirulenceFinder</option>';
 	echo "</select></p>";
   echo "<b>Version:</b> <input type='text' name='VERSION'><br>";
	echo "<input type='submit' value='Show results'>";
	echo "</form>";
 	echo "</body></html>";
}

?>