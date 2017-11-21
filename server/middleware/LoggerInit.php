<?php   
    require 'Psr/Log/LoggerInterface.php';
    require 'Psr/Log/AbstractLogger.php';
    require 'Psr/Log/LogLevel.php';
    require 'KLogger/Logger.php';    
    
   $logger = new Katzgrau\KLogger\Logger('../logs', Psr\Log\LogLevel::WARNING, array (
        'logFormat' => '{date} - {level} - {message}',
        'filename'  => 'error_log'
    ));
    
?>