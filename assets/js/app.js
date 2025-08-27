(function(){
    const navToggle = document.querySelector('.nav-toggle');
    const primaryNav = document.querySelector('.primary-nav');
    if (navToggle && primaryNav) {
        navToggle.addEventListener('click', () => {
            const expanded = navToggle.getAttribute('aria-expanded') === 'true';
            navToggle.setAttribute('aria-expanded', String(!expanded));
            primaryNav.classList.toggle('is-open');
        });
    }

    const langToggle = document.querySelector('.lang-current');
    const langList = document.querySelector('.lang-switcher .lang-list');
    if (langToggle && langList) {
        langToggle.addEventListener('click', () => {
            const expanded = langToggle.getAttribute('aria-expanded') === 'true';
            langToggle.setAttribute('aria-expanded', String(!expanded));
            langList.classList.toggle('is-open');
            langList.toggleAttribute('hidden');
        });
    }
})();
