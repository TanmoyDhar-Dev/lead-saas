import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

const LeadflowSwal = Swal.mixin({
    customClass: {
        popup: 'lf-swal-popup',
        title: 'lf-swal-title',
        htmlContainer: 'lf-swal-text',
        confirmButton: 'lf-swal-confirm',
        cancelButton: 'lf-swal-cancel',
        denyButton: 'lf-swal-deny',
        actions: 'lf-swal-actions',
        icon: 'lf-swal-icon',
    },
    buttonsStyling: false,
    reverseButtons: true,
    focusCancel: true,
});

window.Swal = LeadflowSwal;

/**
 * Confirm a destructive action. Returns true if confirmed.
 */
window.confirmDelete = async function confirmDelete(options = {}) {
    const result = await LeadflowSwal.fire({
        title: options.title || 'Delete permanently?',
        text: options.text || 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: options.confirmText || 'Yes, delete',
        cancelButtonText: options.cancelText || 'Cancel',
    });

    return result.isConfirmed;
};

/**
 * Confirm bulk delete with selected count.
 */
window.confirmBulkDelete = async function confirmBulkDelete(count, entityLabel = 'item') {
    const label = count === 1 ? entityLabel : `${entityLabel}s`;

    return window.confirmDelete({
        title: `Delete ${count} ${label}?`,
        text: 'Selected records will be permanently removed. This cannot be undone.',
        confirmText: `Delete ${count}`,
    });
};

/**
 * Submit a form after SweetAlert confirmation.
 * Form: data-swal-confirm="Delete this lead?" data-swal-title="Are you sure?"
 */
document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (!form.hasAttribute('data-swal-confirm')) return;
    if (form.dataset.swalConfirmed === '1') {
        delete form.dataset.swalConfirmed;
        return;
    }

    event.preventDefault();
    event.stopPropagation();

    const confirmed = await window.confirmDelete({
        title: form.dataset.swalTitle || 'Are you sure?',
        text: form.dataset.swalConfirm || 'This action cannot be undone.',
        confirmText: form.dataset.swalConfirmText || 'Yes, delete',
    });

    if (!confirmed) return;

    form.dataset.swalConfirmed = '1';
    form.submit();
}, true);

export default LeadflowSwal;
