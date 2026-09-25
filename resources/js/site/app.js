import '../bootstrap';
import { initMemberRegistration } from './auth/member-register';
import { initReviewStars } from './reviews';
import Alpine from 'alpinejs';
import { initGlobalBlockUi } from '@/core/block-ui';
import initTitleTooltips from '@/core/title-tooltips';
import { initPasswordConfirmationValidation } from '@/core/password-confirmation';
import { initSiteKtSelects } from '@/core/ktui-selects';

window.Alpine = Alpine;
Alpine.start();
initGlobalBlockUi();

function initKtComponents() {
    try {
        window.KTComponents?.init?.();
    } catch (error) {
        console.warn('[Site] KTComponents init failed:', error);
    }
}

function initReveals() {
    const items = document.querySelectorAll('[data-reveal]');
    if (!items.length) return;

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        items.forEach((el) => el.classList.add('is-revealed'));
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-revealed');
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.15 });

    items.forEach((el) => observer.observe(el));
}

function initSectionNavigation() {
    const links = [...document.querySelectorAll('[data-site-section-link]')]
        .filter((link) => {
            const url = new URL(link.href, window.location.href);

            return url.pathname === window.location.pathname
                && document.getElementById(link.dataset.siteSectionLink);
        });

    if (!links.length) return;

    const sections = links
        .map((link) => ({
            link,
            section: document.getElementById(link.dataset.siteSectionLink),
        }))
        .sort((a, b) => a.section.offsetTop - b.section.offsetTop);

    const setActiveSection = (activeSectionId) => {
        links.forEach((link) => {
            const isActive = link.dataset.siteSectionLink === activeSectionId;
            link.classList.toggle('site-desktop-nav-link--active', isActive && link.classList.contains('site-desktop-nav-link'));
            link.classList.toggle('site-mobile-nav__link--active', isActive && link.classList.contains('site-mobile-nav__link'));
            link.classList.toggle('is-current', isActive && link.classList.contains('home-mobile-menu__link'));

            if (isActive) {
                link.setAttribute('aria-current', 'location');
            } else {
                link.removeAttribute('aria-current');
            }
        });
    };

    let frame = null;
    const update = () => {
        frame = null;
        const headerHeight = document.querySelector('.site-header')?.offsetHeight || 0;
        const activationLine = window.scrollY + Math.max(headerHeight + 24, window.innerHeight * 0.28);
        let active = sections[0];

        sections.forEach((candidate) => {
            if (candidate.section.offsetTop <= activationLine) {
                active = candidate;
            }
        });

        const reachedPageEnd = Math.ceil(window.scrollY + window.innerHeight)
            >= document.documentElement.scrollHeight - 2;

        if (reachedPageEnd) {
            active = sections[sections.length - 1];
        }

        setActiveSection(active.link.dataset.siteSectionLink);
    };

    const scheduleUpdate = () => {
        if (frame !== null) return;
        frame = window.requestAnimationFrame(update);
    };

    window.addEventListener('scroll', scheduleUpdate, { passive: true });
    window.addEventListener('resize', scheduleUpdate);
    window.addEventListener('hashchange', scheduleUpdate);
    window.addEventListener('load', scheduleUpdate, { once: true });
    update();
}

function domReady(fn) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fn, { once: true });
    } else {
        fn();
    }
}

domReady(() => {
    initMemberRegistration(document);
    initPasswordConfirmationValidation(document);
    initTitleTooltips(document);
    initKtComponents();
    initSiteKtSelects(document);
    initReveals();
    initSectionNavigation();
    initReviewStars();
    document.documentElement.classList.add('site-js-ready');
});
