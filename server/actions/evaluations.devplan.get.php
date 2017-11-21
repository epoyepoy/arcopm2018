<?php
 
    # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';
	
	# Get Variables
    $evalID = $args["evalID"];
	$state = $args["state"];
	$userid = $_SESSION["user"]["id"];
	$result = $developmentDao->getDevelopmentPlan($evalID, $state, $userid);
	
	$json = json_encode($result);
    $response->write($json);
	
	
?>