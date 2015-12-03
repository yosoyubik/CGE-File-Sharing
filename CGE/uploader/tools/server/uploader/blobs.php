<?php

// http://www.php.net/manual/es/function.header.php
header("Access-Control-Allow-Origin: *");

$current_directory = getcwd();

/* ini_set('upload_tmp_dir','uploads'); */
error_reporting(E_ALL);
ini_set('display_errors', 'stderr');
ini_set('error_log', $current_directory . '/error_log');
ini_set('log_errors','true');

ob_start();

if ( !empty( $_FILES ) ) {
    // Get metadata
    $metadata_content = $_POST['data'];
    // Get UNIQUE ID
    $UID = $_POST['UID'];
    //$ringTrial = $_POST['ringTrial'];
    $metadata = json_decode($metadata_content);
    // Upload Dir = Isolate Line in excel
    $isolate_folder = $metadata->upload_dir;

      $root_folder = '/home/data2/secure-upload/isolates/';
      $upload_folder = '/home/data2/secure-upload' . '/isolates/' . $UID . '/';

    //$upload_folder = dirname( __FILE__ ) . '/isolates/' . $UID . '/';

    $isolate_directory = $upload_folder . $isolate_folder . '/';



    // Create dir for isolates if non existent
    if (is_dir($upload_folder) === FALSE) {
      mkdir($upload_folder, 0777, true);
      chmod($upload_folder, 0777);
    }

    // CHANGE NAME OF JSON TO META.JSON
    if ($metadata->batch == TRUE){
      $metaName =  $isolate_directory . 'meta.json';
    }

    // Create dir for isolates if non existent
    if (is_dir($isolate_directory) === FALSE) {
      mkdir($isolate_directory, 0777, true);
      chmod($isolate_directory, 0777);

      if ($metadata->batch == TRUE){
        // Write metadata file for the first isolate in the file_names
        $fp = fopen($metaName, 'w');
        chmod($metaName, 0777);
        fwrite($fp, $metadata_content);
        fclose($fp);
      }
    }else{
      // echo 'Isolates folder does exist';
      // echo $isolate_directory;
    }

  	foreach($_FILES as $name=>$file)
  	{
         // Reasemble CHUNKS
         $tempPath = $file['tmp_name'];
         $Path =  $isolate_directory . $_POST['fileName'];
         //echo $Path;

         $chunk_id = $_POST['chunkID'];
         $n_chunks = $_POST['nChunks'];
         $errorChunk = $_POST['errorChunk'];
         $needsMerge = $_POST['needsMerge'];
         $n_digits = strlen((string) $n_chunks);
         $OK = is_uploaded_file( $tempPath );

         if ($OK === TRUE){
           // Check if this is the last file, based on the numnber of files already created
           // It can be more than one isolate, so we take into account the origina name
           // $Path. ".*"
           $answer = array();
           $filecount = count(glob($Path . ".*"));
           $answer['file_count'] = $filecount;
           $answer['files'] = glob($Path . ".*");
           $answer['n_chunks'] = $n_chunks;
           $answer['last_chunk'] = ($filecount + 1 === intval($n_chunks) );
           //echo $Path, PHP_EOL;

           // Name of chunk = file_name + '.' + Chunk_ID
           $chunk_path = $Path . '.' . str_pad($chunk_id, $n_digits, "0", STR_PAD_LEFT);
           $answer['chunk_path'] = $chunk_path;
           if (move_uploaded_file($tempPath, $chunk_path) === TRUE ){
              chmod($chunk_path, 0777);
              $answer['state'] = 'Success Chunk';
              $answer['response'] = $upload_folder;
              $answer['aux_response'] = 'Tempt chunk created';
              $answer['chunkID'] = $chunk_id;
              $answer['files_after'] = glob($Path . ".*");
              //header('Content-Length: ' . ob_get_length());
              echo json_encode($answer);
              //ob_end_flush();
           }else{
               //echo 'ERROR';
               $answer['state'] = 'Error';
               // TODO: replace this by code number
               $answer['response'] = 'Moving chunk';
               $answer['chunkID'] = $chunk_id;
               echo json_encode($answer);
           }

         }else{
            //echo 'ERROR';
            $answer['state'] = 'Error';
            // TODO: replace this by code number
            $answer['response'] = 'is_uploaded_file fails';
            $answer['chunkID'] = $chunk_id;
            echo json_encode($answer);
         }


    }
} else {

    //echo 'No files';
    $answer['state'] = 'Error';
    $answer['response'] = 'No files in the upload';
    echo json_encode($answer);

}





?>
