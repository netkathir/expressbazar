const sidebar = document.getElementById('sidebar');
const content = document.getElementById('content');
const topbar = document.getElementById('topbar');
const toggleBtn = document.getElementById('toggleBtn');
const mobileBtn = document.getElementById('mobileBtn');
const overlay = document.getElementById('overlay');

if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
        sidebar?.classList.toggle('collapsed');
        content?.classList.toggle('full');
        topbar?.classList.toggle('full');
    });
}

if (mobileBtn) {
    mobileBtn.addEventListener('click', () => {
        sidebar?.classList.add('mobile-show');
        overlay?.classList.add('show');
    });
}

if (overlay) {
    overlay.addEventListener('click', () => {
        sidebar?.classList.remove('mobile-show');
        overlay.classList.remove('show');
    });
}
