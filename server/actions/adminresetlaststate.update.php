<?php

    # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';
	
    $evalid = $args["evalid"];
	$userid = $args["userid"];

	# Get Questions
	$result = $adminDao->resetLastState($evalid,$userid);
	
	$json = json_encode($result);
    $response->write($json);


?>
