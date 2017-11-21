(function () {
   
    'use strict';
 
    angular.module("ARCOPM").value('loginData', { login:false, user:null});
     
    angular.module('ARCOPM').factory('Auth', Auth);
 
    Auth.$inject = ['$http','loginData','global'];
  
    function Auth($http,loginData,global) {
       
	    var api = global.api;
		
		var factory = {};
        factory.login = true;
        factory.user = null;
 
        factory.signIn = signIn;
        factory.DirectorySignIn = DirectorySignIn;
        factory.signOut = signOut;
        factory.isLoggedIn = isLoggedIn;
        factory.setLogin = setLogin;
         
 
        return factory;
  
 
        function signIn(credentials) {            
            return $http.post(api + '/auth/login', credentials).then(handleSuccess, handleError);
        }
        
        function DirectorySignIn() {
            return $http.get(api + '/auth/login').then(handleSuccess, handleError);
        }
        
        function signOut() {
            return $http.get(api + '/auth/logout').then(handleSuccess, handleError);
        }
 
        function isLoggedIn() {
            return loginData;
        }
 
        function setLogin(status) {
           this.login = status;
        }
 
        // private functions
 
        function handleSuccess(res) {
             return res.data;
			
        }
 
        function handleError(res)
        {
			if (res.status === 500 || res.status === 401) 
            {
                loginData.login = false;
            }
                return res.data;    
        }
    }
 
})();