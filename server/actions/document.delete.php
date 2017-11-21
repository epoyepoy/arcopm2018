<?php
	# Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';
	
	# PHPExcel 
	//require_once 'middleware/PhpExcel/PHPExcel.php';
    
	$userid = $args["empid"];
    $evalid = $args["evalid"];  
    $evaluationObject = $request->getParsedBody();
	
     if ($_FILES["file"]["error"] > 0)
    {
        $data['response']=2;
    }
    else
    {   
		
		unlink("uploads/".$userid.'/'.$evaluationObject['UploadedFile']);
		rmdir("uploads/".$userid);

		$data = $evaluationsDao->uploadFile($evalid,null); 	
		$data['response'] =1;		//success	
				
    } 
			
    $response->write(json_encode($data));
?>

