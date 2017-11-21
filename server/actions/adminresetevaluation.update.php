<?php

    # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';
	
    $evalid = $args["evalid"];
	$userid = $args["userid"];
	$resetgoals = $args["resetgoals"];

	# Get Questions
	$result = $adminDao->resetEmployeeEvaluation($evalid,$userid,$resetgoals);
	
	$json = json_encode($result);
    $response->write($json);


?>
