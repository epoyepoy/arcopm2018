<?php
	# Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';
	
	# PHPExcel 
	//require_once 'middleware/PhpExcel/PHPExcel.php';
    
	$userid = $args["empid"];
    $evalid = $args["evalid"];  
	$cycle = $args["cycle"]; 
    $evaluationObject = $request->getParsedBody();
	
     if ($_FILES["file"]["error"] > 0)
    {
        $data['response']=2;
    }
    else
    {   
        $fileName = explode(".",$_FILES["file"]["name"]);
		$fileToUpload = $cycle."_signed_evaluation_by_employee.".$fileName[1];
		
		//if file doesnt exist upload to server
		mkdir("uploads/".$userid, 0700);

		move_uploaded_file($_FILES["file"]["tmp_name"],
		"uploads/".$userid.'/'.$fileToUpload);

		$uploadedfile = "uploads/".$userid.'/'.$fileToUpload;
		$data = $evaluationsDao->uploadFile($evalid,$fileToUpload); 	
		$data['response'] =1;		//success	
				
    } 
			
    $response->write(json_encode($data));
?>

