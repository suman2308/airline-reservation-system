/**
 * AeroBook – Premium interaction layer
 * Mobile navigation, scroll reveals, and nav state for the landing experience.
 * Vanilla JS, no dependencies. Safe to include on every page.
 */
(function () {
    'use strict';

    /* ─── 1. Landing mobile menu ─── */
    var burger = document.getElementById('landingBurger');
    var panel = document.getElementById('landingMenuPanel');
    var iconMenu = document.getElementById('burgerIconMenu');
    var iconClose = document.getElementById('burgerIconClose');

    function setMenu(open) {
        if (!burger || !panel) return;
        panel.classList.toggle('open', open);
        burger.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (iconMenu) iconMenu.style.display = open ? 'none' : '';
        if (iconClose) iconClose.style.display = open ? '' : 'none';
    }

    if (burger && panel) {
        burger.addEventListener('click', function (e) {
            e.stopPropagation();
            setMenu(!panel.classList.contains('open'));
        });

        // Close when a link inside the panel is chosen
        panel.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () { setMenu(false); });
        });

        // Close on outside click or Escape
        document.addEventListener('click', function (e) {
            if (panel.classList.contains('open') && !panel.contains(e.target) && !burger.contains(e.target)) {
                setMenu(false);
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') setMenu(false);
        });
    }

    /* ─── 2. Landing nav state on scroll ─── */
    var landingNav = document.getElementById('landingNav');
    var ticking = false;

    function updateNavState() {
        ticking = false;
        if (!landingNav) return;
        landingNav.classList.toggle('scrolled', window.scrollY > 24);
    }

    window.addEventListener('scroll', function () {
        if (!ticking) {
            window.requestAnimationFrame(updateNavState);
            ticking = true;
        }
    }, { passive: true });
    updateNavState();

    /* ─── 3. Reveal-on-scroll ─── */
    var revealEls = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window && revealEls.length) {
        var revealObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

        revealEls.forEach(function (el, i) {
            revealObserver.observe(el);
        });
    } else {
        // Fallback: show everything immediately
        revealEls.forEach(function (el) { el.classList.add('revealed'); });
    }

    /* ─── 4. Active section highlight in landing nav ─── */
    var menuLinks = document.querySelectorAll('.landing-menu a[href^="#"], .landing-menu-panel a[href^="#"]');
    var sections = [];
    menuLinks.forEach(function (link) {
        var id = link.getAttribute('href');
        if (id && id.length > 1) {
            var sec = document.querySelector(id);
            if (sec) sections.push({ id: id, el: sec, link: link });
        }
    });

    if ('IntersectionObserver' in window && sections.length) {
        var spy = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    sections.forEach(function (s) {
                        s.link.classList.toggle('active', s.id === '#' + entry.target.id);
                    });
                }
            });
        }, { threshold: 0.15 });

        sections.forEach(function (s) { spy.observe(s.el); });
    }
})();
