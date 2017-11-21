(function(){

	"use strict";
    
    angular.module("ARCOPM").directive("fileModel", fileModelDirective);
    
    fileModelDirective.$inject = ['$parse'];
    
    function fileModelDirective($parse)
    {

        return {
            restrict: 'A',
            link: fileModelLink
        };
    
     
        function fileModelLink(scope, element, attrs)
        {  
            var model = $parse(attrs.fileModel);
            var modelSetter = model.assign;

            element.bind('change', function()
            {
                scope.$apply(function()
                {
                    modelSetter(scope, element[0].files[0]);
					
                });
            });
        }
    }
     

})();

  