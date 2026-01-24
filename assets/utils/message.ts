/**
 * Shared message display utility
 */
export class SharedMessage {
  private element: HTMLElement | null;
  private css: string = '';
  private icon: string = '';
  private content: string = '';

  constructor(elementId: string = 'sharedMessage') {
    this.element = document.getElementById(elementId);
  }

  set(css: string, icon: string, content: string): void {
    this.css = css;
    this.icon = icon;
    this.content = content;
  }

  show(css: string, icon: string, content: string): void {
    if (!this.element) return;

    this.set(css, icon, content);
    this.element.classList.add(`label-${css}`);
    this.element.innerHTML = `<span><i class="glyphicon glyphicon-${icon}"></i> ${content}</span>`;
    this.element.style.display = 'block';
  }

  reset(): void {
    if (!this.element) return;

    this.element.classList.remove(`label-${this.css}`);
    this.element.style.display = 'none';
    this.set('', '', '');
  }
}
