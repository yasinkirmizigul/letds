const scriptPromises = new Map();

function normalizedUrl(src) {
    return new URL(src, document.baseURI).href;
}

function findScript(src) {
    const url = normalizedUrl(src);

    return Array.from(document.scripts).find((script) => script.src === url) || null;
}

export function loadScriptOnce(src, { errorMessage = 'Script yüklenemedi' } = {}) {
    if (!src) return Promise.reject(new Error('Script kaynağı eksik.'));

    const url = normalizedUrl(src);

    if (scriptPromises.has(url)) {
        return scriptPromises.get(url);
    }

    const existing = findScript(src);
    if (existing?.dataset.appScriptLoaded === 'true') {
        return Promise.resolve(existing);
    }

    const promise = new Promise((resolve, reject) => {
        const script = existing || document.createElement('script');

        script.addEventListener('load', () => {
            script.dataset.appScriptLoaded = 'true';
            resolve(script);
        }, { once: true });

        script.addEventListener('error', () => {
            scriptPromises.delete(url);
            reject(new Error(`${errorMessage}: ${src}`));
        }, { once: true });

        if (!existing) {
            script.src = src;
            script.async = true;
            script.dataset.appScriptSrc = url;
            document.head.appendChild(script);
        }
    });

    scriptPromises.set(url, promise);

    return promise;
}
