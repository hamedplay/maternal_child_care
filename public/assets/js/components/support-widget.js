document.addEventListener('DOMContentLoaded', function () {
    const widget = document.getElementById('supportWidget');
    const fab = document.getElementById('supportFab');
    const closeBtn = document.getElementById('supportClose');
    const panel = document.getElementById('supportPanel');

    if (!widget || !fab || !panel) return;

    function openPanel() {
        widget.classList.add('is-open');
        fab.setAttribute('aria-expanded', 'true');
    }

    function closePanel() {
        widget.classList.remove('is-open');
        fab.setAttribute('aria-expanded', 'false');
    }

    fab.addEventListener('click', function (e) {
        e.stopPropagation();
        if (widget.classList.contains('is-open')) {
            closePanel();
        } else {
            openPanel();
        }
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', closePanel);
    }

    // کلیک بیرون از ویجت → بستن پنل
    document.addEventListener('click', function (e) {
        if (!widget.contains(e.target)) {
            closePanel();
        }
    });

    // کلید Escape → بستن پنل
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closePanel();
        }
    });
});
