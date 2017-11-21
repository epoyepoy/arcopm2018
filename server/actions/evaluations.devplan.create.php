<?php
     # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';

    # Get the goals by parsing the body
    $devplan = $request->getParsedBody();
    $userid = $args["userid"];
	$evalID = $args["evalID"];
	$state = $args["state"];
	
	# Use the DAO to access the database

    $data = $developmentDao->saveDevelopmentPlan($evalID, $devplan, $userid, $state);
    # Encode the data to JSON
	$json = json_encode($data);
	
	# Send the Responce
    $response->write($json);
	
?>