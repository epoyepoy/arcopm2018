<?php
     # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';
	
	# Get variables
	$evals = $request->getParsedBody();
    $empid = $args["empid"];
	# Use the DAO to access the database
    $data = $evaluationsDao->reviseEvaluations($evals,$empid);
	
    # Encode the data to JSON
	$json = json_encode($data);
	
	# Send the Responce
    $response->write($json);
	
?>