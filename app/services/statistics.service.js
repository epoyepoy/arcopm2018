(function(){
	'use strict';

	angular.module("ARCOPM").factory('StatisticsFactory', StatisticsFactory);

	StatisticsFactory.$inject = ["$exceptionHandler",'$http','global'];

	function StatisticsFactory($exceptionHandler, $http, global) {

		var api = global.api;

		var factory = {};
		factory.GetEvaluators = GetEvaluators;
		factory.GetEvaluatorsEvals = GetEvaluatorsEvals;
		factory.GetEvaluatorsAvgTendency = GetEvaluatorsAvgTendency;
		factory.GetPlotChart = GetPlotChart;
		factory.GetBellShapedChart = GetBellShapedChart;
		factory.GetChartsDataAvgTendency = GetChartsDataAvgTendency;
		factory.GetCompanyStats = GetCompanyStats;
		factory.GetCompanyStatsByRegion = GetCompanyStatsByRegion;
		factory.GetCompanyStatsByQuestion = GetCompanyStatsByQuestion;
		factory.GetSatisfactionByQuestion = GetSatisfactionByQuestion;
		factory.GetSatisfactionByGradeQuestion = GetSatisfactionByGradeQuestion;
		factory.GetEvaluationPeriods = GetEvaluationPeriods;
		factory.GetScoresPerSection = GetScoresPerSection;
		factory.GetFamilies = GetFamilies;

		return factory;



        function GetEvaluators(userid)
		{
			return $http.get(api + '/evaluators/' + userid).then(handleSuccess, handleError);
		}

		function GetEvaluatorsEvals(filters)
		{
			return $http.post(api + '/evaluatorsevals/',filters).then(handleSuccess, handleError);
		}
		
		function GetEvaluatorsAvgTendency(filters)
		{
			return $http.post(api + '/evaluatorsavgtendency/',filters).then(handleSuccess, handleError);
		}
		
		function GetPlotChart(filters)
		{
			return $http.post(api + '/plotchart/',filters).then(handleSuccess, handleError);
		}
		
		function GetBellShapedChart(filters)
		{
			return $http.post(api + '/bellshapedchart/',filters).then(handleSuccess, handleError);
		}
		
		function GetChartsDataAvgTendency(filters)
		{
			return $http.post(api + '/chartsdataavgtendency/',filters).then(handleSuccess, handleError);
		}
		
		function GetCompanyStats(filters)
		{
			return $http.post(api + '/companystats/',filters).then(handleSuccess, handleError);
		}
		
		function GetCompanyStatsByRegion(filters)
		{
			return $http.post(api + '/companystatsbyregion/',filters).then(handleSuccess, handleError);
		}
		
		function GetCompanyStatsByQuestion(filters)
		{
			return $http.post(api + '/companystatsbyquestion/',filters).then(handleSuccess, handleError);
		}
		
		function GetScoresPerSection(filters)
		{
			return $http.post(api + '/scorespersection/',filters).then(handleSuccess, handleError);
		}

		function GetSatisfactionByQuestion(filters)
		{
			return $http.post(api + '/satisfactionbyquestion/',filters).then(handleSuccess, handleError);
		}
		
		function GetSatisfactionByGradeQuestion(filters)
		{
			return $http.post(api + '/satisfactionbygradequestion/',filters).then(handleSuccess, handleError);
		}
		
		function GetEvaluationPeriods(){
			return $http.get(api + '/evaluationperiods/').then(handleSuccess, handleError);
		}
		
		function GetFamilies(userid){
			return $http.get(api + '/getfamilies/'+userid).then(handleSuccess, handleError);
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
