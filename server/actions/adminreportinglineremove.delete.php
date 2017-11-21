<?php
    # Authentication/Authorization
    require 'core/Auth.php';
	# Database Connection
	require 'core/Database.php';
	# Delete from app
	$settings = $request->getParsedBody();
	$result = $adminDao->removeEmployeeFromApp($settings);

	$json = json_encode($result);
    $response->write($json);
?>