<?php

    require_once('LoggerInit.php');
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($data[0]["level"])
    {
        case "error":
            $logger->error('Message: ' . $data[0]["message"]);
            $logger->error('Originating URL: ' . $data[0]["url"]);  
            $logger->error('Stacktrace: ' . $data[0]["stackTrace"]);
            break;
        default:
            $logger->error('Message: ' . $data[0]["message"]);
            $logger->error('Originating URL: ' . $data[0]["url"]);  
            $logger->error('Stacktrace: ' . $data[0]["stackTrace"]);
            break;
    }
    
?>