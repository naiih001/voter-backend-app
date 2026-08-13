// ============================================================
//  404 view
// ============================================================

import { ui } from '../core/ui.js';

export function view(root) {
  root.className = 'container';
  root.style.paddingTop = '80px';
  root.appendChild(ui.el('div', { class: 'text-center' },
    ui.el('h1', { class: 'page-title' }, '404'),
    ui.el('p', { class: 'page-subtitle mt-8' }, 'The page you are looking for does not exist.'),
    ui.el('a', { href: '/', class: 'btn-primary', style: 'margin-top:24px;' }, 'Go home')
  ));
}
