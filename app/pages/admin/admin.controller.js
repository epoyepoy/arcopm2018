(function () {

	"use strict";

	// Create the Controller
	angular.module("ARCOPM").controller('adminController', adminController);

	// Inject services to the Controller

	adminController.$inject = ["$scope", "Auth", "loginData", "global", "ngDialog", "AdminFactory", "EvaluationsFactory"];



	// Controller Logic


	function adminController($scope, Auth, loginData, global, ngDialog, AdminFactory, EvaluationsFactory) {
		$scope.message = "none";
		$scope.extraMessagePopup = 'none';
		$scope.show = true;
		$scope.formMessage = true;
		$scope.successMessage = false;
		$scope.scoreClasses = ['info','active','warning','danger'];
		$scope.scoreDefinition = ['Performance Improvement Needed','Building Capability','Achieving Performance','Leading Performance'];

		// Initialize the dashboard
		$scope.init = function () {
			if (!$scope.checkLogin()) {
				return;
			}

			$scope.initNavigation();
			$scope.getProjects();
			$scope.getActiveCycles();
		};


		// Initialize the Data service
		$scope.initNavigation = function () {
			$scope.setPage('admin');
			global.navPages = [];
			global.navPages.push({
				name: "Administration",
				link: "admin",
				current: true
			});

		};


		$scope.getProjects = function () {
			if (!$scope.checkLogin()) {
				return;
			}
			AdminFactory.GetProjects().then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.projects = result.projectsList;
			});
		};

		$scope.setProject = function (project) {
			$scope.filters.projectcode = project.projectCode;
		};


		$scope.getActiveCycles = function () {
			AdminFactory.GetActiveCycles().then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.cycles = result.activeCycles;
			});
		};

		$scope.updateLocalUser = function (user) {
			$scope.localUserData = {
				empno: user.empno,
				userID: user.localUserAccountName,
				password: typeof user.password !== 'undefined' ? user.password : '',
				loggedinid: loginData.user.id,
				isinactive: user.arcopmAcountStatus,
			};
			AdminFactory.updateLocalUser($scope.localUserData).then(function (result) {
				$scope.formMessage = false;
				$scope.successMessage = true;
			});
		};

		$scope.makeLocalUserActive = function (user) {
			$scope.selectedUser.arcopmAcountStatus = 1;
		};

		$scope.makeLocalUserInactive = function (user) {
			$scope.selectedUser.arcopmAcountStatus = 0;
		};

		$scope.editLocalUserPopUp = function (user) {
			$scope.selectedUser = user;
			$scope.formMessage = true;
			$scope.successMessage = false;
			$scope.todoPopup = ngDialog.open({
				template: 'app/pages/admin/popup/admin.localuser.popup.html',
				className: 'ngdialog-theme-default',
				scope: $scope
			});
		};


		$scope.showFilters = function () {
			if (!$scope.show) {
				$scope.show = true;
				$('#evals').show(function () { });
			} else {
				$scope.show = false;
				$('#evals').hide(800, function () { });
			}
		};


		$scope.showUpdateEvaluatorPopup = function (repline,action) {
			if (!$scope.checkLogin()) {
				return;
			}
			$scope.extraMessage = 'none';
			$scope.selectedRepline = repline;
			$scope.tempRepline = angular.copy(repline);
			$scope.action = action;

			$scope.todoPopup = ngDialog.open({
				template: 'app/pages/admin/popup/admin.update.evaluator.popup.html',
				className: 'ngdialog-theme-default',
				scope: $scope
			});
		};


		$scope.deleteFromARCOPM = function (selectedrepline) {
			if (!$scope.checkLogin()) {
				return;
			}
			selectedrepline.userid = loginData.user.id;
			AdminFactory.DeleteFromARCOPM(selectedrepline).then(function (result) {
				$scope.checkifLoggedout(result);
				if (result.success) {
					$scope.extraMessage = 'success';
					$scope.getAdminRights();
				} else {
					$scope.extraMessage = 'error';
					$scope.extraMessageText = result.message;
				}
			});

		};

		$scope.updateReportingLine = function (newrepline,action) {
			if (!$scope.checkLogin()) {
				return;
			}
			newrepline.userid = loginData.user.id;
			if ($scope.validateIDs(newrepline)) {
				AdminFactory.UpdateReportingLine(newrepline).then(function (result) {
					$scope.checkifLoggedout(result);
					if (result.success) {
						$scope.extraMessage = 'created';
						if(action == 'update'){
							$scope.selectedRepline.EvaluatorNumber = result.empReportingLine.EvaluatorNumber;
							$scope.selectedRepline.EvaluatorName = result.empReportingLine.EvaluatorName;
							$scope.selectedRepline.ReportedWrongEvaluator=result.empReportingLine.ReportedWrongEvaluator;

							$scope.selectedRepline.NextEvaluationEvaluatorNumber = result.empReportingLine.NextEvaluationEvaluatorNumber;
							$scope.selectedRepline.NextEvaluationEvaluatorName = result.empReportingLine.NextEvaluationEvaluatorName;
							$scope.selectedRepline.NextEvaluationReportedWrongEvaluator=result.empReportingLine.NextEvaluationReportedWrongEvaluator;

							$scope.selectedRepline.Dotted1Empno = result.empReportingLine.Dotted1Empno;
							$scope.selectedRepline.Dotted1Name = result.empReportingLine.Dotted1Name;
							$scope.selectedRepline.ReportedWrongDot1=result.empReportingLine.ReportedWrongDot1;

							$scope.selectedRepline.NextDotted1Empno = result.empReportingLine.NextDotted1Empno;
							$scope.selectedRepline.NextDotted1Name = result.empReportingLine.NextDotted1Name;
							$scope.selectedRepline.NextReportedWrongDot1=result.empReportingLine.NextReportedWrongDot1;

							$scope.selectedRepline.Dotted2Empno = result.empReportingLine.Dotted2Empno;
							$scope.selectedRepline.Dotted2Name = result.empReportingLine.Dotted2Name;
							$scope.selectedRepline.ReportedWrongDot2=result.empReportingLine.ReportedWrongDot2;

							$scope.selectedRepline.NextDotted2Empno = result.empReportingLine.NextDotted2Empno;
							$scope.selectedRepline.NextDotted2Name = result.empReportingLine.NextDotted2Name;
							$scope.selectedRepline.NextReportedWrongDot2=result.empReportingLine.NextReportedWrongDot2;

							$scope.selectedRepline.Dotted3Empno = result.empReportingLine.Dotted3Empno;
							$scope.selectedRepline.Dotted3Name = result.empReportingLine.Dotted3Name;
							$scope.selectedRepline.ReportedWrongDot3=result.empReportingLine.ReportedWrongDot3;

							$scope.selectedRepline.NextDotted3Empno = result.empReportingLine.NextDotted3Empno;
							$scope.selectedRepline.NextDotted3Name = result.empReportingLine.NextDotted3Name;
							$scope.selectedRepline.NextReportedWrongDot3=result.empReportingLine.NextReportedWrongDot3;
							$scope.selectedRepline.CycleExclude = result.empReportingLine.CycleExclude;
							$scope.selectedRepline.CycleDescription = result.empReportingLine.CycleDescription;
						}else if(action == 'addnew'){
							$scope.getAdminRights();
						}
					} else {
						$scope.extraMessage = 'error';
						$scope.extraMessageText = result.message;
					}
				});
			} else {
				$scope.extraMessage = 'error';
				$scope.extraMessageText = 'You cannot have the same User for 2 different roles. Evaluator, 1st Dotted, 2nd Dotted and 3rd Dotted must be different Users.';
			}
		};


		$scope.getEmployeeEvaluations = function (repline) {
			if (!$scope.checkLogin()) {
				return;
			}
			AdminFactory.GetEmployeeEvaluations(repline.EmpNo).then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.employeeEvaluations = result.employeeEvaluations;
				$scope.extraMessagePopup = 'none';
			});
		};


		$scope.showResetEvaluationPopup = function (repline) {
			if (!$scope.checkLogin()) {
				return;
			}
			$scope.extraMessagePopup = 'loading';
			$scope.replineObject = repline;
			$scope.getEmployeeEvaluations(repline);

			$scope.todoPopup = ngDialog.open({
				template: 'app/pages/admin/popup/admin.reset.evaluation.popup.html',
				className: 'ngdialog-theme-default',
				scope: $scope
			});
		};


		$scope.showDeleteUserPopup = function (repline) {
			if (!$scope.checkLogin()) {
				return;
			}
			$scope.extraMessage = 'none';
			$scope.selectedRepline = repline;
			$scope.todoPopup = ngDialog.open({
				template: 'app/pages/admin/popup/admin.delete.user.popup.html',
				className: 'ngdialog-theme-default',
				scope: $scope
			});
		};

		$scope.showResetEvaluationConfirmPopup = function (evaluation, resetgoals) {
			if (!$scope.checkLogin()) {
				return;
			}
			$scope.extraMessage = 'warning';
			$scope.evaluationObject = evaluation;
			$scope.resetgoals = resetgoals;

			$scope.todoPopup = ngDialog.open({
				template: 'app/pages/admin/popup/admin.reset.evaluation.confirm.popup.html',
				className: 'ngdialog-theme-default',
				scope: $scope
			});
		};
		

		$scope.resetEvaluation = function (evaluation, resetgoals) {
			if (!$scope.checkLogin()) {
				return;
			}
			$scope.extraMessage = 'loading';
			if(resetgoals===2){
				AdminFactory.ResetLastState(evaluation.EvaluationID, loginData.user.id).then(function (result) {
					$scope.checkifLoggedout(result);
					if (result.success) {
						$scope.evaluationObject.ID = result.evaluation.ID;
						$scope.evaluationObject.EvaluationPeriod = result.evaluation.EvaluationPeriod;
						$scope.evaluationObject.StateDescription = result.evaluation.StateDescription;
						$scope.evaluationObject.StateDate = result.evaluation.StateDate;
						$scope.evaluationObject.ManagesTeam = result.evaluation.ManagesTeam;
						$scope.evaluationObject.CreatedByID = result.evaluation.CreatedByID;
						$scope.evaluationObject.CreatedByName = result.evaluation.CreatedByName;
						$scope.evaluationObject.noOfGoalsSet = result.evaluation.noOfGoalsSet;
						$scope.evaluationObject.currentStateAnswersCount = result.evaluation.currentStateAnswersCount;
						$scope.evaluationObject.State = result.evaluation.State;
						$scope.extraMessage = 'resetted';
					} else {
						$scope.extraMessage = 'error';
						$scope.extraMessageText = 'Something went wrong while deleting this development plan. Please contact your administrator.';
					}
				});
			}else{
				AdminFactory.ResetEmployeeEvaluation(evaluation.EvaluationID, loginData.user.id, resetgoals).then(function (result) {
					$scope.checkifLoggedout(result);
					if (result.success) {
						$scope.evaluationObject.ID = result.evaluation.ID;
						$scope.evaluationObject.EvaluationPeriod = result.evaluation.EvaluationPeriod;
						$scope.evaluationObject.StateDescription = result.evaluation.StateDescription;
						$scope.evaluationObject.StateDate = result.evaluation.StateDate;
						$scope.evaluationObject.ManagesTeam = result.evaluation.ManagesTeam;
						$scope.evaluationObject.CreatedByID = result.evaluation.CreatedByID;
						$scope.evaluationObject.CreatedByName = result.evaluation.CreatedByName;
						$scope.evaluationObject.noOfGoalsSet = result.evaluation.noOfGoalsSet;
						$scope.evaluationObject.currentStateAnswersCount = result.evaluation.currentStateAnswersCount;
						$scope.evaluationObject.State = result.evaluation.State;
						$scope.extraMessage = 'resetted';
					} else {
						$scope.extraMessage = 'error';
						$scope.extraMessageText = 'Something went wrong while deleting this development plan. Please contact your administrator.';
					}
				});
			}
			return;
		};
		

		$scope.getAdminRights = function () {
			if (!$scope.checkLogin()) {
				return;
			}
			$scope.message = 'loading';
			if ($scope.reportingLine) {
				$scope.repLineTable = true;
				$('#evals').hide(800, function () {
					$scope.show = false;
					AdminFactory.GetReportingLine($scope.filters).then(function (result) {
						$scope.checkifLoggedout(result);
						if ((result.reportingLine).length) {
							$scope.reportingLine = result.reportingLine;
							$scope.emptyTable = false;
							//$scope.evalsCount = result.evaluations.length;
						} else {
							$scope.emptyTable = true;
						}
						$scope.message = 'none';
					});
				});
			} else if ($scope.localUsers) {
				$scope.localUsersTable = true;
				$('#evals').hide(800, function () {
					$scope.show = false;
					AdminFactory.getEmployee($scope.filters).then(function (result) {
						if ((result.localUsersList).length) {
							$scope.localUsersList = result.localUsersList;
							$scope.emptyTable = false;
						}else{
							$scope.emptyTable = true;
						}
						$scope.message = 'none';
					});
				});
			}
		};


		$scope.validateIDs = function (repline) {
			if (repline.EvaluatorNumber) {
				if (repline.EvaluatorNumber === repline.Dotted1Empno ||
					repline.EvaluatorNumber === repline.Dotted2Empno ||
					repline.EvaluatorNumber === repline.Dotted3Empno) {

					return false;
				}
			}
			if (repline.NextEvaluationEvaluatorNumber) {
				if (repline.NextEvaluationEvaluatorNumber === repline.NextDotted1Empno ||
					repline.NextEvaluationEvaluatorNumber === repline.NextDotted2Empno ||
					repline.NextEvaluationEvaluatorNumber === repline.NextDotted3Empno) {

					return false;
				}
			}
			if (repline.Dotted1Empno) {
				if (repline.Dotted1Empno === repline.Dotted2Empno ||
					repline.Dotted1Empno === repline.Dotted3Empno) {

					return false;
				}
			}
			if (repline.NextDotted1Empno) {
				if (repline.NextDotted1Empno === repline.NextDotted2Empno ||
					repline.NextDotted1Empno === repline.NextDotted3Empno) {

					return false;
				}
			}
			if (repline.Dotted2Empno) {
				if (repline.Dotted2Empno === repline.Dotted1Empno ||
					repline.Dotted2Empno === repline.Dotted3Empno) {

					return false;
				}
			}
			if (repline.NextDotted2Empno) {
				if (repline.NextDotted2Empno === repline.NextDotted1Empno ||
					repline.NextDotted2Empno === repline.NextDotted3Empno) {

					return false;
				}
			}
			if (repline.Dotted3Empno) {
				if (repline.Dotted3Empno === repline.Dotted1Empno ||
					repline.Dotted3Empno === repline.Dotted2Empno) {

					return false;
				}
			}
			if (repline.NextDotted3Empno) {
				if (repline.NextDotted3Empno === repline.NextDotted1Empno ||
					repline.NextDotted3Empno === repline.NextDotted2Empno) {

					return false;
				}
			}

			return true;
		};


		$scope.reportingLineDialog = function (repline) {
			if (!$scope.checkLogin()) {
				return;
			}
			$scope.message = "none";

			$scope.replineObject = repline;
			$scope.todoPopup = ngDialog.open({
				template: 'app/pages/admin/popup/admin.reportingline.popup.html',
				className: 'ngdialog-theme-default',
				scope: $scope
			});
		};


		$scope.resetFilters = function () {
			$scope.filters = {
				empid: '',
				evaluatorid: '',
				dottedid: '',
				projectcode: '',
				isactive: '',
				wrongmanager: '',
				loggedin_user: loginData.user.id
			};
			$scope.has_wrong_repline = '';
		};

		//action

		$scope.checkifLoggedout = function (result) {
			if (result == 401) {
				$scope.logout();
				return;
			}
		};

		$scope.showMessage = function (message) {
			if ($scope.message === message) {
				return true;
			}

			return false;
		};

		$scope.resetDialog = function () {
			$scope.extraMessage='none';
			$scope.showExtraMessage('none');
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

		$scope.showEvalPreviewForPDF = function (evalid,state) {
			$scope.message = "none";
			$scope.getEmpDetails(evalid);
			$scope.getScores(evalid, state);
			console.log($scope.getEmpDetails);
			$scope.todoPopup = ngDialog.open({
				template: 'app/pages/evaluations/popup/evaluations.evalpreviewPDF.popup.html',
				className: 'ngdialog-theme-default eval-prev-pdf',
				scope: $scope
			});
		};
		
		$scope.roundUp = function(score){
		return (Math.round(score * 100) / 100).toFixed(2);
		};
		
		$scope.getEmpDetails = function(evalid){
			if (!$scope.checkLogin()) {
                return;
            }
			EvaluationsFactory.GetEmpDetails(evalid).then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.empDetails = result.empDetails;
				$scope.employeeGrade = $scope.empDetails.empGrade;
            });
		};
		
		$scope.generatePdf = function(){
			kendo.drawing.drawDOM($("#exportthis"),{paperSize:"A3"	}).then(function(group) {
				kendo.drawing.pdf.saveAs(group, "evaluation.pdf");
			});
		};

		
		$scope.showScoresMessage = function (message) {
            if ($scope.scoresMessage === message) {
                return true;
            }

            return false;
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
				var totalEvalScore = 0; var totalEvalWeightScore = 0;
				var totalRevScore = 0; var totalRevWeightScore = 0;
				var totalWeight = 0;
				var tempCalc=0;
				var tempCalc1=0;
				var i = 0;
				angular.forEach(result.evalScores, function(value) {
					i++;
					totalWeight += parseFloat(value.ScoreWeight);
					totalEmpScore += parseFloat(value.EmpScore);
					totalEvalScore += parseFloat(value.EvalScore);
					totalRevScore += parseFloat(value.RevScore);
					totalEmpWeightScore +=  parseFloat($scope.roundUp(value.EmpScore * value.ScoreWeight));
					totalEvalWeightScore +=  parseFloat($scope.roundUp(parseFloat(value.EvalScore * value.ScoreWeight)));
					totalRevWeightScore +=   parseFloat($scope.roundUp(parseFloat(value.RevScore * value.ScoreWeight)));
				});
				$scope.totalWeight = totalWeight;
				$scope.averageEmpScore = totalEmpScore/i;
				$scope.averageEvalScore = totalEvalScore/i;
				$scope.averageRevScore = totalRevScore/i;
				$scope.totalEmpWeightScore = totalEmpWeightScore;
				$scope.totalEvalWeightScore = totalEvalWeightScore;
				$scope.totalRevWeightScore = totalRevWeightScore;
            });
		};

	}


})();