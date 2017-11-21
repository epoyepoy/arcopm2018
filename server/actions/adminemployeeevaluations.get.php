<?php

    # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';
	
    $empid = $args["empid"];

	# Get Questions
	$result = $adminDao->getEmployeeEvaluations($empid);
	
	$json = json_encode($result);
    $response->write($json);


?>
