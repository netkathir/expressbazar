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
