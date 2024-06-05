document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.page-1').forEach(function(element) {
        element.classList.add('disabled');
    });
    reloadGrid('id', 'asc', false, false, null, translationCfg.url.list);
});

function enableMode(mode, lexikTranslationId, locales, url, csrfToken) {
    const editButton = document.getElementById('editButton-' + lexikTranslationId);
    const deleteButton = document.getElementById('deleteButton-' + lexikTranslationId);
    const saveButton = document.getElementById('saveButton-' + lexikTranslationId);
    const cancelButton = document.getElementById('cancelButton-' + lexikTranslationId);

    if (mode == 'edit') {
        editButton.style.display = 'none';
        deleteButton.style.display = 'none';
        saveButton.style.display = 'block';
        cancelButton.style.display = 'block';
        for (i = 0 ; i < locales.length; i++) {
            document.getElementById('content-' + lexikTranslationId + '-' + locales[i]).style.display = 'none';
            document.getElementById('inputContent-' + lexikTranslationId + '-' + locales[i]).style.display = 'block';
        }
    } else if (mode == 'view') {
        editButton.style.display = 'block';
        deleteButton.style.display = 'block';
        saveButton.style.display = 'none';
        cancelButton.style.display = 'none';
        for (i = 0 ; i < locales.length; i++) {
            var oldValue = document.getElementById('content-' + lexikTranslationId + '-' + locales[i]).textContent;
            var newValue = document.getElementById('inputContent-' + lexikTranslationId + '-' + locales[i]).value;

            if (oldValue !== newValue) {
                saveUpdatedLexikTranslations(lexikTranslationId, locales[i], newValue, url, csrfToken);
            }

            document.getElementById('content-' + lexikTranslationId + '-' + locales[i]).style.display = 'block';
            document.getElementById('inputContent-' + lexikTranslationId + '-' + locales[i]).style.display = 'none';
            document.getElementById('btnDelete-' + lexikTranslationId + '-' + locales[i]).style.display = 'none';
            document.getElementById('btnKeyDelete-' + lexikTranslationId).style.display = 'none';
        }
    } else if (mode == 'delete') {
        editButton.style.display = 'none';
        deleteButton.style.display = 'none';
        cancelButton.style.display = 'block';
        for (i = 0 ; i < locales.length; i++) {
            document.getElementById('btnDelete-' + lexikTranslationId + '-' + locales[i]).style.display = 'block';
            document.getElementById('btnKeyDelete-' + lexikTranslationId).style.display = 'block';
        }
    }
}

function saveUpdatedLexikTranslations(lexikTranslationId, locale, newValue, url, csrfToken) {
    let params = [];

    document.getElementById('inputContent-' + lexikTranslationId + '-' + locale).value = newValue;
    document.getElementById('content-' + lexikTranslationId + '-' + locale).innerText = newValue;
    var saveButton = document.getElementById('saveButton-' + lexikTranslationId);
    var trElement = saveButton.closest('tr.content');
    var tdElements = trElement.querySelectorAll('td');
    var translationsElements = trElement.querySelectorAll('td span.locale');

    var spanColumnText = Array.from(tdElements).map(function (td, index) {
        if (index <= 2) {
            var span = td.querySelector('span');
            if (span) {
                var th = trElement.closest('table').querySelectorAll('th')[index];
                return {
                    value: span.innerText.trim(),
                    column: th ? '_' + th.id.split('-')[0] : null
                };
            }
            return null;
        }
    }).filter(Boolean);

    var translationsTexts = Array.from(translationsElements).map(function (translationElement) {
        return translationElement.innerText.trim() + '-' + translationElement.id;
    });

    translationsTexts.forEach(function (translationText) {
        var parts = translationText.split('-');
        var translation = parts[0];
        var locale = parts[3];
        params.push({ name: locale, value: translation });
    });

    spanColumnText.forEach(function (spanText) {
        params.push({ name: spanText.column, value: spanText.value });
    });

    params.push({ name: '_token', value: csrfToken });

    url = url.replace('-id-', lexikTranslationId);

    sendRequest('PUT', url, params, false);
}

function deleteEntry(lexikTranslationId, locale, url, csrfToken, confirmMessage, reloadUrl)
{
    if (confirm(confirmMessage))
    {
        let params = [];

        if (locale !== null) {
            url = url.replace('-locale-', locale);
        }

        url = url.replace('-id-', lexikTranslationId);

        params.push({ name: '_token', value: csrfToken });

        sendRequest('DELETE', url, params, true, reloadUrl);
    }
}

function sendRequest(type, url, params, isReloadGrid, reloadUrl)
{
    let urlParams = params.map(function(param) {
        return encodeURIComponent(param.name) + '=' + encodeURIComponent(param.value);
    }).join('&');

    var xhr = new XMLHttpRequest();
    xhr.open(type, url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    let formData = params.map(function(param) {
        return encodeURIComponent(param.name) + '=' + encodeURIComponent(param.value);
    }).join('&');

    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300) {
            var data = JSON.parse(xhr.responseText);
            if (isReloadGrid) {
                reloadGrid('id', 'asc', false, true, null, reloadUrl);
            }
        } else {
            var errorData = JSON.parse(xhr.responseText);
            console.error('Error: ' + errorData.status + '\n' + errorData.statusText);
        }
    };

    xhr.onerror = function() {
        console.error('Request failed');
    };

    xhr.send(formData);
}

function reloadGrid(orderedBy, sort, afterSortClicked, isAfterDelete, maxPageNumber, url) {
    document.getElementById(orderedBy + "-header").classList.add("column-sorted");
    document.getElementById(orderedBy + "-header").setAttribute("sort-type", sort);
    let table = '';
    let params = [];

    let lastRow = document.querySelector('.table tbody');

    if (afterSortClicked) {
        addFilteredValuesToParams(params, false);

        params.push({ name: 'sidx', value: '_' + orderedBy });
        params.push({ name: 'sord', value: sort });
        params.push({ name: 'page', value: 1 });
        params.push({ name: 'rows', value: 20 });
    }

    let urlParams = params.map(function(param) {
        return encodeURIComponent(param.name) + '=' + encodeURIComponent(param.value);
    }).join('&');

    let completeUrl = url + '?' + urlParams;

    var xhr = new XMLHttpRequest();
    xhr.open('GET', completeUrl, true);
    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300) {
            var data = JSON.parse(xhr.responseText);
            data.translations.forEach(function(item) {
                table += constructHtmlTr(item);
            });

            if (!afterSortClicked && !isAfterDelete) {
                document.querySelector('.table').insertAdjacentHTML('beforeend', table);
            } else {
                lastRow.nextElementSibling.remove();
                lastRow.insertAdjacentHTML('afterend', table);
                if (afterSortClicked) {
                    reverseNextSortOrder(sort, orderedBy, getMaxPageNumber(data.total), url);
                    managePagesChanger(1, getMaxPageNumber(data.total), url);
                }
            }
        } else {
            var errorData = JSON.parse(xhr.responseText);
            console.error('Error: ' + errorData.status + '\n' + errorData.statusText);
        }
    };
    xhr.onerror = function() {
        console.error('Request failed');
    };
    xhr.send();
}

function sortColumn(column, sortOrder, maxPageNumber, url) {
    document.querySelectorAll('.table .column-sorted').forEach(function(column) {
        column.classList.remove('column-sorted');
    });
    document.getElementById(column).classList.add("column-sorted");
    reloadGrid(column, sortOrder, true, false, maxPageNumber, url);
}

function reverseNextSortOrder(sort, orderedBy, maxPageNumber, url) {
    var nextSortOrder = sort === 'asc' ? 'desc' : 'asc';
    document.getElementById(orderedBy + '-header').setAttribute('onclick', "sortColumn('" + orderedBy + "', '" + nextSortOrder + "', '" + maxPageNumber + "', '" + url + "')");
}

function filterColumn(column, url) {
    document.getElementById(column).classList.add("column-filtered");

    let inputColumnsFiltered = document.querySelectorAll('.table input.column-filtered');

    let table = '';
    let lastRow = document.querySelector('.table tbody');
    let params = [];

    addFilteredValuesToParams(params, true);

    params.push({ name: 'page', value: 1 });
    params.push({ name: 'rows', value: 20 });

    let urlParams = params.map(function(param) {
        return encodeURIComponent(param.name) + '=' + encodeURIComponent(param.value);
    }).join('&');

    let completeUrl = url + '?' + urlParams;

    var xhr = new XMLHttpRequest();
    xhr.open('GET', completeUrl, true);
    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300) {
            var data = JSON.parse(xhr.responseText);
            var alert = document.querySelector('.alert');
            if (alert) {
                document.querySelector('.alert').remove();
            }

            if (lastRow.nextElementSibling !== null) {
                lastRow.nextElementSibling.remove();
            }

            if (data.total > 0) {
                data.translations.forEach(function(item) {
                    table += constructHtmlTr(item);
                });
                lastRow.insertAdjacentHTML('afterend', table);
                document.querySelector('.pagination').style.display = 'block';
            } else {
                document.querySelector('.pagination').style.display = 'none';
                let noTranslationsHtml = displayNoTranslations();
                document.querySelector('.container').insertAdjacentHTML('afterend', noTranslationsHtml);
            }

            if (inputColumnsFiltered.length > 0) {
                managePagesChanger(1, getMaxPageNumber(data.total), url);
            } else {
                managePagesChanger(1, document.getElementById('id-header').getAttribute('data-totalpages'), url);
            }
        } else {
            var errorData = JSON.parse(xhr.responseText);
            console.error('Error: ' + errorData.status + '\n' + errorData.statusText);
        }
    };
    xhr.onerror = function() {
        console.error('Request failed');
    };
    xhr.send();
}

function changePage(page, maxPageNumber, url) {
    let table = '';
    let lastRow = document.querySelector('.table tbody');
    let params = [];

    let thColumnSorted = document.querySelector('.table th.column-sorted');

    addFilteredValuesToParams(params, false);

    if (thColumnSorted) {
        let columnSortedId = thColumnSorted.getAttribute('id').replace('-header', '');
        let columnSortedType = thColumnSorted.getAttribute('sort-type');

        params.push({ name: 'sidx', value: '_' + columnSortedId });
        params.push({ name: 'sord', value: columnSortedType });
    }

    params.push({ name: 'page', value: page });
    params.push({ name: 'rows', value: 20 });

    let urlParams = params.map(function(param) {
        return encodeURIComponent(param.name) + '=' + encodeURIComponent(param.value);
    }).join('&');

    let completeUrl = url + '?' + urlParams;

    var xhr = new XMLHttpRequest();
    xhr.open('GET', completeUrl, true);
    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300) {
            var data = JSON.parse(xhr.responseText);
            data.translations.forEach(function(item) {
                table += constructHtmlTr(item);
            });
            lastRow.nextElementSibling.remove();
            lastRow.insertAdjacentHTML('afterend', table);
            managePagesChanger(page, maxPageNumber, url);
        } else {
            var errorData = JSON.parse(xhr.responseText);
            console.error('Error: ' + errorData.status + '\n' + errorData.statusText);
        }
    };

    xhr.onerror = function() {
        console.error('Request failed');
    };

    xhr.send();
}

function managePagesChanger(page, maxPageNumber, url) {
    document.querySelector('.prev').setAttribute('onclick', "changePage(" + (page - 1) + "," + maxPageNumber + ",'" + url + "')");
    document.querySelector('.next').setAttribute('onclick', "changePage(" + (page + 1) + "," + maxPageNumber + ",'" + url + "')");

    if (page != 1) {
        document.querySelector('.prev').classList.remove('disabled');
    } else {
        document.querySelector('.prev').classList.add('disabled');
    }

    let startPage = Math.max(page - 5, 1);
    let endPage = Math.min(page + 5, maxPageNumber);

    let additionalHTML = '';
    for (let i = startPage; i <= endPage; i++) {
        if (i === page) {
            additionalHTML += '<a class="page-' + i + '" href="#" class="disabled">' + i + '</a>';
        } else {
            additionalHTML += '<a class="page-' + i + '" onclick="changePage(' + i + ', ' + maxPageNumber + ', \'' + url + '\')">' + i + '</a>';
        }
    }

    document.querySelector('.dynamicPages').innerHTML = additionalHTML;
    if (document.querySelector('.page-' + page)) {
        document.querySelector('.page-' + page).classList.add('disabled');
    }
}

function getMaxPageNumber(translationsTotal)
{
    let totalPages = translationsTotal / 20;
    let totalPagesRounded = Math.ceil(totalPages);

    return totalPagesRounded;
}

function addFilteredValuesToParams(params, isSearch)
{
    let inputColumnsFiltered = document.querySelectorAll('.table input.column-filtered');

    inputColumnsFiltered.forEach(input => {
        let column = input.getAttribute('id');
        let filterValue = input.value;
        if (column === 'key' || column === 'domain') {
            column = '_' + column;
        }

        if (!isSearch) {
            let columnFilteredValueNotEmpty = filterValue !== '';
            params.push({name: '_search', value: columnFilteredValueNotEmpty});
        } else {
            params.push({name: '_search', value: true});
        }

        params.push({ name: column, value: filterValue });
    });
}

function constructHtmlTr(item) {
    let tr = `
        <tr class="content">
            <td>
                <span>${item._id}</span>
                <div on="editType">
                </div>
                <div class="text-center">
                    <button type="button" class="btn btn-link delete" style="display:none">
                        <i class="glyphicon glyphicon-remove text-danger"></i>
                    </button>
                </div>
            </td>
            <td>
                <span>${item._domain}</span>
                <div on="editType">
                </div>
                <div class="text-center">
                    <button type="button" class="btn btn-link delete" style="display:none">
                        <i class="glyphicon glyphicon-remove text-danger"></i>
                    </button>
                </div>
            </td>
            <td>
                <span>${item._key}</span>
                <div on="editType">
                </div>
                <div class="text-center">
                    <button id="btnKeyDelete-${item._id}" onclick="deleteEntry(${item._id}, null, translationCfg.url.delete, translationCfg.csrfToken, translationCfg.confirmDelete, translationCfg.url.list)" type="button" class="btn btn-link delete" style="display:none">
                        <i class="glyphicon glyphicon-remove text-danger"></i>
                    </button>
                </div>
            </td>
            ${Object.keys(item).filter(key => key !== '_id' && key !== '_domain' && key !== '_key').map(locale => `
            <td>
                <span id="content-${item._id}-${locale}" class="locale">${item[locale]}</span>
                <div>
                    <textarea id="inputContent-${item._id}-${locale}" name="column.index" class="form-control" style="display: none">${item[locale]}</textarea>
                </div>
                <div class="text-center">
                    <button id="btnDelete-${item._id}-${locale}" onclick="deleteEntry(${item._id}, '${locale}', translationCfg.url.deleteLocale, translationCfg.csrfToken, translationCfg.confirmDelete, translationCfg.url.list)" type="button" class="btn btn-link delete" style="display: none">
                        <i class="glyphicon glyphicon-remove text-danger"></i>
                    </button>
                </div>
            </td>
            `).join('')}
            <td>
                <div class="actions">
                    <button id="editButton-${item._id}" onclick="enableMode('edit', ${item._id}, translationCfg.locales)" type="button" class="btn btn-primary btn-sm">
                        <span class="glyphicon glyphicon-pencil"></span>
                    </button>
                    <button id="deleteButton-${item._id}" onclick="enableMode('delete', ${item._id}, translationCfg.locales)" type="button" class="btn btn-danger btn-sm">
                        <span class="glyphicon glyphicon-trash"></span>
                    </button>
                    <button id="saveButton-${item._id}" onclick="enableMode('view', ${item._id}, translationCfg.locales, translationCfg.url.update, translationCfg.csrfToken)" type="button" class="btn btn-success btn-sm" style="display: none">
                        <span class="glyphicon glyphicon-saved"></span>
                    </button>
                    <button id="cancelButton-${item._id}" onclick="enableMode('view', ${item._id}, translationCfg.locales)" type="button" class="btn btn-warning btn-sm" style="display: none">
                        <span class="glyphicon glyphicon-ban-circle"></span>
                    </button>
                    <div></div>
                </div>
            </td>
        </tr>`;

    return tr;
}

function displayNoTranslations()
{
    let div =
        `<div class="alert alert-info">
                No translations
            </div>`;

    return div;
}