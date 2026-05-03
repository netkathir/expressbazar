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

document.addEventListener('submit', (event) => {
    const form = event.target.closest('.js-confirm-delete');
    if (!form || form.dataset.confirmed === 'true') {
        return;
    }

    const message = form.dataset.confirmMessage || 'Delete this item?';
    if (!window.confirm(message)) {
        event.preventDefault();
        event.stopImmediatePropagation();
        return;
    }

    form.dataset.confirmed = 'true';
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

            if (!window.confirm('You have unsaved changes. Leave this page?')) {
                event.preventDefault();
            }
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
