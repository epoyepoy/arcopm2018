(function () {

    "use strict";

    // Create the Controller
    angular.module("ARCOPM").controller('helpController', helpController)
		.animation('.slide-animation', function () {
			return {
				beforeAddClass: function (element, className, done) {
					var scope = element.scope();

					if (className == 'ng-hide') {
						var finishPoint = element.parent().width();
						if(scope.direction !== 'right') {
							finishPoint = -finishPoint;
						}
						TweenMax.to(element, 0.5, {left: finishPoint, onComplete: done });
					}
					else {
						done();
					}
				},
				removeClass: function (element, className, done) {
					var scope = element.scope();

					if (className == 'ng-hide') {
						element.removeClass('ng-hide');

						var startPoint = element.parent().width();
						if(scope.direction === 'right') {
							startPoint = -startPoint;
						}

						TweenMax.fromTo(element, 0.5, { left: startPoint }, {left: 0, onComplete: done });
					}
					else {
						done();
					}
				}
			};
		});

    // Inject services to the Controller

    helpController.$inject = ["$scope", "Auth", "loginData",  "global",  "ngDialog"];



    // Controller Logic

    function helpController($scope, Auth, loginData, global, ngDialog) {
		$scope.message = "none";

        // Initialize the dashboard
        $scope.init = function () {
            if (!$scope.checkLogin()) {
                return;
            }

            $scope.initNavigation();
        };


        // Initialize the Data service
        $scope.initNavigation = function () {
            $scope.setPage('help');
            global.navPages = [];
            global.navPages.push({
                name: "Help",
                link: "help",
                current: true
            });

        };


		$scope.url1 = "server/templates/videos/1_HomePage-MainMenu.mp4";
		$scope.url2 = "server/templates/videos/2_HowtoGetStarted,Conf.&GoalSetting-EmployeesSide.mp4";
		$scope.url3 = "server/templates/videos/3_HowtoGetStarted,Conf.&GoalSetting-EvaluatorsSide.mp4";
		$scope.url4 = "server/templates/videos/4_PerformanceEvaluation-EmployeesSide(Self-Evaluation).mp4";
		$scope.url5 = "server/templates/videos/5_PerformanceEvaluation-EvaluatorsSide.mp4";
		$scope.url6 = "server/templates/videos/6_PerformanceEvaluation-Dotted-LineManagersSide.mp4";
   		$scope.pauseOrPlay = function(ele){
			console.log('asd');
			console.log(ele);
           //var video = angular.element(ele.srcElement);
            //video[0].pause(); // video.play()
   		};



		$scope.showHelpPopup = function (option) {
            if (!$scope.checkLogin()) {
                return;
            }
            $scope.message = "none";
            $scope.option = option;

            $scope.evaluationConfigurationPopup = ngDialog.open({
                template: 'app/pages/help/popup/help.popup.html',
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

		//employee
		if($scope.option == 'emp_pending'){
			$scope.slides = [
				{image: 'assets/images/goto_evaluations.jpg', description: '1. Go to evaluations page.'},
				{image: 'assets/images/myevaluations.jpg', description: '2. Click "MY EVALUATIONS."'}
			];
		}else if($scope.option == 'emp_config'){
			$scope.slides = [
				{image: 'assets/images/goto_goals.jpg', description: '1. Go to goals page.'},
				{image: 'assets/images/my_goals.jpg', description: '2. Click "MY GOALS."'},
				{image: 'assets/images/select_period.jpg', description: '3. Click "Select Evaluation Period" and choose the evaluation period of which you want to configure your goals.'},
				{image: 'assets/images/configuration.jpg', description: '4. Your information line just appeared and now click the "Configuration" button.'},
				{image: 'assets/images/add_new_goal.jpg', description: '5. Complete configuration of your evaluation. Click "Add New Goal" to add goals. Also choose if you manage a team or not. If so you will be able to see the leadership section in the evaluation.'},
				{image: 'assets/images/new_goal.jpg', description: '6. After completing all fields of a goal, click "Submit".'},
				{image: 'assets/images/submit_goals.jpg', description: '7. After completing configuration (weight summary of all goals must be 100), click "Finish Configuration".'},
				{image: 'assets/images/goals_submitted.jpg', description: '8. After configuration\'s finish you will see two new informative buttons. The first one shows you the goals and the second one shows you a preview of your evaluation form.'}
			];
		}else if($scope.option == 'emp_fillin'){
			$scope.slides = [
				{image: 'assets/images/fill_myevaluation.jpg', description: '1. Click "Go to Evaluation Form".'},
				{image: 'assets/images/fill_myevaluation_emp.jpg', description: '2. Fill in your evaluation. You can pause the evaluation at any time with "Save and Exit" button. "Next"/"Back" buttons will be activated only after you complete all questions of the section.'}
			];
		}else if($scope.option == 'eval_pending'){
		//evaluator
			$scope.slides = [
				{image: 'assets/images/goto_evaluations.jpg', description: '1. Go to evaluations page.'},
				{image: 'assets/images/employees_to_evaluate.jpg', description: '2. Click "EMPLOYEES TO EVALUATE."'}
			];
		}else if($scope.option == 'eval_config'){
			$scope.slides = [
				{image: 'assets/images/goto_goals.jpg', description: '1. Go to goals page.'},
				{image: 'assets/images/emp_goals.jpg', description: '2. Click "CONFIGURE EMPLOYEE GOALS."'},
				{image: 'assets/images/select_period_emp.jpg', description: '3. Click "Select Evaluation Period" and choose the evaluation period of which you want to configure employees\' goals.'},
				{image: 'assets/images/configuration_emp.jpg', description: '4. The list with the employees just appeared and now click the "Configuration" button in the employee\'s line.'},
				{image: 'assets/images/add_new_goal_emp.jpg', description: '5. Complete configuration of employee\'s evaluation. Click "Add New Goal" to add goals. Also choose if employee manages a team or not. If so you will be able to see the leadership section in employee\'s evaluation.'},
				{image: 'assets/images/new_goal_emp.jpg', description: '6. After completing all fields of a goal, click "Submit".'},
				{image: 'assets/images/submit_goals_emp.jpg', description: '7. After completing configuration (weight summary of all goals must be 100), click "Finish Configuration".'},
				{image: 'assets/images/goals_submitted_emp.jpg', description: '8. After configuration\'s finish you will see two new informative buttons. The first one shows you the goals and the second one shows you a preview of your evaluation form.'}
			];
		}else if($scope.option == 'eval_fillin_onbehalf'){
			$scope.slides = [
				{image: 'assets/images/fill_on_behalf.jpg', description: '1. Click "Fill in Evaluation Form on behalf of Employee" and complete the evaluation on behalf of the evaluee, or just wait for the evaluee to complete his/her own evaluation.'},
				{image: 'assets/images/evaluation_onbehalf.jpg', description: '2. Fill in the evaluation on behalf of employee. You can pause the evaluation at any time with "Save and Exit" button. "Next"/"Back" buttons will be activated only after you complete all questions of the section.'}
			];
		}else if($scope.option == 'eval_fillin'){
			$scope.slides = [
				{image: 'assets/images/complete_by_evaluator.jpg', description: '1. Click "Go to Evaluation Form".'},
				{image: 'assets/images/fill_evaluation_eval.jpg', description: '2. Fill in the evaluation. You can pause the evaluation at any time with "Save and Exit" button. "Next"/"Back" buttons will be activated only after you complete all questions of the section. You can also see the answers of the employee.'}
			];
		}else if($scope.option == 'eval_revise'){
			$scope.slides = [
				{image: 'assets/images/revise_by_evaluator.jpg', description: '1. After Dotted Line Manager made his/her comments on the evaluation, click "Go to Evaluation Form" once again, in order to revise the evaluation.'},
				{image: 'assets/images/revise_evaluation.jpg', description: '2. Revise the evaluation. You can also see the answers of the employee, dotted line manager and your own previous answers.'}
			];
		}else if($scope.option == 'dot_pending'){
		//dotted
			$scope.slides = [
				{image: 'assets/images/goto_evaluations.jpg', description: '1. Go to evaluations page.'},
				{image: 'assets/images/employees_to_evaluate.jpg', description: '2. Click "MY EVALUATIONS".'}
			];
		}else if($scope.option == 'dot_fillin'){
			$scope.slides = [
				{image: 'assets/images/complete_by_dotted.jpg', description: '1. Click "Go to Evaluation Form" and fill in your evaluation.'},
				{image: 'assets/images/fill_evaluation_dotted.jpg', description: '2. Fill in the evaluation by writing your comments in sections. You can pause the evaluation at any time with "Save and Exit" button. "Next"/"Back" buttons will be activated only after you complete all comments of a section.'}
			];
		}else if($scope.option == 'loc_passchange'){
			//local user
			$scope.slides = [
				{image: 'assets/images/change_password.jpg', description: '1. Click the key button next to your name section (top rigth of the page).'},
				{image: 'assets/images/change_password_popup.jpg', description: '2. Type your old and new password and click "Submit".'}
			];
		}

        $scope.direction = 'left';
        $scope.currentIndex = 0;

        $scope.setCurrentSlideIndex = function (index) {
            $scope.direction = (index > $scope.currentIndex) ? 'left' : 'right';
            $scope.currentIndex = index;
        };

        $scope.isCurrentSlideIndex = function (index) {
            return $scope.currentIndex === index;
        };

        $scope.prevSlide = function () {
            $scope.direction = 'left';
            $scope.currentIndex = ($scope.currentIndex < $scope.slides.length - 1) ? ++$scope.currentIndex : 0;
        };

        $scope.nextSlide = function () {
            $scope.direction = 'right';
            $scope.currentIndex = ($scope.currentIndex > 0) ? --$scope.currentIndex : $scope.slides.length - 1;
		};

		$scope.downloadFile = function(fileName){
            window.open("server/"+fileName,'_self');
		};

    }

	angular.module('ARCOPM').controller('ModalDemoCtrl', function ($uibModal, $log, $document) {
	  var $ctrl = this;

	  $ctrl.open = function (size, parentSelector,image) {
		var parentElem = parentSelector ?
		  angular.element($document[0].querySelector('.modal-demo ' + parentSelector)) : undefined;
		var modalInstance = $uibModal.open({
		  animation: true,
		  ariaLabelledBy: 'modal-title',
		  ariaDescribedBy: 'modal-body',
		  templateUrl: 'app/pages/help/popup/modal_Image.html',
		  controller: 'ModalInstanceCtrl',
		  controllerAs: '$ctrl',
		  size: size,
		  appendTo: parentElem,
		  resolve: {
			items: function () {
				$ctrl.items = [image];
				return $ctrl.items;
			}
		  }
		});
	  };

	});

	// Please note that $uibModalInstance represents a modal window (instance) dependency.
	// It is not the same as the $uibModal service used above.

	angular.module('ARCOPM').controller('ModalInstanceCtrl', function ($uibModalInstance, items) {
	  var $ctrl = this;
	  $ctrl.items = items;
	  $ctrl.selected = {
		item: $ctrl.items[0]
	  };

	  $ctrl.cancel = function () {
		$uibModalInstance.dismiss('cancel');
	  };
	});

})();
