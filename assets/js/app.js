/**
 * Header interactions: menu, search, dark mode, smooth scroll.
 */

document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const header = document.querySelector('[data-header]');
    const nav = document.querySelector('.c-header__nav');
    const menuToggle = document.querySelector('.c-header__menu-toggle');
    const overlay = document.querySelector('.c-header__overlay');
    const searchToggle = document.querySelector('.c-header__search-toggle');
    const searchContainer = document.getElementById('site-search');
    const searchInput = searchContainer ? searchContainer.querySelector('input[type="search"]') : null;
    const darkToggle = document.querySelector('.c-header__dark-toggle');

    const focusableSelectors = 'a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])';
    let lastFocused = null;
    let trapHandler = null;

    const trapFocus = (container) => {
        const focusables = container.querySelectorAll(focusableSelectors);
        if (!focusables.length) { return; }
        const first = focusables[0];
        const last = focusables[focusables.length - 1];
        trapHandler = (e) => {
            if (e.key === 'Tab') {
                if (e.shiftKey && document.activeElement === first) {
                    e.preventDefault();
                    last.focus();
                } else if (!e.shiftKey && document.activeElement === last) {
                    e.preventDefault();
                    first.focus();
                }
            } else if (e.key === 'Escape') {
                closeNav();
                closeSearch();
                menuToggle.focus();
            }
        };
        document.addEventListener('keydown', trapHandler);
        first.focus();
    };

    const openNav = () => {
        nav.classList.add('is-open');
        nav.hidden = false;
        overlay.classList.add('is-active');
        body.classList.add('u-no-scroll');
        menuToggle.setAttribute('aria-expanded', 'true');
        lastFocused = document.activeElement;
        trapFocus(nav);
    };

    const closeNav = () => {
        nav.classList.remove('is-open');
        nav.hidden = true;
        overlay.classList.remove('is-active');
        body.classList.remove('u-no-scroll');
        menuToggle.setAttribute('aria-expanded', 'false');
        document.removeEventListener('keydown', trapHandler);
        if (lastFocused) { lastFocused.focus(); }
    };

    menuToggle && menuToggle.addEventListener('click', () => {
        const expanded = menuToggle.getAttribute('aria-expanded') === 'true';
        expanded ? closeNav() : openNav();
    });

    overlay && overlay.addEventListener('click', () => {
        closeNav();
        closeSearch();
    });

    // Submenu toggles and keyboard navigation
    nav && nav.querySelectorAll('.menu-item-has-children').forEach((item) => {
        const toggle = document.createElement('button');
        toggle.className = 'c-menu__submenu-toggle';
        toggle.setAttribute('aria-expanded', 'false');
        toggle.innerHTML = '<span class="screen-reader-text">Submenú</span>';
        const link = item.querySelector('a');
        link.after(toggle);
        const sublist = item.querySelector('.sub-menu');
        sublist.classList.add('c-menu__sublist');
        item.classList.add('c-menu__item', 'c-menu__item--has-submenu');
        toggle.addEventListener('click', () => {
            const expanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', String(!expanded));
            item.classList.toggle('c-menu__item--open');
        });
    });

    nav && nav.querySelectorAll(':scope > li').forEach(li => li.classList.add('c-menu__item'));
    nav && nav.querySelectorAll('a').forEach(a => a.classList.add('c-menu__link'));

    nav && nav.addEventListener('keydown', (e) => {
        const link = e.target.closest('.c-menu__link');
        if (!link) { return; }
        const item = link.parentElement;
        if (e.key === 'ArrowDown') {
            const sub = item.querySelector('.c-menu__sublist');
            if (sub) {
                item.classList.add('c-menu__item--open');
                const first = sub.querySelector('.c-menu__link');
                first && first.focus();
                e.preventDefault();
            }
        } else if (e.key === 'ArrowUp') {
            const parent = item.parentElement.closest('li');
            if (parent) {
                parent.classList.remove('c-menu__item--open');
                const parentLink = parent.querySelector('.c-menu__link');
                parentLink && parentLink.focus();
                e.preventDefault();
            }
        } else if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
            const siblings = Array.from(item.parentElement.children);
            const index = siblings.indexOf(item);
            const dir = e.key === 'ArrowRight' ? 1 : -1;
            const next = siblings[(index + dir + siblings.length) % siblings.length];
            const nextLink = next.querySelector('.c-menu__link');
            nextLink && nextLink.focus();
            e.preventDefault();
        }
    });

    // Search
    const openSearch = () => {
        searchContainer.classList.add('is-open');
        searchContainer.hidden = false;
        overlay.classList.add('is-active');
        searchToggle.setAttribute('aria-expanded', 'true');
        lastFocused = document.activeElement;
        trapFocus(searchContainer);
        searchInput && searchInput.focus();
    };

    const closeSearch = () => {
        searchContainer.classList.remove('is-open');
        searchContainer.hidden = true;
        overlay.classList.remove('is-active');
        searchToggle.setAttribute('aria-expanded', 'false');
        document.removeEventListener('keydown', trapHandler);
        if (lastFocused) { lastFocused.focus(); }
    };

    searchToggle && searchToggle.addEventListener('click', () => {
        const expanded = searchToggle.getAttribute('aria-expanded') === 'true';
        expanded ? closeSearch() : openSearch();
    });

    // Dark mode
    const storedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const currentTheme = storedTheme ? storedTheme : (prefersDark ? 'dark' : 'light');
    document.documentElement.dataset.theme = currentTheme;

    const updateThemeToggle = () => {
        const theme = document.documentElement.dataset.theme;
        darkToggle.setAttribute('aria-label', theme === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
    };
    updateThemeToggle();

    darkToggle && darkToggle.addEventListener('click', () => {
        const theme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
        document.documentElement.dataset.theme = theme;
        localStorage.setItem('theme', theme);
        updateThemeToggle();
    });

    // Sticky shadow on scroll
    const handleScroll = () => {
        if (window.scrollY > 0) {
            header.classList.add('c-header--scrolled');
        } else {
            header.classList.remove('c-header--scrolled');
        }
    };
    handleScroll();
    window.addEventListener('scroll', handleScroll);

    // Smooth scroll for anchors with href="#id"
    document.addEventListener('click', (e) => {
        const a = e.target.closest('a[href^="#"]');
        if (!a) return;
        const id = a.getAttribute('href').slice(1);
        if (!id) return;
        const target = document.getElementById(id);
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            closeNav();
        }
    });
});

// Services tabs (section_3)
document.addEventListener('click', (e) => {
  const btn = e.target.closest('.services__tab');
  if (!btn) return;

  const wrap = btn.closest('.services__wrap');
  if (!wrap) return;

  // Desactivar pestañas activas
  wrap.querySelectorAll('.services__tab.is-active').forEach(el => {
    el.classList.remove('is-active');
    el.setAttribute('aria-selected', 'false');
  });
  // Activar pestaña
  btn.classList.add('is-active');
  btn.setAttribute('aria-selected', 'true');

  // Ocultar paneles activos
  wrap.querySelectorAll('.services__panel.is-active').forEach(panel => {
    panel.classList.remove('is-active');
    panel.hidden = true;
  });

  const targetId = btn.getAttribute('data-tab-target');
  const panel = wrap.querySelector('#' + targetId);
  if (panel) {
    panel.hidden = false;
    panel.classList.add('is-active');
  }
});
