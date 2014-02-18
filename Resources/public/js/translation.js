'use strict'

var app = angular.module('translationApp', ['ngTable']);

app.controller('TranslationCtrl', ['$scope', '$http', '$timeout', 'ngTableParams', function($scope, $http, $timeout, ngTableParams) {
    $scope.locales = translationParams.locales;
    $scope.columns = [
        { title: 'ID', index: 'id', filter: {'id': 'text'}, sortable: true }, 
        { title: 'Domain', index: 'domain', filter: {'domain': 'text'}, sortable: true },
        { title: 'Key', index: 'key', filter: {'key': 'text'}, sortable: true }
    ];

    for (var key in $scope.locales) {
        var columnDef = { title: $scope.locales[key].toUpperCase(), index: $scope.locales[key], filter: {}, sortable: false };
        columnDef['filter'][$scope.locales[key]] = 'text';

        $scope.columns.push(columnDef);
    }

    var tableData = {
        total: 0,
        getData: function($defer, params) {
            var parameters = ['page='+params.page(), 'row='+params.count()];

            if (Object.keys(params.sorting()).length) {
                var keys = Object.keys(params.sorting());
                parameters.push('sidx=' + keys[0]);
                parameters.push('sord=' + params.sorting()[keys[0]]);
            }
            
            console.log(parameters);
            
            var url = translationParams.listUrl + '?' + parameters.join('&');
            
            $http.get(url).success(function (responseData) {
                $timeout(function() {
                    params.total(responseData.total);
                    $defer.resolve(responseData.translations);
                }, 300);
            });
        }
    };
    
    var options = { page: 1, count: 20, filter: {}, sort: {} };

    $scope.tableParams = new ngTableParams(options, tableData);
    
    $scope.sortGrid = function (tableParams, column) {
        if (column.sortable) {
            tableParams.sorting( column.index, tableParams.isSortBy(column.index, 'asc') ? 'desc' : 'asc' );
        }  
    };
}]);
