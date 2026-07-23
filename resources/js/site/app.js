import '../bootstrap';
import './auth/member-register';

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
    initKtComponents();
    initReveals();
    document.documentElement.classList.add('site-js-ready');
});
