import type { TranslationItem, TranslationConfig } from '../types/config';
import { escapeHtml, renderInputElement } from './html';

/**
 * Build HTML table row for a translation item
 */
export function constructHtmlTr(
  item: TranslationItem,
  showCol: Record<string, boolean>,
  config: TranslationConfig
): string {
  const localeKeys = Object.keys(item).filter(
    (key) => key !== '_id' && key !== '_domain' && key !== '_key'
  );

  const localeCells = localeKeys
    .map(
      (locale) => `
        <td class="col-${locale} ${showCol[locale] === false ? 'hide' : ''}">
            <span id="content-${item._id}-${locale}" class="locale">${escapeHtml(String(item[locale] || ''))}</span>
            <div>
                ${renderInputElement(item._id, locale, String(item[locale] || ''), config.inputType)}
            </div>
            <div class="text-center">
                <button id="btnDelete-${item._id}-${locale}" onclick="TranslationManager.deleteEntry(${item._id}, '${locale}')" type="button" class="btn btn-link delete" style="display: none">
                    <i class="glyphicon glyphicon-remove text-danger"></i>
                </button>
            </div>
        </td>
    `
    )
    .join('');

  return `
    <tr class="content">
        <td class="col-_id ${showCol['_id'] === false ? 'hide' : ''}">
            <span>${item._id}</span>
            <div on="editType"></div>
            <div class="text-center">
                <button type="button" class="btn btn-link delete" style="display:none">
                    <i class="glyphicon glyphicon-remove text-danger"></i>
                </button>
            </div>
        </td>
        <td class="col-_domain ${showCol['_domain'] === false ? 'hide' : ''}">
            <span>${item._domain}</span>
            <div on="editType"></div>
            <div class="text-center">
                <button type="button" class="btn btn-link delete" style="display:none">
                    <i class="glyphicon glyphicon-remove text-danger"></i>
                </button>
            </div>
        </td>
        <td class="col-_key ${showCol['_key'] === false ? 'hide' : ''}">
            <span>${item._key}</span>
            <div on="editType"></div>
            <div class="text-center">
                <button id="btnKeyDelete-${item._id}" onclick="TranslationManager.deleteEntry(${item._id}, null)" type="button" class="btn btn-link delete" style="display:none">
                    <i class="glyphicon glyphicon-remove text-danger"></i>
                </button>
            </div>
        </td>
        ${localeCells}
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
    </tr>
  `;
}

/**
 * Calculate max page number
 */
export function getMaxPageNumber(total: number, maxPageNumber: number): number {
  return Math.ceil(total / maxPageNumber);
}
