// Vendors
import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Bootstrap
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

// Toasts
const toastContainer = document.createElement('div');
toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
document.addEventListener('DOMContentLoaded', () => document.body.appendChild(toastContainer));

const toastBg = {
    info: 'text-bg-info',
    success: 'text-bg-success',
    error: 'text-bg-danger',
};

window.showToast = function (message, level = 'info') {
    const el = document.createElement('div');
    el.className = `toast align-items-center border-0 ${toastBg[level] || toastBg.info}`;
    el.setAttribute('role', 'alert');
    el.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    toastContainer.appendChild(el);
    const toast = new bootstrap.Toast(el, { delay: 8000 });
    el.addEventListener('hidden.bs.toast', () => el.remove());
    toast.show();
};

// Poll for background job notifications (import/analyze start & finish)
function pollNotifications() {
    axios.get('/admin/notifications/poll')
        .then(({ data }) => data.notifications.forEach(n => window.showToast(n.message, n.level)))
        .catch(() => {});
}

document.addEventListener('DOMContentLoaded', () => {
    pollNotifications();
    setInterval(pollNotifications, 5000);
});

// Generic AJAX form submission — used across pages so a form only needs the right class,
// no per-page wiring. ajax-confirm-form asks for confirmation first; ajax-quiet-form doesn't.
document.addEventListener('submit', function (e) {
    const form = e.target;

    if (form.matches('.ajax-confirm-form')) {
        e.preventDefault();

        if (!confirm(form.dataset.confirm)) {
            return;
        }

        axios.post(form.action, new FormData(form))
            .then(({ data }) => window.showToast(data.message, 'success'))
            .catch(() => window.showToast('Failed to start.', 'error'));
    } else if (form.matches('.ajax-quiet-form')) {
        e.preventDefault();

        axios.post(form.action, new FormData(form))
            .then(({ data }) => window.showToast(data.message, 'success'))
            .catch(() => window.showToast('Request failed.', 'error'));
    } else if (form.matches('.ajax-import-form')) {
        e.preventDefault();

        axios.post(form.action, new FormData(form))
            .then(({ data }) => window.showToast(data.message, 'success'))
            .catch(() => window.showToast('Import failed to start.', 'error'))
            .finally(() => form.reset());
    }
});
