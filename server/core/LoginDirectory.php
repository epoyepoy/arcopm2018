<?php

    # Database Connection
	require 'core/Database.php';



    # Automatically login using Single-Sign-On
	if (isset($_SERVER['REMOTE_USER']))
    {
		$username = $_SERVER['REMOTE_USER'] . '@archirodon.net';

		$result = $userDao->getDirectoryUser($username);
        $user = $result["user"];

        if($user)
		{
			if ($user["role"] != null) {


				$_SESSION["login"] = true;
				$_SESSION["user"] = $user;
			/*# Get the user from the database
			$userRole = $userDao->getArcoUser($user["id"]);

			# If the user is in the databse add the user_role to the $user
			if($userRole)
			{
				foreach ($userRole["roles"] as &$role) {
                    if ($role["role"]=='ito')
                        $user["itoRole"]= "1";
                    if ($role["role"]=='administrator')
                        $user["adminRole"]= "1";
                    if ($role["role"]=='inventory')
                        $user["inventoryRole"]= "1";
                    if ($role["role"]=='viewer')
                        $user["viewerRole"]= "1";
                }
                 if ($user["adminRole"]) $user["role"] = "administrator";
                        else if ($user["itoRole"]) $user["role"] = "ito";
                        else if ($user["inventoryRole"]) $user["role"] = "inventory";
                        else $user["role"] = "viewer";
				$_SESSION["login"] = true;
				$_SESSION["user"] = $user;*/

			}
			# If the user is not in the database but he is succesfully validated from the directory then create
			# a new record in the databse with the user id and role "user"
			else
			{
				$result = $userDao->createArcoUser($user["id"], "user");

				if($result)
				{
					$user["role"] = "viewer";
                    $user["viewerRole"]= "1";
					$_SESSION["login"] = true;
					$_SESSION["user"] = $user;
				}
			}

		}
		else
		{
			die("User does not exist");
		}
	}

    if(isset($_SESSION["login"]) && isset($_SESSION["user"]))
    {
        # Create the data object for the Response
        $data["login"] = $_SESSION["login"];
        $data["user"] = $_SESSION["user"];
    }
    else
    {
        $data["login"] = NULL;
        $data["user"] = NULL;
    }

    # Encode to Json
	$json = json_encode($data);

	# Send Response
    $response->write($json);

?>
