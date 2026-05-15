import '../bootstrap';
import './auth/member-register';

function initKtComponents() {
    try {
        window.KTComponents?.init?.();
    } catch (error) {
        console.warn('[Site] KTComponents init failed:', error);
    }
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
    document.documentElement.classList.add('site-js-ready');
});
