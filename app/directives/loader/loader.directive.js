(function(){

	"use strict";
    
    angular.module("ARCOPM").directive("loader", loaderDirective);
    
    function loaderDirective()
    {
            
        return {
                restrict: "E",
                templateUrl: "app/directives/loader/loader.view.html",
                scope: { loading: "=" },
                controller: function($scope){
                
                    $scope.loading = true;
                
                }
     
                };

    }
    
     

})();

 
 