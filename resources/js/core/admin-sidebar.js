const COLLAPSED_CLASS = 'kt-sidebar-collapse';
const DESKTOP_QUERY = '(min-width: 64rem)';
const TOOLTIP_TRIGGER_SELECTOR = [
    '#sidebar_menu > .kt-menu-item > .kt-menu-link[data-sidebar-tooltip]',
    '#sidebar_menu > .kt-menu-item > .kt-menu-label > .kt-menu-link[data-sidebar-tooltip]',
].join(',');
const ACCORDION_TRIGGER_SELECTOR =
    '#sidebar_menu > .kt-menu-item[data-kt-menu-item-toggle="accordion"] > .kt-menu-link';

export default function initAdminSidebar() {
    const sidebar = document.querySelector('#sidebar');
    const sidebarMenu = document.querySelector('#sidebar_menu');
    const sidebarToggle = document.querySelector('#sidebar_toggle');

    if (!sidebar || !sidebarMenu || !sidebarToggle || sidebar.dataset.enhanced === 'true') {
        return;
    }

    sidebar.dataset.enhanced = 'true';

    const desktopMedia = window.matchMedia(DESKTOP_QUERY);
    const tooltip = document.createElement('div');
    tooltip.id = 'admin-sidebar-tooltip';
    tooltip.className = 'admin-sidebar-tooltip kt-tooltip kt-tooltip-light';
    tooltip.setAttribute('role', 'tooltip');
    tooltip.setAttribute('aria-hidden', 'true');
    document.body.appendChild(tooltip);

    let activeTrigger = null;
    let showTimer = null;
    let hideTimer = null;

    const isCollapsed = () => desktopMedia.matches && document.body.classList.contains(COLLAPSED_CLASS);

    const clearTimers = () => {
        window.clearTimeout(showTimer);
        window.clearTimeout(hideTimer);
        showTimer = null;
        hideTimer = null;
    };

    const hideTooltip = () => {
        clearTimers();
        activeTrigger?.removeAttribute('aria-describedby');
        activeTrigger = null;
        tooltip.classList.remove('is-visible');
        tooltip.setAttribute('aria-hidden', 'true');

        hideTimer = window.setTimeout(() => {
            tooltip.classList.remove('show');
        }, 150);
    };

    const positionTooltip = (trigger) => {
        const triggerRect = trigger.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();
        const isRtl = document.documentElement.dir === 'rtl';
        const horizontalGap = 12;
        const viewportGap = 8;
        const preferredLeft = isRtl
            ? triggerRect.left - tooltipRect.width - horizontalGap
            : triggerRect.right + horizontalGap;
        const maxLeft = window.innerWidth - tooltipRect.width - viewportGap;
        const top = triggerRect.top + (triggerRect.height - tooltipRect.height) / 2;

        tooltip.style.left = `${Math.max(viewportGap, Math.min(preferredLeft, maxLeft))}px`;
        tooltip.style.top = `${Math.max(viewportGap, Math.min(top, window.innerHeight - tooltipRect.height - viewportGap))}px`;
        tooltip.dataset.side = isRtl ? 'left' : 'right';
    };

    const showTooltip = (trigger, delay = 90) => {
        if (!isCollapsed()) {
            hideTooltip();
            return;
        }

        clearTimers();
        showTimer = window.setTimeout(() => {
            const title = trigger.dataset.sidebarTooltip?.trim();
            if (!title || !isCollapsed()) return;

            activeTrigger?.removeAttribute('aria-describedby');
            activeTrigger = trigger;
            activeTrigger.setAttribute('aria-describedby', tooltip.id);
            tooltip.textContent = title;
            tooltip.classList.add('show');
            tooltip.setAttribute('aria-hidden', 'false');
            positionTooltip(trigger);

            window.requestAnimationFrame(() => tooltip.classList.add('is-visible'));
        }, delay);
    };

    const revealAccordion = (item, trigger) => {
        const menu = window.KTMenu?.getInstance?.(sidebarMenu);

        if (menu?.show) {
            menu.show(item);
        } else {
            item.classList.add('show');
        }

        trigger.setAttribute('aria-expanded', 'true');
        trigger.focus({ preventScroll: true });
    };

    sidebar.addEventListener('pointerover', (event) => {
        const trigger = event.target instanceof Element
            ? event.target.closest(TOOLTIP_TRIGGER_SELECTOR)
            : null;

        if (!trigger || trigger.contains(event.relatedTarget)) return;
        showTooltip(trigger);
    });

    sidebar.addEventListener('pointerout', (event) => {
        const trigger = event.target instanceof Element
            ? event.target.closest(TOOLTIP_TRIGGER_SELECTOR)
            : null;

        if (!trigger || trigger.contains(event.relatedTarget)) return;
        hideTooltip();
    });

    sidebar.addEventListener('focusin', (event) => {
        const trigger = event.target instanceof Element
            ? event.target.closest(TOOLTIP_TRIGGER_SELECTOR)
            : null;

        if (trigger) showTooltip(trigger, 0);
    });

    sidebar.addEventListener('focusout', (event) => {
        const trigger = event.target instanceof Element
            ? event.target.closest(TOOLTIP_TRIGGER_SELECTOR)
            : null;

        if (trigger && !trigger.contains(event.relatedTarget)) hideTooltip();
    });

    sidebar.addEventListener('click', (event) => {
        const trigger = event.target instanceof Element
            ? event.target.closest(ACCORDION_TRIGGER_SELECTOR)
            : null;

        if (!trigger || !isCollapsed()) return;

        const item = trigger.closest('.kt-menu-item[data-kt-menu-item-toggle="accordion"]');
        if (!item) return;

        event.preventDefault();
        event.stopPropagation();
        hideTooltip();

        sidebarToggle.click();

        if (document.body.classList.contains(COLLAPSED_CLASS)) {
            document.body.classList.remove(COLLAPSED_CLASS);
            sidebarToggle.classList.remove('active');
        }

        window.setTimeout(() => revealAccordion(item, trigger), 320);
    }, true);

    sidebarToggle.addEventListener('click', hideTooltip);
    sidebar.addEventListener('scroll', hideTooltip, true);
    window.addEventListener('resize', hideTooltip);

    const bodyObserver = new MutationObserver(() => {
        if (!isCollapsed()) hideTooltip();
    });
    bodyObserver.observe(document.body, { attributes: true, attributeFilter: ['class'] });

    const accordionObserver = new MutationObserver((records) => {
        records.forEach(({ target }) => {
            if (!(target instanceof Element) || !target.matches('[data-kt-menu-item-toggle="accordion"]')) {
                return;
            }

            const trigger = target.querySelector(':scope > .kt-menu-link');
            trigger?.setAttribute('aria-expanded', target.classList.contains('show') ? 'true' : 'false');
        });
    });
    accordionObserver.observe(sidebarMenu, {
        attributes: true,
        subtree: true,
        attributeFilter: ['class'],
    });
}
