<?php
     # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';

    # Get the goals by parsing the body
    $goal = $request->getParsedBody();
    $userid = $args["userid"];
	$empid = $args["empid"];
	$cycleid = $args["cycleid"];
	
	# Use the DAO to access the database

    $data = $goalsDao->saveGoals($goal, $empid, $userid, $cycleid);
    # Encode the data to JSON
	$json = json_encode($data);
	
	# Send the Responce
    $response->write($json);
	
?>