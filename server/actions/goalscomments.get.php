<?php

    # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';
	
	$evalid = $args["evalid"];

	# Get Questions
	$result = $goalsDao->getComments($evalid);
	
	$json = json_encode($result);
    $response->write($json);


?>
