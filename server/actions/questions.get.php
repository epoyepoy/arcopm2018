<?php
 
    # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';
	
	# Get Questions
    $evalID = $args["evalID"];
	$state = $args["state"];
	$userID=$_SESSION["user"]["id"];
	$result = $evaluationsDao->getQuestions($evalID, $userID, $state);
	
	$json = json_encode($result);
    $response->write($json);
	
	
?>