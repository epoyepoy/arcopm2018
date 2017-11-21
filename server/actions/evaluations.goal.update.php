<?php
     # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';

    # Get the goal by parsing the body
    $goal = $request->getParsedBody();
	
	# Get the user id
    $userid = $args["userid"];
	
	# Use the DAO to access the database
    $data = $goalsDao->updateGoal($goal, $userid);
	
    # Encode the data to JSON
	$json = json_encode($data);
	
	# Send the Responce
    $response->write($json);
	
?>