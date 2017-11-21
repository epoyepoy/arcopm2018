<?php
 
    # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';
	
	# Get reporting line
    $evalID = $args["evalID"];
    $result = $evaluationsDao->getReportingLine($evalID);
	
	$json = json_encode($result);
    $response->write($json);
	
?>