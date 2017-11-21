<?php
     # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';


	# Use the DAO to access the database
    $data = $goalsDao->getGoalAttributes();


    # Encode the data to JSON
	$json = json_encode($data);

	# Send the Responce
    $response->write($json);

?>
