'use strict'

var app = angular.module('translationApp', ['ngTable']);

app.controller('TranslationCtrl', ['$scope', '$http', '$timeout', 'ngTableParams', function($scope, $http, $timeout, ngTableParams) {
    $scope.locales = managedLocales;
    $scope.columns = ['ID', 'Domain', 'Key'];

    for (var key in managedLocales) {
        $scope.columns.push(managedLocales[key].toUpperCase());
    }

    var options = {
        page: 1,  // show first page
        count: 20 // items per page
    };
    
    var tableData = {
        total: 0,
        getData: function($defer, params) {
            var url = dataListUrl + '?page='+params.page()+'&row='+params.count();
            
            $http.get(url).success(function (responseData) {
                $timeout(function() {
                    params.total(responseData.total);
                    $defer.resolve(responseData.translations);
                }, 300);
            });
        }
    };

    $scope.tableParams = new ngTableParams(options, tableData);
}]);
