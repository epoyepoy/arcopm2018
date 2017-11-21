(function () {

    "use strict";

    // Create the Controller
    angular.module("ARCOPM").controller('reportsController', reportsController);

    // Inject services to the Controller

    reportsController.$inject = ["$scope", "Auth", "loginData",  "global", "ReportsFactory"];



    // Controller Logic


    function reportsController($scope, Auth, loginData, global, ReportsFactory) {
		$scope.message = "none";
		$scope.extraMessage = "none";
		$scope.mode = 'Show';

		$scope.statistics = {};
		$scope.show = true;
		$scope.userID = loginData.user.id;

        // Initialize the dashboard
        $scope.init = function () {
            if (!$scope.checkLogin()) {
                return;
            }


            $scope.initNavigation();
			$scope.resetFilters();
			$scope.getEvaluationPeriods();
        };


        // Initialize the Data service
        $scope.initNavigation = function () {
            $scope.setPage('reports');
            global.navPages = [];
            global.navPages.push({
                name: "Reports",
                link: "Reports",
                current: true
            });

        };


		//stats....... variables are variables for the display of filters
		//Type Of Statistics: Evaluator's Team Scores
//		$scope.getStatsForOneEval = function(){
//			$scope.statsforone = true;
//			$scope.statsselfeval = false;
//			$scope.statstendency1 = false;
//			$scope.statstendency2 = false;
//			$scope.statscompany = false;
//			$scope.typeStats = "Evaluator's Team Scores";
//			$scope.formrequired = false;
//			$scope.filters.type_of_statistics = 0;
//			if(!$scope.myStatistics && !$scope.dottedStatistics){
//				$scope.selected_evaluator = 'Select All';
//				$scope.filters.evaluator = '';
//			}
//		};


		$scope.setEvaluator = function(evaluator){
			if(evaluator){
				$scope.filters.evaluator = evaluator.empNo;
				$scope.selected_evaluator = evaluator.empNo + ' , ' + evaluator.empName;
			}else{
				$scope.filters.evaluator = loginData.user.id;
				$scope.selected_evaluator = loginData.user.id + ' , ' + (loginData.user.first_name).trim() + ' - ' + (loginData.user.family_name).trim();
			}
		};
		
		
		$scope.setEvalPeriod = function(cycle){
			if(cycle){
				$scope.filters.cycleid = cycle.CycleID;
				$scope.selected_cycleid = (cycle.CycleDescription).trim();
			}
		};


		$scope.getReports = function(){
			if (!$scope.checkLogin()) {
                return;
            }
			$scope.message = 'loading';
			$scope.extraMessage = 'loading';
			$scope.extraPlusMessage = 'loading';
			$scope.mode = 'Show';
			$scope.addSlideEffect = false;
			$scope.showreps = true;
			$scope.emptyTable = false;
			
			$scope.active = 0;
			
			$('#evals').hide(800,function() {
				$scope.show = false;
				ReportsFactory.GetMyReportingLine($scope.filters).then(function (result) {
					//console.log(result.myReportingLine[0]);
					$scope.checkifLoggedout(result);
					if((result.myReportingLine).length){
						$scope.myReportingLine = result.myReportingLine;
						
						//initialize organization chart data with reviewer(result.myReportingLine[0]) and evaluator(result.myReportingLine[1])
						//these are always the first two boxes of the organization chart
						var datasource = {
							'id': result.myReportingLine[0].empNo,
							'name': result.myReportingLine[0].empName,
							'title': result.myReportingLine[0].empPosition,
							'office':result.myReportingLine[0].RelationshipDesc,
							'className': 'superiors',
							'relationship': '001',
							'children': [
								{'id': result.myReportingLine[1].empNo,'name': result.myReportingLine[1].empName, 'title': result.myReportingLine[1].empPosition, 'office':result.myReportingLine[1].RelationshipDesc, 'className': 'superiors', 'relationship': '101','children': []}
							]
						};
						
						//dotted line managers(one below the other)
						var dottedCnt = 0;
						angular.forEach(result.myReportingLine, function(variable) {
							if(variable.ReportingOrder == 3){
								dottedCnt++;
								if(dottedCnt==1){
									(datasource.children[0].children).push({'id': variable.empNo,'name': variable.empName, 'title': variable.empPosition, 'office':variable.RelationshipDesc, 'className': variable.RelationshipDesc=='EVALUATOR' ? 'evaluators' : 'dotted', 'relationship':'101','children':[]});
								}else if(dottedCnt==2){
									(datasource.children[0].children[0].children).push({'id': variable.empNo,'name': variable.empName, 'title': variable.empPosition, 'office':variable.RelationshipDesc, 'className': variable.RelationshipDesc=='EVALUATOR' ? 'evaluators' : 'dotted', 'relationship':'101','children':[]});
								}else if(dottedCnt==3){
									(datasource.children[0].children[0].children[0].children).push({'id': variable.empNo,'name': variable.empName, 'title': variable.empPosition, 'office':variable.RelationshipDesc, 'className': variable.RelationshipDesc=='EVALUATOR' ? 'evaluators' : 'dotted', 'relationship':'101','children':[]});
								}
							}
						});
						
						//then we create the loggedinUser and afterwards the children of the loggedinUser, beggining from the 1st element below of dotted managers(if exist) of the result.myReportingLine,
						//the first 2 elements in the initialization of datasource are 'my reviewer' and 'my evaluator' as we said 
						if(dottedCnt==0){
							(datasource.children[0].children).push({'id': loginData.user.id,'name': loginData.user.family_name+' - '+loginData.user.first_name, 'title': loginData.user.jobPositionName, 'office':'ME','className': 'myself', 'relationship': '101', 'children': []});
							var loggedinUser_children = datasource.children[0].children[0].children;	//no dotted managers
						}else if(dottedCnt==1){
							(datasource.children[0].children[0].children).push({'id': loginData.user.id,'name': loginData.user.family_name+' - '+loginData.user.first_name, 'title': loginData.user.jobPositionName, 'office':'ME','className': 'myself', 'relationship': '101', 'children': []});
							var loggedinUser_children = datasource.children[0].children[0].children[0].children;	//one dotted manager
						}else if(dottedCnt==2){
							(datasource.children[0].children[0].children[0].children).push({'id': loginData.user.id,'name': loginData.user.family_name+' - '+loginData.user.first_name, 'title': loginData.user.jobPositionName, 'office':'ME','className': 'myself', 'relationship': '101', 'children': []});
							var loggedinUser_children = datasource.children[0].children[0].children[0].children[0].children;	//two dotted managers
						}else if(dottedCnt==3){
							(datasource.children[0].children[0].children[0].children[0].children).push({'id': loginData.user.id,'name': loginData.user.family_name+' - '+loginData.user.first_name, 'title': loginData.user.jobPositionName, 'office':'ME','className': 'myself', 'relationship': '101', 'children': []});
							var loggedinUser_children = datasource.children[0].children[0].children[0].children[0].children[0].children;	//three dotted managers
						}
						angular.forEach(result.myReportingLine, function(value) {
							if(value.ReportingOrder == 4){
								loggedinUser_children.push({'id': value.empNo,'name': value.empName, 'title': value.empPosition, 'office':value.RelationshipDesc, 'className': value.RelationshipDesc=='EVALUATOR' ? 'evaluators' : 'dotted', 'relationship': value.AssignedEvaluations>0 ? '111' : '110','children':[]});
								
								//here we create the children of children of loggedinUser which we will pass them to $.mockjax
								//we are making a new loop inside result and we separate users with ReportingOrder = 4(children of children of loggedinUser)
								//then we take the parentId of these rows and we check it with the children id(from above loop)
								//and then we pass them to $.mockjax below
								var respText = {'children':[]};			//value in which we will pass the children of children of loggedinUser
								angular.forEach(result.myReportingLine, function(val) {
									if(val.ReportingOrder == 5){
										if(value.empNo == val.ParentID){
											(respText.children).push({'id': val.empNo,'name': val.empName, 'title': val.empPosition, 'office':val.RelationshipDesc, 'className': val.RelationshipDesc=='EVALUATOR' ? 'evaluators' : val.RelationshipDesc=='REVIEWER' ? 'reviewers' : 'dotted', 'relationship': '110','children':[]});
										}
									}
								});
							//console.log(respText);
							
							//here we create the $.mockjax of children of loggedinUser
							//mockjax is creating the children nodes only after you click them
								$.mockjax({
									url: '/orgchart/children/'+value.empNo,
									contentType: 'application/json',
									responseTime: 1000,
									responseText: respText
								});
							}
						});
						
						//create custom nodes(just add office above node)
						var nodeTemplate = function(data) {
							return '<span class="office">'+data.office+'</span><div class="title">&nbsp;'+data.name+'</div><div class="content">&nbsp;'+data.title+'</div>';
						};
						
	
						var ajaxURLs = {
						  'children': '/orgchart/children/'
						};
						
						$('#chart-container').empty();
						//create chart
						$('#chart-container').orgchart({
							'data' : datasource,
							'ajaxURL': ajaxURLs,
							'nodeTemplate': nodeTemplate,
							'nodeId': 'id',
							'createNode': function($node, data) {
							var secondMenuIcon = $('<i>', {
							  'class': 'fa fa-info-circle second-menu-icon',
							  click: function() {
								$(this).siblings('.second-menu').toggle();
							  }
							});
							var secondMenu = '<div class="second-menu"><img class="avatar" src="'+$scope.photoUrl+data.id+'b.jpg" onerror="this.src=\''+$scope.photoUrl+'male.gif\'"></div>';
							$node.append(secondMenuIcon).append(secondMenu);
						  }
						});
					}else{
						$scope.emptyTable = true;
					}
					$scope.message = 'none';
				});
			});
		};
		
		
		$scope.showAllImages = function(){
			if($scope.mode == 'Show'){
				$('.second-menu').show();
				$scope.mode = 'Hide';
			}else{
				$('.second-menu').hide();
				$scope.mode = 'Show';
			}
		};
		
		$scope.getEvaluationPeriods = function(){
			if (!$scope.checkLogin()) {
                return;
            }
			ReportsFactory.GetEvaluationPeriods().then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.cycles = result.evaluationPeriods;
            });
		};
		
		
		$scope.percentage = function(assigned,completed){
			var percent = Math.floor(((completed/assigned) * 100)+0.5);
			if(!isNaN(percent)){
				return '('+percent+'%)';
			}else{
				return '';
			}
		};

		$scope.showFilters = function(){
			if(!$scope.show){
				$scope.show = true;
				$('#evals').show(function(){});
			}else{
				$scope.show = false;
				$('#evals').hide(800,function() {});
			}
		};


		$scope.resetFilters = function(){
			$scope.filters = {

				loggedin_user : loginData.user.id,
				cycleid : ''
			};
			$scope.selected_cycleid = '';
		};

		//actions
		$scope.checkifLoggedout = function (result){
			if (result==401){
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

		$scope.showExtraMessage = function (message) {
            if ($scope.extraMessage === message) {
                return true;
            }

            return false;
        };

		$scope.showExtraPlusMessage = function (message) {
            if ($scope.extraPlusMessage === message) {
                return true;
            }

            return false;
        };

    }

})();
