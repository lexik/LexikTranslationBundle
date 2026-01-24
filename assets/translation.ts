import type { TranslationConfig, TranslationItem, ListResponse, UpdateResponse } from './types/config';
import { TranslationApi } from './utils/api';
import { SharedMessage } from './utils/message';
import { constructHtmlTr, getMaxPageNumber } from './utils/grid';

/**
 * Translation Manager - Main class for managing translation grid
 */
class TranslationManager {
  private config: TranslationConfig | null = null;
  private currentPage: number = 1;
  private totalPages: number = 0;
  private order: string = 'id';
  private direction: string = 'asc';
  private showColSelector: boolean = false;
  private showCol: Record<string, boolean> = {};
  private api: TranslationApi | null = null;
  private message: SharedMessage;
  private debounceTimeouts: Record<string, number> = {};

  constructor() {
    this.message = new SharedMessage();
  }

  /**
   * Initialize the translation manager
   */
  init(config: TranslationConfig): void {
    console.log('[TranslationManager] init: Initializing TranslationManager', config);
    this.config = config;
    this.api = new TranslationApi(config);

    // Initialize column visibility (all visible by default)
    this.showCol = {
      _id: true,
      _domain: true,
      _key: true,
    };
    config.locales.forEach((locale) => {
      this.showCol[locale] = true;
    });

    console.log('[TranslationManager] init: DOM readyState:', document.readyState);
    
    // Check if DOM is already loaded
    if (document.readyState === 'loading') {
      console.log('[TranslationManager] init: DOM is loading, waiting for DOMContentLoaded');
      document.addEventListener('DOMContentLoaded', () => {
        console.log('[TranslationManager] init: DOMContentLoaded fired');
        this.initializeFilters();
      });
    } else {
      console.log('[TranslationManager] init: DOM already loaded, executing immediately');
      // DOM is already loaded, execute immediately
      this.initializeFilters();
    }
  }

  /**
   * Initialize filters and setup event listeners
   */
  private initializeFilters(): void {
    console.log('[TranslationManager] initializeFilters: Starting filter initialization');
    
    // Wait for filter inputs to be available in the DOM
    this.waitForInputs(() => {
      console.log('[TranslationManager] initializeFilters: Inputs found, applying filters from URL');
      
      // Apply filters from URL query parameters first
      this.applyFiltersFromQueryString();

      // Setup filter inputs with debounce and URL sync
      this.setupInputListeners();

      // Reload grid with applied filters
      console.log('[TranslationManager] initializeFilters: Reloading grid with applied filters');
      this.reloadGrid();
    });

    // Listen for browser back/forward button
    window.addEventListener('popstate', () => {
      console.log('[TranslationManager] initializeFilters: Popstate event detected');
      this.waitForInputs(() => {
        this.applyFiltersFromQueryString();
        this.reloadGrid();
      });
    });
  }

  /**
   * Wait for filter inputs to be available in the DOM
   */
  private waitForInputs(callback: () => void, timeout = 5000, interval = 50): void {
    console.log('[TranslationManager] waitForInputs: Waiting for filter inputs to be available');
    const startTime = Date.now();
    const checkInputs = () => {
      const inputs = document.querySelectorAll('.table .input-sm');
      console.log(`[TranslationManager] waitForInputs: Found ${inputs.length} input(s)`);
      
      if (inputs.length > 0) {
        console.log('[TranslationManager] waitForInputs: Inputs available, executing callback');
        callback();
      } else if (Date.now() - startTime < timeout) {
        setTimeout(checkInputs, interval);
      } else {
        console.warn('[TranslationManager] waitForInputs: Timeout waiting for filter inputs to be available.');
        // Still setup listeners and reload even if inputs not found
        this.setupInputListeners();
        this.reloadGrid();
      }
    };
    checkInputs();
  }

  /**
   * Apply filters from URL query string to input fields
   */
  private applyFiltersFromQueryString(): void {
    const urlParams = new URLSearchParams(window.location.search);
    console.log('[TranslationManager] applyFiltersFromQueryString: Current URL search:', window.location.search);
    console.log('[TranslationManager] applyFiltersFromQueryString: URL params count:', urlParams.toString().split('&').length);

    let appliedCount = 0;
    
    // Process filter parameters
    urlParams.forEach((value, key) => {
      console.log(`[TranslationManager] applyFiltersFromQueryString: Processing param - key: "${key}", value: "${value}"`);
      
      // Handle filter[_domain], filter[_key], etc.
      if (key.startsWith('filter[') && key.endsWith(']')) {
        // Extract key from filter[_domain] -> _domain
        const filterKey = key.substring(7, key.length - 1);
        console.log(`[TranslationManager] applyFiltersFromQueryString: Extracted filter key: "${filterKey}"`);
        
        const input = document.getElementById(filterKey) as HTMLInputElement;
        if (input) {
          const decodedValue = decodeURIComponent(value);
          console.log(`[TranslationManager] applyFiltersFromQueryString: Setting input "${filterKey}" to "${decodedValue}"`);
          input.value = decodedValue;
          appliedCount++;
        } else {
          console.warn(`[TranslationManager] applyFiltersFromQueryString: Input with id "${filterKey}" not found`);
        }
      } else if (key.startsWith('_')) {
        // Handle direct parameters like _domain, _key, etc.
        console.log(`[TranslationManager] applyFiltersFromQueryString: Processing direct param "${key}"`);
        const input = document.getElementById(key) as HTMLInputElement;
        if (input) {
          const decodedValue = decodeURIComponent(value);
          console.log(`[TranslationManager] applyFiltersFromQueryString: Setting input "${key}" to "${decodedValue}"`);
          input.value = decodedValue;
          appliedCount++;
        } else {
          console.warn(`[TranslationManager] applyFiltersFromQueryString: Input with id "${key}" not found`);
        }
      } else {
        console.log(`[TranslationManager] applyFiltersFromQueryString: Skipping param "${key}" (doesn't match filter pattern)`);
      }
    });
    
    console.log(`[TranslationManager] applyFiltersFromQueryString: Applied ${appliedCount} filter(s) from URL`);
  }

  /**
   * Setup input listeners with debounce and URL synchronization
   */
  private setupInputListeners(): void {
    // Use event delegation on the table to handle all input changes
    const table = document.querySelector('.table');
    if (table) {
      table.addEventListener('keyup', (e) => {
        const target = e.target as HTMLInputElement;
        if (target && target.classList.contains('input-sm') && target.id) {
          if (this.debounceTimeouts[target.id]) {
            clearTimeout(this.debounceTimeouts[target.id]);
          }
          this.debounceTimeouts[target.id] = window.setTimeout(() => {
            this.currentPage = 1;
            this.order = 'id';
            this.direction = 'asc';
            // Update URL with current filters
            this.updateURLFromFilters();
            this.reloadGrid();
          }, 200);
        }
      });
    }
  }

  /**
   * Update URL with current filter values without page reload
   */
  private updateURLFromFilters(): void {
    console.log('[TranslationManager] updateURLFromFilters: Updating URL from current filters');
    const urlParams = new URLSearchParams();
    const inputs = document.querySelectorAll<HTMLInputElement>('.table .input-sm');
    
    console.log(`[TranslationManager] updateURLFromFilters: Found ${inputs.length} input(s)`);

    inputs.forEach((input) => {
      if (input.id && input.value.trim() !== '') {
        // Use filter[key] format for consistency
        const paramKey = `filter[${input.id}]`;
        urlParams.set(paramKey, input.value);
        console.log(`[TranslationManager] updateURLFromFilters: Adding filter - ${paramKey} = "${input.value}"`);
      }
    });

    // Build new URL with filters
    const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
    console.log(`[TranslationManager] updateURLFromFilters: New URL: ${newUrl}`);
    window.history.pushState({ path: newUrl }, '', newUrl);
  }

  /**
   * Toggle column selector visibility
   */
  toggleColSelector(): void {
    this.showColSelector = !this.showColSelector;
    const selector = document.getElementById('columnsSelector');
    if (selector) {
      selector.style.display = this.showColSelector ? 'block' : 'none';
    }
  }

  /**
   * Toggle all columns visibility
   */
  toggleAllColumns(checked: boolean): void {
    const toggleList = document.getElementById('toogle-list');
    if (!toggleList) return;

    toggleList.querySelectorAll<HTMLInputElement>('[id^="toggle-"]').forEach((input) => {
      input.checked = checked;
      this.toggleColumn(input.id.replace('toggle-', ''), checked);
    });
  }

  /**
   * Toggle a specific column visibility
   */
  toggleColumn(column: string, checked: boolean): void {
    this.showCol[column] = checked;

    const header = document.getElementById(`header-${column}`);
    if (header) {
      header.classList.toggle('hide', !checked);
    }

    document.querySelectorAll(`.col-${column}`).forEach((element) => {
      element.classList.toggle('hide', !checked);
    });

    if (this.config?.toggleSimilar) {
      document.querySelectorAll<HTMLInputElement>(`[id^="toggle-${column}_"]`).forEach((input) => {
        const colName = input.id.replace('toggle-', '');
        this.showCol[colName] = checked;
        input.checked = checked;
      });

      document.querySelectorAll(`[id^="header-${column}_"]`).forEach((element) => {
        element.classList.toggle('hide', !checked);
      });

      document.querySelectorAll(`[class^="col-${column}_"]`).forEach((element) => {
        element.classList.toggle('hide', !checked);
      });
    }
  }

  /**
   * Enable edit/view/delete mode for a translation row
   */
  enableMode(mode: 'edit' | 'view' | 'delete', lexikTranslationId: number): void {
    if (!this.config) return;

    const locales = this.config.locales;
    const editButton = document.getElementById(`editButton-${lexikTranslationId}`);
    const deleteButton = document.getElementById(`deleteButton-${lexikTranslationId}`);
    const saveButton = document.getElementById(`saveButton-${lexikTranslationId}`);
    const cancelButton = document.getElementById(`cancelButton-${lexikTranslationId}`);

    if (mode === 'edit') {
      this.message.reset();
      if (editButton) editButton.style.display = 'none';
      if (deleteButton) deleteButton.style.display = 'none';
      if (saveButton) saveButton.style.display = 'block';
      if (cancelButton) cancelButton.style.display = 'block';

      locales.forEach((locale) => {
        const content = document.getElementById(`content-${lexikTranslationId}-${locale}`);
        const input = document.getElementById(`inputContent-${lexikTranslationId}-${locale}`);
        if (content) content.style.display = 'none';
        if (input) input.style.display = 'block';
      });
    } else if (mode === 'view') {
      if (editButton) editButton.style.display = 'block';
      if (deleteButton) deleteButton.style.display = 'block';
      if (saveButton) saveButton.style.display = 'none';
      if (cancelButton) cancelButton.style.display = 'none';

      locales.forEach((locale) => {
        const content = document.getElementById(`content-${lexikTranslationId}-${locale}`);
        const input = document.getElementById(`inputContent-${lexikTranslationId}-${locale}`);
        const btnDelete = document.getElementById(`btnDelete-${lexikTranslationId}-${locale}`);
        const btnKeyDelete = document.getElementById(`btnKeyDelete-${lexikTranslationId}`);

        if (content) content.style.display = 'block';
        if (input) input.style.display = 'none';
        if (btnDelete) btnDelete.style.display = 'none';
        if (btnKeyDelete) btnKeyDelete.style.display = 'none';
      });
    } else if (mode === 'delete') {
      this.message.reset();
      if (editButton) editButton.style.display = 'none';
      if (deleteButton) deleteButton.style.display = 'none';
      if (cancelButton) cancelButton.style.display = 'block';

      locales.forEach((locale) => {
        const content = document.getElementById(`content-${lexikTranslationId}-${locale}`);
        const btnDelete = document.getElementById(`btnDelete-${lexikTranslationId}-${locale}`);
        const btnKeyDelete = document.getElementById(`btnKeyDelete-${lexikTranslationId}`);

        if (content && content.textContent?.trim() !== '') {
          if (btnDelete) btnDelete.style.display = 'block';
          if (btnKeyDelete) btnKeyDelete.style.display = 'block';
        }
      });
    }
  }

  /**
   * Save translation changes
   */
  save(lexikTranslationId: number): void {
    if (!this.config) return;

    let update = false;
    const locales = this.config.locales;

    locales.forEach((locale) => {
      const contentEl = document.getElementById(`content-${lexikTranslationId}-${locale}`);
      const inputEl = document.getElementById(`inputContent-${lexikTranslationId}-${locale}`) as HTMLInputElement | HTMLTextAreaElement | null;

      if (contentEl && inputEl) {
        const oldValue = contentEl.textContent || '';
        const newValue = inputEl.value;
        if (oldValue !== newValue) {
          update = true;
          contentEl.textContent = newValue;
        }
      }
    });

    if (update) {
      this.saveEntry(lexikTranslationId);
    }

    this.enableMode('view', lexikTranslationId);
  }

  /**
   * Save entry to API
   */
  private saveEntry(lexikTranslationId: number): void {
    if (!this.api || !this.config) return;

    const saveButton = document.getElementById(`saveButton-${lexikTranslationId}`);
    if (!saveButton) return;

    const trElement = saveButton.closest('tr.content');
    if (!trElement) return;

    const params: Record<string, string> = {};
    const tdElements = trElement.querySelectorAll<HTMLElement>('td[class^="col-"]');

    tdElements.forEach((td) => {
      const span = td.querySelector('span');
      const col = td.classList[0].replace('col-', '');
      if (span && col) {
        params[col] = span.textContent || '';
      }
    });

    this.api.updateTranslation(lexikTranslationId, params).then((response) => {
      if (response.status === 200) {
        response.json().then((data: UpdateResponse) => {
          this.message.show(
            'success',
            'ok-circle',
            this.config!.label.updateSuccess.replace('%id%', data._key || String(lexikTranslationId))
          );
        });
      } else {
        this.message.show('danger', 'remove-circle', this.config!.label.updateFail.replace('%id%', String(lexikTranslationId)));
      }
    });
  }

  /**
   * Delete translation or locale
   */
  deleteEntry(lexikTranslationId: number, locale: string | null): void {
    if (!this.api || !this.config) return;

    if (!confirm(this.config.label.deleteConfirm)) {
      return;
    }

    if (locale === null) {
      this.api.deleteTranslation(lexikTranslationId).then((response) => {
        if (response.status === 200) {
          response.json().then((data) => {
            this.message.show('success', 'ok-circle', this.config!.label.deleteSuccess.replace('%id%', data._key || String(lexikTranslationId)));
            this.reloadGrid();
          });
        } else {
          this.message.show('danger', 'remove-circle', this.config!.label.deleteFail.replace('%id%', String(lexikTranslationId)));
        }
      });
    } else {
      this.api.deleteTranslationLocale(lexikTranslationId, locale).then((response) => {
        if (response.status === 200) {
          response.json().then((data) => {
            this.enableMode('view', lexikTranslationId);
            const input = document.getElementById(`inputContent-${lexikTranslationId}-${locale}`) as HTMLInputElement | HTMLTextAreaElement | null;
            const content = document.getElementById(`content-${lexikTranslationId}-${locale}`);
            if (input) input.value = '';
            if (content) content.innerText = '';
            this.message.show('success', 'ok-circle', this.config!.label.deleteSuccess.replace('%id%', data._key || String(lexikTranslationId)));
          });
        } else {
          this.message.show('danger', 'remove-circle', this.config!.label.deleteFail.replace('%id%', String(lexikTranslationId)));
        }
      });
    }
  }

  /**
   * Invalidate translation cache
   */
  invalidateCache(): void {
    if (!this.api || !this.config) return;

    this.api.invalidateCache().then((response) => {
      if (response.status === 200) {
        response.json().then((data: { message: string }) => {
          this.message.show('success', 'ok-circle', data.message);
        });
      } else {
        this.message.show('danger', 'remove-circle', 'Error');
      }
    });
  }

  /**
   * Reload translation grid
   */
  reloadGrid(): void {
    if (!this.api || !this.config) {
      console.warn('[TranslationManager] reloadGrid: API or config not available');
      return;
    }

    console.log('[TranslationManager] reloadGrid: Reloading grid');
    const parameters: Record<string, string | number> = {
      sidx: this.order,
      sord: this.direction,
      page: this.currentPage,
      rows: this.config.maxPageNumber,
    };

    this.addFilteredValuesToParams(parameters);
    console.log('[TranslationManager] reloadGrid: Request parameters:', parameters);

    this.api.getPage(parameters).then((response) => {
      console.log('[TranslationManager] reloadGrid: API response status:', response.status);
      if (response.status === 200) {
        response.json().then((data: ListResponse) => {
          let table = '';
          data.translations.forEach((item) => {
            table += constructHtmlTr(item, this.showCol, this.config!);
          });

          this.totalPages = getMaxPageNumber(data.total, this.config.maxPageNumber);
          const tbody = document.querySelector('.table tbody');
          const noTranslation = document.querySelector('.info-no-translation');

          if (tbody) {
            tbody.innerHTML = table;
          }
          if (noTranslation) {
            (noTranslation as HTMLElement).style.display = data.total === 0 ? 'block' : 'none';
          }

          this.managePagesChanger();
        });
      } else {
        this.message.show('danger', 'remove-circle', 'Error');
      }
    }).catch((error) => {
      console.error('Request failed', error);
    });
  }

  /**
   * Sort column
   */
  sortColumn(column: string, direction: string): void {
    this.order = column;
    this.direction = direction;

    this.reverseNextSortOrder(this.order, this.direction);
    this.reloadGrid();
  }

  /**
   * Reverse sort order for next click
   */
  private reverseNextSortOrder(order: string, direction: string): void {
    const nextSortOrder = direction === 'asc' ? 'desc' : 'asc';
    const header = document.getElementById(`header-${order}`);
    if (header) {
      header.setAttribute('onclick', `TranslationManager.sortColumn('${order}', '${nextSortOrder}')`);
    }
  }

  /**
   * Change page
   */
  changePage(page: number): void {
    this.currentPage = page;
    this.reloadGrid();
  }

  /**
   * Manage pagination UI
   */
  private managePagesChanger(): void {
    const pagination = document.querySelector('.pagination');
    if (!pagination) return;

    if (this.totalPages === 0) {
      (pagination as HTMLElement).style.display = 'none';
    } else {
      (pagination as HTMLElement).style.display = 'block';

      const startPage = Math.max(this.currentPage - 5, 1);
      const endPage = Math.min(this.currentPage + 5, this.totalPages);

      let additionalHTML = '<li><a class="prev">&laquo;</a></li>';
      for (let i = startPage; i <= endPage; i++) {
        if (i === this.currentPage) {
          additionalHTML += `<li><a class="page-${i} disabled" href="#">${i}</a></li>`;
        } else {
          additionalHTML += `<li><a class="page-${i}" onclick="TranslationManager.changePage(${i})">${i}</a></li>`;
        }
      }
      additionalHTML += '<li><a class="next">&raquo;</a></li>';

      pagination.innerHTML = additionalHTML;

      const prev = pagination.querySelector('.prev');
      const next = pagination.querySelector('.next');

      if (prev) {
        if (this.currentPage !== 1) {
          prev.setAttribute('onclick', `TranslationManager.changePage(${this.currentPage - 1})`);
        } else {
          prev.classList.add('disabled');
        }
      }

      if (next) {
        if (this.currentPage !== this.totalPages) {
          next.setAttribute('onclick', `TranslationManager.changePage(${this.currentPage + 1})`);
        } else {
          next.classList.add('disabled');
        }
      }
    }
  }

  /**
   * Add filtered values to parameters
   */
  private addFilteredValuesToParams(params: Record<string, string | number>): void {
    console.log('[TranslationManager] addFilteredValuesToParams: Adding filter values to request params');
    let search = false;
    const inputColumnsFiltered = document.querySelectorAll<HTMLInputElement>('.table .input-sm');
    
    console.log(`[TranslationManager] addFilteredValuesToParams: Found ${inputColumnsFiltered.length} input(s) to process`);

    inputColumnsFiltered.forEach((input) => {
      const column = input.getAttribute('id');
      const filterValue = input.value;
      if (column && filterValue.trim() !== '') {
        search = true;
        // Use filter[column] format for backend compatibility
        const paramKey = `filter[${column}]`;
        params[paramKey] = filterValue;
        console.log(`[TranslationManager] addFilteredValuesToParams: Added filter - ${paramKey} = "${filterValue}"`);
      }
    });

    if (search) {
      params._search = true;
      console.log('[TranslationManager] addFilteredValuesToParams: Search mode enabled');
    } else {
      console.log('[TranslationManager] addFilteredValuesToParams: No active filters');
    }
  }
}

// Export singleton instance for global access (compatibility with existing templates)
declare global {
  interface Window {
    TranslationManager: TranslationManager;
  }
}

const manager = new TranslationManager();
window.TranslationManager = manager;

export default manager;
