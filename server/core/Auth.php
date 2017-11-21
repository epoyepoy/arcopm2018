<?php


if ( !isset($_SESSION) || !isset($_SESSION["login"]) || $_SESSION["login"] === false)
{
	# Set the "login" field of the data object of the response to false
   	$data["login"] = false;

   	#Encode to Json
   	//json_encode($data);

   	//$response = $response->withStatus(401);

   	# Respond and Die
   	//$response->getBody()->write($data);

   	header('Status: 401', TRUE, 401);

   	# Stop the Script
   	die();
}

    $server = "http://arcodevel.cloudapp.net/arcopm";

    $data["login"] = $_SESSION["login"];
    $data["user"] = $_SESSION["user"];

?>
