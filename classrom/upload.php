<?php
$filenum = $_POST['filenum']; // 1,2,3,4
$tmp_name = $_FILES['file']['tmp_name'];
$upload = 'data/uploads/';
$filename = $_POST['dataname'];
$new_file_name = $filename . '.chunk-' . $filenum; // pattern : chunk-1myfilename.jpg, chunk-2myfilename.jpg,
$target_file = $upload . $new_file_name;

if(move_uploaded_file( $tmp_name, $target_file )){
	if($_POST['lastpart'] == 'true'){
		$total_chunks = $filenum;
		for ( $i = 1; $i <= $total_chunks; $i++ ) {
			$file = fopen( $upload .  $filename . '.chunk-' . $i , 'r');
			$fsize = filesize($upload .  $filename . '.chunk-' . $i);
			//$fsize = 1000000;
			$buff = fread( $file, $fsize);
			fclose( $file );
			
			$final = fopen($upload .  $filename , 'a');
			$write = fwrite($final, $buff);
			fclose($final);

			unlink($upload .  $filename . '.chunk-' . $i );
		}
	}
	echo 1;
}else{
	echo 0;
}




?>