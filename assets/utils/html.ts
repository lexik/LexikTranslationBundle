/**
 * Escape HTML to prevent XSS
 */
export function escapeHtml(unsafe: string): string {
  return unsafe
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

/**
 * Render input element based on type
 */
export function renderInputElement(id: number, locale: string, value: string, inputType: 'text' | 'textarea'): string {
  const escapedValue = escapeHtml(value);
  if (inputType === 'textarea') {
    return `<textarea id="inputContent-${id}-${locale}" name="column.index" class="form-control" style="display: none">${escapedValue}</textarea>`;
  } else {
    return `<input type="text" id="inputContent-${id}-${locale}" name="column.index" class="form-control" style="display: none" value="${escapedValue}">`;
  }
}
