(function(){
    const navToggle = document.querySelector('.nav-toggle');
    const primaryNav = document.querySelector('.primary-nav');
    if (navToggle && primaryNav) {
        const closeMenu = () => {
            primaryNav.classList.remove('is-open');
            navToggle.classList.remove('is-active');
            navToggle.setAttribute('aria-expanded', 'false');
        };

        navToggle.addEventListener('click', () => {
            const expanded = navToggle.getAttribute('aria-expanded') === 'true';
            navToggle.setAttribute('aria-expanded', String(!expanded));
            navToggle.classList.toggle('is-active');
            primaryNav.classList.toggle('is-open');
        });

        document.addEventListener('click', (e) => {
            if (!primaryNav.contains(e.target) && !navToggle.contains(e.target)) {
                closeMenu();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeMenu();
                navToggle.focus();
            }
        });
    }

    const langSelect = document.querySelector('.language-select');
    if (langSelect) {
        langSelect.addEventListener('change', (e) => {
            const url = e.target.value;
            if (url) {
                window.location.href = url;
            }
        });
    }
})();
