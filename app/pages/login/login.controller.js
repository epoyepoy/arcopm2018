(function(){

	"use strict";

	// Create the Controller
	angular.module("ARCOPM").controller('loginController', loginController);

	// Inject services to the Controller
	loginController.$inject = ["$scope","Auth","loginData","$state"];

    function loginController($scope,Auth,loginData,$state)
    {
        $scope.finetuning =false;
        $scope.login = loginData.login;
        $scope.username = "";
        $scope.password = "";
        $scope.message = "";
        $scope.loading = false;



        $scope.$watch(function () { return loginData.login },function()
        {
			$scope.login = loginData.login;
            if(!loginData.login)
            {
                $state.go("login");
            }
        });

        $scope.isLoading = function()
        {
            return $scope.loading;
        }

        $scope.signIn = function()
        {
            var credentials = {};
            credentials.username = $scope.username;
            credentials.password = $scope.password;

            $scope.loading = true;

              Auth.signIn(credentials).then(function(result)
             {

				 //console.log(result);
                if(result.success === false)
                 {
                     $scope.message = "Server Error! Please contact an administrator.";
                     $scope.loading = false;
                 }
                 else
                 {
                     loginData.login = result.login;
                     loginData.user = result.user;
                     if(!result.login)
                     {
                         $scope.message = result.error;
						 $scope.loading = false;
                     }
                     else
                     {
                         $scope.setCookie("pmlogin",loginData.login);
                         $scope.setCookie("pmuser",loginData.user);

                         $state.go("homepage");
                         $scope.loading = false;
                     }
                 }
        	 });

        }



        $scope.directoryLogin = function()
        {
            $scope.loading = true;

              Auth.DirectorySignIn().then(function(result)
             {
                if(result.success === false)
                 {
                     $scope.message = "Server Error! Please contact an administrator.";
                     $scope.loading = false;
                 }
                 else
                 {
                     loginData.login = result.login;
                     loginData.user = result.user;

                     if(!result.login)
                     {
                         $scope.message = "Wrong Credentials!";
						 $scope.loading = false;
                     }
                     else
                     {
                         $scope.setCookie("pmlogin",loginData.login);
                         $scope.setCookie("pmuser",loginData.user);

                         $state.go("homepage");
                         $scope.loading = false;
                     }
                 }
        	 });

        }


        $scope.init = function()
        {
            //$scope.directoryLogin();
        }

    }

})();
