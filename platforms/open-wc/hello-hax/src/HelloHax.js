import { LitElement, html, css } from 'lit-element';
import '@lrnwebcomponents/h-a-x/h-a-x.js';

export class HelloHax extends LitElement {
  static get properties() {
    return {
      title: { type: String },
      page: { type: String },
    };
  }

  static get styles() {
    return css`
      :host {
        display: block;
      }
    `;
  }

  render() {
    return html`
      <main>
        <h-a-x>
          <slot></slot>
        </h-a-x>
      </main>
    `;
  }
}
