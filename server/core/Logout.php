<?php
    
    # Set the session variables 
    $_SESSION["login"] = false;
    $_SESSION["user"] = null;

    # Create the data object for the Response
    $data["login"] = $_SESSION["login"];
    $data["user"] = $_SESSION["user"];
    
    # Encode to Json
	$json = json_encode($data);

	# Send Response
    $response->write($json);

    # Destroy the Session
    session_destroy();
      
?>