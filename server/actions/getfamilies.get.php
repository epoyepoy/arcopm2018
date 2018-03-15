<?php

    # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';
	
	$userid = $args["userid"];
    $cycle = $args["cycle"];


	$result = $statisticsDao->GetFamilies($userid,$cycle);
	
	$json = json_encode($result);
    $response->write($json);


?>
