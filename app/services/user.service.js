(function () {

    'use strict';

    angular.module('ARCOPM').factory('UserFactory', UserFactory);

    UserFactory.$inject = ["$exceptionHandler", '$http', 'global'];

    function UserFactory($exceptionHandler, $http, global) {

	    var api = global.api;

		var factory = {};

        factory.UpdatePassword = UpdatePassword;


        return factory;


        function UpdatePassword(newpass,oldpass) {
            return $http.put(api + '/updatepass/' + newpass + '/' + oldpass).then(handleSuccess, handleError);
        }

        function handleSuccess(res) {

		  	return res.data;

        }

        function handleError(error) {
			if (error.status==401) {return error.status;}
            var errorString = "An error has occured. HTTP error: " + error.status + " Text: " + error.statusText;
            if (error.config!=null){
                errorString += " URL: " + error.config.url;
            }
            $exceptionHandler(errorString);
            return function () {
                return { success: false, message: "An error has occurred." };
            };
        }
    }

})();
