<?php
 
    # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';
	
	# Get Questions
    $evalID = $args["evalid"];
	
	$result = $evaluationsDao->getEmployeeHistory($evalID);
	
	$json = json_encode($result);
    $response->write($json);
	
	
?>