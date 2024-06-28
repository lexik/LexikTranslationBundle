const TranslationManager = (() => {

    let translationCfg = {};
    let _currentPage = 1;
    let _totalPages = 0;
    let _order = 'id';
    let _direction = 'asc';
    let _showColSelector = false;
    let _showCol = {};

    const init = (config) =>
    {
        translationCfg = config;

        const debounceTimeouts = {};

        document.addEventListener('DOMContentLoaded', function () {
            reloadGrid();

            document.querySelectorAll('.input-sm').forEach(input => {
                input.addEventListener('keyup', function() {
                    if (debounceTimeouts[this.id]) {
                        clearTimeout(debounceTimeouts[this.id]);
                    }
                    debounceTimeouts[this.id] = setTimeout(() => {
                        _currentPage = 1;
                        _order = 'id';
                        _direction = 'asc';
                        reloadGrid();
                    }, 200);
                });
            });
        });
    };

    const sharedMessage = {
        element: document.getElementById('sharedMessage'),
        css: '',
        icon: '',
        content: '',

        set: function (css, icon, content) {
            this.css = css;
            this.icon = icon;
            this.content = content;
        },

        show: function (css, icon, content) {
            this.set(css, icon, content);
            this.element.classList.add('label-' + this.css);
            this.element.innerHTML = '<span><i class="glyphicon glyphicon-' + this.icon + '"></i> ' + this.content + '</span>';
            this.element.style.display = 'block';
        },

        reset: function () {
            this.element.classList.remove('label-' + this.css);
            this.element.style.display = 'none';
            this.set('', '', '');
        }
    };

    const translationApiManager = {
        token: null,

        setToken: function (token) {
            this.token = token;
        },

        getPage: async function () {

            let parameters = {
                sidx: _order,
                sord: _direction,
                page: _currentPage,
                rows: translationCfg.maxPageNumber
            };

            addFilteredValuesToParams(parameters);

            return await fetch(translationCfg.url.list + '?' + new URLSearchParams(parameters));
        },

        invalidateCache: async function () {
            const url = translationCfg.url.invalidateCache;
            const parameters = this.initializeParametersWithCsrf();

            return await fetch(url + '?' + new URLSearchParams(parameters), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
        },

        updateTranslation: async function (translationId, params) {
            const url = translationCfg.url.update.replace('-id-', translationId);

            const parameters = this.initializeParametersWithCsrf();
            Object.assign(parameters, params);

            return await fetch(url, {
                method: 'PUT',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams(parameters)
            });
        },

        deleteTranslationLocale: async function (translationId, locale) {
            const url = translationCfg.url.deleteLocale
                .replace('-id-', translationId)
                .replace('-locale-', locale);

            const parameters = this.initializeParametersWithCsrf();

            return await fetch(url + '?' + new URLSearchParams(parameters), {
                method: 'DELETE'
            });
        },

        deleteTranslation: async function (translationId) {
            const url = translationCfg.url.delete.replace('-id-', translationId);
            const parameters = this.initializeParametersWithCsrf();

            return await fetch(url + '?' + new URLSearchParams(parameters), {
                method: 'DELETE'
            });
        },

        initializeParametersWithCsrf: function() {
            const parameters = {};

            if (translationCfg.csrfToken) {
                parameters._token = translationCfg.csrfToken;
            }

            return parameters;
        }
    };

    const toggleColSelector = () =>
    {
        _showColSelector = !_showColSelector;

        if (_showColSelector) {
            document.getElementById('columnsSelector').style.display = 'block';
        } else {
            document.getElementById('columnsSelector').style.display = 'none';
        }
    }

    const toggleAllColumns = (checked) =>
    {
        document.getElementById('toogle-list').querySelectorAll('[id^="toggle-"]').forEach(input => {
            input.checked = checked;
            toggleColumn(input.id.replace('toggle-', ''), checked);
        });
    }

    const toggleColumn = (column, checked) =>
    {
        _showCol[column] = checked;

        document.getElementById('header-' + column).classList.toggle('col-hide', !checked);
        document.querySelectorAll('.col-' + column).forEach(element => {
            element.classList.toggle('col-hide', !checked);
        });

        if (translationCfg.toggleSimilar) {
            document.querySelectorAll('[id^="toggle-' + column + '_"]').forEach(input => {
                _showCol[input.id.replace('toggle-', '')] = checked;
                input.checked = checked;
            });
            document.querySelectorAll('[id^="header-' + column + '_"]').forEach(element => {
                element.classList.toggle('col-hide', !checked);
            });
            document.querySelectorAll('[class^="col-' + column + '_"]').forEach(element => {
                element.classList.toggle('col-hide', !checked);
            });
        }
    }

    const enableMode = (mode, lexikTranslationId) =>
    {
        const locales = translationCfg.locales;
        const editButton = document.getElementById('editButton-' + lexikTranslationId);
        const deleteButton = document.getElementById('deleteButton-' + lexikTranslationId);
        const saveButton = document.getElementById('saveButton-' + lexikTranslationId);
        const cancelButton = document.getElementById('cancelButton-' + lexikTranslationId);

        if (mode === 'edit') {
            sharedMessage.reset();
            editButton.style.display = 'none';
            deleteButton.style.display = 'none';
            saveButton.style.display = 'block';
            cancelButton.style.display = 'block';
            for (let i = 0; i < locales.length; i++) {
                document.getElementById('content-' + lexikTranslationId + '-' + locales[i]).style.display = 'none';
                document.getElementById('inputContent-' + lexikTranslationId + '-' + locales[i]).style.display = 'block';
            }
        } else if (mode === 'view') {
            editButton.style.display = 'block';
            deleteButton.style.display = 'block';
            saveButton.style.display = 'none';
            cancelButton.style.display = 'none';
            for (let i = 0; i < locales.length; i++) {
                document.getElementById('content-' + lexikTranslationId + '-' + locales[i]).style.display = 'block';
                document.getElementById('inputContent-' + lexikTranslationId + '-' + locales[i]).style.display = 'none';
                document.getElementById('btnDelete-' + lexikTranslationId + '-' + locales[i]).style.display = 'none';
                document.getElementById('btnKeyDelete-' + lexikTranslationId).style.display = 'none';
            }
        } else if (mode === 'delete') {
            sharedMessage.reset();
            editButton.style.display = 'none';
            deleteButton.style.display = 'none';
            cancelButton.style.display = 'block';
            for (let i = 0; i < locales.length; i++) {
                if (document.getElementById('content-' + lexikTranslationId + '-' + locales[i]).textContent.trim() !== '') {
                    document.getElementById('btnDelete-' + lexikTranslationId + '-' + locales[i]).style.display = 'block';
                    document.getElementById('btnKeyDelete-' + lexikTranslationId).style.display = 'block';
                }
            }
        }
    };

    const save = (lexikTranslationId) =>
    {
        let update = false;
        const locales = translationCfg.locales;

        for (let i = 0; i < locales.length; i++) {
            let oldValue = document.getElementById('content-' + lexikTranslationId + '-' + locales[i]).textContent;
            let newValue = document.getElementById('inputContent-' + lexikTranslationId + '-' + locales[i]).value;
            if (oldValue !== newValue) {
                update = true;
                document.getElementById('content-' + lexikTranslationId + '-' + locales[i]).textContent = newValue;
            }
        }
        if (update) {
            saveEntry(lexikTranslationId);
        }

        enableMode('view', lexikTranslationId);
    }

    const saveEntry = (lexikTranslationId) =>
    {
        let params = {};
        let saveButton = document.getElementById('saveButton-' + lexikTranslationId);
        let trElement = saveButton.closest('tr.content');
        let tdElements = trElement.querySelectorAll('td[class^="col-"]');
        tdElements.forEach(function (td, index) {
            let span = td.querySelector('span');
            let col = td.classList[0].replace('col-', '');
            params[col] = span.textContent;
        });

        translationApiManager.updateTranslation(lexikTranslationId, params).then(function (response) {
            if (response.status === 200) {
                response.json().then(function (data) {
                    sharedMessage.show('success', 'ok-circle', translationCfg.label.updateSuccess.replace('%id%', data._key));
                });
            } else {
                sharedMessage.show('danger', 'remove-circle', translationCfg.label.updateFail.replace('%id%', lexikTranslationId));
            }
        });

    };

    const deleteEntry = (lexikTranslationId, locale) =>
    {
        if (confirm(translationCfg.label.deleteConfirm)) {
            if (locale === null) {
                translationApiManager.deleteTranslation(lexikTranslationId).then(function (response) {
                    if (response.status === 200) {
                        let data = response.json();
                        sharedMessage.show('success', 'ok-circle', translationCfg.label.deleteSuccess.replace('%id%', data._key));
                        reloadGrid();
                    } else {
                        sharedMessage.show('danger', 'remove-circle', translationCfg.label.deleteFail.replace('%id%', lexikTranslationId));
                    }
                });
            } else {
                translationApiManager.deleteTranslationLocale(lexikTranslationId, locale).then(function (response) {
                    if (response.status === 200) {
                        let data = response.json();
                        enableMode('view', lexikTranslationId);
                        document.getElementById('inputContent-' + lexikTranslationId + '-' + locale).value = '';
                        document.getElementById('content-' + lexikTranslationId + '-' + locale).innerText = '';
                        sharedMessage.show('success', 'ok-circle', translationCfg.label.deleteSuccess.replace('%id%', data._key));
                    } else {
                        sharedMessage.show('danger', 'remove-circle', translationCfg.label.deleteFail.replace('%id%', lexikTranslationId));
                    }
                });
            }
        }
    };

    const invalidateCache = () =>
    {
        translationApiManager.invalidateCache().then(function (response) {
            if (response.status === 200) {
                response.json().then(function (data) {
                    sharedMessage.show('success', 'ok-circle', data.message);
                });
            } else {
                sharedMessage.show('danger', 'remove-circle', 'Error');
            }
        });
    }

    const reloadGrid = () =>
    {
        translationApiManager.getPage().then(function (response) {
            if (response.status === 200) {
                response.json().then(function (data) {
                    let table = '';
                    data.translations.forEach(function (item) {
                        table += constructHtmlTr(item);
                    });

                    _totalPages = getMaxPageNumber(data.total);
                    document.querySelector('.table tbody').innerHTML = table;
                    document.querySelector('.info-no-translation').style.display = data.total === 0 ? 'block' : 'none';
                    managePagesChanger();
                });
            } else {
                sharedMessage.show('danger', 'remove-circle', 'Error');
            }
        }).catch(error => {
            console.error('Request failed', error);
        });
    };

    const sortColumn = (column, direction) =>
    {
        _order = column;
        _direction = direction;

        reverseNextSortOrder(_order, _direction);
        reloadGrid();
    };

    const reverseNextSortOrder = (_order, _direction) =>
    {
        let nextSortOrder = _direction === 'asc' ? 'desc' : 'asc';
        document.getElementById('header-' + _order).setAttribute(
            'onclick', "TranslationManager.sortColumn('" + _order + "', '" + nextSortOrder + "')"
        );
    };

    const changePage = (page) =>
    {
        _currentPage = page;
        reloadGrid();
    };

    const managePagesChanger = () =>
    {
        const pagination = document.querySelector('.pagination');

        if (_totalPages === 0) {
            pagination.style.display = 'none';
        } else {
            pagination.style.display = 'block';

            let startPage = Math.max(_currentPage - 5, 1);
            let endPage = Math.min(_currentPage + 5, _totalPages);

            let additionalHTML = '<li><a class="prev">&laquo;</a></li>';
            for (let i = startPage; i <= endPage; i++) {
                if (i === _currentPage) {
                    additionalHTML += '<li><a class="page-' + i + ' disabled" href="#">' + i + '</a></li>';
                } else {
                    additionalHTML += '<li><a class="page-' + i + '" onclick="TranslationManager.changePage(' + i + ')">' + i + '</a></li>';
                }
            }
            additionalHTML += '<li><a class="next">&raquo;</a></li>';

            pagination.innerHTML = additionalHTML;

            const prev = document.querySelector('.prev');
            const next = document.querySelector('.next');

            if (_currentPage !== 1) {
                prev.setAttribute('onclick', "TranslationManager.changePage(" + (_currentPage - 1) + ")");
            } else {
                prev.classList.add('disabled');
            }
            if (_currentPage !== _totalPages) {
                next.setAttribute('onclick', "TranslationManager.changePage(" + (_currentPage + 1) + ")");
            } else {
                next.classList.add('disabled');
            }
        }
    };

    const getMaxPageNumber = (total) => Math.ceil(total / translationCfg.maxPageNumber);

    const addFilteredValuesToParams = (params) =>
    {
        let search = false;
        let inputColumnsFiltered = document.querySelectorAll('.table input');

        inputColumnsFiltered.forEach(input => {
            let column = input.getAttribute('id');
            let filterValue = input.value;
            if (filterValue.trim() !== '') {
                search = true;
                params[column] = filterValue;
            }
        });

        if (search) {
            params['_search'] = true;
        }
    };

    const constructHtmlTr = (item) =>
    {
        const renderInputElement = (id, locale, value) => {
            if (translationCfg.inputType === 'textarea') {
                return `<textarea id="inputContent-${id}-${locale}" name="column.index" class="form-control" style="display: none">${value}</textarea>`;
            } else {
                return `<input type="text" id="inputContent-${id}-${locale}" name="column.index" class="form-control" style="display: none" value="${value}">`;
            }
        };

        return `
            <tr class="content">
                <td class="col-_id ${_showCol['_id'] === false ? 'col-hide' : ''}">
                    <span>${item._id}</span>
                    <div on="editType">
                    </div>
                    <div class="text-center">
                        <button type="button" class="btn btn-link delete" style="display:none">
                            <i class="glyphicon glyphicon-remove text-danger"></i>
                        </button>
                    </div>
                </td>
                <td class="col-_domain ${_showCol['_domain'] === false ? 'col-hide' : ''}">
                    <span>${item._domain}</span>
                    <div on="editType">
                    </div>
                    <div class="text-center">
                        <button type="button" class="btn btn-link delete" style="display:none">
                            <i class="glyphicon glyphicon-remove text-danger"></i>
                        </button>
                    </div>
                </td>
                <td class="col-_key ${_showCol['_key'] === false ? 'col-hide' : ''}">
                    <span>${item._key}</span>
                    <div on="editType">
                    </div>
                    <div class="text-center">
                        <button id="btnKeyDelete-${item._id}" onclick="TranslationManager.deleteEntry(${item._id}, null)" type="button" class="btn btn-link delete" style="display:none">
                            <i class="glyphicon glyphicon-remove text-danger"></i>
                        </button>
                    </div>
                </td>
                ${Object.keys(item).filter(key => key !== '_id' && key !== '_domain' && key !== '_key').map(locale => `
                    <td class="col-${locale} ${_showCol[locale] === false ? 'col-hide' : ''}">
                        <span id="content-${item._id}-${locale}" class="locale">${escapeHtml(item[locale])}</span>
                        <div>
                            ${renderInputElement(item._id, locale, item[locale])}
                        </div>
                        <div class="text-center">
                            <button id="btnDelete-${item._id}-${locale}" onclick="TranslationManager.deleteEntry(${item._id}, '${locale}')" type="button" class="btn btn-link delete" style="display: none">
                                <i class="glyphicon glyphicon-remove text-danger"></i>
                            </button>
                        </div>
                    </td>
                `).join('')}
                <td>
                    <div class="actions">
                        <button id="editButton-${item._id}" onclick="TranslationManager.enableMode('edit', ${item._id})" type="button" class="btn btn-primary btn-sm">
                            <span class="glyphicon glyphicon-pencil"></span>
                        </button>
                        <button id="deleteButton-${item._id}" onclick="TranslationManager.enableMode('delete', ${item._id})" type="button" class="btn btn-danger btn-sm">
                            <span class="glyphicon glyphicon-trash"></span>
                        </button>
                        <button id="saveButton-${item._id}" onclick="TranslationManager.save(${item._id})" type="button" class="btn btn-success btn-sm" style="display: none">
                            <span class="glyphicon glyphicon-saved"></span>
                        </button>
                        <button id="cancelButton-${item._id}" onclick="TranslationManager.enableMode('view', ${item._id})" type="button" class="btn btn-warning btn-sm" style="display: none">
                            <span class="glyphicon glyphicon-ban-circle"></span>
                        </button>
                        <div></div>
                    </div>
                </td>
            </tr>`;
    };

    const escapeHtml = (unsafe) =>
    {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    };

    return {
        init,
        toggleColSelector,
        toggleAllColumns,
        toggleColumn,
        changePage,
        sortColumn,
        enableMode,
        save,
        deleteEntry,
        invalidateCache
    }
})();