<?php
     # Authentication/Authorization
    require 'core/Auth.php';

	# Database Connection
	require 'core/Database.php';

    # Get the project by parsing the body
    $answers = $request->getParsedBody();
    $userID=$_SESSION["user"]["id"];
    $evalid = $args["evalID"];
	$state = $args["state"];
	$finish = $args["finish"];
	$pause = $args["pause"];
	# Use the DAO to access the database
    $data = $evaluationsDao->saveAnswers($answers, $evalid, $state, $userID, $finish, $pause);
    //$data["result"] = $projectDao->updateProjectTodo($project,$user);
    //$data["project"] = $project;
	
    # Encode the data to JSON
	$json = json_encode($data);
	
	# Send the Responce
    $response->write($json);
	
?>