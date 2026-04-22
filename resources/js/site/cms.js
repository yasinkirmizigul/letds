function initCountups() {
    const items = document.querySelectorAll('[data-countup-value]');
    if (!items.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;

            const el = entry.target;
            const target = Number(el.getAttribute('data-countup-value') || '0');
            const duration = 1200;
            const start = performance.now();

            const tick = (now) => {
                const progress = Math.min(1, (now - start) / duration);
                const value = Math.round(target * progress);
                el.textContent = value.toLocaleString('tr-TR');

                if (progress < 1) {
                    requestAnimationFrame(tick);
                }
            };

            requestAnimationFrame(tick);
            observer.unobserve(el);
        });
    }, { threshold: 0.35 });

    items.forEach((item) => observer.observe(item));
}

function initHeroSlider() {
    const root = document.querySelector('[data-home-slider]');
    if (!root) return;

    const slides = [...root.querySelectorAll('[data-home-slide]')];
    const indicators = [...root.querySelectorAll('[data-home-slide-indicator]')];
    if (slides.length <= 1) return;

    let index = 0;
    let timer = null;

    const render = () => {
        slides.forEach((slide, slideIndex) => {
            slide.classList.toggle('hidden', slideIndex !== index);
        });

        indicators.forEach((indicator, indicatorIndex) => {
            indicator.classList.toggle('bg-white', indicatorIndex === index);
            indicator.classList.toggle('bg-white/35', indicatorIndex !== index);
        });
    };

    const go = (nextIndex) => {
        index = (nextIndex + slides.length) % slides.length;
        render();
    };

    const start = () => {
        window.clearInterval(timer);
        timer = window.setInterval(() => go(index + 1), 5500);
    };

    root.querySelector('[data-home-slider-prev]')?.addEventListener('click', () => {
        go(index - 1);
        start();
    });

    root.querySelector('[data-home-slider-next]')?.addEventListener('click', () => {
        go(index + 1);
        start();
    });

    indicators.forEach((indicator, indicatorIndex) => {
        indicator.addEventListener('click', () => {
            go(indicatorIndex);
            start();
        });
    });

    render();
    start();
}

document.addEventListener('DOMContentLoaded', () => {
    initHeroSlider();
    initCountups();
});
