(function(){

	"use strict";
    
    angular.module("ARCOPM").directive("login", loginDirective);
    
    // Inject services to the Controller
	loginDirective.$inject = ["$http","loginData"];
    
    function loginDirective()
    {

        return {
                restrict: "E",
                templateUrl: "app/directives/login/login.view.html",
                scope: { login: "=" } 

            };

    }
    


})();


 
 