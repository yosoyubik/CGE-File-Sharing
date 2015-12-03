<?php #! /usr/bin/php5 -q
################################################################################
#                       CGE SERVICE - User Overview                            #
################################################################################

$service = 'Userpages';
$version = '1.0';

include_once('/srv/www/php-lib/cge_std.php'); // Including CGE_std clases and functions
$CGE = new CGE; // Load the Class

function runLink($service, $version, $runid, $name){
	$str = "<tr><td valign='middle' height='5'><form action='/cge/show_result.php' method='POST'>";
	$str .= " <input type='hidden' name='UID' value='$runid'>";
	$str .= " <input type='hidden' name='SERVICE' value='$service'>";
   $str .= " <input type='hidden' name='VERSION' value='$version'>";
	$str .= " <input type='submit' value='$name'>";
	$str .= "</form></td></tr>\n";
   return $str;
}

$serviceRoot = "/srv/www/htdocs/services/".$service."-".$version."/"; #SERVICE ROOT

# CGE MENU
# Format is: ServerName, "(Link/Path.html, 'NameOfLink'),(Link/Path.html, 'NameOfLink')"
$CGE->std_header("User Overview", "(./,'User Home'),(/services/,'Services')", FALSE); // Print the Menu
?>
<!-- START CONTENT -->
<style type="text/css">
	 table, tr, td {
		margin: 0px;
		padding: 0px;
		text-align: center;
		vertical-align: middle;
		position:relative;
	 }
</style>
<div id='userinfo' style='width:1024px;float:left;'>
<!-- USER INFO -->

</div>
<div id='runs' style='width:1024px;float:left;'>
<!-- RUN INFO -->
<table><tr>
<?php
#RETRIEVING USER DATA FROM DATABASE
$last_login = time();
$services = array();
if(isset($_SESSION['USERNAME'])){
	// CONNECT TO THE DATABASE
	$mysqli = new mysqli('cge', 'cgeclient', 'www', 'cge');
	if (!mysqli_connect_errno()) {
		//CHECK FOR CORRECT USERNAME AND PASSWORD
		$stmt = $mysqli->prepare("SELECT service, run_id, date FROM runs WHERE user_id = ?");
		$stmt->bind_param('s', $USERNAME);
		$USERNAME = $_SESSION['USERNAME'];
		//EXECUTE PREPARED STATEMENT
		$stmt->execute();
		// BIND RESULT VARIABLES
		$stmt->bind_result($service, $run_id, $date);
		// FETCH VALUES
		$i=1;
		while($stmt->fetch()){
			$tmp = split('-',$service);
			$curService = $tmp[0];
			$curVersion = $tmp[1];
			$name = "Run ".$i." (".$date.")";
			if(!isset($services[$curService])){$services[$curService] = array();};
			$services[$curService][] = array($curVersion,$run_id,$name);
			$i++;
		}
		if (preg_match("/(\d{2})\/(\d{2})\-(\d{4}) (\d{2})\:(\d{2})\:(\d{2})/", $last_login, $regs)){
		   $last_login = mktime($regs[4],$regs[5],$regs[6],$regs[2],$regs[1],$regs[3]);
		}
		// CLOSE STATEMENT
		$stmt->close();
		// CLOSE DATABASE
		$mysqli->close();
	}
}


//#$filepath = "/srv/www/htdocs/services/user.dat";
//$filepath = "/panfs1/cge-servers/RunDatabase.dat";
//if (($f = fopen($filepath, "r")) !== FALSE) {
//	$i = 0;
//	while (($data = fgetcsv($f, 0,"\t")) !== FALSE) {
//		if(substr($data[0], 0, 1) != "#"){
//			#Service	runID	userID	IP	Location	Date	TechType	runName
//			$service = split('-',trim($data[0]));
//			$version = $service[1];
//			$service = $service[0];
//			$runid = trim($data[1]);
//			$userID = trim($data[2]);
//			if(sizeof($data)>=8){
//				$name = trim($data[7]);
//			}else{
//				$name = "Run".$i." ".trim($data[5]);
//			};
//			if (isset($_SESSION['USERNAME'])){
//				if ($userID === $_SESSION['USERNAME']){
//					if(!isset($services[$service])){$services[$service] = array();};
//					$services[$service][] = array($version,$runid,$name);
//				};
//			};
//			$i++;
//		};
//	};
//	fclose($f);
//};


//var_dump($services);
$i=1;
foreach ($services as $service => $value) {
	 if($i>5){echo"</tr><tr>";$i=1;}; # max 5 Services per line
    echo "<td style='width:200px;vertical-align:top;'><table style='margin-bottom: 20px;'><tr><td style='border-bottom: 1px dashed #000;text-align:center;font-weight:bold;font-size:20;'>$service</td></tr><tr height='5'><td>&nbsp;</td></tr>\n";
	 foreach ($value as $run) { echo runLink($service, $run[0], $run[1], $run[2]); }
	 echo "</table></td>";
	 $i++;
}
?>
</tr></table>
</div><td style="vertical-align:top;"></td>

<?php

$CGE->Piwik(15); // Printing Piwik codes!!

# STANDARD FOOTER
# First a simple headline like: "Support"
# Then a list of emails like this: "('Scientific problems','foo','foo@cbs.dtu.dk'),('Technical problems','bar','bar@cbs.dtu.dk')"
$CGE->standard_foot("Support","('Technical problems','Martin Thomsen','mcft@cbs.dtu.dk')");
?>