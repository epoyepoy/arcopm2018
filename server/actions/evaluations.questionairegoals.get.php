<?php
     # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';

    # Get the empid
	$empid = $args["empid"];
	$userid = $args["userid"];
	$evalid = $args["evalid"];

	# Use the DAO to access the database
    $data = $goalsDao->getQuestionaireGoals($empid,$userid,$evalid);


    # Encode the data to JSON
	$json = json_encode($data);

	# Send the Responce
    $response->write($json);

?>
