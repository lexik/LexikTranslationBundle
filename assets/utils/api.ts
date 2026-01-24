import type { TranslationConfig, ListResponse, UpdateResponse, DeleteResponse, MessageResponse } from '../types/config';

/**
 * API client for translation operations
 */
export class TranslationApi {
  private csrfToken: string;

  constructor(private config: TranslationConfig) {
    this.csrfToken = config.csrfToken;
  }

  /**
   * Get paginated translations
   */
  async getPage(params: Record<string, string | number>): Promise<Response> {
    const searchParams = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
      searchParams.append(key, String(value));
    });

    return fetch(`${this.config.url.list}?${searchParams}`);
  }

  /**
   * Invalidate translation cache
   */
  async invalidateCache(): Promise<Response> {
    const params = this.getCsrfParams();
    const searchParams = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
      searchParams.append(key, String(value));
    });

    return fetch(`${this.config.url.invalidateCache}?${searchParams}`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
  }

  /**
   * Update a translation
   */
  async updateTranslation(translationId: number, params: Record<string, string>): Promise<Response> {
    const url = this.config.url.update.replace('-id-', String(translationId));
    const bodyParams = this.getCsrfParams();
    Object.assign(bodyParams, params);

    return fetch(url, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams(bodyParams),
    });
  }

  /**
   * Delete a translation locale
   */
  async deleteTranslationLocale(translationId: number, locale: string): Promise<Response> {
    const url = this.config.url.deleteLocale
      .replace('-id-', String(translationId))
      .replace('-locale-', locale);

    const params = this.getCsrfParams();
    const searchParams = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
      searchParams.append(key, String(value));
    });

    return fetch(`${url}?${searchParams}`, {
      method: 'DELETE',
    });
  }

  /**
   * Delete a translation
   */
  async deleteTranslation(translationId: number): Promise<Response> {
    const url = this.config.url.delete.replace('-id-', String(translationId));
    const params = this.getCsrfParams();
    const searchParams = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
      searchParams.append(key, String(value));
    });

    return fetch(`${url}?${searchParams}`, {
      method: 'DELETE',
    });
  }

  /**
   * Get CSRF parameters
   */
  private getCsrfParams(): Record<string, string> {
    const params: Record<string, string> = {};
    if (this.csrfToken) {
      params._token = this.csrfToken;
    }
    return params;
  }
}
