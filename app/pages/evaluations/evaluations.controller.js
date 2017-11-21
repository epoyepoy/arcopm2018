(function () {

    "use strict";

    // Create the Controller
    angular.module("ARCOPM").controller('evaluationsController', evaluationsController);

    // Inject services to the Controller

    evaluationsController.$inject = ["$scope", "Auth", "loginData",  "global", "EvaluationsFactory", "ngDialog", "dataService", "$state"];



    // Controller Logic


    function evaluationsController($scope, Auth, loginData, global, EvaluationsFactory, ngDialog, dataService, $state) {
        $scope.message = "none";
		$scope.extraMessage = "none";
		$scope.extraMessagePopup = "none";
		$scope.MainEvalFilter1_3 = "all";
		$scope.MainEvalFilter4_9 = "all";
		$scope.MainEvalFilter10 = "all";
        $scope.MyEvalFilter="all";
		$scope.myEvaluations = false;
		$scope.employeesToEvaluate = false;
		$scope.goal = {};
		$scope.parseInt = parseInt;
		$scope.tempConfig = {};
		$scope.selected1_3 = [];
		$scope.selected4_9 = [];
		$scope.selected10 = [];
		$scope.status5Evals1_3 = [];
		$scope.status5Evals4_9 = [];
		$scope.status5Evals10 = [];
		$scope.selectAllCheckBox1_3 = false;
		$scope.selectAllCheckBox1_3 = false;
		$scope.selectAllCheckBox1_3 = false;


        // Initialize the evaluations
        $scope.init = function () {
            if (!$scope.checkLogin()) {
                return;
            }

            $scope.initNavigation();
			$scope.getEvaluations();
            $scope.getMyEvaluations();
			$scope.getEvaluationsCycles();
        };


        // Initialize the Data service
        $scope.initNavigation = function () {
            $scope.setPage('evaluationLists');
            global.navPages = [];
            global.navPages.push({
                name: "Evaluations",
                link: "evaluationLists",
                activetab:"evaluationLists",
                current: true
            });

        };


		//data
		$scope.getEvaluations = function(){
			if (!$scope.checkLogin()) {
                return;
            }
			$scope.message = "loading";
			EvaluationsFactory.GetEvaluations(loginData.user.id).then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.evaluations = result.evaluations;
				angular.forEach(result.evaluations, function(value) {
					if(value.State == 5){
						if(value.grade <= 3) ($scope.status5Evals1_3).push(value.EvaluationID);
						if(value.grade >= 4 && value.grade <= 9) ($scope.status5Evals4_9).push(value.EvaluationID);
						if(value.grade >= 10) ($scope.status5Evals10).push(value.EvaluationID);
					}
				});
				$scope.message = "none";
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

		$scope.isIndeterminate = function(group) {
			if(group == '1_3'){
				return ($scope.selected1_3.length !== 0 &&
				$scope.selected1_3.length !== $scope.status5Evals1_3.length);
			}else if(group == '4_9'){
				return ($scope.selected4_9.length !== 0 &&
				$scope.selected4_9.length !== $scope.status5Evals4_9.length);
			}else if(group == '10'){
				return ($scope.selected10.length !== 0 &&
				$scope.selected10.length !== $scope.status5Evals10.length);
			}
		};

		$scope.isChecked = function(group) {
			if(group == '1_3'){
				return $scope.selected1_3.length === $scope.status5Evals1_3.length;
			}else if(group == '4_9'){
				return $scope.selected4_9.length === $scope.status5Evals4_9.length;
			}else if(group == '10'){
				return $scope.selected10.length === $scope.status5Evals10.length;
			}
		};

		$scope.toggleAll = function(group) {
			if(group == '1_3'){
				if ($scope.selected1_3.length === $scope.status5Evals1_3.length) {
					$scope.selected1_3 = [];
				} else if ($scope.selected1_3.length === 0 || $scope.selected1_3.length > 0) {
					$scope.selected1_3 = $scope.status5Evals1_3.slice(0);
				}
			}else if(group == '4_9'){
				if ($scope.selected4_9.length === $scope.status5Evals4_9.length) {
					$scope.selected4_9 = [];
				} else if ($scope.selected4_9.length === 0 || $scope.selected4_9.length > 0) {
					$scope.selected4_9 = $scope.status5Evals4_9.slice(0);
				}
			}else if(group == '10'){
				if ($scope.selected10.length === $scope.status5Evals10.length) {
					$scope.selected10 = [];
				} else if ($scope.selected10.length === 0 || $scope.selected10.length > 0) {
					$scope.selected10 = $scope.status5Evals10.slice(0);
				}
			}
		};
		
		
		$scope.checkBoxAppear = function(evaluation,group){
			if(evaluation.State==5 && evaluation.resumeSection==0 && evaluation.editBy==loginData.user.id && evaluation.finishedFlag==0){
				if(group == '1_3'){
					$scope.selectAllCheckBox1_3 = true;		//if we find at least one checkbox in a line of group 1_3, then we show also the correspondance 'select all' checkbox
				}else if(group == '4_9'){
					$scope.selectAllCheckBox4_9 = true;		//if we find at least one checkbox in a line of group 4_9, then we show also the correspondance 'select all' checkbox
				}else if(group == '10'){
					$scope.selectAllCheckBox10 = true;		//if we find at least one checkbox in a line of group 10, then we show also the correspondance 'select all' checkbox
				}
				return true;
			}else{
				return false;
			}
		};
		
		
		$scope.reviseSelectedDialog = function(group){
			if (!$scope.checkLogin()) {
                return;
            }
			$scope.extraMessage = "warning";
			$scope.tempGroup = group;
			if(group == '1_3'){
				$scope.tempSelected = $scope.selected1_3;
			}else if(group == '4_9'){
				$scope.tempSelected = $scope.selected4_9;
			}else if(group == '10'){
				$scope.tempSelected = $scope.selected10;
			}
			$scope.todoPopup = ngDialog.open({
				template: 'app/pages/evaluations/popup/evaluations.revise.popup.html',
				className: 'ngdialog-theme-default',
				scope: $scope
			});
		};
		
		
		$scope.reviseSelected = function(selectedEvals,group){
			if (!$scope.checkLogin()) {
                return;
            }
			EvaluationsFactory.ReviseEvaluations(selectedEvals,loginData.user.id).then(function (result) {
				$scope.checkifLoggedout(result);
				//if revised successfully, then we reload evaluations in order to get new states
				//also we deselect all evaluations accordingly to group(as we revised them)
				if(result.success == true){
					$scope.extraMessage = "revised";
					$scope.getEvaluations();
					if(group == '1_3'){
						$scope.selected1_3 = [];
					}else if(group == '4_9'){
						$scope.selected4_9 = [];
					}else if(group == '10'){
						$scope.selected10 = [];
					}
				}else{
					$scope.extraMessage = "error";
					$scope.extraMessageText = result.message;
				}
            });
		};


		$scope.getMyEvaluations = function(){
			if (!$scope.checkLogin()) {
                return;
            }
			$scope.extraMessage = "loading";
			EvaluationsFactory.GetMyEvaluations(loginData.user.id).then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.personalevaluations = result.myevaluations;
				$scope.extraMessage = "none";
            });
		};

		$scope.getEvaluationsCycles = function(){
			if (!$scope.checkLogin()) {
                return;
            }
			EvaluationsFactory.GetEvaluationsCycles().then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.evaluationscycles = result.activeGoalCycles;
            });
		};


		$scope.reportingLineDialog = function(evaluationObject,cycleid){
			if (!$scope.checkLogin()) {
                return;
            }
            $scope.extraMessage = "loading";
			EvaluationsFactory.GetUserReportingLine(evaluationObject.EmployeeID,cycleid).then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.empReportingLine = result.empReportingLine;
				$scope.extraMessage = "none";
            });
			$scope.evaluationObject = evaluationObject;
			$scope.todoPopup = ngDialog.open({
				template: 'app/pages/evaluations/popup/evaluations.reportingline.popup.html',
				className: 'ngdialog-theme-default',
				scope: $scope
			});
		};


		//actions
		$scope.fileUploadDialog = function(evaluationObject,cycle){
			$scope.evaluationObject = evaluationObject;
			$scope.cycle = cycle;
			$scope.message = "none";
			$scope.todoPopup = ngDialog.open({
				template: 'app/pages/evaluations/popup/evaluations.fileupload.popup.html',
				className: 'ngdialog-theme-default',
				scope: $scope
			});
		};


        $scope.fileUpload = function(cycle){
            var filename = $scope.evaluationObject.fileToUpload.name;
            var filetype = filename.substring(filename.lastIndexOf(".") + 1);
            if(filetype == 'pdf'){
                EvaluationsFactory.FileUpload($scope.evaluationObject,cycle).then(function (result) {
                    if(result['response'] == 1) {
                        $scope.evaluationObject.UploadedFile = result.UploadedFile;
                        $scope.evaluationObject.UploadedDate = result.UploadedDate;
                        $scope.message = "success";
                    }else if(result['response'] == 2){
						$scope.message = "file_upload_error";
					}
                });
            }else{
                $scope.message = "error_filetype";
            }
		};


		$scope.fileDeleteDialog = function(evaluationObject){
			$scope.evaluationObject = evaluationObject;
			$scope.extraMessage = "warning";
			$scope.todoPopup = ngDialog.open({
				template: 'app/pages/evaluations/popup/evaluations.filedelete.popup.html',
				className: 'ngdialog-theme-default',
				scope: $scope
			});
		};

		$scope.fileDelete = function(){
			$scope.extraMessage = "loading";
			EvaluationsFactory.FileDelete($scope.evaluationObject).then(function(result){
				if(result['response'] == 1) {
                        $scope.evaluationObject.UploadedFile = result.UploadedFile;
                        $scope.extraMessage = "deleted";
						$scope.message = "none";
                    }else if(result['response'] == 2){
						$scope.extraMessage = "error";
					}
			});
		};


        $scope.downloadFile = function(evaluationObject){
			var filename = evaluationObject.UploadedFile;
			var empid = evaluationObject.EmployeeID;
            window.open("server/uploads/"+empid+"/"+filename,'_self');
		};
		
		
		$scope.showEvalPreview = function (evaluation,cycle) {
            if (!$scope.checkLogin()) {
                return;
            }
            $scope.extraMessagePopup = "loading";
            $scope.evaluationObj = evaluation;
			$scope.activeCycle = cycle;

            $scope.evalPreviewPopup = ngDialog.open({
                template: 'app/pages/evaluations/popup/evaluations.evaluationpreview.popup.html',
                className: 'ngdialog-theme-default eval-prev',
                scope: $scope
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
				$scope.extraMessagePopup = "none";
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
		
		$scope.getUserGoals = function(empid,evalid){
			if (!$scope.checkLogin()) {
                return;
            }
			EvaluationsFactory.GetQuestionaireGoals(empid,loginData.user.id,evalid).then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.goals = result.goals;
            });
		};
		
		$scope.generatePdf = function(){
			kendo.drawing.drawDOM($("#exportthis"),{paperSize:"A3",forcePageBreak: ".page-break"}).then(function(group) {
				kendo.drawing.pdf.saveAs(group, "evaluation.pdf");
			});
		};
		

		$scope.gotoEvalForm = function (evalID,evaluationObject,fromList) {
			if(!angular.isUndefined(evaluationObject) && evaluationObject.onBehalfFlag == 0){
				var behalfUser = evaluationObject.EmployeeID+' '+evaluationObject.employeeName;
			}else{
				var behalfUser = '';
			}
			if(!angular.isUndefined(fromList)){
				var list = fromList;
			}else{
				var list = '';
			}
			dataService.setEvaluationID(evalID);
			dataService.setBehalfUser(behalfUser);
			dataService.setEmpID(evaluationObject.EmployeeID);
			dataService.setState(evaluationObject.State);
			dataService.setResume(evaluationObject.resumeSection);
			dataService.setFromList(list);
            $state.go("evaluationForm");
        };


		$scope.gotoGoals = function(period,periodID) {
            $state.go("evaluationGoals",{cycle : period, cycleID : periodID, employeesGoals : true});
        };

		//filters
		$scope.greaterThan = function(prop, val){
			return function(item){
				return item[prop] > val;
			};
		};

		$scope.lessThan = function(prop, val){
			return function(item){
				return item[prop] < val;
			};
		};


		$scope.applyEvalFilters = function(grade){

            return function (evaluationObject) {
				var tempScope;
				if(grade == 3){
					tempScope = $scope.MainEvalFilter1_3;
				}else if(grade == 9){
					tempScope = $scope.MainEvalFilter4_9;
				}else if(grade == 10){
					tempScope = $scope.MainEvalFilter10;
				}
                else{
                    tempScope = $scope.MyEvalFilter;
                }
				if (tempScope === "all") return true;
                if (tempScope === "1" && evaluationObject.State < "2" ) return true;
                if (tempScope === "2" && evaluationObject.State >= "2"  && evaluationObject.State <= "4") return true;
                if (tempScope === "3" && evaluationObject.State === "5" ) return true;
				if (tempScope === "4" && evaluationObject.wrongManager === "1" ) return true;
				if (tempScope === "5" && evaluationObject.isForAction === "1" && evaluationObject.wrongManager !== "1" && evaluationObject.finishedFlag==0) return true;
				if (tempScope === "6" && evaluationObject.isForAction === "2" && evaluationObject.wrongManager !== "1" && evaluationObject.finishedFlag==0) return true;
                return false;
			};
		};

        //function that shows or hides the evaluations tables, depenging on if we have evaluations per Grade Team
		$scope.getCount = function(evalsObj, val1, val2){
			var show = false;
            for (var evalO in evalsObj) {
				if(evalsObj[evalO].grade >= val1 && evalsObj[evalO].grade <= val2){
					show = true;
					break;
				}
            }
             return show;
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
		
		$scope.showExtraMessagePopup = function (message) {
            if ($scope.extraMessagePopup === message) {
                return true;
            }

            return false;
        };


		$scope.changeExtraMessage = function(message){
			$scope.extraMessage = message;
		};


		$scope.changeMessage = function(message){
			$scope.message = message;
		};


		$scope.updateEvaluation = function(evalObject){
			if (!$scope.checkLogin()) {
                return;
            }

			var empid = evalObject.EmployeeID;
			var managesTeam = evalObject.ManagesTeam;
			EvaluationsFactory.UpdateEvaluation(empid,managesTeam,loginData.user.id).then(function (result) {
				$scope.checkifLoggedout(result);
				if (result.success) {
					$scope.extraMessage = 'created';
					$scope.tempConfig.managesteam = managesTeam;
					$scope.todoPopup = ngDialog.open({
						template: 'app/pages/evaluations/popup/evaluations.employeemanagesteam.save.popup.html',
						className: 'ngdialog-theme-default',
						scope: $scope
					});
				} else {
					$scope.extraMessage = 'error';
					$scope.extraMessageText = 'Something went wrong while saving your selection. Please contact your administrator.';

				}
			});
		};

		//retrieve all goals of employee
		$scope.getGoals = function(evalObj){
			if (!$scope.checkLogin()) {
                return;
            }
			var weight = 0;
			var goalLimit = 0;
			var empid = evalObj.EmployeeID;
			var evalid = evalObj.EvaluationID;
			$scope.showAddNewGoalButton = true;
			EvaluationsFactory.GetGoals(empid,loginData.user.id,evalid).then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.goals = result.goals;
				var i=0;
				angular.forEach(result.goals, function(value) {
					weight = weight + parseInt(value.Weight);
					i++;
				});

				$scope.remainingWeight = 100 - weight;
				//Grades 1-3 cannot have goals, so i make remainingWeight=0 in order to activate the 'Start Evaluation' button.
				//Grades 4-9 can have 5 goals. Grades 10+ can have 6 goals.
				if(evalObj.grade <= 3){
					$scope.remainingWeight = 0;
				}else if(evalObj.grade >=4 && evalObj.grade <=9){
					goalLimit = 5;
				}else if(evalObj.grade >=10){
					goalLimit = 6;
				}

				if(i == goalLimit || weight > 100){
					$scope.showAddNewGoalButton = false;
				}
            });
		};


		$scope.noNullWeight = function(){
			if(angular.isUndefined($scope.goal.Weight)){
				$scope.goal.Weight = 0;
			}
		};


		$scope.noNullTempWeight = function(){
			if(angular.isUndefined($scope.tempGoal.Weight)){
				$scope.tempGoal.Weight = 0;
			}
		};


		$scope.getGoalAttributes = function(){
			if (!$scope.checkLogin()) {
                return;
            }
			EvaluationsFactory.GetGoalAttributes().then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.attributes = result.goalAttributes;
			});
		};


		$scope.showRejectManagerPopup = function(evaluation){
			if (!$scope.checkLogin()) {
                return;
            }
            $scope.extraMessage = 'warning';
			$scope.tempEvaluation = evaluation;

            $scope.todoPopup = ngDialog.open({
                template: 'app/pages/evaluations/popup/evaluations.reject.manager.popup.html',
                className: 'ngdialog-theme-default',
                scope: $scope
            });
		};


		$scope.rejectManager = function(evaluation){
			if (!$scope.checkLogin()) {
                return;
            }

			var empid = evaluation.EmployeeID;
			var yourActionState = evaluation.yourActionState;
			EvaluationsFactory.SetWrongManager(empid,yourActionState).then(function (result) {
				$scope.checkifLoggedout(result);
				if (result.success) {
					$scope.extraMessage = 'deleted';
					evaluation.wrongManager = '1';
				} else {
					$scope.extraMessage = 'error';
					$scope.extraMessageText = 'Something went wrong while saving your selection. Please contact your administrator.';

				}
			});
		};


		$scope.showRevertRejectionPopup = function(evaluation){
			if (!$scope.checkLogin()) {
                return;
            }
            $scope.extraMessage = 'warning';
			$scope.tempEvaluation = evaluation;

            $scope.todoPopup = ngDialog.open({
                template: 'app/pages/evaluations/popup/evaluations.revert.manager.popup.html',
                className: 'ngdialog-theme-default',
                scope: $scope
            });
		};


		$scope.revertManager = function(evaluation){
			if (!$scope.checkLogin()) {
                return;
            }

			var empid = evaluation.EmployeeID;
			var yourActionState = evaluation.yourActionState;
			EvaluationsFactory.RevertWrongManager(empid,yourActionState).then(function (result) {
				$scope.checkifLoggedout(result);
				if (result.success) {
					$scope.extraMessage = 'reverted';
					evaluation.wrongManager = '0';
				} else {
					$scope.extraMessage = 'error';
					$scope.extraMessageText = 'Something went wrong while saving your selection. Please contact your administrator.';

				}
			});
		};


		$scope.getUserGoals = function(empid,evalid){
			if (!$scope.checkLogin()) {
                return;
            }
			EvaluationsFactory.GetQuestionaireGoals(empid,loginData.user.id,evalid).then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.goals = result.goals;
				$scope.extraMessagePopup = "none";
            });
		};


		// Shows Evaluation Configuration popup
        $scope.showEvalConfiguration = function (evaluation) {
            if (!$scope.checkLogin()) {
                return;
            }
            $scope.extraMessagePopup = "loading";
			$scope.tempEval = evaluation;

            $scope.goalConfigurationPopup = ngDialog.open({
                template: 'app/pages/evaluations/popup/viewgoals.popup.html',
                className: 'ngdialog-theme-default',
                scope: $scope
            });

        };


		$scope.checkifLoggedout = function (result){
			if (result==401){
				$scope.logout();
				return;
			}
		}
    }


})();
