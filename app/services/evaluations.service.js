(function(){
	'use strict';

	angular.module("ARCOPM").factory('EvaluationsFactory', EvaluationsFactory);

	EvaluationsFactory.$inject = ["$exceptionHandler",'$http','global'];

	function EvaluationsFactory($exceptionHandler, $http, global) {

		var api = global.api;

		var factory = {};
		factory.GetEvaluations = GetEvaluations;
        factory.GetMyEvaluations = GetMyEvaluations;
		factory.GetEvaluationsCycles = GetEvaluationsCycles;
        factory.GetQuestions = GetQuestions;
		factory.GetScores = GetScores;
		factory.GetScoreScales = GetScoreScales;
		factory.GetDottedComments = GetDottedComments;
		factory.GetSections = GetSections;
        factory.GetEmpDetails = GetEmpDetails;
		factory.GetReportingLine = GetReportingLine;
        factory.GetUserReportingLine = GetUserReportingLine;
        factory.GetUserRole = GetUserRole;
		factory.GetEvaluationsStatistics = GetEvaluationsStatistics;
		factory.FileUpload =  FileUpload;
		factory.FileDelete =  FileDelete;
		factory.SetAnswers = SetAnswers;
		factory.UpdateEvaluation = UpdateEvaluation;
		factory.AddNewGoal = AddNewGoal;
		factory.GetGoals = GetGoals;
		factory.GetGoalsHistory = GetGoalsHistory;
		factory.GetMyGoalsPerCycle = GetMyGoalsPerCycle;
		factory.GetQuestionaireGoals = GetQuestionaireGoals;
		factory.GetUsersToSetGoals = GetUsersToSetGoals;
		factory.DeleteGoal = DeleteGoal;
		factory.UpdateGoal = UpdateGoal;
		factory.GetDevPlans = GetDevPlans;
		factory.AddNewDevPlan = AddNewDevPlan;
		factory.UpdateDevPlan = UpdateDevPlan;
		factory.DeleteDevPlan = DeleteDevPlan;
		factory.UpdateState = UpdateState;
		factory.GetGoalAttributes = GetGoalAttributes;
		factory.GetDevPlanHistory = GetDevPlanHistory;
        factory.UpdateDevelopmentPlanStatus = UpdateDevelopmentPlanStatus;
		factory.GetActiveGoalCycles = GetActiveGoalCycles;
		factory.UpdateGoalState = UpdateGoalState;
		factory.SetWrongManager = SetWrongManager;
		factory.RevertWrongManager = RevertWrongManager;
		factory.SendGoalsBack = SendGoalsBack;
		factory.ReviseEvaluations = ReviseEvaluations;
		factory.SaveComment = SaveComment;
		factory.GetComments = GetComments;
		factory.CloneSelectedGoals = CloneSelectedGoals;

		return factory;


		// Get my evaluations.
		function GetMyEvaluations(userid)
		{
			return $http.get(api + '/myevaluations/' + userid).then(handleSuccess, handleError);
		}

        function GetEvaluations(userid)
		{
			return $http.get(api + '/evaluations/' + userid).then(handleSuccess, handleError);
		}

		function GetEvaluationsCycles()
		{
			return $http.get(api + '/evaluationscycles/').then(handleSuccess, handleError);
		}

		//Get active cycles
		function GetActiveGoalCycles()
		{
			return $http.get(api + '/cycles/').then(handleSuccess, handleError);
		}

		// Get questions based on evaluation.
		function GetQuestions(evalID,state)
		{
			return $http.get(api + '/questions/' + evalID + '/' + state).then(handleSuccess, handleError);
		}

		// Get sections.
		function GetSections(evalID,userid,state)
		{
			return $http.get(api + '/sections/' + evalID + '/' + userid + '/' + state).then(handleSuccess, handleError);
		}

		// Get scores.
		function GetScores(evalID,userid,state)
		{
			return $http.get(api + '/scores/' + evalID + '/' + userid + '/' + state).then(handleSuccess, handleError);
		}
		
		// Get scores scales.
		function GetScoreScales(evalID)
		{
			return $http.get(api + '/scorescales/' + evalID).then(handleSuccess, handleError);
		}
		
		// Get gotted comments
		function GetDottedComments(evalID)
		{
			return $http.get(api + '/evaluations/dottedcomments/' + evalID).then(handleSuccess, handleError);
		}

		function GetDevPlans(evalID,state)
		{
			return $http.get(api + '/evaluations/devplan/'+evalID+'/'+state).then(handleSuccess, handleError);
		}

		function AddNewDevPlan(evalID,object,userid,state) {
            return $http.post(api + '/evaluations/addnewdevplan/'+evalID+'/'+userid+'/'+state,object).then(handleSuccess, handleError);
        }

		function UpdateDevPlan(devplan,userid) {
            return $http.post(api + '/evaluations/updatedevplan/'+userid,devplan).then(handleSuccess, handleError);
        }

		function DeleteDevPlan(devplanid) {
            return $http.post(api + '/evaluations/deletedevplan/'+devplanid).then(handleSuccess, handleError);
        }

		function GetDevPlanHistory(evalID)
		{
			return $http.get(api + '/evaluations/devplanhistory/'+evalID).then(handleSuccess, handleError);
		}
        
        function UpdateDevelopmentPlanStatus(devplan,userid)
		{
			return $http.post(api + '/evaluations/updatedevplanstatus/'+userid,devplan).then(handleSuccess, handleError);
		}

        function GetEmpDetails(evalID)
		{
			return $http.get(api + '/empdetails/' + evalID).then(handleSuccess, handleError);
		}

        function GetUserRole(evalID)
		{
			return $http.get(api + '/userrole/' + evalID).then(handleSuccess, handleError);
		}

		function GetReportingLine(evalID)
		{
			return $http.get(api + '/reportingline/' + evalID).then(handleSuccess, handleError);
		}

        function GetUserReportingLine(evalID,cycleid)
		{
			return $http.get(api + '/userreportingline/' + evalID + '/' + cycleid).then(handleSuccess, handleError);
		}

        function SetAnswers(evalID,object,state,finish,pause)
		{
			return $http.put(api + '/answers/' + evalID + '/' + state + '/' + finish + '/' + pause, object).then(handleSuccess, handleError);
		}

		// Get charts evaluations based on grade.
		function GetEvaluationsStatistics(userid)
		{
			return $http.get(api + '/evaluations/statistics/' + userid).then(handleSuccess, handleError);
		}

		function FileUpload(evaluation,cycle)
		{
			var formData = new FormData();
			formData.append("file",evaluation.fileToUpload);


			return $http.post(
				api + '/evaluations/documents/'+evaluation.EmployeeID+'/'+evaluation.EvaluationID+'/'+cycle,
				formData,
				{
					transformRequest: angular.identity,
					headers: { 'Content-Type': undefined }
				}
			).then(handleSuccess, handleError);
		}
		
		function FileDelete(evaluation){
			return $http.post(api + '/evaluations/documentsdelete/'+evaluation.EmployeeID+'/'+evaluation.EvaluationID, evaluation).then(handleSuccess, handleError);
		}

		function UpdateEvaluation(empid,managesTeam,userid,cycleid) {
            return $http.post(api + '/evaluations/updateevaluation/'+empid+'/'+managesTeam+'/'+userid+'/'+cycleid).then(handleSuccess, handleError);
        }

		//get goals inside evaluation form
		function GetQuestionaireGoals(empid,userid,evalid)
		{
			return $http.get(api + '/evaluations/questionairegoals/' + empid + '/' + userid + '/' + evalid).then(handleSuccess, handleError);
		}

		//get goals
		function GetGoals(empid,cycleid)
		{
			return $http.get(api + '/evaluations/goals/' + empid + '/' + cycleid).then(handleSuccess, handleError);
		}
		
		//get goals
		function GetGoalsHistory(empid,cycleid)
		{
			return $http.get(api + '/evaluations/goalshistory/' + empid + '/' + cycleid).then(handleSuccess, handleError);
		}

		//get my goals list
		function GetMyGoalsPerCycle(userid,cycleid)
		{
			return $http.get(api + '/evaluations/mygoalspercycle/' + userid + '/' + cycleid).then(handleSuccess, handleError);
		}

		//get goals per cycle
		function GetUsersToSetGoals(userid,cycleid)
		{
			return $http.get(api + '/evaluations/goalspercycle/' + userid + '/' + cycleid).then(handleSuccess, handleError);
		}

		function AddNewGoal(userid,empid,object,cycleid) {
            return $http.post(api + '/evaluations/addnewgoal/'+userid+'/'+empid+'/'+cycleid,object).then(handleSuccess, handleError);
        }

		function DeleteGoal(goalid) {
            return $http.post(api + '/evaluations/deletegoal/'+goalid).then(handleSuccess, handleError);
        }

		function UpdateGoal(goal,userid,goalstate) {
            return $http.post(api + '/evaluations/updategoal/'+userid,goal).then(handleSuccess, handleError);
        }

		function UpdateState(evalid,cycleid,userid,empid,onbehalf) {
            return $http.post(api + '/evaluations/updatestate/'+evalid+'/'+cycleid+'/'+userid+'/'+empid+'/'+onbehalf).then(handleSuccess, handleError);
        }

		function UpdateGoalState(cycleid,empid) {
            return $http.post(api + '/evaluations/updategoalstate/'+cycleid+'/'+empid).then(handleSuccess, handleError);
        }

		function GetGoalAttributes(){
			return $http.get(api + '/evaluations/goalattributes/').then(handleSuccess, handleError);
		}

		function SetWrongManager(empid,youraction) {
            return $http.post(api + '/evaluations/rejectmanager/'+empid+'/'+youraction).then(handleSuccess, handleError);
        }
		
		function RevertWrongManager(empid,youraction) {
            return $http.post(api + '/evaluations/revertmanager/'+empid+'/'+youraction).then(handleSuccess, handleError);
        }

		function SendGoalsBack(evalid) {
            return $http.post(api + '/evaluations/sendbackgoals/'+evalid).then(handleSuccess, handleError);
		}
		
		function ReviseEvaluations(evals,empid) {
            return $http.post(api + '/evaluations/revise/'+empid,evals).then(handleSuccess, handleError);
        }
		
		function SaveComment(evalid, userid, state, comment) {
            return $http.post(api + '/evaluations/goalssavecomment/'+evalid+'/'+userid+'/'+state,comment).then(handleSuccess, handleError);
        }
		
		function GetComments(evalid){
			return $http.get(api + '/evaluations/goalscomments/'+evalid).then(handleSuccess, handleError);
		}
		
		function CloneSelectedGoals(goals,evalid,userid) {
            return $http.post(api + '/evaluations/cloneselectedgoals/'+userid+'/'+evalid,goals).then(handleSuccess, handleError);
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
