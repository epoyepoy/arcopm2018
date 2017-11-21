(function(){

	"use strict";

	// Create the Controller
	angular.module("ARCOPM").controller('loginController', loginController);

	// Inject services to the Controller
	loginController.$inject = ["$scope","Auth","loginData"];

    function loginController($scope,Auth,loginData)
    {

        $scope.login = loginData.login;
        $scope.username = "";
        $scope.password = "";
        $scope.message = "";

        $scope.$watch(function () { return loginData.login },function()
        {
            $scope.login = loginData.login;
        });

        $scope.signIn = function()
        {
            var credentials = {};
            credentials.username = $scope.username;
            credentials.password = $scope.password;
			console.log($scope.password);
			if(credentials.password.trim() !== "")
			{
			 Auth.signIn(credentials).then(function(result)
             {
                if(result.success === false)
                 {
                     $scope.message = "Server Error! Please contact an administrator.";
                 }
                 else
                 {
                     loginData.login = result.login;
                     loginData.user = result.user;
                     if(!result.login)
                     {
                         $scope.message = "Wrong Credentials";
                     }
                 }
        	 });
		 	}
		 	else {
				$scope.message = "Password Cannot Be Empty";
			 }
		 }

        }

        $scope.handleSuccess = function(response)
        {
            //console.log(response);
            var res = response.data;

            $scope.login = res.login;
            $scope.message = res.message;
        }

        $scope.handleError = function(response)
        {
            console.log(response);
        }

    }

})();
