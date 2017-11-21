(function(){
	'use strict';

	angular.module("ARCOPM").factory('AdminFactory', AdminFactory);

	AdminFactory.$inject = ["$exceptionHandler",'$http','global'];

	function AdminFactory($exceptionHandler, $http, global) {

		var api = global.api;

		var factory = {};
		factory.getEmployee = getEmployee;
		factory.updateLocalUser = updateLocalUser;
		factory.GetProjects = GetProjects;
		factory.GetActiveCycles = GetActiveCycles;
		factory.GetReportingLine = GetReportingLine;
		factory.UpdateReportingLine = UpdateReportingLine;
		factory.GetEmployeeEvaluations = GetEmployeeEvaluations;
		factory.ResetEmployeeEvaluation = ResetEmployeeEvaluation;
		factory.DeleteFromARCOPM = DeleteFromARCOPM;
		factory.ResetLastState = ResetLastState;


		return factory;


		function getEmployee(filters)
		{
			return $http.post(api + '/localusers/',filters).then(handleSuccess, handleError);
		}

        function updateLocalUser(filters)
		{
			return $http.post(api + '/update-localusers/',filters).then(handleSuccess, handleError);
		}
		
		// Get projects.
		function GetProjects(userid)
		{
			return $http.get(api + '/projects/').then(handleSuccess, handleError);
		}
		
		// Get projects.
		function GetActiveCycles()
		{
			return $http.get(api + '/activecycles/').then(handleSuccess, handleError);
		}
		
		//Get reporting line
		function GetReportingLine(filters)
		{
			return $http.post(api + '/adminreportingline/',filters).then(handleSuccess, handleError);
		}
		
		//Update reporting line
		function UpdateReportingLine(settings)
		{
			return $http.post(api + '/adminreportinglineupdate/',settings).then(handleSuccess, handleError);
		}
		
		//Delete From ARCOPM
		function DeleteFromARCOPM(settings)
		{
			return $http.post(api + '/adminreportinglineremove/',settings).then(handleSuccess, handleError);
		}

		// Get employee evaluations
		function GetEmployeeEvaluations(empid)
		{
			return $http.get(api + '/employeeevaluations/' + empid).then(handleSuccess, handleError);
		}
		
		// Reset employee evaluation
		function ResetEmployeeEvaluation(evalid,userid,resetgoals)
		{
			return $http.post(api + '/resetevaluation/' + evalid + '/' + userid + '/' + resetgoals).then(handleSuccess, handleError);
		}
		
		// Reset evaluation to previous state
		function ResetLastState(evalid,userid)
		{
			return $http.post(api + '/resetlaststate/' + evalid + '/' + userid).then(handleSuccess, handleError);
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
