(function () {

    "use strict";

    // Create the Controller
    angular.module("ARCOPM").controller('supportController', supportController);

    // Inject services to the Controller

    supportController.$inject = ["$scope", "Auth", "loginData",  "global",  "ngDialog", "SupportFactory"];



    // Controller Logic


    function supportController($scope, Auth, loginData, global, ngDialog, SupportFactory) {
		$scope.message = "none";
		$scope.countGR = 0;			//count we use in view, in order to see if region of Greece is already written in HTML
		
        // Initialize the dashboard
        $scope.init = function () {
            if (!$scope.checkLogin()) {
                return;
            }
			
            $scope.initNavigation();
			$scope.supportUserData();
        };

		
        // Initialize the Data service
        $scope.initNavigation = function () {
            $scope.setPage('support');
            global.navPages = [];
            global.navPages.push({
                name: "Support",
                link: "support",
                current: true
            });
			
        };
		
		
		//data
		$scope.supportUserData = function(){
			if (!$scope.checkLogin()) {
                return;
            }
			var supportUsersGR = [];
			SupportFactory.GetSupportUser().then(function(result){
				$scope.supportUsers = result.supportData; 
				
				angular.forEach(result.supportData, function(value) {
					if(value.Region == 'GREECE'){
						supportUsersGR.push(value);
					}
				});
				supportUsersGR[1].countGR = 1;
				$scope.supportUsersGR = supportUsersGR.reverse();		//reverse because Pampouca must be first
			});
		};
		

		//action
        
		$scope.showMessage = function (message) {
            if ($scope.message === message) {
                return true;
            }

            return false;
        };
		
		

    }


})();