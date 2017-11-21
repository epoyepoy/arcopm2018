<?php
 
    # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';
	
	
	$result = $evaluationsDao->getEvaluationCycles();
	
	$json = json_encode($result);
	
	
    $response->write($json);
	
	
?>