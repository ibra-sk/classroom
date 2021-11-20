<?php
/*
UploadiFive
Copyright (c) 2012 Reactive Apps, Ronnie Garcia
*/

// Set the uplaod directory
$uploadDir = '/classrom/data/uploads/';

// Set the allowed file extensions
//$fileTypes = array('jpg', 'jpeg', 'gif', 'png', 'mkv', 'mp4'); // Allowed file extensions

$verifyToken = md5('unique_salt' . $_POST['timestamp']);

if (!empty($_FILES) && $_POST['token'] == $verifyToken) {
	$tempFile   = $_FILES['Filedata']['tmp_name'];
	$uploadDir  = $_SERVER['DOCUMENT_ROOT'] . $uploadDir;
	$targetFile = $uploadDir . $_FILES['Filedata']['name'];

	// Validate the filetype
	$fileParts = pathinfo($_FILES['Filedata']['name']);
	// Save the file
	if(move_uploaded_file($tempFile, $targetFile)){
		echo 'success';
	}else{
		echo 'failed';
	}
	
}else{
	echo 'error';
}
?>