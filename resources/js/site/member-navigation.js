export function initMemberNavigation() {
    const nav = document.querySelector('.site-member-nav');
    if (!nav) return;

    const links = [...nav.querySelectorAll('a[href]')];
    const currentPath = window.location.pathname.replace(/\/+$/, '');
    const samePage = (link) => new URL(link.href, window.location.href).pathname.replace(/\/+$/, '') === currentPath;
    const baseLink = links.find((link) => samePage(link) && !new URL(link.href, window.location.href).hash);
    const sections = links
        .filter((link) => samePage(link))
        .map((link) => ({ link, id: new URL(link.href, window.location.href).hash.slice(1) }))
        .filter(({ id }) => id && document.getElementById(id))
        .map(({ link, id }) => ({ link, section: document.getElementById(id) }));

    if (!baseLink || !sections.length) return;

    const setActive = (activeLink) => {
        links.forEach((link) => {
            const active = link === activeLink;
            link.classList.toggle('is-active', active);
            if (active) link.setAttribute('aria-current', link === baseLink ? 'page' : 'location');
            else link.removeAttribute('aria-current');
        });
    };

    let frame = null;
    const update = () => {
        frame = null;
        const headerHeight = document.querySelector('.site-header')?.getBoundingClientRect().height || 0;
        const activationLine = window.scrollY + Math.max(headerHeight + 24, window.innerHeight * .28);
        let active = baseLink;

        sections.forEach(({ link, section }) => {
            if (window.scrollY + section.getBoundingClientRect().top <= activationLine) active = link;
        });

        const matchingHash = sections.find(({ section }) => `#${section.id}` === window.location.hash);
        const selectedIndex = sections.findIndex(({ link }) => link === active);
        const hashRect = matchingHash?.section.getBoundingClientRect();

        if (matchingHash && sections.indexOf(matchingHash) >= selectedIndex
            && hashRect.top < window.innerHeight && hashRect.bottom > headerHeight) {
            active = matchingHash.link;
        } else if (active === baseLink
            && Math.ceil(window.scrollY + window.innerHeight) >= document.documentElement.scrollHeight - 2) {
            active = sections.find(({ section }) => {
                const rect = section.getBoundingClientRect();
                return rect.top < window.innerHeight && rect.bottom > headerHeight;
            })?.link || baseLink;
        }

        setActive(active);
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
