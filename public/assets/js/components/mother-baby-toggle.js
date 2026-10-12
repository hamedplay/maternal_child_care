document.addEventListener('DOMContentLoaded', function () {
    const section = document.getElementById('mother-baby-section');
    if (!section) return;

    const switchButtons = section.querySelectorAll('.mode-switch-btn');
    const panels = section.querySelectorAll('.mode-panel');

    switchButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const targetMode = btn.getAttribute('data-mode');

            switchButtons.forEach(function (b) {
                b.classList.remove('active');
                b.setAttribute('aria-selected', 'false');
            });
            btn.classList.add('active');
            btn.setAttribute('aria-selected', 'true');

            panels.forEach(function (panel) {
                panel.classList.toggle('active', panel.getAttribute('data-panel') === targetMode);
            });
        });
    });
});
