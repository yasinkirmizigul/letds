const TITLE_SELECTOR = '[title]:not([title=""])';
const TRIGGER_SELECTOR = '[data-app-tooltip]:not([data-app-tooltip=""])';
const SPECIALIZED_TOOLTIP_SELECTOR = [
    '[data-kt-tooltip]',
    '[data-sidebar-tooltip]',
    '[data-history-tooltip]',
    '[data-form-section-tooltip]',
].join(', ');

const SHOW_DELAY = 150;
const VIEWPORT_MARGIN = 8;
const TOOLTIP_GAP = 9;

function asElement(target) {
    if (target instanceof Element) return target;
    return target?.parentElement instanceof Element ? target.parentElement : null;
}

function ensureAccessibleName(element, title) {
    const isInteractive = element.matches('button, a[href], input, select, textarea, [role="button"], [tabindex]');
    const hasAccessibleName = element.hasAttribute('aria-label')
        || element.hasAttribute('aria-labelledby')
        || element.textContent.trim() !== '';

    if (isInteractive && !hasAccessibleName) {
        element.setAttribute('aria-label', title);
    }
}

function upgradeTitle(element) {
    if (!(element instanceof Element)) return;

    const title = (element.getAttribute('title') ?? '').trim();
    if (!title) return;

    if (!element.matches(SPECIALIZED_TOOLTIP_SELECTOR)) {
        element.dataset.appTooltip = title;
        ensureAccessibleName(element, title);
    }

    // Native title bubbles are removed so only the shared themed tooltip is shown.
    element.removeAttribute('title');
}

function upgradeTitlesWithin(root) {
    if (root instanceof Element && root.matches(TITLE_SELECTOR)) {
        upgradeTitle(root);
    }

    root.querySelectorAll?.(TITLE_SELECTOR).forEach(upgradeTitle);
}

function describedByIds(element) {
    return (element.getAttribute('aria-describedby') ?? '')
        .split(/\s+/)
        .filter(Boolean);
}

export default function initTitleTooltips(root = document) {
    const doc = root instanceof Document ? root : root.ownerDocument;
    if (!doc?.body || doc.documentElement.dataset.appTitleTooltipsReady === 'true') {
        return () => {};
    }

    doc.documentElement.dataset.appTitleTooltipsReady = 'true';
    upgradeTitlesWithin(root);

    const tooltip = doc.createElement('div');
    tooltip.id = 'app-title-tooltip';
    tooltip.className = 'app-title-tooltip';
    tooltip.setAttribute('role', 'tooltip');
    tooltip.setAttribute('aria-hidden', 'true');
    doc.body.appendChild(tooltip);

    let activeTrigger = null;
    let showTimer = null;

    const clearShowTimer = () => {
        if (showTimer !== null) {
            window.clearTimeout(showTimer);
            showTimer = null;
        }
    };

    const setDescribedBy = (element, includeTooltip) => {
        const ids = describedByIds(element).filter((id) => id !== tooltip.id);
        if (includeTooltip) ids.push(tooltip.id);

        if (ids.length) {
            element.setAttribute('aria-describedby', ids.join(' '));
        } else {
            element.removeAttribute('aria-describedby');
        }
    };

    const hide = () => {
        clearShowTimer();

        if (activeTrigger) {
            setDescribedBy(activeTrigger, false);
        }

        activeTrigger = null;
        tooltip.classList.remove('is-visible');
        tooltip.setAttribute('aria-hidden', 'true');
    };

    const position = () => {
        if (!activeTrigger?.isConnected) {
            hide();
            return;
        }

        const triggerRect = activeTrigger.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();
        const preferredPlacement = activeTrigger.dataset.tooltipPlacement === 'bottom' ? 'bottom' : 'top';
        const roomAbove = triggerRect.top - VIEWPORT_MARGIN;
        const roomBelow = window.innerHeight - triggerRect.bottom - VIEWPORT_MARGIN;
        let placement = preferredPlacement;

        if (placement === 'top' && roomAbove < tooltipRect.height + TOOLTIP_GAP && roomBelow > roomAbove) {
            placement = 'bottom';
        } else if (placement === 'bottom' && roomBelow < tooltipRect.height + TOOLTIP_GAP && roomAbove > roomBelow) {
            placement = 'top';
        }

        const idealLeft = triggerRect.left + (triggerRect.width / 2) - (tooltipRect.width / 2);
        const maxLeft = Math.max(VIEWPORT_MARGIN, window.innerWidth - tooltipRect.width - VIEWPORT_MARGIN);
        const left = Math.min(Math.max(idealLeft, VIEWPORT_MARGIN), maxLeft);
        const top = placement === 'bottom'
            ? triggerRect.bottom + TOOLTIP_GAP
            : triggerRect.top - tooltipRect.height - TOOLTIP_GAP;
        const arrowX = Math.min(
            Math.max(triggerRect.left + (triggerRect.width / 2) - left, 12),
            Math.max(12, tooltipRect.width - 12),
        );

        tooltip.dataset.placement = placement;
        tooltip.style.left = `${Math.round(left)}px`;
        tooltip.style.top = `${Math.round(Math.max(VIEWPORT_MARGIN, top))}px`;
        tooltip.style.setProperty('--app-tooltip-arrow-x', `${Math.round(arrowX)}px`);
    };

    const reveal = (trigger) => {
        const content = (trigger.dataset.appTooltip ?? '').trim();
        if (!content) return;

        if (activeTrigger && activeTrigger !== trigger) {
            setDescribedBy(activeTrigger, false);
        }

        activeTrigger = trigger;
        tooltip.textContent = content;
        tooltip.setAttribute('aria-hidden', 'false');
        tooltip.classList.add('is-visible');
        setDescribedBy(trigger, true);
        position();
    };

    const scheduleReveal = (trigger, delay = SHOW_DELAY) => {
        clearShowTimer();
        showTimer = window.setTimeout(() => reveal(trigger), delay);
    };

    const triggerFrom = (target) => asElement(target)?.closest(TRIGGER_SELECTOR) ?? null;
    const remainsInside = (trigger, target) => target instanceof Node && trigger.contains(target);

    const onMouseOver = (event) => {
        const trigger = triggerFrom(event.target);
        if (!trigger || remainsInside(trigger, event.relatedTarget)) return;
        scheduleReveal(trigger);
    };

    const onMouseOut = (event) => {
        const trigger = triggerFrom(event.target);
        if (!trigger || remainsInside(trigger, event.relatedTarget)) return;
        if (trigger === activeTrigger || showTimer !== null) hide();
    };

    const onFocusIn = (event) => {
        const trigger = triggerFrom(event.target);
        if (trigger) scheduleReveal(trigger, 0);
    };

    const onFocusOut = (event) => {
        const trigger = triggerFrom(event.target);
        if (trigger && !remainsInside(trigger, event.relatedTarget)) hide();
    };

    const onKeyDown = (event) => {
        if (event.key === 'Escape') hide();
    };

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'attributes') {
                upgradeTitle(mutation.target);
                return;
            }

            mutation.addedNodes.forEach((node) => {
                if (node instanceof Element) upgradeTitlesWithin(node);
            });
        });
    });

    observer.observe(root, {
        attributes: true,
        attributeFilter: ['title'],
        childList: true,
        subtree: true,
    });

    doc.addEventListener('mouseover', onMouseOver);
    doc.addEventListener('mouseout', onMouseOut);
    doc.addEventListener('focusin', onFocusIn);
    doc.addEventListener('focusout', onFocusOut);
    doc.addEventListener('keydown', onKeyDown);
    window.addEventListener('resize', position);
    window.addEventListener('scroll', position, true);

    return () => {
        hide();
        observer.disconnect();
        doc.removeEventListener('mouseover', onMouseOver);
        doc.removeEventListener('mouseout', onMouseOut);
        doc.removeEventListener('focusin', onFocusIn);
        doc.removeEventListener('focusout', onFocusOut);
        doc.removeEventListener('keydown', onKeyDown);
        window.removeEventListener('resize', position);
        window.removeEventListener('scroll', position, true);
        tooltip.remove();
        delete doc.documentElement.dataset.appTitleTooltipsReady;
    };
}
