const sidebar = document.getElementById('sidebar');
const content = document.getElementById('content');
const topbar = document.getElementById('topbar');
const toggleBtn = document.getElementById('toggleBtn');
const mobileBtn = document.getElementById('mobileBtn');
const overlay = document.getElementById('overlay');
const deleteConfirmModalEl = document.getElementById('adminDeleteConfirmModal');
const deleteConfirmMessageEl = document.getElementById('adminDeleteConfirmMessage');
const deleteConfirmButtonEl = document.getElementById('adminDeleteConfirmButton');
const backConfirmModalEl = document.getElementById('adminBackConfirmModal');
const backConfirmMessageEl = document.getElementById('adminBackConfirmMessage');
const backConfirmButtonEl = document.getElementById('adminBackConfirmButton');
const deleteConfirmModal = deleteConfirmModalEl && window.bootstrap
    ? new bootstrap.Modal(deleteConfirmModalEl)
    : null;
const backConfirmModal = backConfirmModalEl && window.bootstrap
    ? new bootstrap.Modal(backConfirmModalEl)
    : null;
let pendingDeleteForm = null;
let pendingBackLink = null;
window.__adminBypassBeforeUnload = false;

const setDesktopSidebarState = (collapsed) => {
    sidebar?.classList.toggle('collapsed', collapsed);
    content?.classList.toggle('full', collapsed);
    topbar?.classList.toggle('full', collapsed);
    toggleBtn?.setAttribute('aria-expanded', String(!collapsed));
};

if (localStorage.getItem('adminSidebar') === 'hidden') {
    localStorage.setItem('adminSidebar', 'expanded');
}

if (localStorage.getItem('adminSidebar') === 'collapsed') {
    setDesktopSidebarState(true);
}

if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
        const isCollapsed = !sidebar?.classList.contains('collapsed');

        setDesktopSidebarState(isCollapsed);
        localStorage.setItem('adminSidebar', isCollapsed ? 'collapsed' : 'expanded');
    });
}

if (mobileBtn) {
    mobileBtn.addEventListener('click', () => {
        const isOpen = sidebar?.classList.toggle('mobile-show') ?? false;

        overlay?.classList.toggle('show', isOpen);
        mobileBtn.setAttribute('aria-expanded', String(isOpen));
    });
}

if (overlay) {
    overlay.addEventListener('click', () => {
        sidebar?.classList.remove('mobile-show');
        overlay.classList.remove('show');
        mobileBtn?.setAttribute('aria-expanded', 'false');
    });
}

<<<<<<< HEAD
window.adminConfirm = (message, options = {}) => new Promise((resolve) => {
    const modalElement = document.getElementById('adminConfirmModal');

    if (!modalElement || !window.bootstrap?.Modal) {
        resolve(window.confirm(message));
        return;
    }

    const title = modalElement.querySelector('#adminConfirmModalTitle');
    const body = modalElement.querySelector('[data-admin-confirm-message]');
    const okButton = modalElement.querySelector('[data-admin-confirm-ok]');
    const cancelButton = modalElement.querySelector('[data-admin-confirm-cancel]');
    const modal = window.bootstrap.Modal.getOrCreateInstance(modalElement);

    let confirmed = false;

    if (title) {
        title.textContent = options.title || 'Confirm action';
    }

    if (body) {
        body.textContent = message || 'Are you sure?';
    }

    if (okButton) {
        okButton.textContent = options.confirmText || 'Confirm';
        okButton.className = `btn ${options.confirmClass || 'btn-danger'}`;
    }

    if (cancelButton) {
        cancelButton.textContent = options.cancelText || 'Cancel';
    }

    const cleanup = () => {
        okButton?.removeEventListener('click', onConfirm);
        modalElement.removeEventListener('hidden.bs.modal', onHidden);
    };

    const onConfirm = () => {
        confirmed = true;
        modal.hide();
    };

    const onHidden = () => {
        cleanup();
        resolve(confirmed);
    };

    okButton?.addEventListener('click', onConfirm, { once: true });
    modalElement.addEventListener('hidden.bs.modal', onHidden, { once: true });
    modal.show();
});

document.addEventListener('submit', (event) => {
    const submittedForm = event.target.closest('form');
    const form = submittedForm?.matches('.js-confirm-delete')
        ? submittedForm
        : submittedForm?.querySelector('input[name="_method"][value="DELETE" i]') ? submittedForm : null;

=======
const isDeleteForm = (form) => {
>>>>>>> b613057478c82536e6c638344512541362616b16
    if (!form || form.dataset.confirmed === 'true') {
        return false;
    }

    if (form.classList.contains('js-confirm-delete') || form.dataset.confirmMessage) {
        return true;
    }

    if (form.getAttribute('onsubmit')?.includes('confirm(')) {
        return true;
    }

    const methodField = form.querySelector('input[name="_method"]');
    return methodField?.value?.toUpperCase() === 'DELETE';
};

const openDeleteConfirm = (form) => {
    if (!deleteConfirmModal || !deleteConfirmMessageEl || !deleteConfirmButtonEl) {
        return false;
    }

    pendingDeleteForm = form;
    deleteConfirmMessageEl.textContent = form.dataset.confirmMessage || 'Delete this item?';
    deleteConfirmModal.show();
    return true;
};

if (deleteConfirmButtonEl) {
    deleteConfirmButtonEl.addEventListener('click', () => {
        if (!pendingDeleteForm) {
            return;
        }

        const form = pendingDeleteForm;
        pendingDeleteForm = null;
        form.dataset.confirmed = 'true';
        deleteConfirmModal?.hide();
        form.submit();
    });
}

if (deleteConfirmModalEl) {
    deleteConfirmModalEl.addEventListener('hidden.bs.modal', () => {
        pendingDeleteForm = null;
    });
}

const openBackConfirm = (link, message) => {
    if (!backConfirmModal || !backConfirmMessageEl || !backConfirmButtonEl) {
        return false;
    }

    pendingBackLink = link;
    backConfirmMessageEl.textContent = message || link.dataset.confirmMessage || 'Are you sure you want to go back without editing?';
    backConfirmModal.show();
    return true;
};

if (backConfirmButtonEl) {
    backConfirmButtonEl.addEventListener('click', () => {
        if (!pendingBackLink) {
            return;
        }

        const link = pendingBackLink;
        pendingBackLink = null;
        window.__adminBypassBeforeUnload = true;
        backConfirmModal?.hide();
        window.location.href = link.href;
    });
}

if (backConfirmModalEl) {
    backConfirmModalEl.addEventListener('hidden.bs.modal', () => {
        pendingBackLink = null;
    });
}

document.addEventListener('submit', (event) => {
    const form = event.target instanceof HTMLFormElement ? event.target : event.target.closest('form');
    if (!isDeleteForm(form)) {
        return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();
<<<<<<< HEAD

    const message = form.dataset.confirmMessage || 'Delete this item?';
    window.adminConfirm(message, {
        title: 'Delete item',
        confirmText: 'Delete',
        confirmClass: 'btn-danger',
    }).then((confirmed) => {
        if (!confirmed) {
            return;
        }

        form.dataset.confirmed = 'true';
        HTMLFormElement.prototype.submit.call(form);
    });
=======

    if (openDeleteConfirm(form)) {
        return;
    }

    if (window.confirm(form.dataset.confirmMessage || 'Delete this item?')) {
        form.dataset.confirmed = 'true';
        form.submit();
    }
>>>>>>> b613057478c82536e6c638344512541362616b16
}, true);

document.querySelectorAll('form[data-dirty-check]').forEach((form) => {
    let isDirty = false;
    let isSubmitting = false;

    const markDirty = () => {
        isDirty = true;
    };

    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);
    form.addEventListener('submit', () => {
        isSubmitting = true;
    });

    document.querySelectorAll('[data-dirty-back]').forEach((link) => {
        link.addEventListener('click', (event) => {
            if (isSubmitting) {
                return;
            }

            event.preventDefault();
<<<<<<< HEAD
            window.adminConfirm('You have unsaved changes. Leave this page?', {
                title: 'Unsaved changes',
                confirmText: 'Leave page',
                confirmClass: 'btn-warning',
            }).then((confirmed) => {
                if (confirmed) {
                    window.location.href = link.href;
                }
            });
=======

            const message = isDirty
                ? 'You have unsaved changes. Are you sure you want to go back without saving?'
                : 'Are you sure you want to go back without editing?';

            if (openBackConfirm(link, message)) {
                return;
            }

            if (!window.confirm(message)) {
                return;
            }

            window.location.href = link.href;
>>>>>>> b613057478c82536e6c638344512541362616b16
        });
    });

    window.addEventListener('beforeunload', (event) => {
        if (!isDirty || isSubmitting || window.__adminBypassBeforeUnload) {
            return;
        }

        event.preventDefault();
        event.returnValue = '';
    });
});

document.querySelectorAll('.alert.alert-success:not(.admin-flash-message), .alert.alert-danger:not(.admin-flash-message)').forEach((alert) => {
    if (alert.querySelector('[data-bs-dismiss="alert"], .btn-close')) {
        return;
    }

    const closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.className = 'btn-close';
    closeButton.setAttribute('aria-label', 'Close');
    closeButton.addEventListener('click', () => alert.remove());

    alert.classList.add('alert-dismissible', 'fade', 'show');
    alert.append(closeButton);
});
