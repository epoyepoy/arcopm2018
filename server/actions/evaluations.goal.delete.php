<?php
     # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';

    # Get the goal id
	$goalid = $args["goalid"];
	
	# Use the DAO to access the database
    $data = $goalsDao->deleteGoal($goalid);
	
    # Encode the data to JSON
	$json = json_encode($data);
	
	# Send the Responce
    $response->write($json);
	
?>