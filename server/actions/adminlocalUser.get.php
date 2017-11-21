<?php
     # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';

    # Get the project by parsing the body
    $filters = $request->getParsedBody();
	# Use the DAO to access the database
    $data = $adminDao->getLocalUsers($filters);
    //$data["result"] = $projectDao->updateProjectTodo($project,$user);
    //$data["project"] = $project;
	
    # Encode the data to JSON
	$json = json_encode($data);
	
	# Send the Responce
    $response->write($json);
	
?>