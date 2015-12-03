<?php #! /usr/bin/php5 -q
################################################################################
#                                CGE SERVICES                                  #
################################################################################
# CONFIG VARIABLE
$serviceRoot = "/srv/www/htdocs/services/";

# STANDARD CBS PAGE TEMPLATES, always include this file
include_once('/var/www/php/cge_std-2.0.php'); // Including CGE_std clases and functions
// $domain = 'https://cge.cbs.dtu.dk';
$meta_headers = "<base href=/>";
$banner = ''; // '' is default = red CGE banner
$java_scripts = '';
$styles_heets = '';
$CGE = new CGE('CGE Server', $meta_headers, $banner, $styles_heets, $java_scripts);

$CGE->std_header("File-Sharing Upload", "(/all.php,'Upload'),(/files.php,'Download')", FALSE);

if (substr($_SERVER['REMOTE_ADDR'],0,3) == '10.'){$cbsUser = TRUE;}else{$cbsUser = FALSE;}
// phpInfo();
// REQUIRE THE USER TO LOGIN
if($CGE->user_is_logged_in()){
?>
<!-- START INDHOLD -->
<style type="text/css">
   .pad{padding-left: 30px;}
</style>

<!--
<?php
$user= $_ENV['DB_ENV_MYSQL_USER'];
$passwd = $_ENV['DB_ENV_MYSQL_PASSWORD'];
$db = $_ENV['DB_ENV_MYSQL_DATABASE'];
$port= $_ENV['DB_PORT_3306_TCP_PORT'];
$host= $_ENV['DB_PORT_'. $port .'_TCP_ADDR'];

// echo $host. '#'. $user.'#' .$passwd. '#'.$db. '#'.$port;

$mysqli = new mysqli($host, $user, $passwd, $db, $port);
// CHECK CONNECTION
    if (!mysqli_connect_errno()) {
        // echo 'Fine!';
        // CHECK USERNAME AND SESSIONID
        // $stmt = $mysqli->prepare("show tables");
        $stmt = $mysqli->prepare("SELECT email FROM users WHERE usr='cisneros';");
        // $stmt->bind_param('s', 'cisneros');
        // // EXECUTE AND GET RESULTS
        $stmt->execute();
        $stmt->bind_result($test);
        while ($stmt->fetch()) {
            printf("%s\n", $test);
        }
        // echo 'Fine! ' . $test . ' #';
        // // CLOSE DATABASE
        $mysqli->close();

}else {
    echo 'Error!';
}

 ?> -->

<!-- JavaScript Uploader Iframe -->
<div id="iframeWrapper">
<iframe onload="onIFrameLoad();" style="overflow:hidden;" id="myIframe" src="uploader" height="520" width="1024" frameBorder="5" ></iframe>
</div>
<!-- END OF CONTENT -->
<?php
} // END LOGIN REQUIREMENT
$CGE->Piwik(14); // Printing Piwik codes!!

# Displays a standard footer; two parameters:
# First a simple headline like: "Support"
# then a list of emails like this: "('Scientific problems','foo','foo@cbs.dtu.dk'),('Technical problems','bar','bar@cbs.dtu.dk')"
$CGE->standard_foot("Support","('Technical problems','CGE Support','cgehelp')");
?>
