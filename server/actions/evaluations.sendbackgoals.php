<?php
     # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';
	
	# Get variables
    $evalid = $args["evalid"];
    $userid = $args["userid"];
    $state = $args["state"];
	# Use the DAO to access the database
    $data = $goalsDao->sendBackGoals($evalid,$userid,$state);
	
    # Encode the data to JSON
	$json = json_encode($data);
	
	# Send the Responce
    $response->write($json);
	
?>