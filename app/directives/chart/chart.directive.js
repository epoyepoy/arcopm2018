(function(){

	"use strict";
    

angular.module("ARCOPM").directive('chart', chartDirective);

function chartDirective () 
{

    return{
              restrict: 'E',
              scope: {
                        data: '=',
                        colors: '=',
                        labels: '='
                    },
              templateUrl: "app/directives/chart/chart.template.html",    
              link: chartLink

          };  
   
}
    
function chartLink($scope, element, attrs) 
{
    
     
    $scope.canvas = element[0].querySelector('.chart-canvas');
     
    /*
     $scope.$watch(function () { return $scope.data },function()
     {  
       
         $scope.showIncomeChart($scope.canvas);

     },true);
    */
    
    $scope.showIncomeChart = function(canvas)
    {
       
        //$scope.colors = ['#5a6a73', '#819582','#d9b658','#b397a1', '#A9A18C', '#B99C6B'];

         var options = { };	

         var chartData = [];
        
            for(var i=0;i<$scope.data.length;i++)
            {
                var element = 
                     {
                        value: $scope.data[i].cnt,
                        color:$scope.data[i].color,
                        label: $scope.data[i].name
                     };

                chartData.push(element);
            }


         var context = canvas.getContext("2d");
         var myPieChart = new Chart(context).Pie(chartData,options);

    }
        
    
    $scope.showIncomeChart($scope.canvas);
         
}
    
    
})();