<?php
     # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';

    # Get the userid
	$userid = $args["userid"];
	$cycleid = $args["cycleid"];

	# Use the DAO to access the database
    $data = $goalsDao->getUsersToSetGoals($userid,$cycleid);


    # Encode the data to JSON
	$json = json_encode($data);

	# Send the Responce
    $response->write($json);

?>
