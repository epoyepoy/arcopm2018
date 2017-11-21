<?php

require_once 'exceptions/ApplicationException.php';

/**
 * Authorization middleware class that checks that the requester is logged in.
 */
class Authentication {

    public function __invoke($request, $response, $next) {

        # Ensure that the user is logged in
        if (!isset($_SESSION["user"]))
            throw new ApplicationException("Not Authorized.", 401);

        return $next($request, $response);
    }

}
