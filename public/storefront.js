document.addEventListener('click', (event) => {
    const chip = event.target.closest('.add-chip');
    if (!chip) {
        return;
    }

    chip.textContent = 'ADDED';
    chip.disabled = true;
    chip.style.color = '#1fa85b';
    chip.style.borderColor = 'rgba(31, 168, 91, 0.35)';
});
