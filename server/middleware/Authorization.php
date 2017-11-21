<?php

class Authorization {

    private $rolesAllowed;

    public function __construct(...$roles) {
        # set the allowed roles for the specific route
        $this->rolesAllowed = $roles;
    }

    public function __invoke($request, $response, $next) {
        # get the user's role
        $userRole = filter_var($_SESSION["user"]->role);

        # Ensure that the user has the permission required to access the resource
        if (!in_array($userRole, $this->rolesAllowed))
            throw new ApplicationException("You do not have the permissions required to access this resource or perform this action.", 403);

        return $next($request, $response);
    }

}
