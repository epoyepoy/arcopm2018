(function(){

	"use strict";

	// Create the Controller
	angular.module("ARCOPM").controller('appController', appController);

	// Inject services to the Controller
	appController.$inject = ["$scope","Auth","loginData","$state","global","$cookies","$cookieStore", "ngDialog", "UserFactory"];
	
	// Controller Logic
	function appController($scope,Auth,loginData,$state,global,$cookies,$cookieStore,ngDialog,UserFactory)
	{
		
		$scope.page = "homepage";
		
        $scope.user = null;
        
        $scope.navPages = [];
        $scope.$watch(function () { return loginData.login },function()
        {
            if(!loginData.login)
            {
                $state.go("login");
            }
        });
        
        $scope.$watch(function () { return loginData.user },function()
        {
			$scope.user = loginData.user;
        });
        
        $scope.$watch(function () { return global.navPages },function()
        {
            $scope.navPages = global.navPages;
        });
        
        
        
        
        $scope.logout = function()
        {
            Auth.signOut().then(function(result)
             { 
                
                 loginData.login = result.login;
                 loginData.user = result.user;
				 $scope.setCookie("pmlogin",null);
	             $scope.setCookie("pmuser", null); 
//console.log(Auth.isLoggedIn());
                 $state.go("login");
 
        	 });
        };
        
        
        
        // Everytime data is returned from the server the loggin and user are also refreshed
        $scope.updateLoginStatus = function(login,user)
		{  
          
			loginData.login = login;
            loginData.user = user;
		};
        
        $scope.checkLogin = function()
        {            
            if(!loginData.login || !$scope.getCookie("pmlogin"))
            {
                ngDialog.closeAll();
                $state.go("login");
                return false;
            }
            return true;
        };
        
		
		// Shows add New development plan popup
        $scope.showChangePassPopup = function (evlID) {
            if (!$scope.checkLogin()) {
                return;
            }
            $scope.extraMessage = 'none';

            $scope.changePassPopup = ngDialog.open({
                template: 'app/shared/popup/changePassword.popup.html',
                className: 'ngdialog-theme-default',
                scope: $scope
            });
        };
		
		
		$scope.changePassword = function(newpass,oldpass){
			if (!$scope.checkLogin()) {
                return;
            }
			UserFactory.UpdatePassword(newpass,oldpass).then(function (result) {
				if (result.success) {
					$scope.extraMessage = 'created';
				} else {
					$scope.extraMessage = 'error';
					$scope.extraMessageText = 'Something went wrong while saving your selection. Please contact your administrator.';

				}
			});
			
		};
		
		
		$scope.showExtraMessage = function (message) {
            if ($scope.extraMessage === message) {
                return true;
            }

            return false;
        };
		
		$scope.isPage = function(page)
		{
			if($scope.page === page)
			{
				return true;
			}
			
			return false;
		};
        
        // Checks if the give array has elements
        $scope.isEmpty = function(array)
        {
            if(array)
            {
                if(array.length === 0)
                {
                    return true;
                }
            }
            
            return false;
        };
		
        // Checks if the current user is administrator, manager etc.
        $scope.isUser = function(userRole)
        {
            if(loginData.user)
            {
                if( loginData.user.role === userRole )
                {
                    return true;
                }
            }
            
            return false;
        };
        
        
		$scope.setPage = function(page)
		{
			$scope.page = page;
            
            if(
                (page === "users" && loginData.user.role != "administrator") ||
                
                (page === "reports" && loginData.user.role === "user")
                )
            {
                $state.go("unauthorized");
            }
		};
		
        $scope.init = function()
        {    
            loginData.login = $scope.getCookie("pmlogin");
            loginData.user = $scope.getCookie("pmuser");
        };
        
        $scope.getCookie = function(cookieName)
        {
            return $cookieStore.get(cookieName);
        };
        
        $scope.setCookie = function(cookieName,value)
        {
            $cookieStore.put(cookieName, value);
        };
        
        
		 
	}
	

})();

// angular.module("ARCOPM").run(function($rootScope,$state) {
//     var lastDigestRun = new Date();
//     setInterval(function () {
//         var now = Date.now();
//         if (now - lastDigestRun > 10 * 60 * 1000) {
//            $state.go("login");
//         }
//     }, 1000);

//     $rootScope.$watch(function() {
//         lastDigestRun = new Date();
//     });
// });
