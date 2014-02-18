'use strict'

var app = angular.module('translationApp', ['ngTable']);

app.controller('TranslationCtrl', ['$scope', '$http', '$timeout', 'ngTableParams', function($scope, $http, $timeout, ngTableParams) {
    $scope.locales = translationParams.locales;
    $scope.hideColBtnLabel = translationParams.hideColBtnLabel;
    $scope.hideColSelector = false;

    // columns definition
    $scope.columns = [
        { title: 'ID', index: 'id', filter: {'id': 'text'}, sortable: true, visible: true }, 
        { title: 'Domain', index: 'domain', filter: {'domain': 'text'}, sortable: true, visible: true },
        { title: 'Key', index: 'key', filter: {'key': 'text'}, sortable: true, visible: true }
    ];

    for (var key in $scope.locales) {
        var columnDef = { title: $scope.locales[key].toUpperCase(), index: $scope.locales[key], filter: {}, sortable: false, visible: true };
        columnDef['filter'][$scope.locales[key]] = 'text';

        $scope.columns.push(columnDef);
    }

    // grid data
    var tableData = {
        total: 0,
        getData: function($defer, params) {
            var parameters = ['page='+params.page(), 'row='+params.count()];

            if (Object.keys(params.sorting()).length) {
                var keys = Object.keys(params.sorting());
                parameters.push('sidx=' + keys[0]);
                parameters.push('sord=' + params.sorting()[keys[0]]);
            }
            
            if (Object.keys(params.filter()).length) {
                parameters.push('_search=true');
                for (var key in params.filter()) {
                    parameters.push(key + '=' + params.filter()[key]);
                }
            }
            
            var url = translationParams.listUrl + '?' + parameters.join('&');
            
            $http.get(url).success(function (responseData) {
                $timeout(function() {
                    params.total(responseData.total);
                    $defer.resolve(responseData.translations);
                }, 200);
            });
        }
    };
    
    var defaultOptions = { page: 1, count: 20, filter: {}, sort: {'id': 'asc'} };

    $scope.tableParams = new ngTableParams(defaultOptions, tableData);
    
    // scope function
    $scope.sortGrid = function (tableParams, column) {
        if (column.sortable) {
            tableParams.sorting( column.index, tableParams.isSortBy(column.index, 'asc') ? 'desc' : 'asc' );
        }  
    };
}]);
