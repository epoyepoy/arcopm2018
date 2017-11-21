<?php
     # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';
	
	# Get variables
    $empid = $args["empid"];
	$youraction = $args["youraction"];
	
	# Use the DAO to access the database
    $data = $evaluationsDao->SetWrongManager($empid, $youraction);
	
    # Encode the data to JSON
	$json = json_encode($data);
	
	# Send the Responce
    $response->write($json);
	
?>