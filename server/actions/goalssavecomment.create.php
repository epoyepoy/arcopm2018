<?php
     # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';

    # Get the goals by parsing the body
    $comment = $request->getParsedBody();
    $userid = $args["userid"];
	$evalid = $args["evalid"];
	$state = $args["state"];
	
	# Use the DAO to access the database
    $data = $goalsDao->saveComment($evalid, $userid, $state, $comment['description']);
    # Encode the data to JSON
	$json = json_encode($data);
	
	# Send the Responce
    $response->write($json);
	
?>