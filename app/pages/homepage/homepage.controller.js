(function () {

    "use strict";

    // Create the Controller
    angular.module("ARCOPM").controller('homepageController', homepageController);

    // Inject services to the Controller

    homepageController.$inject = ["$scope", "Auth", "loginData",  "global", "EvaluationsFactory", "ngDialog"];



    // Controller Logic


    function homepageController($scope, Auth, loginData, global, EvaluationsFactory, ngDialog) {
		$scope.message = "none";
		
        // Initialize the dashboard
        $scope.init = function () {
            if (!$scope.checkLogin()) {
                return;
            }
			$scope.imageExists();
            $scope.initNavigation();
			
        };
		
		
        // Initialize the Data service
        $scope.initNavigation = function () {
            global.navPages = [];
            global.navPages.push({
                name: "Homepage",
                link: "homepage",
                current: true
            });
			
        };
		
		
		//actions
		
        
       $scope.imageExists = function() {
          var image = new Image();
          var src='https://arcofsdata.archirodon.net/employee_photo/'+loginData.user.id+'b.jpg';
          image.src = src;
          if (image.width == 0) {
              loginData.user['imgSrc']='assets/images/accountIcon.svg';
          } else {
              loginData.user['imgSrc']=src;
          }
        }
        
		$scope.showMessage = function (message) {
            if ($scope.message === message) {
                return true;
            }

            return false;
        };
		
		
		//filters
		$scope.greaterThan = function(prop, val){
			return function(item){
				return item[prop] > val;
			};
		};
		
		$scope.lessThan = function(prop, val){
			return function(item){
				return item[prop] < val;
			};
		};

    }


})();