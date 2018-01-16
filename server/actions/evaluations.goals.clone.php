<?php
     # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';
	
	# Get variables
	$goals = $request->getParsedBody();
    $evalid = $args["evalid"];
	$userid = $args["userid"];
	# Use the DAO to access the database
    $data = $goalsDao->cloneSelectedGoals($goals,$evalid,$userid);
	
    # Encode the data to JSON
	$json = json_encode($data);
	
	# Send the Responce
    $response->write($json);
	
?>