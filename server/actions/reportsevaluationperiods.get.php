<?php

    # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';

	# Get Questions
	$result = $reportsDao->GetEvaluationPeriods();
	
	$json = json_encode($result);
    $response->write($json);


?>
