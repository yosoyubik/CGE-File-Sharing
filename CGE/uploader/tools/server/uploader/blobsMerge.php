<?php

// http://www.php.net/manual/es/function.header.php
header("Access-Control-Allow-Origin: *");

$current_directory = getcwd();

/* ini_set('upload_tmp_dir','uploads'); */
error_reporting(E_ALL);
ini_set('display_errors', 'stderr');
ini_set('error_log', $current_directory . '/error_log');
ini_set('log_errors','true');


// Get metadata
$metadata_content = $_POST['data'];
// Get UNIQUE ID
$UID = $_POST['UID'];
//$ringTrial = $_POST['ringTrial'];
$metadata = json_decode($metadata_content);

// Upload Dir = Isolate Line in excel
$isolate_folder = $metadata->upload_dir;

  //$upload_folder = $metadata->rootFolder . '/isolates/' . $UID . '/';
  $upload_folder = '/home/data2/secure-upload' . '/isolates/' . $UID . '/';


$isolate_directory = $upload_folder . $isolate_folder . '/';

$Path =  $isolate_directory . $_POST['fileName'];

$chunk_files = glob($Path . ".*");

$answer['original_scandir'] = $chunk_files;

if ($chunk_files  === FALSE){
   $error = true;
   $answer['error_code'] = 'scandir';
}
$error = false;
$file_dest = fopen($Path, "c");

if ($file_dest === FALSE){
   $error = true;
   $answer['error_code'] = 'fopen';
}

$n_chunks = $_POST['nChunks'];
$n_digits = strlen((string) $n_chunks);

chmod($Path, 0777);
//echo 'Packing...', PHP_EOL;
$merged = false;

foreach($chunk_files as $chunk_file){
  $new_chunk = file_get_contents($chunk_file);

  if ($new_chunk === FALSE){
      $error = true;
      $answer['error_code'] = 'file_get_contents';
  }
  fwrite($file_dest, $new_chunk);
  // delete the chunk file
  //if (unlink($isolate_directory . $chunk_file)){
  if (unlink($chunk_file) === TRUE){
    // Example of fseeking huge files
    // https://php.net/manual/es/function.fseek.php
    if (fseek($file_dest, 0, SEEK_END) !== 0){
      $error = true;
      $answer['error_code'] = 'fseek';
    }
  }else{
    $merged = false;
    $error = true;
    $answer['error_code'] = 'unlink';
  }
}


$answer['last_scandir'] = scandir($isolate_directory);
$answer['path'] = $Path;
if (count(glob($Path . ".*")) === 0 ){
 $merged = true;
}
$answer['merge_flag_old'] = count(glob($Path . ".*"));

if ($merged && !$error){
   $answer['state'] = 'Success';
   $answer['response'] = $upload_folder;
}else{
   $answer['state'] = 'Error';
   $answer['response'] = 'Not merged ' . $_POST['fileName']  . ' ' . $n_digits;
}

fclose($file_dest);
echo json_encode($answer);




?>
