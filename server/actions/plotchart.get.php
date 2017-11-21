<?php

    # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';

	# Get Questions
	$filters = $request->getParsedBody();

	$result = $statisticsDao->GetPlotChart($filters);
	
	$json = json_encode($result);
    $response->write($json);


?>
