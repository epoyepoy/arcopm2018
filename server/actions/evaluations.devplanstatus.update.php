<?php
     # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';

    $devplan = $request->getParsedBody();
    $userid = $args["userid"];
	
	# Use the DAO to access the database

    $data = $developmentDao->updateDevelopmentPlanStatus($devplan, $userid);
    # Encode the data to JSON
	$json = json_encode($data);
	
	# Send the Responce
    $response->write($json);
	
?>