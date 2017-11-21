<?php
 
    # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';
	
	# Get Questions
    $userid = $args["userid"];

	$result = $statisticsDao->GetEvaluators($userid);
	
	$json = json_encode($result);
    $response->write($json);
	
	
?>