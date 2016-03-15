'use strict';

var app = angular.module('translationApp', ['ngTable']);

/**
 * Shared object to display user messages.
 */
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

/**
 * Api manager service.
 */
app.factory('translationApiManager', ['$http', function ($http) {
    return {
        token: null,

        setToken: function (token) {
            this.token = token;
        },

        getPage: function (params, tableData) {
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

            var url = (null != this.token) ? translationCfg.url.listByToken.replace('-token-', this.token) : translationCfg.url.list;

            return $http.get(url, {'params': parameters});
        },

        invalidateCache: function () {
            return $http.get(translationCfg.url.invalidateCache, {headers: {'X-Requested-With': 'XMLHttpRequest'}});
        },

        updateTranslation: function (translation) {
            var url = translationCfg.url.update.replace('-id-', translation._id);

            var parameters = [];
            for (var name in translation) {
                parameters.push(name+'='+encodeURIComponent(translation[name]));
            }

            // force content type to make SF create a Request with the PUT parameters
            return $http({ 'url': url, 'data': parameters.join('&'), method: 'PUT', headers: {'Content-Type': 'application/x-www-form-urlencoded'} });
        },

        deleteTranslationLocale: function (translation, locale) {
            var url = translationCfg.url.deleteLocale
                .replace('-id-', translation._id)
                .replace('-locale-', locale);

            return $http.delete(url);
        },

        deleteTranslation: function (translation) {
            return $http.delete(translationCfg.url.delete.replace('-id-', translation._id));
        }
    };
}]);

/**
 * ngTable column definition and parameters builder service.
 */
app.factory('tableParamsManager', ['ngTableParams', 'translationApiManager', function (ngTableParams, translationApiManager) {
    return {
        columns: [],
        tableParams: null,
        defaultOptions: { page: 1, count: 20, filter: {}, sort: {'_id': 'asc'} },

        build: function (locales, labels) {
            this.columns = [
                { title: 'ID', index: '_id', edit: false, delete: false, filter: false, sortable: true, visible: true },
                { title: labels.domain, index: '_domain', edit: false, delete: false, filter: {'_domain': 'text'}, sortable: true, visible: true },
                { title: labels.key, index: '_key', edit: false, delete: true, filter: {'_key': 'text'}, sortable: true, visible: true }
            ];

            for (var key in locales) {
                var columnDef = { title: locales[key].toUpperCase(), index: locales[key], edit: true, delete: true, filter: {}, sortable: false, visible: true };
                columnDef['filter'][locales[key]] = 'text';

                this.columns.push(columnDef);
            }

            // grid data
            var tableData = {
                total: 0,
                currentSort: {},
                currentFilter: {},
                getData: function($defer, params) {
                    translationApiManager
                        .getPage(params, this)
                        .success(function (responseData) {
                            params.total(responseData.total);
                            $defer.resolve(responseData.translations);
                        });
                }
            };

            this.tableParams = new ngTableParams(this.defaultOptions, tableData);
        },

        reloadTableData: function () {
            this.tableParams.reload();
        },

        getColumnsDefinition: function () {
            return this.columns;
        },

        getTableParams: function () {
            return this.tableParams;
        }
    };
}]);

/**
 * Translation grid controller.
 */
app.controller('TranslationController', [
    '$scope', '$location', '$anchorScroll', 'sharedMessage', 'tableParamsManager', 'translationApiManager',
    function ($scope, $location, $anchorScroll, sharedMessage, tableParamsManager, translationApiManager) {

        $scope.locales = translationCfg.locales;
        $scope.editType = translationCfg.inputType;
        $scope.autoCacheClean = translationCfg.autoCacheClean;
        $scope.labels = translationCfg.label;
        $scope.hideColumnsSelector = false;
        $scope.areAllColumnsSelected = true;
        $scope.profilerTokens = translationCfg.profilerTokens;
        $scope.sharedMsg = sharedMessage;

        tableParamsManager.build($scope.locales, $scope.labels);

        $scope.columns = tableParamsManager.getColumnsDefinition();
        $scope.tableParams = tableParamsManager.getTableParams();

        // override default changePage function to scroll to top on change page
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

        // go to the top of the grid on page change
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

        // invalidate translation cache
        $scope.invalidateCache = function () {
            translationApiManager
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
            angular.forEach($scope.columns, function(column) {
                column.visible = $scope.areAllColumnsSelected;
            });
        };
}]);

/**
 * Translations source controller.
 */
app.controller('DataSourceController', [
    '$scope', 'tableParamsManager', 'translationApiManager',
    function ($scope, tableParamsManager, translationApiManager) {
        $scope.selectedToken = null;
        $scope.defaultSourceClass = 'btn-info';
        $scope.tokenSourceClass = 'btn-default';
        $scope.showProfiles = false;

        // use the given profile token as translations source
        $scope.changeToken = function (selectedToken) {
            translationApiManager.setToken(selectedToken);

            if ('' != selectedToken) {
                tableParamsManager.reloadTableData();
            }
        };

        $scope.resetSource = function () {
            $scope.selectedToken = null;
            $scope.defaultSourceClass = 'btn-info';
            $scope.tokenSourceClass = 'btn-default';
            $scope.showProfiles = false;

            translationApiManager.setToken($scope.selectedToken);
            tableParamsManager.reloadTableData();
        };

        $scope.useTokenAsSource = function () {
            $scope.defaultSourceClass = 'btn-default';
            $scope.tokenSourceClass = 'btn-info';
            $scope.showProfiles = true;

            if ($scope.profilerTokens.length) {
                $scope.selectedToken = $scope.profilerTokens[0].token;
                translationApiManager.setToken($scope.selectedToken);
                tableParamsManager.reloadTableData();
            } else {
                $scope.selectedToken = '';
            }
        };
}]);

/**
 * Directive to switch table row in edit mode.
 */
app.directive('editableRow', [
    'translationApiManager', 'tableParamsManager', 'sharedMessage',
    function (translationApiManager, tableParamsManager, sharedMessage) {
        return {
            restrict: 'A',
            scope: {
                translation: '=translation',
                columns: '=columns',
                editType: '=editType'
            },
            template: $('#editable-row-template').html(),
            link: function ($scope, element, attrs) {
                $scope.mode = null;

                $scope.enableMode = function (mode) {
                    $scope.mode = mode;
                    sharedMessage.reset();
                };

                $scope.disableMode = function () {
                    $scope.mode = null;
                    sharedMessage.reset();
                };

                $scope.save = function (event, source) {
                    if ( (source == 'input' || source == 'textarea') && event.which == 27 ) { // escape key
                        $scope.mode = null;

                    } else if ( source == 'btn-save' || (source == 'input' && event.which == 13) ) { // click btn OR return key
                        translationApiManager
                            .updateTranslation($scope.translation)
                            .success(function (data) {
                                $scope.mode = null;
                                $scope.translation = data;
                                sharedMessage.set('success', 'ok-circle', translationCfg.label.updateSuccess.replace('%id%', data._key));
                            }).error(function () {
                                sharedMessage.set('danger', 'remove-circle', translationCfg.label.updateFail.replace('%id%', $scope.translation._key));
                            });
                    }
                };

                $scope.delete = function (column) {
                    if (!window.confirm('Confirm ?')) {
                        return;
                    }

                    if (column.index == '_key') {
                        translationApiManager
                            .deleteTranslation($scope.translation)
                            .success(function (data) {
                                sharedMessage.set('success', 'ok-circle', translationCfg.label.deleteSuccess.replace('%id%', data._key));
                                $scope.mode = null;
                                tableParamsManager.reloadTableData();
                            }).error(function () {
                                sharedMessage.set('danger', 'remove-circle', translationCfg.label.deleteFail.replace('%id%', $scope.translation._key));
                            });
                    } else {
                        translationApiManager
                            .deleteTranslationLocale($scope.translation, column.index)
                            .success(function (data) {
                                sharedMessage.set('success', 'ok-circle', translationCfg.label.deleteSuccess.replace('%id%', data._key));
                                $scope.translation[column.index] = '';
                            }).error(function () {
                                sharedMessage.set('danger', 'remove-circle', translationCfg.label.deleteFail.replace('%id%', $scope.translation._key));
                            });
                    }
                };
            }
        };
}]);
