<?php

    # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';

	# Get Questions
	$settings = $request->getParsedBody();

	$result = $adminDao->updateReportingLine($settings);
	
	$json = json_encode($result);
    $response->write($json);


?>
