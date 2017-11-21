(function () {
   
    'use strict';
 
     
    angular.module('ARCOPM').factory('Util', Util);
 
    Util.$inject = ["ngDialog"];
  
    function Util(ngDialog) {
       
	    
		var factory = {};
        
        factory.showPopup = showPopup;
        factory.getIndex = getIndex;
        factory.calculateRemainingDays  = calculateRemainingDays;
        factory.isEmpty = isEmpty; 
        factory.roundNum = roundNum;
        factory.getCookie = getCookie;
        factory.setCookie = setCookie;
        factory.getCookieObject = getCookieObject;
        factory.setCookieObject = setCookieObject;
        
        return factory;
        
        
        // Show a dialogue popup
        function showPopup(template,scope)
         { 

             var dialog = ngDialog.open({
                    template: template,
                    className: 'ngdialog-theme-default',
                    scope: scope
                });
             
             return dialog;
         }
        
        
        // Checks if an array is null or empty
        function isEmpty(array)
        {
            if(array)
            {
                if(array.length === 0)
                {
                    return true;
                }
            }
            
            return false;
        }
        
        // Finds the index of the element with id: "id" in the "array" array.
        function getIndex(array,id)
        {
            if(array)
            {
                for(var i=0;i<array.length;i++)
                {
                    if(array[i].id == id)
                    {
                        return i;
                    }
                }
            }
            return -1;
        }
        
        
  
        /************* Number formating  ***********/
        
        function roundNum(value, decimals)
        {
            return Number(Math.round(value+'e'+decimals)+'e-'+decimals);
        }
        
       
        /************* Cookies **************/
         
        
        // Sets a new cookie
        function setCookie(key,value)
         {
             document.cookie = key+"="+value;
         }
         
        // Retrieves a cookie
         function getCookie(key)
         {
            var name = key + "=";
            var ca = document.cookie.split(';');
            for(var i=0; i<ca.length; i++)
            {
                var c = ca[i];
                while (c.charAt(0)==' ') c = c.substring(1);
                if (c.indexOf(name) == 0) return c.substring(name.length,c.length);
            }
            return "";
         }
         
        // Sets a new Cookie Object
         function setCookieObject(key,obj)
         {
             var value = JSON.stringify(obj);
             document.cookie = key+"="+value;
         }
         
        // Retrieves an object from the cookie
         function getCookieObject(key)
         {
            var name = key + "=";
            var ca = document.cookie.split(';');
            for(var i=0; i<ca.length; i++)
            {
                var c = ca[i];
                while (c.charAt(0)==' ')
                {
                    c = c.substring(1);
                }
                if (c.indexOf(name) == 0)
                {
                    var str = c.substring(name.length,c.length);
                    return JSON.parse(str);
                }
            }
            return "";
         }
 
        
    }
 
})();