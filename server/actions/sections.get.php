<?php
 
    # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';
	
	# Get Sections
    $evalID = $args["evalID"];
	$userID = $args["userid"];
	$state = $args["state"];
	$result = $evaluationsDao->getQuestionaireSections($evalID, $userID, $state);
	
	$json = json_encode($result);
    $response->write($json);
	
	
?>