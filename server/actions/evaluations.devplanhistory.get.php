<?php
 
    # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';
	
	# Get Variables
    $evalID = $args["evalID"];
	$result = $developmentDao->getDevelopmentPlanHistory($evalID);
	
	$json = json_encode($result);
    $response->write($json);
	
	
?>