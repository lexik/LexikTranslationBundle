'use strict'

var app = angular.module('translationApp', ['ngTable']);

app.factory('sharedMessage', function () {
    return {
        css: '',
        icon: '',
        content: '',
        set: function (css, icon, content) {
            this.css = css;
            this.icon = icon;
            this.content = content;
        },
        reset: function () {
            this.css = '';
            this.icon = '';
            this.content = '';
        }
    };
});

app.controller('TranslationCtrl', ['$scope', '$http', '$timeout', 'ngTableParams', 'sharedMessage', function($scope, $http, $timeout, ngTableParams, sharedMessage) {
    $scope.locales = translationCfg.locales;
    $scope.editType = translationCfg.inputType;
    $scope.hideColBtnLabel = translationCfg.label.hideCol;
    $scope.saveRowBtnLabel = translationCfg.label.saveRow;
    $scope.hideColSelector = false;
    $scope.saveMsg = sharedMessage;

    // columns definition
    $scope.columns = [
        { title: 'ID', index: 'id', edit: false, filter: {'id': 'text'}, sortable: true, visible: true }, 
        { title: 'Domain', index: 'domain', edit: false, filter: {'domain': 'text'}, sortable: true, visible: true },
        { title: 'Key', index: 'key', edit: false, filter: {'key': 'text'}, sortable: true, visible: true }
    ];

    for (var key in $scope.locales) {
        var columnDef = { title: $scope.locales[key].toUpperCase(), index: $scope.locales[key], edit: true, filter: {}, sortable: false, visible: true };
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

            var url = translationCfg.url.list + '?' + parameters.join('&');

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

app.directive('editableRow', ['$http', 'sharedMessage', function ($http, sharedMessage) {
    return {
        restrict: 'A',
        scope: {
            translation: '=translation',
            columns: '=columns',
            editType: '=editType'
        },
        template: $('#editable-row-template').html(),
        link: function ( $scope, element, attrs ) {
            $scope.message = null;
            $scope.edit = false;
            
            $scope.toggleEdit = function () {
                $scope.edit = !$scope.edit;
                sharedMessage.reset();
            };

            $scope.save = function (event) {
                if (event.which == 27) { // ecsape key
                    $scope.edit = false;

                } else if ( ($scope.editType == 'textarea' && event.type == 'click') || ($scope.editType == 'text' && event.which == 13) ) { // click btn OR return key
                    var url = translationCfg.url.update.replace('-id-', $scope.translation.id);

                    var parameters = [];
                    for (var name in $scope.translation) {
                        parameters.push(name+'='+$scope.translation[name]);
                    }

                    // force content type to make SF create a Request with the PUT parameters
                    $http({ 'url': url, 'data': parameters.join('&'), method: 'PUT', headers: {'Content-Type': 'application/x-www-form-urlencoded'} })
                        .success(function (data, status, headers, config) {
                            $scope.edit = false;
                            $scope.translation = data;
                            sharedMessage.set('text-success', 'ok-circle', 'The translation #'+data.id+' has been successfully updated.');
                        }).error(function (data, status, headers, config) {
                            sharedMessage.set('text-danger', 'remove-circle', 'Fail to update translation #'+$scope.translation.id+'.');
                        });
                }
            };
        }
    };
}]);
