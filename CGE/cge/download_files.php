<?php
###############################################################
#               CGE SERVICES DOWNLOAD RESULTS                 #
###############################################################

// function for force downloading files
function downloadFile( $filePath, $filename ){

  // Must be fresh start
  if( headers_sent() )
    die('Headers Sent');

  // Required for some browsers
  if(ini_get('zlib.output_compression'))
    ini_set('zlib.output_compression', 'Off');

  // File Exists?
  $fullPath = $filePath.$filename;
  if( file_exists($fullPath) ){
	 $pos = strrpos($filename, '.gz');
	 if($pos !== false){
		$filename = substr($filename, 0, $pos);
	 };
    header("Content-Description: File Transfer");
    header("Content-Disposition: attachment; filename=$filename;" );
    header("Content-Type: application/force-download");
    header("Content-Transfer-Encoding: binary");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: private",false); // required for certain browsers
    header("Expires: 0");
    header("Pragma: public"); // required
    #header("Content-Length: ".filesize($fullPath));
    header("X-Sendfile: $fullPath");
    ob_clean();
    flush();
    readgzfile( $fullPath );

  } else
    die('File Not Found');
}#END FUNCTION 

#GETTING FORM VALUES
$service = preg_replace('/[^A-Za-z]/', '', $_POST['service']);
$version = preg_replace('/[^0-9\.]/', '', $_POST['version']);
$filename = preg_replace('/[^A-Za-z\.\_]/', '', $_POST['filename']);
$pathid = preg_replace('/[^0-9\_]/', '', $_POST['pathid']);

if($service != "" && $version != "" && $filename != "" && $pathid != ""){
	#FULL FILE PATH
	$filePath = "/panfs1/cge-servers/".$service."/".$service."-".$version."/IO/".$pathid."/downloads/";
	#$filePath = "/home/data1/services/".$service."/".$service."-".$version."/IO/".$pathid."/downloads/";
	#DOWNLOADING FILE!
	//echo "$file<br>";
	if(!file_exists($filePath.$filename)){
	 if(substr($filename, -3, 3) === '.gz'){
		$filename = substr($filename, 0, strlen($filename)-3);
	 }else{
		$filename .= '.gz';
	 }
	}
	//echo "$filename<br>";
	downloadFile($filePath, $filename);
}else{
	echo "<html><body>Missing input</body></html>";
}
?>