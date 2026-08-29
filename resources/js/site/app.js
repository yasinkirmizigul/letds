import '../bootstrap';
import './auth/member-register';
import { initReviewStars } from './reviews';
import Alpine from 'alpinejs';
import { initGlobalBlockUi } from '@/core/block-ui';
import initTitleTooltips from '@/core/title-tooltips';
import { initPasswordConfirmationValidation } from '@/core/password-confirmation';

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

function domReady(fn) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fn, { once: true });
    } else {
        fn();
    }
}

domReady(() => {
    initPasswordConfirmationValidation(document);
    initTitleTooltips(document);
    initKtComponents();
    initReveals();
    initReviewStars();
    document.documentElement.classList.add('site-js-ready');
});
