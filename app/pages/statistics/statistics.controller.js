(function () {

    "use strict";

    // Create the Controller
    angular.module("ARCOPM")
			.config(['ChartJsProvider', function (ChartJsProvider) {
				// Configure all charts
				ChartJsProvider.setOptions({
				  chartColors: ['#bfdbf3', '#72e572', '#f5cb99'],
				  responsive: true
				});
			  }])
			.controller('statisticsController', statisticsController);

    // Inject services to the Controller

    statisticsController.$inject = ["$scope", "Auth", "loginData",  "global", "StatisticsFactory"];



    // Controller Logic


    function statisticsController($scope, Auth, loginData, global, StatisticsFactory) {
		$scope.message = "none";
		$scope.messagePerfEle = "none";
		$scope.messageDesRegion= "none"
		$scope.messageKSA = "none";
		$scope.messageGULF = "none";
		$scope.messageNA = "none";
		$scope.messageEURO = "none";

		$scope.statistics = {};
		$scope.show = true;
		$scope.userID = loginData.user.id;


		/* ------------- CHARTS ------------- */
		//bar chart
		$scope.labelsBar = ['Perf. Stand.', 'Goals', 'Core Comp.', 'Lead. Comp.', 'Overall Perf.'];
		$scope.optionsBar = {
			legend: { display: true },
			responsive:true,
			scales: {
				yAxes: [{
					ticks: {
						min: 1,
						max: 4,
						stepSize: 0.5
					}
				}]
			}
		};
		$scope.seriesBar = ['Average', 'Min', 'Max'];


		//line charts
		$scope.labelsLine = ['Perf. Improv. Needed', 'Build. Capability', 'Achieving Perf.', 'Leading Perf.'];
		$scope.seriesLine = ['Evaluator\'s Performance Evaluations'];
		$scope.datasetOverride = [{ yAxisID: 'y-axis-1'}];
		$scope.optionsLine = {
		legend: { display: true },
		tooltipEvents: [],
			tooltips: {
				callbacks: { //added to display percentages
				        label: function(tooltipItem, data) {
				        	var dataset = data.datasets[tooltipItem.datasetIndex];
				          var total = dataset.data.reduce(function(previousValue, currentValue, currentIndex, array) {
				            return parseInt(previousValue) + parseInt(currentValue);
				          });
				          var currentValue = dataset.data[tooltipItem.index];
				          var precentage = Math.floor(((currentValue/total) * 100)+0.5);
				          return currentValue + " (" +precentage+"%)" ;
				        }
				}
			},
		showTooltips: true,
		tooltipCaretSize: 0,
		onAnimationComplete: function () {
			this.showTooltip(this.segments, true);
		},
		scales: {
			yAxes: [
				{
				  id: 'y-axis-1',
				  type: 'linear',
				  display: true,
				  position: 'left'
			}
		]}
		};


		//Line Chart Options Comparison
		$scope.labelsLineComparison = ['Perf. Improv. Needed', 'Build. Capability', 'Achieving Perf.', 'Leading Perf.'];
		$scope.datasetOverrideComparison = [{ yAxisID: 'y-axis-1'}];
		$scope.optionsLineComparison = {
			tooltipEvents: [],
			tooltips: {
				callbacks: { //added to display percentages
					label: function(tooltipItem, data) {
						var dataset = data.datasets[tooltipItem.datasetIndex];
					  var total = dataset.data.reduce(function(previousValue, currentValue, currentIndex, array) {
						return parseInt(previousValue) + parseInt(currentValue);
					  });
					  var currentValue = dataset.data[tooltipItem.index];
					  var precentage = Math.floor(((currentValue/total) * 100)+0.5);
					  return currentValue + " (" +precentage+"%)" ;
					}
				}
			},
			showTooltips: true,
			tooltipCaretSize: 0,
			legend: { display: true },
			onAnimationComplete: function () {
				this.showTooltip(this.segments, true);
			},
			scales: {
				yAxes: [
					{
					  id: 'y-axis-1',
					  type: 'linear',
					  display: true,
					  position: 'left'
				}
			]}
		};

		// Line char options Self Evaluation
		$scope.optionsLineSelfEval = {
		legend: { display: true },
		tooltips: {
			callbacks: { //added to display percentages
				label: function(tooltipItem, data) {
					var dataset = data.datasets[tooltipItem.datasetIndex];
				  var total = dataset.data.reduce(function(previousValue, currentValue, currentIndex, array) {
					return parseInt(previousValue) + parseInt(currentValue);
				  });
				  var currentValue = dataset.data[tooltipItem.index];
				  var precentage = Math.floor(((currentValue/total) * 100)+0.5);
				  return currentValue + " (" +precentage+"%)" ;
				}
			}
		},
		scales: {
			yAxes: [
				{
				  id: 'y-axis-1',
				  type: 'linear',
				  display: true,
				  position: 'left'
			}
		]}
		};
		$scope.optionsLineCompany = {
		tooltipEvents: [],
		tooltips: {
			callbacks: { //added to display percentages
					label: function(tooltipItem, data) {
						var dataset = data.datasets[tooltipItem.datasetIndex];
					  var total = dataset.data.reduce(function(previousValue, currentValue, currentIndex, array) {
						return parseInt(previousValue) + parseInt(currentValue);
					  });
					  var currentValue = dataset.data[tooltipItem.index];
					  var precentage = Math.floor(((currentValue/total) * 100)+0.5);
					  return currentValue + " (" +precentage+"%)" ;
					}
			}
		},
		showTooltips: true,
		tooltipCaretSize: 0,
		onAnimationComplete: function () {
			this.showTooltip(this.segments, true);
		},
		scales: {
			yAxes: [
				{
				  id: 'y-axis-1',
				  type: 'linear',
				  display: true,
				  position: 'left'
			}
		]}
		};

		//Options For Scores Per Section Distribution
		$scope.optionsLineScoresPerSection = {
			legend: { display: true },
			tooltipEvents: [],
			tooltips: {
				callbacks: { //added to display percentages
						label: function(tooltipItem, data) {
							var dataset = data.datasets[tooltipItem.datasetIndex];
						  var total = dataset.data.reduce(function(previousValue, currentValue, currentIndex, array) {
							return parseInt(previousValue) + parseInt(currentValue);
						  });
						  var currentValue = dataset.data[tooltipItem.index];
						  var precentage = Math.floor(((currentValue/total) * 100)+0.5);
						  return currentValue + " (" +precentage+"%)" ;
						}
				}
			},
			showTooltips: true,
			tooltipCaretSize: 0,
			onAnimationComplete: function () {
				this.showTooltip(this.segments, true);
			},
			scales: {
				yAxes: [
					{
					  id: 'y-axis-1',
					  type: 'linear',
					  display: true,
					  position: 'left'
				}
			]}
			};

		$scope.optionsLineStatsByRegion = {
		legend: { display: true },
		tooltipEvents: [],
		tooltips: {
			callbacks: { //added to display percentages
					label: function(tooltipItem, data) {
						var dataset = data.datasets[tooltipItem.datasetIndex];
					  var total = dataset.data.reduce(function(previousValue, currentValue, currentIndex, array) {
						return parseInt(previousValue) + parseInt(currentValue);
					  });
					  var currentValue = dataset.data[tooltipItem.index];
					  var precentage = Math.floor(((currentValue/total) * 100)+0.5);
					  return currentValue + " (" +precentage+"%)" ;
					}
			}
		},
		showTooltips: true,
		tooltipCaretSize: 0,
		onAnimationComplete: function () {
			this.showTooltip(this.segments, true);
		},
		scales: {
			yAxes: [
				{
				  id: 'y-axis-1',
				  type: 'linear',
				  display: true,
				  position: 'left'
			}
		]}
		};
		$scope.optionsLineCompanySatisf = {
		elements: {
				line: {
						fill: false
					}
		},
		tooltips: {
			callbacks: { //added to display percentages
					label: function(tooltipItem, data) {
						var dataset = data.datasets[tooltipItem.datasetIndex];
					  var total = dataset.data.reduce(function(previousValue, currentValue, currentIndex, array) {
						return parseInt(previousValue) + parseInt(currentValue);
					  });
					  var currentValue = dataset.data[tooltipItem.index];
					  var precentage = Math.floor(((currentValue/total) * 100)+0.5);
					  return currentValue + " (" +precentage+"%)" ;
					}
			}
		},
		showTooltips: true,
		tooltipCaretSize: 0,
		onAnimationComplete: function () {
			this.showTooltip(this.segments, true);
		},
		legend: { display: true },
		scales: {
			yAxes: [
				{
				  id: 'y-axis-1',
				  type: 'linear',
				  display: true,
				  position: 'left'

			}
		]}
		};

		//radar chart
		$scope.labelsRadar = ['Perf. Stand.', 'Goals', 'Core Comp.', 'Lead. Comp.', 'Overall Perf.'];
		$scope.optionsRadar = {
			legend: { display: true },
				scale:{
				ticks: {
					beginAtZero: true,
					max: 4
			}
		}
		};
		$scope.seriesRadar = ["Evaluator's Performance Evaluations", "Employees' Self-Evaluations"];

		//pie chart
		$scope.labelsPie = ["Completed", "Pending"];
		$scope.optionsPie = {
			legend: { display: true },
			tooltipEvents: [],
			tooltips: {
	      	callbacks: { //added to display percentages
				        label: function(tooltipItem, data) {
				        	var dataset = data.datasets[tooltipItem.datasetIndex];
				          var total = dataset.data.reduce(function(previousValue, currentValue, currentIndex, array) {
				            return parseInt(previousValue) + parseInt(currentValue);
				          });
				          var currentValue = dataset.data[tooltipItem.index];
				          var precentage = Math.floor(((currentValue/total) * 100)+0.5);
				          return precentage + "% (" +currentValue+")" ;
				        }
				      }
				    },
			showTooltips: true,
			tooltipCaretSize: 0,
			onAnimationComplete: function () {
				this.showTooltip(this.segments, true);
			}
		};

		$scope.colorsSelfEval = ['#bfdbf3', '#f5cb99'];
		$scope.colorsCompany = ['#4CAF50', '#D96459'];
		//$scope.colorsCompanyStatsByQuestion = ['#bfdbf3','#bfdbf3','#bfdbf3','#bfdbf3','#bfdbf3','#bfdbf3','#bfdbf3','#bfdbf3','#bfdbf3','#bfdbf3','#bfdbf3'];

        // Initialize the dashboard
        $scope.init = function () {
            if (!$scope.checkLogin()) {
                return;
            }


            $scope.initNavigation();
			$scope.getEvaluators();
			$scope.resetFilters();
			$scope.getEvaluationPeriods();
			$scope.getFamilies();
        };


        // Initialize the Data service
        $scope.initNavigation = function () {
            $scope.setPage('statistics');
            global.navPages = [];
            global.navPages.push({
                name: "Statistics",
                link: "statistics",
                current: true
            });

        };


		//stats....... variables are variables for the display of filters
		//Type Of Statistics: Evaluator's Team Scores
		$scope.getStatsForOneEval = function(){
			$scope.statsforone = true;
			$scope.statsselfeval = false;
			$scope.statstendency1 = false;
			$scope.statstendency2 = false;
			$scope.statscompany = false;
			$scope.typeStats = "Evaluator's Team Scores";
			$scope.formrequired = false;
			$scope.filters.type_of_statistics = 0;
			if(!$scope.myStatistics && !$scope.dottedStatistics){
				$scope.selected_evaluator = 'Select All';
				$scope.filters.evaluator = '';
			}
		};

		//Type Of Statistics: Gap Employee Self-Evaluation & Evaluator
		$scope.getStatsForEval = function(){
			$scope.statsforone = false;
			$scope.statsselfeval = true;
			$scope.statstendency1 = false;
			$scope.statstendency2 = false;
			$scope.statscompany = false;
			$scope.typeStats = 'Gap Employee Self-Evaluation & Evaluator';
			$scope.formrequired = false;
			$scope.filters.type_of_statistics = 1;
			if(!$scope.myStatistics && !$scope.dottedStatistics){
				$scope.selected_evaluator = 'Select All';
				$scope.filters.evaluator = '';
			}
		};

		//Type Of Statistics: Evaluators' Comparison
		$scope.getStatsForManyEvals = function(){
			$scope.statsforone = false;
			$scope.statsselfeval = false;
			$scope.statstendency1 = true;
			$scope.statstendency2 = false;
			$scope.statscompany = false;
			$scope.typeStats = "Evaluators' Comparison";
			$scope.formrequired = true;
			$scope.filters.type_of_statistics = 2;
			$scope.selected_evaluator = 'Select All';
			$scope.filters.evaluator = '';
			$scope.filters.res_type = '';
			$scope.res_descr = '';
		};

		//Type Of Statistics: N/A (until further information from HR)
		$scope.getStatsForManyEvals2 = function(){
			$scope.statsforone = false;
			$scope.statsselfeval = false;
			$scope.statstendency1 = false;
			$scope.statstendency2 = true;
			$scope.statscompany = false;
			$scope.typeStats = 'For Many Evaluators - Tendency 2';
			$scope.formrequired = false;
			$scope.selected_evaluator = 'Select All';
			$scope.filters.evaluator = '';
		};

		//Type Of Statistics: General Statistics
		$scope.getStatsForCompany = function(){
			$scope.statsforone = false;
			$scope.statsselfeval = false;
			$scope.statstendency1 = false;
			$scope.statstendency2 = false;
			$scope.statscompany = true;
			$scope.typeStats = 'General Statistics';
			$scope.formrequired = false;
			$scope.selected_evaluator = 'Select All';
			$scope.filters.evaluator = '';
		};

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
		
		$scope.setFamily = function(family){
			if(family){
				$scope.filters.family = family.family_code;
				$scope.selected_family = family.family_code+' - '+family.family_desc;
			}
		};


		$scope.getEvaluators = function(){
			if (!$scope.checkLogin()) {
                return;
            }
			StatisticsFactory.GetEvaluators(loginData.user.id).then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.evaluators = result.evaluators;
            });
		};


		$scope.getStatistics = function(){
			if (!$scope.checkLogin()) {
                return;
            }
			$scope.message = 'loading';
			$scope.messageGULF = 'loading';
			$scope.messageKSA = 'loading';
			$scope.messageEURO = 'loading';
			$scope.messageNA = 'loading';
			$scope.extraPlusMessage = 'loading';
			$scope.messagePerfEle = 'loading';
			$scope.messageDesRegion = 'loading';
			$scope.addSlideEffect = false;
			$scope.showstats = true;
			$scope.emptyTable = false;
			$scope.active = 0;
			$scope.evalsCount = '';

			//variables for the display of results(after submit)
			$scope.forone = $scope.statsforone;
			$scope.selfeval = $scope.statsselfeval;
			$scope.tendency1 = $scope.statstendency1;
			$scope.tendency2 = $scope.statstendency2;
			$scope.company = $scope.statscompany;
			$scope.regionInTitles = $scope.region_descr;
			if($scope.dottedStatistics ){
				$scope.filters.myStatistics = 2;		//dotted statistics
			}else if($scope.myStatistics){
				$scope.filters.myStatistics = 1;		//evaluator statistics
			}else{
				$scope.filters.myStatistics = 0;		//reviewer statistics
			}
			$scope.res_type ? $scope.filters.res_type = 1 : $scope.filters.res_type = 0;
			$('#evals').hide(800,function() {
				$scope.show = false;
				//stats and charts for 'For One Evaluator' and 'Gap Self Evaluation & Evaluator'
				if($scope.forone || $scope.selfeval){
					StatisticsFactory.GetEvaluatorsEvals($scope.filters).then(function (result) {
						$scope.checkifLoggedout(result);
						if((result.evaluations).length){
							$scope.evaluations = result.evaluations;
							$scope.evalsCount = result.evaluations.length;
						}else{
							$scope.emptyTable = true;
						}
						$scope.message = 'none';
					});
					StatisticsFactory.GetPlotChart($scope.filters).then(function (result) {
						$scope.checkifLoggedout(result);
						if(result.evaluations[0]){
							var tempArray = result.evaluations[0];
							$scope.dataBar = [
								[tempArray.AvgPerfScore,tempArray.AvgGoalScore,tempArray.AvgCoreCompScore,tempArray.AvgLeadershipScore,tempArray.AvgOverallScore],
								[tempArray.MinPerfScore,tempArray.MinGoalScore,tempArray.MinCoreCompScore,tempArray.MinLeadershipScore,tempArray.MinOverallScore],
								[tempArray.MaxPerfScore,tempArray.MaxGoalScore,tempArray.MaxCoreCompScore,tempArray.MaxLeadershipScore,tempArray.MaxOverallScore]
							];
							$scope.dataRadar = [
								[tempArray.AvgPerfScore,tempArray.AvgGoalScore,tempArray.AvgCoreCompScore,tempArray.AvgLeadershipScore,tempArray.AvgOverallScore],
								[tempArray.EmpAvgPerfScore,tempArray.EmpAvgGoalScore,tempArray.EmpAvgCoreCompScore,tempArray.EmpAvgLeadershipScore,tempArray.EmpAvgOverallScore]
							];
						}
					});
					StatisticsFactory.GetBellShapedChart($scope.filters).then(function (result) {
						$scope.checkifLoggedout(result);
						if(result.evaluations[0]){
							var tempArray = result.evaluations[0];
							$scope.dataLineForOne = [
								[tempArray.PerfImproNeededCount,tempArray.BuildingCapabilityCount,tempArray.AchievingPerformanceCount,tempArray.LeadingPerformanceCount]
							];
							$scope.dataLineSelfEval = [
								[tempArray.PerfImproNeededCount,tempArray.BuildingCapabilityCount,tempArray.AchievingPerformanceCount,tempArray.LeadingPerformanceCount],
								[tempArray.EmpPerfImproNeededCount,tempArray.EmpBuildingCapabilityCount,tempArray.EmpAchievingPerformanceCount,tempArray.EmpLeadingPerformanceCount]
							];
						}
					});
				//stats and charts for 'Evaluator Tendency'
				}else if($scope.tendency1){
					StatisticsFactory.GetEvaluatorsAvgTendency($scope.filters).then(function (result) {
						$scope.checkifLoggedout(result);
						if((result.evaluations).length){
							$scope.evaluations = result.evaluations;
							$scope.evalsCount = result.evaluations.length;
						}else{
							$scope.emptyTable = true;
						}
						$scope.message = 'none';
					});
					StatisticsFactory.GetChartsDataAvgTendency($scope.filters).then(function (result) {
						$scope.checkifLoggedout(result);
						if(result.evaluations[0]){
							var tempArray = result.evaluations[0];
							$scope.dataBarTendency1 = [
								[tempArray.AVGPScore,tempArray.AVGGScore,tempArray.AVGCScore,tempArray.AVGLScore,tempArray.AVGOScore],
								[tempArray.MINPScore,tempArray.MINGScore,tempArray.MINCScore,tempArray.MINLScore,tempArray.MINOScore],
								[tempArray.MAXPScore,tempArray.MAXGScore,tempArray.MAXCScore,tempArray.MAXLScore,tempArray.MAXOScore]
							];
							$scope.dataLineTendency1 = [
								[tempArray.OPerfImproNeededCount,tempArray.OBuildingCapabilityCount,tempArray.OAchievingPerformanceCount,tempArray.OLeadingPerformanceCount]
							];
						}
					});
				//charts for Company Statistics
				}else if($scope.company)
				{

					//case where we select 'Select All' in regions. Then we bring 10 PieCharts and 2 LineCharts.
						//2 PieCharts with the overall data of both years(previous implementation of GetCompanyStats)
						//and 2 PieCharts for every region separately for both years also(8 PieCharts in summary, 2{years} x 4{regions})
						//1 LineChart for overall data and 1 LineChart with the regions lines separately
				if($scope.filters.region == 'Select All'){
						StatisticsFactory.GetCompanyStatsByRegion($scope.filters).then(function (result) {
							$scope.checkifLoggedout(result);
							if(result.statsRegion){
								$scope.noDataStatsByRegion = false;
								var tempArray = result.statsRegion;
								$scope.seriesLineStatsByRegion = [];
								$scope.dataLineStatsByRegion = [];
								$scope.noDataKSA = true; $scope.noDataNA = true; $scope.noDataGULF = true; $scope.noDataEURO = true;
								$scope.dataPieCurrentKSA = []; $scope.dataPieCurrentNA = []; $scope.dataPieCurrentGULF = []; $scope.dataPieCurrentEURO = [];
								$scope.dataPieNextKSA = []; $scope.dataPieNextNA = []; $scope.dataPieNextGULF = []; $scope.dataPieNextEURO = [];
								angular.forEach(tempArray, function(value) {
									($scope.seriesLineStatsByRegion).push(value.regionCode);
									var tempRow = [value.OPerfImproNeededCount,value.OBuildingCapabilityCount,value.OAchievingPerformanceCount,value.OLeadingPerformanceCount];
									($scope.dataLineStatsByRegion).push(tempRow);
								});
							}else{
								$scope.noDataStatsByRegion = true;
							}
							$scope.messageDesRegion = 'none';
						});
					}else{
						$scope.messageDesRegion = 'none';
					}

					StatisticsFactory.GetCompanyStats($scope.filters).then(function (result)
					{
						$scope.checkifLoggedout(result);
						if(result.completedPies[0]){
							$scope.noData = false;
							var tempArray = result.completedPies[0];
							$scope.dataPieCurrent = [tempArray.completedCurrentPeriod, parseInt(tempArray.totalAssignedCurrentPeriod)- parseInt(tempArray.completedCurrentPeriod)];
							$scope.dataPieNext = [tempArray.completedNextPeriod, parseInt(tempArray.totalAssignedNextPeriod) - parseInt(tempArray.completedNextPeriod)];
							$scope.dataLineCompany = [
								[tempArray.OPerfImproNeededCount,tempArray.OBuildingCapabilityCount,tempArray.OAchievingPerformanceCount,tempArray.OLeadingPerformanceCount]
							];
							$scope.currentPeriod = tempArray.currentPeriodDescription;
							$scope.nextPeriod = tempArray.nextPeriodDescription;
						}else{
							$scope.noData = true;
						}
						$scope.message = 'none';
					});
					$scope.filtersksa = angular.copy($scope.filters);
					$scope.filtersksa.region='ksa';
					StatisticsFactory.GetCompanyStats($scope.filtersksa).then(function (result)
					{
						$scope.checkifLoggedout(result);
						$scope.messageKSA = 'none';
						if(result.completedPies[0]){
							$scope.noData = false;
							var tempArray = result.completedPies[0];
							$scope.dataPieCurrentKSA = [tempArray.completedCurrentPeriod, parseInt(tempArray.totalAssignedCurrentPeriod)- parseInt(tempArray.completedCurrentPeriod)];
							$scope.dataPieNextKSA = [tempArray.completedNextPeriod, parseInt(tempArray.totalAssignedNextPeriod) - parseInt(tempArray.completedNextPeriod)];
							$scope.noDataKSA = false;
						}
					});

					$scope.filtersgulf = angular.copy($scope.filters);
					$scope.filtersgulf.region='gulf';
					StatisticsFactory.GetCompanyStats($scope.filtersgulf).then(function (result)
					{
						$scope.checkifLoggedout(result);
						$scope.messageGULF = 'none';
						if(result.completedPies[0]){
							$scope.noData = false;
							var tempArray = result.completedPies[0];
							$scope.dataPieCurrentGULF = [tempArray.completedCurrentPeriod, parseInt(tempArray.totalAssignedCurrentPeriod)- parseInt(tempArray.completedCurrentPeriod)];
							$scope.dataPieNextGULF = [tempArray.completedNextPeriod, parseInt(tempArray.totalAssignedNextPeriod) - parseInt(tempArray.completedNextPeriod)];
							$scope.noDataGULF = false;
						}
					});

					$scope.filtersna = angular.copy($scope.filters);
					$scope.filtersna.region='na';
					StatisticsFactory.GetCompanyStats($scope.filtersna).then(function (result)
					{
						$scope.checkifLoggedout(result);
						$scope.messageNA = 'none';
						if(result.completedPies[0]){
							$scope.noData = false;
							var tempArray = result.completedPies[0];
							$scope.dataPieCurrentNA = [tempArray.completedCurrentPeriod, parseInt(tempArray.totalAssignedCurrentPeriod)- parseInt(tempArray.completedCurrentPeriod)];
							$scope.dataPieNextNA = [tempArray.completedNextPeriod, parseInt(tempArray.totalAssignedNextPeriod) - parseInt(tempArray.completedNextPeriod)];
							$scope.noDataNA = false;
							
						}
					});

					$scope.filterseuro = angular.copy($scope.filters);
					$scope.filterseuro.region='europe';
					StatisticsFactory.GetCompanyStats($scope.filterseuro).then(function (result)
					{
						$scope.checkifLoggedout(result);
						$scope.messageEURO = 'none';
						if(result.completedPies[0]){
							$scope.noData = false;
							var tempArray = result.completedPies[0];
							$scope.dataPieCurrentEURO = [tempArray.completedCurrentPeriod, parseInt(tempArray.totalAssignedCurrentPeriod)- parseInt(tempArray.completedCurrentPeriod)];
							$scope.dataPieNextEURO = [tempArray.completedNextPeriod, parseInt(tempArray.totalAssignedNextPeriod) - parseInt(tempArray.completedNextPeriod)];
							$scope.noDataEURO = false;
							
						}
					});
					//Get Bar Chart Data for Scores per Question
					StatisticsFactory.GetCompanyStatsByQuestion($scope.filters).then(function (result){
						$scope.checkifLoggedout(result);
						$scope.dataBarByQuestionId2 = [];$scope.labelsBarByQuestionId2 = [];$scope.dataBarByQuestionId4 = [];
						$scope.labelsBarByQuestionId4 = [];$scope.dataBarByQuestionId5 = [];$scope.labelsBarByQuestionId5 = [];
						$scope.hoverBarByQuestionId2 = [];$scope.hoverBarByQuestionId4 = [];$scope.hoverBarByQuestionId5 = [];
						if(result.avgScorePerQuestion[0]){
							//Create options for Company Chart -> Section: Performance Standards Average Score per Question
							$scope.optionsBarCompanyPS = barChartOptionsForCompany($scope.hoverBarByQuestionId2);

							//Create options for Company Chart -> Section: Core Competencies Average Score per Question
							$scope.optionsBarCompanyCC = barChartOptionsForCompany($scope.hoverBarByQuestionId4);

							//Create options for Company Chart -> Section: Leadership Competencies Average Score per Question
							$scope.optionsBarCompanyLC = barChartOptionsForCompany($scope.hoverBarByQuestionId5);

							$scope.noDataScorePerQuestion = false;
							var tempArray = result.avgScorePerQuestion;
							angular.forEach(tempArray, function(value) {
								if(value.ID == 2){
									($scope.dataBarByQuestionId2).push(value.average);
									($scope.labelsBarByQuestionId2).push((value.QuestionDescripton).substr(0,20) + '...');
									($scope.hoverBarByQuestionId2).push((value.QuestionDescripton).slice(0,-1));
								}else if(value.ID == 4){
									($scope.dataBarByQuestionId4).push(value.average);
									($scope.labelsBarByQuestionId4).push((value.QuestionDescripton).substr(0,20) + '...');
									($scope.hoverBarByQuestionId4).push((value.QuestionDescripton).slice(0,-1));
								}else if(value.ID == 5){
									($scope.dataBarByQuestionId5).push(value.average);
									($scope.labelsBarByQuestionId5).push((value.QuestionDescripton).substr(0,20) + '...');
									($scope.hoverBarByQuestionId5).push((value.QuestionDescripton).slice(0,-1));
								}
							});
						}else{
							$scope.noDataScorePerQuestion = true;
						}
					});

					//Get Line Chart Data for Scores per Section
					StatisticsFactory.GetScoresPerSection($scope.filters).then(function (result)
					{
						$scope.checkifLoggedout(result);
						if(result.scoresPerSection[0]){
							$scope.noDataScorePerSection = false;
							var tempArray = result.scoresPerSection;
							//$scope.labelsLineCompanySection = ['Very Dissatisfied', 'Dissatisfied', 'Neither Dissatisfied Nor Satisfied', 'Satisfied', 'Very Satisfied'];
							$scope.seriesLineCompanySection = ['Performance Standards', 'Goals', 'Core Competencies', 'Leadership Competencies'];
							$scope.dataLineCompanySection = [
								[tempArray[0].PPerfImproNeededCount,tempArray[0].PBuildingCapabilityCount,tempArray[0].PAchievingPerformanceCount,tempArray[0].PLeadingPerformanceCount],
								[tempArray[0].GPerfImproNeededCount,tempArray[0].GBuildingCapabilityCount,tempArray[0].GAchievingPerformanceCount,tempArray[0].GLeadingPerformanceCount],
								[tempArray[0].CPerfImproNeededCount,tempArray[0].CBuildingCapabilityCount,tempArray[0].CAchievingPerformanceCount,tempArray[0].CLeadingPerformanceCount],
								[tempArray[0].LPerfImproNeededCount,tempArray[0].LBuildingCapabilityCount,tempArray[0].LAchievingPerformanceCount,tempArray[0].LLeadingPerformanceCount]
							];
							$scope.messagePerfEle = 'none';
						}else{
							$scope.noDataScorePerSection = true;
						}
					});
					

					StatisticsFactory.GetSatisfactionByQuestion($scope.filters).then(function (result)
					{
						$scope.checkifLoggedout(result);
						if(result.satisfactionByQuestion[0]){
							$scope.noDataSatisfByQuest = false;
							var tempArray = result.satisfactionByQuestion;
							$scope.labelsLineCompanySatisf = ['Very Dissatisfied', 'Dissatisfied', 'Neither Dissatisfied Nor Satisfied', 'Satisfied', 'Very Satisfied'];
							$scope.seriesLineCompanySatisf = [tempArray[0].Question, tempArray[1].Question];
							
							$scope.dataLineCompanySatisf = [
							
								[tempArray[0].vdisatisfiedCnt,tempArray[0].disatisfiedCnt,tempArray[0].nsatisfiedCnt,tempArray[0].satisfiedCnt,tempArray[0].vsatisfiedCnt],
								[tempArray[1].vdisatisfiedCnt,tempArray[1].disatisfiedCnt,tempArray[1].nsatisfiedCnt,tempArray[1].satisfiedCnt,tempArray[1].vsatisfiedCnt]
							];
						}else{
							$scope.noDataSatisfByQuest = true;
						}
					});

					$scope.filters.questionid = 12;
					$scope.labelsLineCompanySatisf = ['Very Dissatisfied', 'Dissatisfied', 'Neither Dissatisfied Nor Satisfied', 'Satisfied', 'Very Satisfied'];
					StatisticsFactory.GetSatisfactionByGradeQuestion($scope.filters).then(function (result) {
						$scope.checkifLoggedout(result);
						if(result.satisfactionByQuestion[0]){
							$scope.noDataSatisfByGradeQuest12 = false;
							var tempArray = result.satisfactionByQuestion;
							
							$scope.seriesLineCompanySatisf12 = [];
							$scope.dataLineCompanySatisf12 = [];
							angular.forEach(tempArray, function(value) {
								($scope.seriesLineCompanySatisf12).push('Grade '+value.empGrade);
								var tempRow = [value.vdisatisfiedCnt,value.disatisfiedCnt,value.nsatisfiedCnt,value.satisfiedCnt,value.vsatisfiedCnt];
								($scope.dataLineCompanySatisf12).push(tempRow);
							});
						}else{
							$scope.noDataSatisfByGradeQuest12 = true;
						}
						//i am putting this call inside the previous in order to be able to change the questionid filter, otherwise is always 14 because javascript is
						//running faster than the action and questionid prevents to change
						//3rd chart
						$scope.filters.questionid = 14;
						StatisticsFactory.GetSatisfactionByGradeQuestion($scope.filters).then(function (result) {
							$scope.checkifLoggedout(result);
							if(result.satisfactionByQuestion[0]){
								$scope.noDataSatisfByGradeQuest14 = false;
								var tempArray = result.satisfactionByQuestion;
								$scope.seriesLineCompanySatisf14 = [];
								$scope.dataLineCompanySatisf14 = [];
								angular.forEach(tempArray, function(value) {
									($scope.seriesLineCompanySatisf14).push('Grade '+value.empGrade);
									var tempRow = [value.vdisatisfiedCnt,value.disatisfiedCnt,value.nsatisfiedCnt,value.satisfiedCnt,value.vsatisfiedCnt];
									($scope.dataLineCompanySatisf14).push(tempRow);
								});
							}else{
								$scope.noDataSatisfByGradeQuest14 = true;
							}
							$scope.extraPlusMessage = 'none';
						});
					});
				}

			});
		};

		$scope.getEvaluationPeriods = function(){
			if (!$scope.checkLogin()) {
                return;
            }
			StatisticsFactory.GetEvaluationPeriods().then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.cycles = result.evaluationPeriods;
            });
		};
		
		$scope.getFamilies = function(){
			if (!$scope.checkLogin()) {
                return;
            }
			StatisticsFactory.GetFamilies(loginData.user.id).then(function (result) {
				$scope.checkifLoggedout(result);
				$scope.families = result.familyList;
				
				if(($scope.families).length == 1){
					$scope.setFamily($scope.families[0]);
				}
            });
			
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
				employee : '',
				evaluator : '',
				grade : '',
				lead_comp_descr : 'Select All',
				goals_descr : 'Select All',
				over_perf_descr : 'Select All',
				perf_stand_descr : 'Select All',
				core_comp_descr : 'Select All',
				emp_lead_comp_descr : 'Select All',
				emp_goals_descr : 'Select All',
				emp_over_perf_descr : 'Select All',
				emp_perf_stand_descr : 'Select All',
				emp_core_comp_descr : 'Select All',
				position : '',
				region : '',
				is_manager : '',
				has_goals : '',
				loggedin_user : loginData.user.id,
				myStatistics : 0,
				questionid : '',
				res_type : '',
				project : '',
				cycleid : '',
				type_of_statistics : '',
				calibrated : 0,
				family : ''
			};
			$scope.grade_descr = '';
			$scope.res_descr = '';
			$scope.region_descr = '';
			$scope.selected_cycleid = '';
			$scope.selected_family = '';
			$scope.calibrated_res = 'No';
			if(!$scope.myStatistics && !$scope.dottedStatistics){
				$scope.typeStats = '';
				$scope.selected_evaluator = 'Select All';
			}
		};


		$scope.initialLetters = function(str){
			if(str){
				var matches = str.match(/\b(\w)/g);
				var descr = '';
				angular.forEach(matches, function(value) {
					descr += value;
				});
			}else{
				var descr = '';
			}
			return descr;
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

		$scope.showMessageDestrRegion = function (message) {
            if ($scope.messageDesRegion === message) {
                return true;
            }

            return false;
        };

		$scope.showMessagePerfEle = function (message) {
            if ($scope.messagePerfEle === message) {
                return true;
            }

            return false;
        };


		$scope.showExtraMessageKSA = function (message) {
            if ($scope.messageKSA === message) {
                return true;
            }

            return false;
        };

		$scope.showExtraMessageGULF = function (message) {
            if ($scope.messageGULF === message) {
                return true;
            }

            return false;
		};

		$scope.showExtraMessageNA = function (message) {
            if ($scope.messageNA === message) {
                return true;
            }

            return false;
		};

		$scope.showExtraMessageEURO = function (message) {
            if ($scope.messageEURO === message) {
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


		//function that creates the options(mainly the tooltips) of bar charts in Company Statistics
		function barChartOptionsForCompany(hoverBarByQuestionId){
			return {
				responsive:true,
				scales: {
					yAxes: [{ ticks: {min: 1,max: 4,stepSize: 0.5 } }]
				},
				tooltipEvents: [],
				tooltips: {
					callbacks: { //added to display percentages
						label: function(tooltipItem, data) {
							var dataset = data.datasets[tooltipItem.datasetIndex];
							//retrieve value of current bar
							var currentValue = dataset.data[tooltipItem.index];
							//retrieve the correspondance label of current bar from SQL database
							var str = hoverBarByQuestionId[tooltipItem.index];
							//if length is above 270 characters then we split it in 4 strings and we put them in the returning array(4 items)
							//in order to present the label in 4 lines(tooltip requires an array in order to present lines)
							if(str.length > 270){
								//n : find the 1st correspondance(index) of ' ' character after the first 90 characters
								var n = str.indexOf(" ",90);
								//m : find the 1st correspondance(index) of ' ' character after the first 180 characters
								var m = str.indexOf(" ",180);
								//o : find the 1st correspondance(index) of ' ' character after the first 270 characters
								var o = str.indexOf(" ",270);
								//cut string beggining from 0 to n character
								var left_text = str.substr(0, n);
								//cut string beggining from n to m character
								var middle_text = str.substr(n, (m-n));

								//with the code below we are catching the case where the whole string is just above 270 characters
								//and there is no ' ' character after the first 270 characters. In this case we create 3 lines,
								//otherwise we create 4 lines as it is meant in this block of code
								if(o !== -1){
									//cut string beggining from m to o character
									var middle_text2 = str.substr(m, (o-m));
									//cut string from o to the end of characters
									var rest_text = str.substr(o);
									//return the 4 strings we created in an array in order to see them in 4 lines(by adding also in the last string the :currentValue)
									return [left_text, middle_text, middle_text2, rest_text + ' : ' + currentValue];
								}else{
									//cut string from m to the end of characters
									var rest_text = str.substr(m);
									//return the 3 strings we created in an array in order to see them in 3 lines(by adding also in the last string the :currentValue)
									return [left_text, middle_text, rest_text + ' : ' + currentValue];
								}

							//if length is between 180 and 270 characters then we split it in 3 strings and we put them in the returning array(3 items)
							//in order to present the label in 3 lines(tooltip requires an array in order to present lines)
							}else if(str.length > 180 && str.length <= 270){
								//n : find the 1st correspondance(index) of ' ' character after the first 90 characters
								var n = str.indexOf(" ",90);
								//m : find the 1st correspondance(index) of ' ' character after the first 180 characters
								var m = str.indexOf(" ",180);
								//cut string beggining from 0 to n character
								var left_text = str.substr(0, n);
								//cut string beggining from n to m character
								var middle_text = str.substr(n, (m-n));
								//cut string from m to the end of characters
								var rest_text = str.substr(m);
								//return the 3 strings we created in an array in order to see them in 3 lines(by adding also in the last string the :currentValue)
								return [left_text, middle_text, rest_text + ' : ' + currentValue];
							//if length is between 90 and 180 characters then we split it in 2 strings and we put them in the returning array(2 items)
							}else if(str.length > 69 && str.length <= 180){
								//n : find the 1st correspondance(index) of ' ' character after the first 69 characters
								var n = str.indexOf(" ",69);
								//cut string beggining from 0 to n character
								var left_text = str.substr(0, n);
								//cut string from n to the end of characters
								var rest_text = str.substr(n);
								//return the 2 strings we created in an array in order to see them in 2 lines(by adding also in the last string the :currentValue)
								return [left_text, rest_text + ' : ' + currentValue];
							//if length is below 69 characters then we leave the string as it is, in 1 line
							}else{
								return hoverBarByQuestionId[tooltipItem.index] + ' : ' + currentValue;
							}
						}
					}
				},
				showTooltips: true,
				tooltipCaretSize: 0,
				onAnimationComplete: function () {
					this.showTooltip(this.segments, true);
				}
			};
		}
    }

})();
