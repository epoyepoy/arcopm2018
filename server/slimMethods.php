<?php

# method
$method = $request->getMethod();

$request->isGet();
$request->isPost();
$request->isPut();
$request->isDelete();
$request->isHead();
$request->isPatch();
$request->isOptions();

# URI
$uri = $request->getUri();

$uri->getScheme();
$uri->getHost();
$uri->getPort();
$uri->getPath();
$uri->getBasePath();
$uri->getQuery();  
$uri->getQueryParams();  

?>