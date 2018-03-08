(function () {

    "use strict";

    // Create the Controller
    angular.module("ARCOPM").controller('evaluationFormController', evaluationFormController);

    // Inject services to the Controller

    evaluationFormController.$inject = ["$scope","$compile", "Auth", "loginData",  "global", "EvaluationsFactory", "ngDialog", "dataService", "$state", "arcopmState"];

    // Controller Logic
    function evaluationFormController($scope,$compile, Auth, loginData, global, EvaluationsFactory, ngDialog, dataService, $state, arcopmState) {
		$scope.message = "loading";
		$scope.parseInt = parseInt;

        $scope.userRole ='';
		$scope.goal = {};
		$scope.scoreClasses = ['info','active','warning','danger'];
		$scope.scoreDefinition = ['Performance Improvement Needed','Building Capability','Achieving Performance','Leading Performance'];
        $scope.src ="http://asd.com/asd/asd.jpg";

        $scope.evaluation = dataService.getEvaluationID();
		$scope.behalfUser = dataService.getBehalfUser();
		$scope.empid = dataService.getEmpID();
		$scope.userid = loginData.user.id;
		$scope.state = dataService.getState();
		$scope.resume = dataService.getResume();
		$scope.list = dataService.getFromList();
        $scope.hasDotted = dataService.getHasDotted();
        $scope.arcopmState = arcopmState;
        $scope.parseFloat = parseFloat;


        // Initialize the evaluations
        $scope.init = function () {
            if (!$scope.checkLogin()) {
                return;
            }

            $scope.initNavigation();
        };


        // Initialize the Data service
        $scope.initNavigation = function () {
            $scope.setPage('evaluationForm');
            global.navPages = [];
             global.navPages.push({
                name: "Evaluations",
                link: "evaluationLists",
                activetab:"evaluationLists",
                current: false
            });
            global.navPages.push({
                name: "Evaluation Form",
                link: "evaluationForm",
                activetab:"evaluationLists",
                current: true
            });
            $scope.getEmpDetails($scope.evaluation);
            $scope.getUserRole($scope.evaluation);
			//$scope.getDevPlans($scope.evaluation,$scope.state);
            $scope.getReportingLine($scope.evaluation);
            $scope.getQuestions($scope.evaluation,$scope.state);
			$scope.getSections($scope.evaluation,$scope.state);
			$scope.getScores($scope.evaluation,$scope.state);
            $scope.getDottedScores($scope.evaluation);
			$scope.getScoreScales($scope.evaluation,$scope.state);
			if($scope.list != 'mylist') $scope.getDottedAnswers($scope.evaluation);
			($scope.empid == $scope.userid) ? $scope.myevaluation = true : $scope.myevaluation = false;
		};


		$scope.getQuestions = function(evalid,state){
			if (!$scope.checkLogin()) {
                return;
            }
			EvaluationsFactory.GetQuestions(evalid,state).then(function (result) {
				$scope.checkifLoggedout(result);
				var i = 0;
				var questionsCounter = 0;
				var section = 0;
				$scope.questions = result.questions;
				angular.forEach(result.questions, function(value) {
					if(value.SectionID != section){
						//sections counter
						section = value.SectionID;
						i++;
					}
					questionsCounter++;
				});
				$scope.sections = i;
            });
		};


		$scope.getSections = function(evalid,state){
			if (!$scope.checkLogin()) {
                return;
            }
			EvaluationsFactory.GetSections(evalid,loginData.user.id,state).then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.questionnaireSections = result.sections;

				//we add here the removal of loading because is the last query call of the controller in $scope.initNavigation
				$scope.message = 'none';
				var pendingAnswers = false;
                if(state != arcopmState.EvalByDotted){
                    angular.forEach(result.sections, function(value) {
                        if(value.PendingAnswers != 0){
                            //sections counter
                            pendingAnswers = true;
                        }
                        $scope.pendingAnswers = pendingAnswers;
                    });
                }
            });
		};


		$scope.getScores = function(evalid,state){
			if (!$scope.checkLogin()) {
                return;
            }
			$scope.scoresMessage = 'loading';
			EvaluationsFactory.GetScores(evalid,loginData.user.id,state).then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.scoresMessage = 'none';
				$scope.scores = result.evalScores;
				var totalEmpScore = 0; var totalEmpWeightScore = 0;
                var totalDotScore = 0; var totalDotWeightScore = 0;
				var totalEvalScore = 0; var totalEvalWeightScore = 0;
				var totalRevScore = 0; var totalRevWeightScore = 0;
				var totalWeight = 0;
				var i = 0;
				angular.forEach(result.evalScores, function(value) {
					i++;
					totalWeight += parseFloat(value.ScoreWeight);
					totalEmpScore += parseFloat(value.EmpScore);
                    totalDotScore += parseFloat(value.DotScore);
					totalEvalScore += parseFloat(value.EvalScore);
					totalRevScore += parseFloat(value.RevScore);
					totalEmpWeightScore +=  parseFloat($scope.roundUp(value.EmpScore * value.ScoreWeight));
                    totalDotWeightScore +=  parseFloat($scope.roundUp(value.DotScore * value.ScoreWeight));
					totalEvalWeightScore +=  parseFloat($scope.roundUp(parseFloat(value.EvalScore * value.ScoreWeight)));
					//console.log($scope.roundUp(parseFloat(value.EvalScore * value.ScoreWeight)));
					totalRevWeightScore +=   parseFloat($scope.roundUp(parseFloat(value.RevScore * value.ScoreWeight)));
				});
				$scope.totalWeight = totalWeight;
				$scope.averageEmpScore = totalEmpScore/i;
                $scope.averageDotScore = totalDotScore/i;
				$scope.averageEvalScore = totalEvalScore/i;
				$scope.averageRevScore = totalRevScore/i;
				$scope.totalEmpWeightScore = totalEmpWeightScore;
                $scope.totalDotWeightScore = totalDotWeightScore;
				$scope.totalEvalWeightScore = totalEvalWeightScore;
				$scope.totalRevWeightScore = totalRevWeightScore;
            });
		};
        
        
        $scope.getDottedScores = function(evalid){
            if (!$scope.checkLogin()) {
                return;
            }
			EvaluationsFactory.GetDottedScores(evalid).then(function (result) {
				$scope.dottedScores = result.dottedScores;
            });
        };


		$scope.roundUp = function(score){
		return (Math.round(score * 100) / 100).toFixed(2);
		};

		$scope.getScoreScales = function(evalid,state){
			if (!$scope.checkLogin()) {
                return;
            }
			$scope.scoresMessage = 'loading';
			EvaluationsFactory.GetScoreScales(evalid).then(function (result) {
				$scope.scoreScales = result.scoreScales[0];
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


//		$scope.getDevPlans = function(evalid,state){
//			if (!$scope.checkLogin()) {
//                return;
//            }
//			$scope.showAddNewDevplanButton = true;
//			EvaluationsFactory.GetDevPlans(evalid,state).then(function (result) {
//				$scope.checkifLoggedout(result);
//				$scope.devplans = result.developmentPlan;
//				var i=0;
//				angular.forEach(result.developmentPlan, function(value) {
//					i++;
//				});
//				if(i == 2 && $scope.employeeGrade < 10){
//					$scope.showAddNewDevplanButton = false;
//				}
//				if(i == 3 && $scope.employeeGrade >= 10){
//					$scope.showAddNewDevplanButton = false;
//				}
//            });
//		};


		$scope.getDevPlanHistory = function(evalid){
			if (!$scope.checkLogin()) {
                return;
            }
			$scope.showAddNewDevplanButton = true;
			EvaluationsFactory.GetDevPlanHistory(evalid).then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.historydevplans = result.developmentPlan;
                var i=0;
				angular.forEach(result.developmentPlan, function(value) {
                    if(value.State == $scope.state && value.UserID == $scope.userid) i++;
				});
				if(i == 2 && $scope.employeeGrade < 10){
					$scope.showAddNewDevplanButton = false;
				}
				if(i == 3 && $scope.employeeGrade >= 10){
					$scope.showAddNewDevplanButton = false;
				}
            });
		};


		$scope.getRowColor = function(state){
			if(state == arcopmState.EvalByEmployee){ return 'background-color : #ececec'; }
			if(state == arcopmState.EvalByDotted){ return 'background-color : #ffcc84'; }
			if(state == arcopmState.EvalByEvaluator){ return 'background-color : #c5e0b6'; }
		};


        $scope.getEmpDetails = function(evalid){
			if (!$scope.checkLogin()) {
                return;
            }
			EvaluationsFactory.GetEmpDetails(evalid).then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.empDetails = result.empDetails;
				$scope.employeeGrade = $scope.empDetails.empGrade;
                $scope.getDevPlanHistory(evalid);

				$scope.activeSection =1;				//indicates the active section
				//we set activeSection to 2 when employee has grade less tha 4
				//we set activeSection to resume variable(if exists), in order to return to the section we paused evaluation

				if(	dataService.getResume() != 0){
					$scope.activeSection = 	dataService.getResume();
				}
			   else if(result.empDetails.empGrade < 10){
					$scope.activeSection = 2;
				}
            });
		};


        $scope.getUserRole = function(evalid){
			if (!$scope.checkLogin()) {
                return;
            }
			EvaluationsFactory.GetUserRole(evalid).then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.userRole = result.userRole['userType'];
            });

		};


		$scope.getReportingLine = function(evalid){
			if (!$scope.checkLogin()) {
                return;
            }
			EvaluationsFactory.GetReportingLine(evalid).then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.reportingLine  = result.reportingLine;
            });
		};


		//save answers (particular section)
		$scope.setAnswers = function(nextback,section,state,pause,finish){
			if (!$scope.checkLogin()) {
                return;
            }
			$scope.message = "loading";
			window.scrollTo(0,100);					//go to top of page in every Next, Back, Finish or Pause button
			var sectionQuestions = [];
			if(state < arcopmState.EvalComplete){
				if(state > arcopmState.EvalByEmployee && $scope.userRole == 'emp'){		//case of 'My Evaluation' of employee. He can always view it without saving anything(his own answers, even if it is not completed by evaluator/dotted)
					$scope.activeSection = nextback;
					$scope.message = "none";
				}else{
					angular.forEach($scope.questions, function(question) {
						if(question.SectionID == section){
							sectionQuestions.push(question);
						}
					});
					angular.forEach(sectionQuestions, function(sectionQuestion) {
						sectionQuestion.userRole = $scope.userRole;
						sectionQuestion.GoalID = null;
					});
					angular.forEach($scope.goals, function(goal) {
						if(section == 3 && state != arcopmState.EvalComplete){
							var tempGoalAnswer = {};
							tempGoalAnswer.GoalID = goal.GoalID;
                            //if weight is 0, then we send null to database in order to avoid to save zero weight as it is forbidden
							if(state==arcopmState.EvalByEmployee){
								goal.EmpAchievement!=0 ? tempGoalAnswer.answer = goal.EmpAchievement : tempGoalAnswer.answer = null;
							}else if(state==arcopmState.EvalByDotted){
								goal.DottedAvgAnswer!=0 ? tempGoalAnswer.answer = goal.DottedAvgAnswer : tempGoalAnswer.answer = null;
							}else if(state == arcopmState.EvalByEvaluator){
                                goal.EvalAchievement!=0 ? tempGoalAnswer.answer = goal.EvalAchievement : tempGoalAnswer.answer = null;
							}else if(state == arcopmState.EvalByReviewer){
                                goal.RevAchievement!=0 ? tempGoalAnswer.answer = goal.RevAchievement : tempGoalAnswer.answer = null;
							}
							tempGoalAnswer.QuestionID = null;
							//sectionQuestions.push(nextback);
							sectionQuestions.push(tempGoalAnswer);
						}
					});
                    //console.log(sectionQuestions);
					//set variable finish to 1, only when user press 'Finish' button
					if(angular.isUndefined(finish)){
						finish = 0;
					}

					EvaluationsFactory.SetAnswers($scope.evaluation,sectionQuestions,state,finish,pause).then(function (result) {
						$scope.checkifLoggedout(result);
						if(result.success == true){
							if(angular.isUndefined(nextback))
							{
								$scope.activeSection = 0;
								$scope.getScores($scope.evaluation,$scope.state);
							}
							else if($scope.activeSection == nextback){					//case when we user press 'Save and Exit' (activeSection remains the same as before)
                                $state.go('evaluationLists',{fromList:$scope.list});
							}else{														//all other cases -'Next', 'Back', 'Finish'- (in this cases activeSection always changes)
								$scope.activeSection = nextback;
								$scope.getScores($scope.evaluation,$scope.state);
								$scope.getSections($scope.evaluation,$scope.state);
							}
							$scope.message = "none";
						}else{
							$scope.evaluationFormAlertDialog(result);
						}
					});
				}
			}else{										//just view the evaluation
				$scope.activeSection = nextback;
				$scope.message = "none";
			}
		};


		$scope.setActiveSection = function(sectionID,state){
			$scope.setAnswers(sectionID,$scope.activeSection,state,0);
		};


		//we pass the answer in the question model
		$scope.setMyValue = function(question,answer){
			question.answer = answer;
		};


		//split possible answers of questions(we receive them as one string separating by commas)
		$scope.mySplit = function(string) {
			var array = string.split(',');
			return array;
		};


		//Create new Development Plan function
        $scope.addNewDevPlan = function (evalID,tempDevplan,tempState) {
            if (!$scope.checkLogin()) {
                return;
            }

            $scope.extraMessage = 'loading';

			EvaluationsFactory.AddNewDevPlan(evalID,tempDevplan,loginData.user.id,tempState).then(function (result) {
				$scope.checkifLoggedout(result);
				if (result.success) {
					$scope.getDevPlanHistory(evalID);
					$scope.extraMessage = 'created';
				} else {
					$scope.extraMessage = 'error';
					$scope.extraMessageText = 'Something went wrong while creating a Development Plan. Please contact your administrator.';

				}
			});
            return;
        };


		$scope.editDevPlan = function(evalID,editDevplan){
			if (!$scope.checkLogin()) {
                return;
            }
            $scope.extraMessage = 'loading';

			EvaluationsFactory.UpdateDevPlan(editDevplan,loginData.user.id).then(function (result) {
				$scope.checkifLoggedout(result);
				if (result.success) {
					$scope.getDevPlanHistory(evalID);
					$scope.extraMessage = 'created';
				} else {
					$scope.extraMessage = 'error';
					$scope.extraMessageText = 'Something went wrong while updating development plan. Please contact your administrator.';

				}
			});

            return;
		};


		$scope.deleteDevPlan = function(evalID,devplan){
			if (!$scope.checkLogin()) {
                return;
            }
            $scope.extraMessage = 'loading';

			EvaluationsFactory.DeleteDevPlan(devplan.DevelopmentPlanID).then(function (result) {
				$scope.checkifLoggedout(result);
                if (result.success) {
                    $scope.getDevPlanHistory(evalID);
                    $scope.extraMessage = 'deleted';
                } else {
                    $scope.extraMessage = 'error';
                    $scope.extraMessageText = 'Something went wrong while deleting this development plan. Please contact your administrator.';

                }
            });
            return;
		};
        
        
        $scope.updateDevelopmentPlanStatus = function(devplan){
            if (!$scope.checkLogin()) {
                return;
            }
			EvaluationsFactory.UpdateDevelopmentPlanStatus(devplan,loginData.user.id).then(function (result) {
				$scope.checkifLoggedout(result);
                //console.log(result);
				if (result.success) {
					$scope.extraMessage = 'created';
					//$scope.managesteam = managesTeam;
                    //$scope.cycleGoal.EvaluationID = result.evalid;
					$scope.todoPopup = ngDialog.open({
						template: 'app/pages/evaluations/popup/evaluations.status.devplan.save.popup',
						className: 'ngdialog-theme-default',
						scope: $scope
					});
				} else {
					$scope.extraMessage = 'error';
					$scope.extraMessageText = 'Something went wrong while saving your selection. Please contact your administrator.';

				}
			});
        };


		$scope.evaluationFormAlertDialog = function(resultObject){
            $scope.message = "none";
			$scope.evaluationObject = resultObject;
			$scope.todoPopup = ngDialog.open({
				template: 'app/pages/evaluations/popup/evaluationForm.AlertDialog.popup.html',
				className: 'ngdialog-theme-default',
				scope: $scope
			});
		};


		// Shows add New development plan popup
        $scope.showAddNewDevplanPopup = function (evalID,state) {
            if (!$scope.checkLogin()) {
                return;
            }
            $scope.extraMessage = 'none';
			$scope.tempEvalID = evalID;
			$scope.tempState = state;

            $scope.todoPopup = ngDialog.open({
                template: 'app/pages/evaluations/popup/evaluationForm.new.devplan.popup.html',
                className: 'ngdialog-theme-default',
                scope: $scope
            });
        };


		// Shows Edit development plan popup
        $scope.showEditDevplanPopup = function (evalID,devplan,state) {
            if (!$scope.checkLogin()) {
                return;
            }
            $scope.extraMessage = 'none';
			$scope.tempEvalID = evalID;
			$scope.tempDevPlan = devplan;
			$scope.tempState = state;

            $scope.todoPopup = ngDialog.open({
                template: 'app/pages/evaluations/popup/evaluationForm.edit.devplan.popup.html',
                className: 'ngdialog-theme-default',
                scope: $scope
            });
        };

		// Shows Delete development plan popup
        $scope.showDeleteDevplanPopup = function (evalID,devplan) {
            if (!$scope.checkLogin()) {
                return;
            }
            $scope.extraMessage = 'warning';
			$scope.tempEvalID = evalID;
			$scope.tempDevPlan = devplan;

            $scope.todoPopup = ngDialog.open({
                template: 'app/pages/evaluations/popup/evaluationForm.delete.devplan.popup.html',
                className: 'ngdialog-theme-default',
                scope: $scope
            });
        };



		//function which manages questions' status(enabled or disabled) and questions' requirement
		$scope.questionStatus = function(evalStatus,questionFillBy,userrole){
			//false -> enabled -> required, true -> disabled -> non required
			if(evalStatus == arcopmState.EvalByEmployee && questionFillBy.search("emp") != -1 && (userrole == 'emp' || userrole == 'eval')) return false;
			if(evalStatus == arcopmState.EvalByDotted && questionFillBy.search("dot") != -1 && userrole == 'dotted') return false;
			if(evalStatus == arcopmState.EvalByEvaluator && questionFillBy.search("eval") != -1 && userrole == 'eval') return false;
			if(evalStatus == arcopmState.EvalByReviewer && questionFillBy.search("eval") != -1 && userrole == 'eval') return false;
			if(evalStatus == arcopmState.EvalComplete) return true;
			return true;
		};


		$scope.getDottedAnswers = function(evalid){
			if (!$scope.checkLogin()) {
                return;
            }
			EvaluationsFactory.GetDottedAnswers(evalid).then(function (result) {
				if (result.success) {
					$scope.dottedAnswers = result.dottedAnswers;
				}else{
					$scope.extraMessage = 'error';
                    $scope.extraMessageText = 'Something went wrong while deleting Goal. Please contact your administrator.';
				}
            });
		};


		$scope.noNullEmpAchievement = function(goal){
			if(angular.isUndefined(goal.EmpAchievement)){
				goal.EmpAchievement = 0;
			}
			else if(goal.EmpAchievement>120)
			{
				goal.EmpAchievement = 120;
			}
		};
        
        $scope.noNullDottedAvgAnswer = function(goal){
			if(angular.isUndefined(goal.DottedAvgAnswer)){
				goal.DottedAvgAnswer = 0;
			}
			else if(goal.DottedAvgAnswer>120)
			{
				goal.DottedAvgAnswer = 120;
			}
		};       

		$scope.noNullEvalAchievement = function(goal){
			if(angular.isUndefined(goal.EvalAchievement)){
				goal.EvalAchievement = 0;
			}
			else if(goal.EvalAchievement>120)
			{
				goal.EvalAchievement = 120;
			}
		};

		$scope.noNullEvalRevAchievement = function(goal){
			if(angular.isUndefined(goal.RevAchievement)){
				goal.RevAchievement = 0;
			}
			else if(goal.RevAchievement>120)
			{
				goal.RevAchievement = 120;
			}
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

		$scope.showScoresMessage = function (message) {
            if ($scope.scoresMessage === message) {
                return true;
            }

            return false;
        };

		$scope.checkifLoggedout = function (result){
			if (result==401){
				$scope.logout();
				return;
			}
		};


		$scope.showEvalPreviewForPDF = function () {

			$scope.message = "none";

			$scope.todoPopup = ngDialog.open({
				template: 'app/pages/evaluations/popup/evaluations.evalpreviewPDF.popup.html',
				className: 'ngdialog-theme-default eval-prev-pdf',
				scope: $scope
			});
        };


		$scope.generatePdf = function(){
			kendo.drawing.drawDOM($("#exportthis"),{paperSize:"A3"	}).then(function(group) {
				kendo.drawing.pdf.saveAs(group, "evaluation.pdf");
			});
		};
        
        $scope.showDottedAnswers = function(questionid){
            var notificationClass = 'question'+questionid;
            if ($(".notifications").children("."+notificationClass).children().length > 0) {
                $(".notifications").children("."+notificationClass).fadeToggle(300);
            }
        };
        
        $scope.showDottedScores = function(scoresClass){
            if ($(".notifications").children("."+scoresClass).children().length > 0) {
                $(".notifications").children("."+scoresClass).fadeToggle(300);
            }
        };
        
        $scope.checkDottedAnswersExistance = function(questionid){
            var dotAnswersExist = false;
            angular.forEach($scope.dottedAnswers, function(dotAnswer) {
                if(dotAnswer.QuestionID == questionid){
                    dotAnswersExist = true;
                }
            });
            return dotAnswersExist;
        };
        
    }



})();
