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

$CGE->std_header("File-Sharing Download", "(/all.php,'Upload'),(/files.php,'Download')", FALSE);

if (substr($_SERVER['REMOTE_ADDR'],0,3) == '10.'){$cbsUser = TRUE;}else{$cbsUser = FALSE;}
// phpInfo();
// REQUIRE THE USER TO LOGIN
if($CGE->user_is_logged_in()){
?>
<!-- START INDHOLD -->
<style type="text/css">
   .pad{padding-left: 30px;}
</style>

<!-- START OF CONTENT -->
<table id='isoview'>
  <tr><th>Submission</th><th>Sample</th><th>File Name</th><th>Download File</th><th>Download Metadata</th></tr>
<?php

if(isset($_SESSION['USERNAME'],$_SESSION['SESSIONID'])){
  // GET Files names
  $folders = glob("/home/data2/secure-upload/isolates/*/*/*");
  $submissions = 0;
  foreach($folders as $file){
      $paths = explode("/", $file);
      $submission = $paths[count($paths)-3];
      $submission_date =  explode("_", $submission);

      $date = join('-', array_slice($submission_date, 1, 3));
      $sample = $paths[count($paths)-2];
      $file_name = $paths[count($paths)-1];

      if (strcmp($file_name, 'meta.json') !== 0){
          echo '<tr><th>'.$date.'</th><th>'.$sample.'</th>';

          $partial_folder = join('/', array_slice($paths, 0, count($paths)-1));
          $meta_path = $partial_folder . '/meta.json';
          echo '<th>'.$file_name.'</th>';
          echo "<th><form action='/cge/download_data.php' method='post'>
          <input type='hidden' name='filename' value='$file_name'>
          <input type='hidden' name='fullPath' value='$file'>
          <input type='submit' value='file'>
          </form></th>";
          echo "<th><form action='/cge/download_data.php' method='post'>
          <input type='hidden' name='filename' value='meta.json'>
          <input type='hidden' name='fullPath' value='$meta_path'>
          <input type='submit' value='Metadata'>
          </form></th>";
          echo '</tr>';
      }

    //   echo $folder;
    //   $samples = glob($folder.'/');
    //   $samples_count = 0;
    //    foreach($samples as $sample){
    //        echo $sample;
    //        $files = glob($sample.'/');
    //        foreach($files as $file){
    //            echo $file;
    //         //    if ($file_name !=== 'meta.json'){
    //            if (strcmp($file_name, 'meta.json') !== 0){
    //                echo '<tr><th>'.$submissions.'</th><th>'.$samples_count.'</th>';
    //                $paths = explode("/", $file);
    //                $file_name = $paths[count($paths)-1];
    //                $partial_folder = join('/', array_slice($paths, 0, count($paths)-1));
    //                $meta_path = $partial_folder . 'meta.json';
    //                echo '<th></th><th>'.$file_name.'</th>';
    //                echo "<th><form action='/cge/download_data.php' method='post'>
    //                <input type='hidden' name='filename' value='$file_name'>
    //                <input type='hidden' name='fullPath' value='$file'>
    //                <input type='submit' value='Download file'>
    //                </form></th>";
    //                echo "<th><form action='/cge/download_data.php' method='post'>
    //                <input type='hidden' name='filename' value='meta.json'>
    //                <input type='hidden' name='fullPath' value='$meta_path'>
    //                <input type='submit' value='Download file'>
    //                </form></th>";
    //                echo '</tr>';
    //            }
    //            $samples_count+=1;
    //        }
      //
    //    }
    //    $submissions+=1;

    //   $paths = explode("/", $file);
    //   $file_name = $paths[count($paths)-1];
    //   echo '<tr><th></th><th>'.$file_name.'</th>';
    //   echo "<th><form action='/cge/download_data.php' method='post'>
    //   <input type='hidden' name='filename' value='$file_name'>
    //   <input type='hidden' name='fullPath' value='$file'>
    //   <input type='submit' value='Download file'>
    //   </form></th>";
    //   echo '</tr>';
  }
}
?>
</table>
<!-- END OF CONTENT -->
<br>


<!-- END OF CONTENT -->
<?php
} // END LOGIN REQUIREMENT
$CGE->Piwik(14); // Printing Piwik codes!!

# Displays a standard footer; two parameters:
# First a simple headline like: "Support"
# then a list of emails like this: "('Scientific problems','foo','foo@cbs.dtu.dk'),('Technical problems','bar','bar@cbs.dtu.dk')"
$CGE->standard_foot("Support","('Technical problems','CGE Support','cgehelp')");
?>
