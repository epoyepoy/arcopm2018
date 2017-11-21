<?php
     # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';

    # Get the devplan id
	$devplanid = $args["devplanid"];
	
	# Use the DAO to access the database
    $data = $developmentDao->deleteDevelopmentPlan($devplanid);
	
    # Encode the data to JSON
	$json = json_encode($data);
	
	# Send the Responce
    $response->write($json);
	
?>