(function () {
   
    'use strict';
 
    angular.module('ARCOPM').factory('DocumentsFactory', DocumentsFactory);
 
    DocumentsFactory.$inject = ['$http','global'];
  
function DocumentsFactory($http, Global) {

    var api = Global.api;

    var factory = {};
 
    factory.GetAll = GetAll;
    factory.Create = Create;
    factory.Delete = Delete;

    
    return factory;
 
     
    
    // Get all documents
    function GetAll(projectId)
    {   
        return $http.get(api + '/projects/' + projectId + '/documents').then(handleSuccess, handleError);
    }


    // Add a new document
    function Create(projectId, file,actionType)
    {
		
        var formData = new FormData();
        formData.append("file",file);
        
        

        return $http.post(
            api + '/projects/' + projectId + '/' + actionType + '/documents',
            formData,
            {
                transformRequest: angular.identity,
                headers: { 'Content-Type': undefined }
            }
            ).then(handleSuccess, handleError);
    }


    // Delete a document with {id}
    function Delete(projectId, id) 
    {   
        return $http.delete(api + '/projects/' + projectId + '/documents/' + id).then(handleSuccess, handleError);
    }
    
    

    
 
    // Handle a succesful response [ Status code: 200 ]
    function handleSuccess(response) 
    {   
        return response.data;
    }

    // Handle the response if status code is > 299
    function handleError(response)
    { 
        return { error : true, code: response.status, message: response.data.message  }
    }
}
 
})();