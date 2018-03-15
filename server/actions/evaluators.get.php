<?php
 
    # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';
	
	# Get params
    $userid = $args["userid"];
    $cycle = $args["cycle"];

	$result = $statisticsDao->GetEvaluators($userid,$cycle);
	
	$json = json_encode($result);
    $response->write($json);
	
	
?>