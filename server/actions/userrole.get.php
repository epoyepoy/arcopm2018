<?php
 
    # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';
	
	# Get User Role
    $evalID = $args["evalID"];
	$userID = $_SESSION["user"]["id"];
	$result = $evaluationsDao->getUserRole($evalID, $userID);
	$json = json_encode($result);
    $response->write($json);
	
	
?>