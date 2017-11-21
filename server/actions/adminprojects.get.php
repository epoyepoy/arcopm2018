<?php

    # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';

	# Get Questions
	$result = $adminDao->getProjects();
	
	$json = json_encode($result);
    $response->write($json);


?>
