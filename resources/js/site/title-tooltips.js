import initTitleTooltips from '@/core/title-tooltips';

function domReady(callback) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback, { once: true });
        return;
    }

    callback();
}

domReady(() => initTitleTooltips(document));
