(function(){
	'use strict';

	angular.module("ARCOPM").factory('EvaluationFormFactory', EvaluationFormFactory);

	EvaluationFormFactory.$inject = ["$exceptionHandler",'$http','global'];

	function EvaluationFormFactory($exceptionHandler,$http, global) {

		var api = global.api;

		var factory = {};
		factory.GetEvaluations = GetEvaluations;
		factory.GetEvaluationsStatistics = GetEvaluationsStatistics;
		factory.FileUpload =  FileUpload;

		return factory;


		// Get evaluations based on grade.
		function GetEvaluations(userid)
		{
			return $http.get(api + '/evaluations/' + userid).then(handleSuccess, handleError);
		}


		// Get charts evaluations based on grade.
		function GetEvaluationsStatistics(userid)
		{
			return $http.get(api + '/evaluations/statistics/' + userid).then(handleSuccess, handleError);
		}


		function FileUpload(evaluation)
		{
			var formData = new FormData();
			formData.append("file",evaluation.fileToUpload);


			return $http.post(
				api + '/projects/' + evaluation.empno + '/' +evaluation.grade+ '/documents',
				formData,
				{
					transformRequest: angular.identity,
					headers: { 'Content-Type': undefined }
				}
			).then(handleSuccess, handleError);
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
