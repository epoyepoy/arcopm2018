<?php
 
    # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';
	
	# Get reporting line
    $evalID = $args["evalID"];
	$cycleid = $args["cycleid"];
    $result = $evaluationsDao->getUserReportingLine($evalID,$cycleid);
	
	$json = json_encode($result);
    $response->write($json);
	
?>