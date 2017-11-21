(
    function () {

        "use strict";
        var app = angular.module('ARCOPM', ['ui.router', 'ngCookies', 'pickadate', 'ngAnimate', 'chart.js', 'ngDialog', 'ngSanitize' , 'ui.bootstrap', 'ngMaterial'])
                         .run(['$rootScope','$state','$location','Auth', function ($rootScope, $state, $location, Auth) {
								$rootScope.$state = $state;
                                $rootScope.photoUrl = 'https://arcofsdata.archirodon.net/employee_photo/';
                                
								//check if user is still online, if not send him in login page test xx
								$rootScope.$on('$stateChangeStart', function (event, toState, toParams, fromState, fromParams) {
                                   
									if(!Auth.isLoggedIn().login){
                                        $location.path('/login');
                                    }
                                    else if (toState.authenticate)
                                        {
                                           //redirect to homepage if not admin and trying to access admin menu
                                            if (Auth.isLoggedIn().user.isAdmin!=1) {
                                                $state.transitionTo("homepage");
                                                event.preventDefault(); 
                                            }
                                        }
								});
							}]);

        app.filter('customNumber', function(){
                  return function(input, size) {
                    var zero = (size ? size : 4) - input.toString().length + 1;
                    return Array(+(zero > 0 && zero)).join("0") + input;
                  }
              });

       app.directive('errSrc', function() {
          return {
            link: function(scope, element, attrs) {

              scope.$watch(function() {
                  return attrs['ngSrc'];
                }, function (value) {
                  if (!value) {
                    element.attr('src', attrs.errSrc);
                  }
              });

              element.bind('error', function() {
                element.attr('src', attrs.errSrc);
              });
            }
          }
        });

        app.config(['$stateProvider', '$urlRouterProvider', '$locationProvider', defineStates]);


        app.factory('$exceptionHandler', ['$log', '$window', '$injector', function ($log, $window, $injector)  {
            return function (exception, cause)  {
                $log.log(exception);
                try {
                    let commonHeaders = $injector.get('$http').defaults.headers.common;
                    const logMessage = [{
                        level: 'error',
                        message: exception.toString(),
                        url: $window.location.href,
                        stackTrace: exception.stack!=null ? exception.stack : ''
                    }];
                    let xmlhttp = new XMLHttpRequest();
                    xmlhttp.open('POST', 'server/middleware/Logger.php');
                    xmlhttp.setRequestHeader('Content-Type', 'application/json;charset=UTF-8');
                    for (let header in commonHeaders) {
                        if (commonHeaders.hasOwnProperty(header)) {
                            let headerValue = commonHeaders[header];
                            if (angular.isFunction(headerValue)) {
                                headerValue = headerValue();
                            }
                            xmlhttp.setRequestHeader(header, headerValue);
                        }
                    }
                    xmlhttp.send(angular.toJson(logMessage));
                } catch (loggingError) {
                    $log.log(loggingError);
                }
            };
        }]);


        function defineStates($stateProvider, $urlRouterProvider, $locationProvider) {

            $urlRouterProvider.otherwise('homepage');


            $stateProvider
                .state('homepage', {
                    url: '/homepage',
                    templateUrl: 'app/pages/homepage/homepage.view.html',
                });
            $stateProvider
                .state('evaluationLists', {
                    url: '/evaluationLists',
                    templateUrl: 'app/pages/evaluations/evaluations.view.html',
                });
            $stateProvider
                .state('evaluationForm', {
                    url: '/evaluationForm',
                    templateUrl: 'app/pages/evaluations/evaluationForm.view.html',
                });
			$stateProvider
                .state('evaluationGoals', {
                    url: '/evaluationGoals',
                    templateUrl: 'app/pages/goals/goals.view.html',
					params: { cycle: null, cycleID:null, employeesGoals: null }
                });
            $stateProvider
                .state('support', {
                    url: '/support',
                    templateUrl: 'app/pages/support/support.view.html',
                });
             $stateProvider
                .state('statistics', {
                    url: '/statistics',
                    templateUrl: 'app/pages/statistics/statistics.view.html',
                });
			$stateProvider
                .state('help', {
                    url: '/help',
                    templateUrl: 'app/pages/help/help.view.html',
                });
			$stateProvider
                .state('reports', {
                    url: '/reports',
                    templateUrl: 'app/pages/reports/reports.view.html',
                });	
			$stateProvider
                .state('admin', {
                    url: '/admin',
                    templateUrl: 'app/pages/admin/admin.view.html',
                    authenticate: true
                });
            /*$stateProvider
            .state('users',
            {
                url: '/users',
                templateUrl: 'app/pages/users/users.view.html',
                params: { role: null }
            }
            );*/

            $stateProvider
                .state('login', {
                    url: '/login',
                    templateUrl: 'app/pages/login/login.view.html',
                    params: {
                        role: null
                    },
                });

            $stateProvider
                .state('unauthorized', {
                    url: '/unauthorized',
                    templateUrl: 'app/pages/access/unauthorized.view.html',
                    params: {
                        role: null
                    }
                });


        }



    }


)();
