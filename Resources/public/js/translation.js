'use strict';

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
            this.set('', '', '');
        }
    };
});

app.controller('TranslationController', [
    '$scope', '$http', '$timeout', '$location', '$anchorScroll', 'ngTableParams', 'sharedMessage',
    function ($scope, $http, $timeout, $location, $anchorScroll, ngTableParams, sharedMessage) {

        $scope.locales = translationCfg.locales;
        $scope.editType = translationCfg.inputType;
        $scope.autoCacheClean = translationCfg.autoCacheClean;
        $scope.hideColBtnLabel = translationCfg.label.hideCol;
        $scope.toggleAllColBtnLabel = translationCfg.label.toggleAllCol;
        $scope.invalidateCacheBtnLabel = translationCfg.label.invalidateCache;
        $scope.saveRowBtnLabel = translationCfg.label.saveRow;
        $scope.saveLabel = translationCfg.label.save;
        $scope.hideColSelector = false;
        $scope.areAllColumnsSelected = true;
        $scope.sharedMsg = sharedMessage;

        // columns definition
        $scope.columns = [
            { title: 'ID', index: '_id', edit: false, filter: false, sortable: true, visible: true },
            { title: translationCfg.label.domain, index: '_domain', edit: false, filter: {'_domain': 'text'}, sortable: true, visible: true },
            { title: translationCfg.label.key, index: '_key', edit: false, filter: {'_key': 'text'}, sortable: true, visible: true }
        ];

        for (var key in $scope.locales) {
            var columnDef = { title: $scope.locales[key].toUpperCase(), index: $scope.locales[key], edit: true, filter: {}, sortable: false, visible: true };
            columnDef['filter'][$scope.locales[key]] = 'text';

            $scope.columns.push(columnDef);
        }

        // grid data
        var tableData = {
            total: 0,
            currentSort: {},
            currentFilter: {},
            getData: function($defer, params) {
                var parameters = {};

                if (Object.keys(params.sorting()).length) {
                    var keys = Object.keys(params.sorting());
                    parameters['sidx'] = keys[0];
                    parameters['sord'] = params.sorting()[keys[0]];

                    if (!angular.equals(this.currentSort, params.sorting())) {
                        params.page(1);
                        this.currentSort = params.sorting();
                    }
                }

                if (Object.keys(params.filter()).length) {
                    parameters['_search'] = true;
                    for (var key in params.filter()) {
                        parameters[key] = params.filter()[key];
                    }

                    if (!angular.equals(this.currentFilter, params.filter())) {
                        params.page(1);
                        this.currentFilter = params.filter();
                    }
                }

                parameters['page'] = params.page();
                parameters['rows'] = params.count();

                $http.get(translationCfg.url.list, {'params': parameters}).success(function (responseData) {
                    $timeout(function() {
                        params.total(responseData.total);
                        $defer.resolve(responseData.translations);
                    }, 100);
                });
            }
        };

        var defaultOptions = { page: 1, count: 20, filter: {}, sort: {'_id': 'asc'} };

        $scope.tableParams = new ngTableParams(defaultOptions, tableData);

        // scope function
        $scope.tableParams.changePage = function (pageNumber) {
            $scope.tableParams.page(pageNumber);
            $location.hash('translation-grid');
            $anchorScroll();
        };

        // trigger the grid sorting
        $scope.sortGrid = function (column) {
            if (column.sortable) {
                $scope.tableParams.sorting( column.index, $scope.tableParams.isSortBy(column.index, 'asc') ? 'desc' : 'asc' );
            }
        };

        // go the top of the grid on page change
        $scope.changePage = function (pageNumber) {
            $scope.tableParams.page(pageNumber);
            $location.hash('translation-grid');
            $anchorScroll();
        };

        // toggle show/hide column with a similar name (if "en" is clicked all "en_XX" columns will be toggled too)
        $scope.toggleSimilar = function (currentCol) {
            if (translationCfg.toggleSimilar) {
                angular.forEach($scope.columns, function (column) {
                    if ( column.index != currentCol.index && column.index.indexOf(currentCol.index+'_') == 0 ) {
                        column.visible = !currentCol.visible; // use the negation because it seems the model value has not been refreshed yet.
                    }
                });
            }
        };

        // invalidate the cache
        $scope.invalidateCache = function () {
            $http.get(translationCfg.url.invalidateCache, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
                .success(function (responseData) {
                    sharedMessage.set('success', 'ok-circle', responseData.message);
                })
                .error(function (responseData, status, headers, config) {
                    sharedMessage.set('danger', 'remove-circle', 'Error');
                })
            ;
        };

        // toggle all columns
        $scope.toggleAllColumns = function () {
            $scope.areAllColumnsSelected = !$scope.areAllColumnsSelected;
            angular.forEach($scope.columns, function(column){
                column.visible = $scope.areAllColumnsSelected;
            });
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
                    var url = translationCfg.url.update.replace('-id-', $scope.translation._id);

                    var parameters = [];
                    for (var name in $scope.translation) {
                        parameters.push(name+'='+encodeURIComponent($scope.translation[name]));
                    }

                    // force content type to make SF create a Request with the PUT parameters
                    $http({ 'url': url, 'data': parameters.join('&'), method: 'PUT', headers: {'Content-Type': 'application/x-www-form-urlencoded'} })
                        .success(function (data, status, headers, config) {
                            $scope.edit = false;
                            $scope.translation = data;
                            sharedMessage.set('success', 'ok-circle', translationCfg.label.successMsg.replace('%id%', data._key));
                        }).error(function (data, status, headers, config) {
                            sharedMessage.set('danger', 'remove-circle', translationCfg.label.errorMsg.replace('%id%', $scope.translation._key));
                        });
                }
            };
        }
    };
}]);
