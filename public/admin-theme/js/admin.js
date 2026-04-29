const sidebar = document.getElementById('sidebar');
const content = document.getElementById('content');
const topbar = document.getElementById('topbar');
const toggleBtn = document.getElementById('toggleBtn');
const mobileBtn = document.getElementById('mobileBtn');
const overlay = document.getElementById('overlay');

if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
        const isHidden = sidebar?.classList.toggle('sidebar-hidden') ?? false;

        content?.classList.toggle('sidebar-hidden', isHidden);
        topbar?.classList.toggle('sidebar-hidden', isHidden);
        toggleBtn.setAttribute('aria-expanded', String(!isHidden));
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
