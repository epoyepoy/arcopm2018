<?php

    # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';
	
	$userid = $args["userid"];

	# Get Questions
	$result = $statisticsDao->GetFamilies($userid);
	
	$json = json_encode($result);
    $response->write($json);


?>
