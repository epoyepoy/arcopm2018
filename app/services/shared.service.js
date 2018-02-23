(function () {
  angular.module('ARCOPM').factory('dataService', function() {
  
  
      // private variable
  var evaluationID;
  var onBehalfUser;
  var state;
  var resume;
  var empid;
  var fromList;
  var hasDotted;

  // public API
       return {
            getEvaluationID: function () {
                if(angular.isUndefined(evaluationID))
                    {evaluationID=sessionStorage["evalID"]; }
                return evaluationID;
            },
            setEvaluationID: function(value) {
                evaluationID = value;
                sessionStorage["evalID"]=value; 
            },
			getBehalfUser: function () {
                if(angular.isUndefined(onBehalfUser))
                    {onBehalfUser=sessionStorage["behalfUser"]; }
                return onBehalfUser;
            },
			setBehalfUser: function(value) {
                onBehalfUser = value;
                sessionStorage["behalfUser"]=value; 
            },
			getState: function () {
                if(angular.isUndefined(state))
                    {state=sessionStorage["State"]; }
                return state;
            },
			setState: function(value) {
                state = value;
                sessionStorage["State"]=value; 
            },
			getResume: function () {
                if(angular.isUndefined(resume))
                    {resume=sessionStorage["resumeSection"]; }
                return resume;
            },
			setResume: function(value) {
                resume = value;
                sessionStorage["resumeSection"]=value; 
            },
			getEmpID: function () {
                if(angular.isUndefined(empid))
                    {empid=sessionStorage["EmployeeID"]; }
                return empid;
            },
			setEmpID: function(value) {
                empid = value;
                sessionStorage["EmployeeID"]=value; 
            },
			getFromList: function () {
                if(angular.isUndefined(fromList))
                    {fromList=sessionStorage["list"]; }
                return fromList;
            },
			setFromList: function(value) {
                fromList = value;
                sessionStorage["list"]=value; 
            },
			getHasDotted: function () {
                if(angular.isUndefined(hasDotted))
                    {hasDotted=sessionStorage["HasDottedFlag"]; }
                return hasDotted;
            },
			setHasDotted: function(value) {
                hasDotted = value;
                sessionStorage["HasDottedFlag"]=value; 
            }
  };
})

})();