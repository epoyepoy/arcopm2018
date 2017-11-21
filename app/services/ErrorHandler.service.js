(function () {
   
    'use strict';

    angular.module('ARCOPM').factory('ErrorHandler', ErrorHandler);

    ErrorHandler.$inject = ['$http','$state','Auth','PopupService'];

     function ErrorHandler($http,$state,Auth,PopupService)
    {

        var factory = {};
        
        factory.message = "";
       // factory.handleErrorCode = factory.handleErrorCode;
        factory.handleErrorCode = handleErrorCode;
        return factory;
        
        function handleErrorCode(code)
        { 
            switch (code)
            {
                case 401:
                    this.message = "Unauthorized User";
                    Auth.clearLogin();
                    PopupService.closeAll();
                    $state.go("login");
                    break;
                case 403:
                this.message = "Restricted Access";
                PopupService.closeAll();
                $state.go("main.unauthorized");
                break;
                case 500:
                    this.message = "Internal Server Error";
                    break;
                default: 
                    this.message = "Unknown Status Code";
            }
        }

        function closePopups()
        {
            if(PopupService.dialog)
            {
                PopupService.dialog.close();
            }
        }


    }
 
})();