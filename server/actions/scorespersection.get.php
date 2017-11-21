<?php

    # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';

	# Get Scores Per Section
	$filters = $request->getParsedBody();

	$result = $statisticsDao->GetCompanyStatsBySection($filters);
	
	$json = json_encode($result);
    $response->write($json);


?>
