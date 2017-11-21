<?php
 
    # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';
	
	# Get user
    //$user = $args["userid"];

	$result = $userDao->getSupportUser();
	
	$json = json_encode($result);
	
	
    $response->write($json);
	
	
?>