<?php
     # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';

    # Get the project by parsing the body
    $empno = $_SESSION["user"]["id"];
    $newpass = $args["newpass"];
	$oldpass = $args["oldpass"];
	# Use the DAO to access the database
    $data = $userDao->updatePassword($empno, $newpass, $oldpass );
    //$data["result"] = $projectDao->updateProjectTodo($project,$user);
    //$data["project"] = $project;
	
    # Encode the data to JSON
	$json = json_encode($data);
	
	# Send the Responce
    $response->write($json);
	
?>