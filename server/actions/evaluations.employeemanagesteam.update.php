<?php
     # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';
	
	# Get the employee id
    $empid = $args["empid"];
	$managesteam= $args["managesteam"];
	$userid = $args["userid"];
	$cycleid = $args["cycleid"];
	
	
	# Use the DAO to access the database
    $data = $evaluationsDao->updateEvaluation($empid, $managesteam, $userid, $cycleid);
	
    # Encode the data to JSON
	$json = json_encode($data);
	
	# Send the Responce
    $response->write($json);
	
?>