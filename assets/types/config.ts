/**
 * Configuration interface for TranslationManager
 */
export interface TranslationConfig {
  locales: string[];
  inputType: 'text' | 'textarea';
  autoCacheClean: boolean;
  profilerTokens: string[] | null;
  toggleSimilar: string;
  csrfToken: string;
  maxPageNumber: number;
  url: {
    list: string;
    listByToken: string;
    update: string;
    delete: string;
    deleteLocale: string;
    invalidateCache: string;
  };
  label: {
    hideCol: string;
    toggleAllCol: string;
    invalidateCache: string;
    allTranslations: string;
    profiler: string;
    dataSource: string;
    latestProfiles: string;
    profile: string;
    saveRow: string;
    domain: string;
    key: string;
    save: string;
    updateSuccess: string;
    updateFail: string;
    deleteConfirm: string;
    deleteSuccess: string;
    deleteFail: string;
    noTranslations: string;
  };
}

/**
 * Translation item from API
 */
export interface TranslationItem {
  _id: number;
  _domain: string;
  _key: string;
  [locale: string]: string | number;
}

/**
 * API response for list
 */
export interface ListResponse {
  translations: TranslationItem[];
  total: number;
}

/**
 * API response for update
 */
export interface UpdateResponse {
  _id: number;
  _domain: string;
  _key: string;
  [locale: string]: string | number;
}

/**
 * API response for delete
 */
export interface DeleteResponse {
  deleted: boolean;
  _key?: string;
}

/**
 * API response message
 */
export interface MessageResponse {
  message: string;
}
