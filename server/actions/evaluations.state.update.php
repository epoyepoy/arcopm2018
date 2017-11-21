<?php
     # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';

	# Get variables
	$evalid = $args["evalid"];
    $cycleid = $args["cycleid"];
	$userid = $args["userid"];
	$empid = $args["empid"];
	$onbehalf = $args["onbehalf"];
	
	# Use the DAO to access the database
    $data = $evaluationsDao->updateState($evalid,$userid,$cycleid,$empid,$onbehalf);

    # Encode the data to JSON
	$json = json_encode($data);

	# Send the Responce
    $response->write($json);

?>
