(function(){

	"use strict";

    angular.module("ARCOPM").directive('fileModelMultiple', fileModelMultipleDirective);
    
    fileModelMultipleDirective.$inject = ['$parse'];
    
    function fileModelMultipleDirective($parse) 
    {
        return { 
            restrict: 'A',
            link: fileModelMultipleLink
        };
        
        
        function fileModelMultipleLink(scope, element, attrs)
        {
            var model = $parse(attrs.fileModelMultiple);
            var isMultiple = attrs.multiple;
            var modelSetter = model.assign;

            element.bind('change', function () {
                var values = [];
                angular.forEach(element[0].files, function (item) {
                    var value = item;
                    values.push(value);
                });
                scope.$apply(function () {
                    if (isMultiple) {
                        modelSetter(scope, values);
                    } else {
                        modelSetter(scope, values[0]);
                    }
                });
            });
         }
    }
    

})();
