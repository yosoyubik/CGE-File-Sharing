<?php
###############################################################
#               CGE SERVICES DOWNLOAD RESULTS                 #
###############################################################

// function for force downloading files
function downloadFile( $fullPath, $filename ){

  // Must be fresh start
  if( headers_sent() )
    die('Headers Sent');

  // Required for some browsers
  if(ini_get('zlib.output_compression'))
    ini_set('zlib.output_compression', 'Off');
    // echo `ls -la /`;
    // echo `ls -la /home/`;
    // echo `ls -la /home/data2/`;
    // echo `ls -la /home/data2/secure-upload/`;
    // echo `ls -la /home/data2/secure-upload/isolates/`;
    // echo `ls -la /home/data2/secure-upload/isolates/*`;
    // echo `ls -la /home/data2/secure-upload/isolates/*/0/`;
    // echo `ls -la $fullPath`;
    // echo $fullPath;
  // File Exists?
  if( file_exists($fullPath) ){
	//  $pos = strrpos($filename, '.gz');
	//  if($pos !== false){
	// 	$filename = substr($filename, 0, $pos);
	//  };
    //header("Pragma: public"); // required
    //header("Expires: 0");
    //header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    //header("Cache-Control: private",false); // required for certain browsers
    //header("Content-Type: application/force-download");
    //header("Content-Disposition: attachment; filename=".$filename.";" );
    //header("Content-Transfer-Encoding: binary");
    //header("Content-Length: ".filesize($fullPath));
    //ob_clean();
    //flush();
    //readfile( $fullPath );

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
$fullPath = $_POST['fullPath'];
$filename = $_POST['filename'];

if($fullPath != "" && $filename != ""){
	downloadFile($fullPath, $filename);
}else{
	echo "<html><body>Missing input</body></html>";
}
?>
