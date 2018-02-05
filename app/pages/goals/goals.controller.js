(function () {

    "use strict";

    // Create the Controller
    angular.module("ARCOPM").controller('goalsController', goalsController);

    // Inject services to the Controller

    goalsController.$inject = ["$scope", "Auth", "loginData",  "global", "EvaluationsFactory", "ngDialog", "dataService", "$state", "$stateParams", "arcopmState"];



    // Controller Logic


    function goalsController($scope, Auth, loginData, global, EvaluationsFactory, ngDialog, dataService, $state, $stateParams, arcopmState) {
        $scope.message = "none";
		$scope.extraMessage = "none";
		$scope.plusMessage = "none";
		$scope.myGoals = false;
		$scope.employeesGoals = false;
		$scope.selected = [];
		$scope.listGoals = [];		//goals from employee and dotted
		$scope.goal = {};
		$scope.parseInt = parseInt;
		$scope.arcopmState = arcopmState;
		$scope.loggedinUser = loginData.user.id;
		$scope.addSlideEffect = false;


        // Initialize the evaluations
        $scope.init = function () {
            if (!$scope.checkLogin()) {
                return;
            }

            $scope.initNavigation();
			$scope.getActiveGoalCycles();
			
			if($stateParams.cycle){
				$scope.employeesGoals = $stateParams.employeesGoals;
				$scope.getGoalsPerCycle($stateParams.cycleID, $stateParams.cycle);
			}
        };


        // Initialize the Data service
        $scope.initNavigation = function () {
            $scope.setPage('evaluationGoals');
            global.navPages = [];
            global.navPages.push({
                name: "Goals",
                link: "evaluationGoals",
                activetab:"evaluationGoals",
                current: true
            });

        };


		//data
		$scope.getGoalsPerCycle = function(cycleid,cycledesc){
			if (!$scope.checkLogin()) {
                return;
            }
			$scope.extraMessage = 'loading';
			$scope.filterDesc1 = cycledesc;
			EvaluationsFactory.GetUsersToSetGoals(loginData.user.id,cycleid).then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.cycleGoals = result.CycleGoals;
				$scope.extraMessage = 'none';
            });
		};

		$scope.getMyGoalsPerCycle = function(cycleid, cycledesc){
			if (!$scope.checkLogin()) {
                return;
            }
			$scope.extraMessage = 'loading';
			$scope.filterDesc = cycledesc;
			EvaluationsFactory.GetMyGoalsPerCycle(loginData.user.id,cycleid).then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.personalCycleGoals = result.CycleGoals;
				$scope.extraMessage = 'none';
            });
		};


		$scope.getMyEvaluations = function(){
			if (!$scope.checkLogin()) {
                return;
            }
			EvaluationsFactory.GetMyEvaluations(loginData.user.id).then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.personalevaluations = result.myevaluations;
            });
		};

		$scope.getActiveGoalCycles = function(){
			if (!$scope.checkLogin()) {
                return;
            }
			EvaluationsFactory.GetActiveGoalCycles().then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.cycles = result.activeGoalCycles;
            });
		};


		$scope.reportingLineDialog = function(goal){
			if (!$scope.checkLogin()) {
                return;
            }
            $scope.message = "loading";
			EvaluationsFactory.GetUserReportingLine(goal.Empno,goal.CycleID).then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.empReportingLine = result.empReportingLine;
				$scope.message = "none";
            });
			$scope.goalObject = goal;
			$scope.todoPopup = ngDialog.open({
				template: 'app/pages/goals/popup/goals.reportingline.popup.html',
				className: 'ngdialog-theme-default',
				scope: $scope
			});
		};


		$scope.showMessage = function (message) {
            if ($scope.message === message) {
                return true;
            }

            return false;
        };


		$scope.showExtraMessage = function (message) {
            if ($scope.extraMessage === message) {
                return true;
            }

            return false;
        };
		
		
		$scope.showPlusMessage = function (message) {
            if ($scope.plusMessage === message) {
                return true;
            }

            return false;
        };


		$scope.changeExtraMessage = function(message){
			$scope.extraMessage = message;
		};


        // Shows Evaluation Configuration popup
        $scope.showEvalConfiguration = function (goal,from,onbehalf) {
            if (!$scope.checkLogin()) {
                return;
            }
            $scope.message = "loading";
			$scope.cycleGoal = goal;
			$scope.evalID = goal.EvaluationID;
			$scope.from = from;
			$scope.onbehalf = onbehalf;
			if(goal.yourActionState == 4){
				$scope.role = 'dotted';
				$scope.active = 1;
			}else if(goal.yourActionState == 5){
				$scope.role = 'eval';
				$scope.active = 2;
			}else{
				$scope.role = 'emp';
				$scope.active = 0;
			}

            $scope.goalConfigurationPopup = ngDialog.open({
                template: 'app/pages/goals/popup/configuration.popup.html',
                className: 'ngdialog-theme-default',
                scope: $scope
            });

        };


		// Shows add New goal popup
        $scope.showAddNewGoalPopup = function (goals,cycleGoal,role,evalid) {
            if (!$scope.checkLogin()) {
                return;
            }
            $scope.extraMessage = 'none';
			$scope.goal = cycleGoal;
			$scope.goal.Weight = 1;
			$scope.goal.attributeCode = 'N';
			$scope.tempRole = role;
			$scope.tempGoals = goals;
			$scope.evID = evalid;
			$scope.getGoalAttributes();

            $scope.todoPopup = ngDialog.open({
                template: 'app/pages/goals/popup/goals.new.goal.popup.html',
                className: 'ngdialog-theme-default',
                scope: $scope
            });
        };


		// Shows edit goal popup
        $scope.showEditGoalPopup = function (goal,role) {
            if (!$scope.checkLogin()) {
                return;
            }
            $scope.extraMessage = 'none';
			$scope.tempGoal = goal;
			$scope.tempRole = role;
			$scope.prevWeight = goal.Weight;
			//store also these values to a temporary object(previous) in order to have them when someone cancels an edit goal action (exit button in edit goal)
			$scope.prevGoalDescr = goal.GoalDescription;
			$scope.prevAttrCode = goal.AttributeCode;
			//we calculate remainingWeight only for employee
            if(role == 'emp'){
                var totalRemainingWeight = parseInt($scope.remainingWeight)+parseInt(prevWeight);
                $scope.totalRemainingWeight = totalRemainingWeight;
            }else{
                $scope.totalRemainingWeight = 100;
            }
			$scope.getGoalAttributes();

            $scope.todoPopup = ngDialog.open({
                template: 'app/pages/goals/popup/goals.edit.goal.popup.html',
                className: 'ngdialog-theme-default',
                scope: $scope
            });
        };


		// Shows delete goal popup
        $scope.showDeleteGoalPopup = function (goal,role) {
            if (!$scope.checkLogin()) {
                return;
            }
            $scope.extraMessage = 'warning';
			$scope.tempGoal = goal;
			$scope.tempRole = role;

            $scope.todoPopup = ngDialog.open({
                template: 'app/pages/goals/popup/goals.delete.goal.popup.html',
                className: 'ngdialog-theme-default',
                scope: $scope
            });
        };


		$scope.showEvalPreview = function (goal) {
            if (!$scope.checkLogin()) {
                return;
            }
            $scope.message = "loading";
            $scope.cycleGoal = goal;

            $scope.evalPreviewPopup = ngDialog.open({
                template: 'app/pages/goals/popup/goals.evaluationpreview.popup.html',
                className: 'ngdialog-theme-default eval-prev',
                scope: $scope
            });

        };


		$scope.updateEvaluation = function(managesTeam,empid,cycleid){
			if (!$scope.checkLogin()) {
                return;
            }

			EvaluationsFactory.UpdateEvaluation(empid,managesTeam,loginData.user.id,cycleid).then(function (result) {
				$scope.checkifLoggedout(result);
				if (result.success) {
					$scope.extraMessage = 'created';
					$scope.managesteam = managesTeam;
                    $scope.cycleGoal.EvaluationID = result.evalid;
					$scope.todoPopup = ngDialog.open({
						template: 'app/pages/goals/popup/goals.employeemanagesteam.save.popup.html',
						className: 'ngdialog-theme-default',
						scope: $scope
					});
				} else {
					$scope.extraMessage = 'error';
					$scope.extraMessageText = 'Something went wrong while saving your selection. Please contact your administrator.';

				}
			});
		};
		
		//retrieve all goals of employee(history)
		$scope.getGoalsHistory = function(cycleGoalObj){
			if (!$scope.checkLogin()) {
                return;
            }

			var empno = cycleGoalObj.Empno;
			var cycleid = cycleGoalObj.CycleID;
			EvaluationsFactory.GetGoalsHistory(empno,cycleid).then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.allGoals = result.EmpGoals;
				
				//console.log(weight);
				$scope.message = "none";
            });
		};

		//retrieve all goals of employee
		$scope.getGoals = function(cycleGoalObj,role){
			if (!$scope.checkLogin()) {
                return;
            }
			var weightEmp = 0, weightDotted = 0, weightEval = 0;
			var goalLimit = 0;
			var empno = cycleGoalObj.Empno;
			var cycleid = cycleGoalObj.CycleID;
			$scope.showAddNewGoalButtonEmp = true;
			$scope.showAddNewGoalButtonDotted = true;
			$scope.showAddNewGoalButtonEval = true;
			$scope.totalWeight = 0;
			EvaluationsFactory.GetGoals(empno,cycleid).then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.goals = result.EmpGoals;
				$scope.listGoals = [];
				var empGoalsCnt=0, dottedGoalsCnt=0, evalGoalsCnt=0;
				angular.forEach(result.EmpGoals, function(value) {
					if(value.GoalState==0){
						weightEmp = weightEmp + parseInt(value.Weight);
						empGoalsCnt++;
						if(value.GoalExists==0) ($scope.listGoals).push(value.GoalID);
					}else if(value.GoalState==1){
						weightDotted = weightDotted + parseInt(value.Weight);
						dottedGoalsCnt++;
						if(value.GoalExists==0) ($scope.listGoals).push(value.GoalID);
					}else if(value.GoalState==2){
						weightEval = weightEval + parseInt(value.Weight);
						evalGoalsCnt++;
					}
				});
				
				//if role is dotted we don't care about remaining weight
				if(role == 'emp'){
					$scope.remainingWeight = 100 - weightEmp;
				}else if(role=='eval'){
					$scope.remainingWeight = 100 - weightEval;
				}else{
					$scope.remainingWeight = 100;
				}
				//Grades 1-3 cannot have goals, so i make remainingWeight=0 in order to activate the 'Start Evaluation' button.
				//Grades 4-9 can have 5 goals. Grades 10+ can have 6 goals.
				if(cycleGoalObj.grade <= 3){
					$scope.remainingWeight = 0;
				}else if(cycleGoalObj.grade >=4 && cycleGoalObj.grade <=9){
					goalLimit = 5;
				}else if(cycleGoalObj.grade >=10){
					goalLimit = 6;
				}

				//Add new goal button must disappear when goals have reached goalLimit or totalWeight is equal to 100 and we have at least one goal added.
				//apart from dotted in whom the only limit is the number of goals
				if(role == 'emp'){
					if((empGoalsCnt == goalLimit || weightEmp == 100) && empGoalsCnt != 0){
						$scope.showAddNewGoalButtonEmp = false;
					}
				}else if(role == 'dotted'){
					if(dottedGoalsCnt == goalLimit){
						$scope.showAddNewGoalButtonDotted = false;
					}
				}else if(role == 'eval'){
					if((evalGoalsCnt == goalLimit || weightEval == 100) && evalGoalsCnt != 0){
						$scope.showAddNewGoalButtonEval = false;
					}
				}
				//console.log(weight);
				$scope.message = "none";
            });
		};

		$scope.getUserGoals = function(empid,evalid){
			if (!$scope.checkLogin()) {
                return;
            }
			EvaluationsFactory.GetQuestionaireGoals(empid,loginData.user.id,evalid).then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.goals = result.goals;
            });
		};
		
		$scope.getQuestions = function(evalid,state){
			if (!$scope.checkLogin()) {
                return;
            }
			EvaluationsFactory.GetQuestions(evalid,state).then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.questions = result.questions;
				$scope.message = 'none';
            });
		};

		$scope.getSections = function(evalid,state){
			if (!$scope.checkLogin()) {
                return;
            }
			EvaluationsFactory.GetSections(evalid,loginData.user.id,state).then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.questionnaireSections = result.sections;
            });
		};


        //Create new Goal function
        $scope.addNewGoal = function (tempGoal,role) {
            if (!$scope.checkLogin()) {
                return;
            }

            $scope.extraMessage = 'loading';
			var empid = tempGoal.Empno;
			var cycleid = tempGoal.CycleID;
			//goal can be added only if weight is lower or equal to the remaining Weight
			if(tempGoal.Weight <= $scope.remainingWeight){
				EvaluationsFactory.AddNewGoal(loginData.user.id,empid,tempGoal,cycleid).then(function (result) {
					$scope.checkifLoggedout(result);
					if (result.success) {
						//evalObj.EvaluationID = result.evalid;
						$scope.getGoals(tempGoal,role);
						$scope.extraMessage = 'created';
					} else {
						$scope.extraMessage = 'error';
						$scope.extraMessageText = 'Something went wrong while creating a new Goal. Please contact your administrator.';

					}
				});
			}else{
				$scope.extraMessage = 'warning';
			}
            return;
        };
		
		
		//Add goals from existing list(only in evaluator)
		$scope.cloneSelectedGoals = function(selectedGoals,evalid,cycleGoal,role){
			$scope.extraMessage = 'loading';
			EvaluationsFactory.CloneSelectedGoals(selectedGoals,evalid,loginData.user.id).then(function (result) {
				$scope.checkifLoggedout(result);
				if (result.success) {
					$scope.selected = [];
					$scope.getGoals(cycleGoal,role);
					$scope.extraMessage = 'created';
				} else {
					$scope.extraMessage = 'error';
					$scope.extraMessageText = 'Something went wrong while creating a new Goal. Please contact your administrator.';

				}
			});
		};

		//Delete goal function
		$scope.deleteGoal = function(goal,role){
			if (!$scope.checkLogin()) {
                return;
            }
            $scope.extraMessage = 'loading';

			EvaluationsFactory.DeleteGoal(goal.GoalID).then(function (result) {
				$scope.checkifLoggedout(result);
                if (result.success) {
                    $scope.getGoals(goal,role);
                    $scope.extraMessage = 'deleted';
                } else {
                    $scope.extraMessage = 'error';
                    $scope.extraMessageText = 'Something went wrong while deleting Goal. Please contact your administrator.';

                }
            });
            return;
		};


		$scope.editGoal = function(editGoal,prevWeight,role){
			if (!$scope.checkLogin()) {
                return;
            }
            $scope.extraMessage = 'loading';

            //we calculate remainingWeight only for employee
            if(role == 'emp'){
                var totalRemainingWeight = parseInt($scope.remainingWeight)+parseInt(prevWeight);
                
            }else{
                var totalRemainingWeight = 100;
            }
            
            $scope.totalRemainingWeight = totalRemainingWeight;
            
			//goal can be added only if weight is lower or equal to the remaining Weight plus the previous weight of the record we are trying to modify.
			if(editGoal.Weight <= totalRemainingWeight){
				EvaluationsFactory.UpdateGoal(editGoal,loginData.user.id).then(function (result) {
					$scope.checkifLoggedout(result);
					if (result.success) {
						$scope.getGoals(editGoal,role);
						$scope.extraMessage = 'created';
					} else {
						$scope.extraMessage = 'error';
						$scope.extraMessageText = 'Something went wrong while creating a new Goal. Please contact your administrator.';

					}
				});
			}else{
				$scope.extraMessage = 'warning';
				$scope.getGoals(editGoal);
			}
            return;
		};

		//Updating state function after pressing 'Submit Goals' button in configuration popup
		$scope.updateState = function(goal,onbehalf){
			if (!$scope.checkLogin()) {
                return;
            }
            //$scope.extraMessage = 'loading';
            $scope.message = 'loading';

			EvaluationsFactory.UpdateState(0,goal.CycleID,loginData.user.id,goal.Empno,onbehalf).then(function (result) {
				$scope.checkifLoggedout(result);
				if (result.success) {
					$scope.cycleGoal.EvalState = result.evaluation.State;
					$scope.cycleGoal.EvaluationID = result.evaluation.EvaluationID;
                    $scope.cycleGoal.yourActionStateDescr = result.evaluation.yourActionStateDescr;
                    $scope.goals = [];
					//$scope.extraMessage = 'none';
					$scope.message = 'created';
				} else {
					$scope.message = 'error';
					$scope.messageText = 'Something went wrong while creating a new Goal. Please contact your administrator.';

				}
			});
		};


		$scope.showRejectManagerPopup = function(cyclegoal){
			if (!$scope.checkLogin()) {
                return;
            }
            $scope.extraMessage = 'warning';
			$scope.tempCyclegoal= cyclegoal;

            $scope.todoPopup = ngDialog.open({
                template: 'app/pages/goals/popup/goals.reject.manager.popup.html',
                className: 'ngdialog-theme-default',
                scope: $scope
            });
		};


		$scope.rejectManager = function(cycleGoal){
			if (!$scope.checkLogin()) {
                return;
            }

			var empid = cycleGoal.Empno;
			var yourActionState = cycleGoal.yourActionState;
			EvaluationsFactory.SetWrongManager(empid,yourActionState).then(function (result) {
				$scope.checkifLoggedout(result);
				if (result.success) {
					$scope.extraMessage = 'deleted';
					cycleGoal.wrongManager = '1';
				} else {
					$scope.extraMessage = 'error';
					$scope.extraMessageText = 'Something went wrong while saving your selection. Please contact your administrator.';

				}
			});
		};
		
		
		$scope.showRevertRejectionPopup = function(cyclegoal){
			if (!$scope.checkLogin()) {
                return;
            }
            $scope.extraMessage = 'warning';
			$scope.tempCyclegoal= cyclegoal;

            $scope.todoPopup = ngDialog.open({
                template: 'app/pages/goals/popup/goals.revert.manager.popup.html',
                className: 'ngdialog-theme-default',
                scope: $scope
            });
		};
		
		$scope.sendGoalsBack = function(cycleGoal){
			if (!$scope.checkLogin()) {
                return;
            }

            $scope.extraMessage = 'loading';
			var evalid = cycleGoal.EvaluationID;
			EvaluationsFactory.SendGoalsBack(evalid).then(function (result) {
				$scope.checkifLoggedout(result);
				if (result.success) {
					$scope.extraMessage = 'updated';
					cycleGoal.EvalState  = 0;
				} else {
					$scope.extraMessage = 'error';
					$scope.extraMessageText = 'Something went wrong while saving your selection. Please contact your administrator.';

				}
			});
		};

		$scope.showSendBackPopup = function(cycleGoal){
			if (!$scope.checkLogin()) {
                return;
            }
            $scope.extraMessage = 'warning';
			$scope.tempCyclegoal= cycleGoal;

            $scope.todoPopup = ngDialog.open({
                template: 'app/pages/goals/popup/goals.sendBack.popup.html',
                className: 'ngdialog-theme-default',
                scope: $scope
            });
		};
		
		$scope.showSendForwardPopup = function(goal){
			if (!$scope.checkLogin()) {
                return;
            }
            $scope.message = 'warning';
			$scope.cycleGoal= goal;

            $scope.todoPopup = ngDialog.open({
                template: 'app/pages/goals/popup/goals.sendForward.popup.html',
                className: 'ngdialog-theme-default',
                scope: $scope
            });
		};

		$scope.revertManager = function(cycleGoal){
			if (!$scope.checkLogin()) {
                return;
            }

			var empid = cycleGoal.Empno;
			var yourActionState = cycleGoal.yourActionState;
			EvaluationsFactory.RevertWrongManager(empid,yourActionState).then(function (result) {
				$scope.checkifLoggedout(result);
				if (result.success) {
					$scope.extraMessage = 'reverted';
					cycleGoal.wrongManager = '0';
				} else {
					$scope.extraMessage = 'error';
					$scope.extraMessageText = 'Something went wrong while saving your selection. Please contact your administrator.';

				}
			});
		};

		$scope.checkifLoggedout = function (result){
			if (result==401){
				$scope.logout();
				return;
			}
		};


		$scope.noNullWeight = function(){
			if(angular.isUndefined($scope.goal.Weight)){
				$scope.goal.Weight = 1;
			}
			else if($scope.goal.Weight>$scope.remainingWeight)
			{
				$scope.goal.Weight = $scope.remainingWeight;
			}
		};


		$scope.noNullTempWeight = function(){
			if(angular.isUndefined($scope.tempGoal.Weight)){
				$scope.tempGoal.Weight = 1;
			}
			else if($scope.tempGoal.Weight>$scope.totalRemainingWeight)
			{
				$scope.tempGoal.Weight = $scope.totalRemainingWeight;
			}
		};


		$scope.getGoalAttributes = function(){
			if (!$scope.checkLogin()) {
                return;
            }
			EvaluationsFactory.GetGoalAttributes().then(function (result) {
				var tempResult = result.goalAttributes;
				
				//remove N/A from possible attribute options
				angular.forEach(result.goalAttributes, function(value,key) {
					if(value.AttributeCode == "N"){
						tempResult.splice(key, 1);
					}
				});

				$scope.attributes = tempResult;
			});
		};
		
		
		$scope.generatePdf = function(){
			kendo.drawing.drawDOM($("#exportthis"),{paperSize:"A3"	}).then(function(group) {
				kendo.drawing.pdf.saveAs(group, "goals.pdf");
			});
		};
		
		
		$scope.toggle = function (item, list) {
			var idx = list.indexOf(item);
			if (idx > -1) {
			  list.splice(idx, 1);
			}
			else {
			  list.push(item);
			}
		};
		
		
		$scope.exists = function (item, list) {
			return list.indexOf(item) > -1;
		};


		$scope.isIndeterminate = function() {
			return ($scope.selected.length !== 0 &&
			$scope.selected.length !== $scope.listGoals.length);
		};


		$scope.isChecked = function() {
			return $scope.selected.length === $scope.listGoals.length;
		};
		
		
		$scope.toggleAll = function() {
            if ($scope.selected.length === $scope.listGoals.length) {
                $scope.selected = [];
            } else if ($scope.selected.length === 0 || $scope.selected.length > 0) {
                $scope.selected = $scope.listGoals.slice(0);
            }
		};
        
        //Deselect all goals on closing of 'add new goal' dialog
        $scope.deselectAll = function(){
            var list = angular.copy($scope.selected);
            angular.forEach(list, function(value) {
                var idx = ($scope.selected).indexOf(value);
                if (idx > -1) {
                    ($scope.selected).splice(idx, 1);
                }
            });
        };
		
		
		$scope.showGoalPreviewPopup = function(goals,cycleGoal,arcopmstate){
			if (!$scope.checkLogin()) {
                return;
            }
            $scope.extraMessage = 'warning';
			$scope.tempGoals = goals;
			$scope.tempCycleGoal = cycleGoal;
            $scope.arcopm_state = arcopmstate;

            $scope.todoPopup = ngDialog.open({
                template: 'app/pages/goals/popup/goals.pdfPreview.popup.html',
                className: 'ngdialog-theme-default',
                scope: $scope
            });
		};
		
		
		$scope.showGoalsHistoryPopup = function(cycleGoal){
			if (!$scope.checkLogin()) {
                return;
            }
            $scope.extraPlusMessage = 'loading';
			$scope.getGoalsHistory(cycleGoal);
			$scope.tempCycleGoal= cycleGoal;

            $scope.todoPopup = ngDialog.open({
                template: 'app/pages/goals/popup/goals.history.popup.html',
                className: 'ngdialog-theme-default',
                scope: $scope
            });
		};
		
		
		// Shows comments popup
        $scope.showCommentsPopup = function (goal,mode) {
            if (!$scope.checkLogin()) {
                return;
            }
            $scope.extraMessage = 'loading';
			$scope.textComment = false;
			$scope.tempGoal = goal;
			$scope.commentsMode = mode;

            $scope.todoPopup = ngDialog.open({
                template: 'app/pages/goals/popup/goals.comments.popup.html',
                className: 'ngdialog-theme-default',
                scope: $scope
            });
        };
		
		
		$scope.getComments = function(evalid){
			if (!$scope.checkLogin()) {
                return;
            }
			EvaluationsFactory.GetComments(evalid).then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.comments = result.comments;
				$scope.extraMessage = 'none';
            });
		};
		
		
		$scope.saveComment = function(goal,newcomment){
			if (!$scope.checkLogin()) {
                return;
            }
            $scope.extraMessage = 'loading';

			EvaluationsFactory.SaveComment(goal.EvaluationID,loginData.user.id,goal.EvalState,newcomment).then(function (result) {
				$scope.checkifLoggedout(result);
				if (result.success) {
					$scope.getComments(goal.EvaluationID);
					$scope.extraMessage = 'none';
				} else {
					$scope.message = 'error';
					$scope.messageText = 'Something went wrong while creating a new Goal. Please contact your administrator.';

				}
			});
			return;
		};
		
		
		$scope.sumWeight = function(weight){
			$scope.totalWeight = parseInt($scope.totalWeight) + parseInt(weight); 
		};
        
        
        /* -------FILTERS--------- */
        //return goals with goalState
		$scope.goalsState = function(state){
			return function(item){
				return item.GoalState == state;
			};
		};
        
        //return goals with lower goalState
        $scope.lowerGoalsState = function(state){
			return function(item){
				return item.GoalState < state;
			};
		};

    }


})();
