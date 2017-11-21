(function(){
	'use strict';

	angular.module("ARCOPM").factory('SupportFactory', SupportFactory);

	SupportFactory.$inject = ["$exceptionHandler",'$http','global'];

	function SupportFactory($exceptionHandler,$http, global) {

		var api = global.api;

		var factory = {};
		factory.GetSupportUser = GetSupportUser;

		return factory;


		// Get evaluations based on grade.
		function GetSupportUser(userid)
		{
			return $http.get(api + '/support/').then(handleSuccess, handleError);
		}


		// Handle a succesful response [ Status code: 200 ]
		function handleSuccess(response)
		{
			return response.data;
		}


		// Handle the response if status code is > 299
		function handleError(error)
		{
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
