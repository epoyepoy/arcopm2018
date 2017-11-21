<?php
     # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';

    # Get the project by parsing the body
    $args = $request->getParsedBody();
	# Use the DAO to access the database
    $data = $adminDao->updateUsers($args['empno'] , $args['userID'] , $args['password'] , $args['loggedinid'] , $args['isinactive'] );
    //$data["result"] = $projectDao->updateProjectTodo($project,$user);
    //$data["project"] = $project;
	
    # Encode the data to JSON
	$json = json_encode($data);

	# Send the Responce
    $response->write($json);
	
?>