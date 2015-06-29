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

app.service('TranslationApiManager', function ($http) {

    this.getPage = function (params, tableData) {
        var parameters = {};

        if (Object.keys(params.sorting()).length) {
            var keys = Object.keys(params.sorting());
            parameters['sidx'] = keys[0];
            parameters['sord'] = params.sorting()[keys[0]];

            if (!angular.equals(tableData.currentSort, params.sorting())) {
                params.page(1);
                tableData.currentSort = params.sorting();
            }
        }

        if (Object.keys(params.filter()).length) {
            parameters['_search'] = true;
            for (var key in params.filter()) {
                parameters[key] = params.filter()[key];
            }

            if (!angular.equals(tableData.currentFilter, params.filter())) {
                params.page(1);
                tableData.currentFilter = params.filter();
            }
        }

        parameters['page'] = params.page();
        parameters['rows'] = params.count();

        return $http.get(translationCfg.url.list, {'params': parameters});
    };

    this.invalidateCache = function () {
        return $http.get(translationCfg.url.invalidateCache, {headers: {'X-Requested-With': 'XMLHttpRequest'}});
    };

    this.updateTranslation = function (translation) {
        var url = translationCfg.url.update.replace('-id-', translation._id);

        var parameters = [];
        for (var name in translation) {
            parameters.push(name+'='+encodeURIComponent(translation[name]));
        }

        // force content type to make SF create a Request with the PUT parameters
        return $http({ 'url': url, 'data': parameters.join('&'), method: 'PUT', headers: {'Content-Type': 'application/x-www-form-urlencoded'} });
    };

});

app.controller('TranslationController', [
    '$scope', '$location', '$anchorScroll', 'ngTableParams', 'sharedMessage', 'TranslationApiManager',
    function ($scope, $location, $anchorScroll, ngTableParams, sharedMessage, TranslationApiManager) {

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
                TranslationApiManager
                    .getPage(params, this)
                    .success(function (responseData) {
                        params.total(responseData.total);
                        $defer.resolve(responseData.translations);
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
            TranslationApiManager
                .invalidateCache()
                .success(function (responseData) {
                    sharedMessage.set('success', 'ok-circle', responseData.message);
                })
                .error(function () {
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

app.directive('editableRow', ['TranslationApiManager', 'sharedMessage', function (TranslationApiManager, sharedMessage) {
    return {
        restrict: 'A',
        scope: {
            translation: '=translation',
            columns: '=columns',
            editType: '=editType'
        },
        template: $('#editable-row-template').html(),
        link: function ($scope, element, attrs) {
            $scope.edit = false;

            $scope.toggleEdit = function () {
                $scope.edit = !$scope.edit;
                sharedMessage.reset();
            };

            $scope.enableEdit = function () {
                $scope.edit = true;
                sharedMessage.reset();
            };

            $scope.disableEdit = function () {
                $scope.edit = false;
                sharedMessage.reset();
            };

            $scope.save = function (event, source) {
                if ( (source == 'input' || source == 'textarea') && event.which == 27 ) { // escape key
                    $scope.edit = false;

                } else if ( source == 'btn-save' || (source == 'input' && event.which == 13) ) { // click btn OR return key
                    TranslationApiManager
                        .updateTranslation($scope.translation)
                        .success(function (data) {
                            $scope.edit = false;
                            $scope.translation = data;
                            sharedMessage.set('success', 'ok-circle', translationCfg.label.successMsg.replace('%id%', data._key));
                        }).error(function () {
                            sharedMessage.set('danger', 'remove-circle', translationCfg.label.errorMsg.replace('%id%', $scope.translation._key));
                        });
                }
            };
        }
    };
}]);
