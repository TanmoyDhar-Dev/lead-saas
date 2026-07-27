/**
 * LeadFlow toaster — project-wide toast notifications.
 *
 * Usage:
 *   window.toast.success('Saved')
 *   window.toast.error('Failed')
 *   window.toast.warning('Check row 22')
 *   window.toast.info('Heads up')
 *   window.toast.show({ type, title, message, html, duration })
 */
const ICONS = {
    success: `<svg class="lf-toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
    error: `<svg class="lf-toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
    warning: `<svg class="lf-toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>`,
    info: `<svg class="lf-toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
};

const DEFAULT_DURATION = {
    success: 4500,
    error: 7000,
    warning: 8000,
    info: 5000,
};

function ensureContainer() {
    let el = document.getElementById('lf-toast-container');
    if (el) return el;

    el = document.createElement('div');
    el.id = 'lf-toast-container';
    el.className = 'lf-toast-container';
    el.setAttribute('aria-live', 'polite');
    el.setAttribute('aria-atomic', 'false');
    document.body.appendChild(el);
    return el;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function showToast(options = {}) {
    const type = options.type || 'info';
    const title = options.title || '';
    const message = options.message || '';
    const html = options.html || '';
    const duration = options.duration ?? DEFAULT_DURATION[type] ?? 5000;

    const container = ensureContainer();
    const toast = document.createElement('div');
    toast.className = `lf-toast lf-toast--${type}`;
    toast.setAttribute('role', 'status');

    const bodyHtml = html
        ? html
        : (message ? `<p class="lf-toast-message">${escapeHtml(message)}</p>` : '');

    toast.innerHTML = `
        <div class="lf-toast-accent"></div>
        <div class="lf-toast-body">
            ${ICONS[type] || ICONS.info}
            <div class="lf-toast-content">
                ${title ? `<p class="lf-toast-title">${escapeHtml(title)}</p>` : ''}
                ${bodyHtml}
            </div>
            <button type="button" class="lf-toast-close" aria-label="Dismiss">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    `;

    const remove = () => {
        toast.classList.add('lf-toast-leave');
        window.setTimeout(() => toast.remove(), 220);
    };

    toast.querySelector('.lf-toast-close')?.addEventListener('click', remove);

    container.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('lf-toast-enter'));

    if (duration > 0) {
        window.setTimeout(remove, duration);
    }

    return { dismiss: remove, el: toast };
}

const toast = {
    show: showToast,
    success(message, opts = {}) {
        return showToast({ ...opts, type: 'success', message: opts.message ?? message, title: opts.title });
    },
    error(message, opts = {}) {
        return showToast({ ...opts, type: 'error', message: opts.message ?? message, title: opts.title });
    },
    warning(message, opts = {}) {
        return showToast({ ...opts, type: 'warning', message: opts.message ?? message, title: opts.title });
    },
    info(message, opts = {}) {
        return showToast({ ...opts, type: 'info', message: opts.message ?? message, title: opts.title });
    },
    /**
     * Show a list of row-level import issues.
     * @param {Array<{row?: number|null, message?: string}>} samples
     */
    importErrors(samples = [], opts = {}) {
        const list = Array.isArray(samples) ? samples.filter(Boolean) : [];
        if (list.length === 0) return null;

        const items = list.slice(0, 25).map((item) => {
            const label = item.row ? `Row ${item.row}` : 'File';
            return `<li><span class="lf-toast-row">${escapeHtml(label)}</span> ${escapeHtml(item.message || 'Invalid data')}</li>`;
        }).join('');

        const more = list.length > 25
            ? `<p class="lf-toast-more">+${list.length - 25} more issues not shown</p>`
            : '';

        return showToast({
            type: 'warning',
            title: opts.title || 'Import error report',
            html: `<ul class="lf-toast-list">${items}</ul>${more}`,
            duration: opts.duration ?? 12000,
        });
    },
};

window.toast = toast;

/**
 * Boot session / validation flashes into toasts (called from layout).
 */
window.bootLeadflowToasts = function bootLeadflowToasts(payload = {}) {
    if (payload.success) {
        toast.success(payload.success);
    }
    if (payload.error) {
        toast.error(payload.error);
    }
    if (Array.isArray(payload.errors) && payload.errors.length > 0) {
        if (payload.errors.length === 1) {
            toast.error(payload.errors[0]);
        } else {
            toast.error(null, {
                title: 'There were some errors with your request',
                html: `<ul class="lf-toast-list">${payload.errors.map((e) => `<li>${escapeHtml(e)}</li>`).join('')}</ul>`,
                duration: 9000,
            });
        }
    }
    if (Array.isArray(payload.import_errors) && payload.import_errors.length > 0) {
        toast.importErrors(payload.import_errors);
    }
};

export default toast;
