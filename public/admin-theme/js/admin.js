const sidebar = document.getElementById('sidebar');
const content = document.getElementById('content');
const topbar = document.getElementById('topbar');
const toggleBtn = document.getElementById('toggleBtn');
const mobileBtn = document.getElementById('mobileBtn');
const overlay = document.getElementById('overlay');

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

    if (!form || form.dataset.confirmed === 'true') {
        return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();

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
            if (!isDirty || isSubmitting) {
                return;
            }

            event.preventDefault();
            window.adminConfirm('You have unsaved changes. Leave this page?', {
                title: 'Unsaved changes',
                confirmText: 'Leave page',
                confirmClass: 'btn-warning',
            }).then((confirmed) => {
                if (confirmed) {
                    window.location.href = link.href;
                }
            });
        });
    });

    window.addEventListener('beforeunload', (event) => {
        if (!isDirty || isSubmitting) {
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
