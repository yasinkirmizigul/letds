const instances = new WeakMap();
const observers = new WeakMap();
const TURKISH_VIS_LOCALE = {
    current: 'geçerli',
    time: 'zaman',
    deleteSelected: 'Seçili kaydı sil',
};
const VIEW_LABELS = {
    day: 'Günlük',
    week: 'Haftalık',
    month: 'Aylık',
};
const TURKISH_MOMENT_LOCALE = {
    months: 'Ocak_Şubat_Mart_Nisan_Mayıs_Haziran_Temmuz_Ağustos_Eylül_Ekim_Kasım_Aralık'.split('_'),
    monthsShort: 'Oca_Şub_Mar_Nis_May_Haz_Tem_Ağu_Eyl_Eki_Kas_Ara'.split('_'),
    weekdays: 'Pazar_Pazartesi_Salı_Çarşamba_Perşembe_Cuma_Cumartesi'.split('_'),
    weekdaysShort: 'Paz_Pzt_Sal_Çar_Per_Cum_Cmt'.split('_'),
    weekdaysMin: 'Pz_Pt_Sa_Ça_Pe_Cu_Ct'.split('_'),
    longDateFormat: {
        LT: 'HH:mm',
        LTS: 'HH:mm:ss',
        L: 'DD.MM.YYYY',
        LL: 'D MMMM YYYY',
        LLL: 'D MMMM YYYY HH:mm',
        LLLL: 'dddd, D MMMM YYYY HH:mm',
    },
    calendar: {
        sameDay: '[Bugün saat] LT',
        nextDay: '[Yarın saat] LT',
        nextWeek: 'dddd [saat] LT',
        lastDay: '[Dün saat] LT',
        lastWeek: '[Geçen] dddd [saat] LT',
        sameElse: 'L',
    },
    relativeTime: {
        future: '%s sonra',
        past: '%s önce',
        s: 'birkaç saniye',
        ss: '%d saniye',
        m: 'bir dakika',
        mm: '%d dakika',
        h: 'bir saat',
        hh: '%d saat',
        d: 'bir gün',
        dd: '%d gün',
        M: 'bir ay',
        MM: '%d ay',
        y: 'bir yıl',
        yy: '%d yıl',
    },
    ordinal: (number) => `${number}.`,
    week: {
        dow: 1,
        doy: 7,
    },
};
let turkishMomentLocaleReady = false;

function qsa(root, selector) {
    return Array.from((root || document).querySelectorAll(selector));
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

function safeUrl(value) {
    const url = String(value ?? '').trim();

    if (!url) return '';
    if (url.toLowerCase().startsWith('javascript:')) return '';

    return url;
}

function safeClassName(value, fallback = '') {
    const className = String(value ?? '').trim();

    if (!className) return fallback;

    return className
        .split(/\s+/)
        .filter((part) => /^[a-zA-Z0-9_:/.-]+$/.test(part))
        .join(' ') || fallback;
}

function parseJson(value, fallback = []) {
    try {
        return value ? JSON.parse(value) : fallback;
    } catch (_) {
        return fallback;
    }
}

function parseSource(root, source) {
    if (!source) return [];

    const scopedRoot = root || document;
    const sourceElement = source.startsWith('#')
        ? document.getElementById(source.slice(1))
        : scopedRoot.querySelector(source);

    return parseJson(sourceElement?.textContent, []);
}

function parseViews(value) {
    return String(value || '')
        .split(',')
        .map((view) => view.trim())
        .filter((view) => Object.prototype.hasOwnProperty.call(VIEW_LABELS, view));
}

function startOfDay(date) {
    const next = new Date(date);
    next.setHours(0, 0, 0, 0);

    return next;
}

function addDays(date, amount) {
    const next = new Date(date);
    next.setDate(next.getDate() + amount);

    return next;
}

function addMonths(date, amount) {
    const next = new Date(date);
    next.setMonth(next.getMonth() + amount);

    return next;
}

function startOfWeek(date) {
    const next = startOfDay(date);
    const day = next.getDay() || 7;
    next.setDate(next.getDate() - day + 1);

    return next;
}

function startOfMonth(date) {
    const next = startOfDay(date);
    next.setDate(1);

    return next;
}

function rangeForView(view, anchor) {
    if (view === 'day') {
        const start = startOfDay(anchor);

        return { start, end: addDays(start, 1) };
    }

    if (view === 'week') {
        const start = startOfWeek(anchor);

        return { start, end: addDays(start, 7) };
    }

    const start = startOfMonth(anchor);

    return { start, end: addMonths(start, 1) };
}

function timeAxisForView(view) {
    if (view === 'day') return { scale: 'hour', step: 2 };
    if (view === 'week') return { scale: 'day', step: 1 };

    return { scale: 'day', step: 2 };
}

function shiftAnchor(view, anchor, direction) {
    if (view === 'day') return addDays(anchor, direction);
    if (view === 'week') return addDays(anchor, direction * 7);

    return addMonths(anchor, direction);
}

function viewRangeLabel(view, anchor) {
    const date = new Intl.DateTimeFormat('tr-TR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });
    const month = new Intl.DateTimeFormat('tr-TR', {
        month: 'long',
        year: 'numeric',
    });

    if (view === 'day') return date.format(anchor);
    if (view === 'week') {
        const range = rangeForView(view, anchor);

        return `${date.format(range.start)} - ${date.format(addDays(range.end, -1))}`;
    }

    return month.format(anchor);
}

function dateKey(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function defaultAnchorForView(items, view) {
    if (view !== 'day') return items[0]?.start || new Date();

    const counts = new Map();

    items.forEach((item) => {
        const key = dateKey(item.start);
        const entry = counts.get(key) || { count: 0, date: item.start };
        entry.count += 1;
        counts.set(key, entry);
    });

    return Array.from(counts.values())
        .sort((left, right) => right.count - left.count || right.date.getTime() - left.date.getTime())[0]?.date
        || items[0]?.start
        || new Date();
}

function createTimelineToolbar(views, activeView) {
    const toolbar = document.createElement('div');
    const label = document.createElement('div');
    const nav = document.createElement('div');
    const tabs = document.createElement('div');
    const previous = document.createElement('button');
    const next = document.createElement('button');
    const buttons = new Map();

    toolbar.className = 'app-history-toolbar';
    label.className = 'app-history-toolbar__label';
    nav.className = 'app-history-toolbar__nav';
    tabs.className = 'app-history-toolbar__tabs';

    previous.type = 'button';
    previous.className = 'app-history-toolbar__nav-button';
    previous.setAttribute('aria-label', 'Önceki aralık');
    previous.textContent = '‹';

    next.type = 'button';
    next.className = 'app-history-toolbar__nav-button';
    next.setAttribute('aria-label', 'Sonraki aralık');
    next.textContent = '›';

    views.forEach((view) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'app-history-toolbar__tab';
        button.dataset.historyTimelineViewButton = view;
        button.textContent = VIEW_LABELS[view];
        button.setAttribute('aria-pressed', view === activeView ? 'true' : 'false');
        tabs.appendChild(button);
        buttons.set(view, button);
    });

    nav.append(previous, next);
    toolbar.append(tabs, label, nav);

    return { toolbar, label, previous, next, buttons };
}

function getVisApi() {
    const vis = window.vis || window.VisTimeline || null;
    const Timeline = vis?.Timeline || window.Timeline || null;
    const DataSet = vis?.DataSet || window.DataSet || null;
    const moment = vis?.moment || window.moment || null;

    return Timeline ? { Timeline, DataSet, moment } : null;
}

function ensureTurkishMomentLocale(api) {
    const moment = api?.moment;

    if (!moment || turkishMomentLocaleReady) return;

    try {
        if (typeof moment.defineLocale === 'function') {
            moment.defineLocale('tr', TURKISH_MOMENT_LOCALE);
        }
    } catch (_) {}

    try {
        if (typeof moment.locale === 'function') {
            moment.locale('tr');
        }
    } catch (_) {}

    turkishMomentLocaleReady = true;
}

function itemStart(item) {
    return item.start || item.startAt || item.start_at_iso || item.created_at_iso || item.updated_at_iso || item.date_iso || null;
}

function itemEnd(item) {
    return item.end || item.endAt || item.end_at_iso || null;
}

function itemType(item, container) {
    const requestedType = String(item.type || container?.dataset.historyTimelineItemType || 'box').trim();
    const allowedTypes = ['box', 'point', 'range', 'background'];

    return allowedTypes.includes(requestedType) ? requestedType : 'box';
}

function normalizeDate(value) {
    if (!value) return null;

    const date = value instanceof Date ? value : new Date(value);

    return Number.isNaN(date.getTime()) ? null : date;
}

function variantClass(item) {
    return safeClassName(item.variant || item.type || 'default', 'default');
}

function dateParts(item) {
    const date = item.start instanceof Date ? item.start : normalizeDate(itemStart(item));
    const day = item.avatarDay || (date
        ? new Intl.DateTimeFormat('tr-TR', { day: '2-digit' }).format(date)
        : '--');
    const month = item.avatarMonth || (date
        ? new Intl.DateTimeFormat('tr-TR', { month: 'short' }).format(date).replace('.', '')
        : '');

    return {
        day: String(day).toLocaleUpperCase('tr-TR'),
        month: String(month).toLocaleUpperCase('tr-TR'),
    };
}

function itemTooltip(item) {
    const explicit = item.tooltip || item.tooltipText;

    if (explicit) return String(explicit);

    return [
        item.title || item.label || item.name,
        item.subtitle || item.meta,
        item.description || item.detail,
        item.date || item.time,
        item.status || item.status_label,
    ].filter(Boolean).join('\n');
}

function metronicItemTitle(item) {
    return String(item.nodeTitle || item.shortTitle || item.status || item.label || item.name || item.title || 'Kayit');
}

function metronicItemContent(item) {
    const href = safeUrl(item.url || item.href);
    const itemElement = document.createElement(href ? 'a' : 'div');
    const name = document.createElement('div');
    const avatar = document.createElement('div');
    const avatarFallback = document.createElement('div');
    const tooltip = itemTooltip(item);
    const date = dateParts(item);
    const count = Number(item.count || item.total || 0);
    const title = count > 1 ? `${metronicItemTitle(item)} (${count})` : metronicItemTitle(item);

    if (href) {
        itemElement.href = href;
    }

    if (tooltip) {
        itemElement.dataset.historyTooltip = tooltip;
        itemElement.title = tooltip;
    }

    itemElement.classList.add('text-center');
    name.classList.add('fw-bolder', 'font-semibold', 'mb-2', 'text-primary');
    name.textContent = title;

    avatar.classList.add('symbol', 'symbol-circle', 'symbol-30', 'kt-avatar', 'size-8', 'mx-auto');
    avatarFallback.classList.add('kt-avatar-fallback', 'bg-primary', 'text-primary-foreground', 'font-semibold', 'text-xs');
    avatarFallback.textContent = date.day;
    avatarFallback.setAttribute('aria-label', [date.day, date.month].filter(Boolean).join(' '));

    avatar.appendChild(avatarFallback);
    itemElement.append(name, avatar);

    return itemElement;
}

function metronicItemContentHtml(item) {
    return metronicItemContent(item).outerHTML;
}

function normalizeItems(rawItems = []) {
    return (Array.isArray(rawItems) ? rawItems : [])
        .map((item, index) => {
            const start = normalizeDate(itemStart(item));
            const end = normalizeDate(itemEnd(item));

            return {
                ...item,
                id: item.id || `history-${index + 1}`,
                start,
                end,
            };
        })
        .filter((item) => item.start);
}

function renderEmpty(container, text) {
    container.innerHTML = `
        <div class="app-history-empty">
            ${escapeHtml(text || 'Kayıt bulunmuyor.')}
        </div>
    `;
}

function renderFallback(container, items) {
    container.innerHTML = `
        <div class="app-history-fallback">
            ${items.map((item) => `
                <div class="app-history-fallback__row app-history-fallback__row--${variantClass(item)}">
                    ${metronicItemContentHtml(item)}
                </div>
            `).join('')}
        </div>
    `;
}

function destroyObserver(container) {
    const observer = observers.get(container);

    if (!observer) return;

    try {
        observer.disconnect();
    } catch (_) {}

    observers.delete(container);
}

export function destroyHistoryTimeline(container) {
    if (!container) return;

    const instance = instances.get(container);

    if (instance) {
        try {
            instance.destroy();
        } catch (_) {}
    }

    instances.delete(container);
    destroyObserver(container);
}

export function destroyHistoryTimelines(root = document) {
    qsa(root, '[data-history-timeline], .app-history-timeline').forEach((container) => {
        destroyHistoryTimeline(container);
    });
}

export function renderHistoryTimeline(container, rawItems = [], options = {}) {
    if (!container) return null;

    destroyHistoryTimeline(container);

    const emptyText = options.emptyText || container.dataset.historyTimelineEmpty || 'Kayıt bulunmuyor.';
    const height = options.height || container.dataset.historyTimelineHeight || '280px';
    const compact = options.compact ?? container.dataset.historyTimelineCompact === 'true';
    const locale = String(options.locale || container.dataset.historyTimelineLocale || 'tr');
    const momentLocale = locale.toLowerCase().startsWith('tr') ? 'tr' : locale;
    const stack = options.stack ?? container.dataset.historyTimelineStack !== 'false';
    const verticalScroll = options.verticalScroll ?? container.dataset.historyTimelineVerticalScroll === 'true';
    const items = normalizeItems(rawItems);
    const views = parseViews(options.views || container.dataset.historyTimelineViews);
    let activeView = options.view || container.dataset.historyTimelineView || views[0] || null;

    container.classList.add('app-history-timeline');
    container.classList.toggle('app-history-timeline--compact', Boolean(compact));
    container.style.setProperty('--app-history-timeline-height', height);

    if (!items.length) {
        renderEmpty(container, emptyText);
        return null;
    }

    const api = getVisApi();

    if (!api) {
        renderFallback(container, items);
        return null;
    }

    ensureTurkishMomentLocale(api);

    if (views.length && !views.includes(activeView)) {
        activeView = views[0];
    }

    let anchorDate = normalizeDate(options.anchor || container.dataset.historyTimelineAnchor) || defaultAnchorForView(items, activeView);
    let toolbar = null;
    let initialRange = null;

    if (activeView) {
        initialRange = rangeForView(activeView, anchorDate);
        toolbar = createTimelineToolbar(views, activeView);
    }

    const canvas = document.createElement('div');
    canvas.className = 'app-history-timeline__canvas';
    container.innerHTML = '';
    if (toolbar) {
        container.appendChild(toolbar.toolbar);
    }
    container.appendChild(canvas);

    const data = items.map((item, index) => {
        const type = itemType(item, container);
        const timelineItem = {
            id: item.id || index + 1,
            content: metronicItemContent(item),
            start: item.start,
            type,
        };

        if (type === 'range' && item.end && item.end > item.start) {
            timelineItem.end = item.end;
        }

        return timelineItem;
    });

    let timeline;

    try {
        const timelineItems = api.DataSet ? new api.DataSet(data) : data;
        timeline = new api.Timeline(canvas, timelineItems, {
            autoResize: true,
            editable: false,
            height,
            horizontalScroll: true,
            margin: {
                item: 20,
                axis: 40,
            },
            maxHeight: height,
            moveable: true,
            orientation: {
                axis: 'top',
                item: 'top',
            },
            selectable: false,
            showCurrentTime: false,
            stack,
            verticalScroll,
            locale,
            locales: {
                tr: TURKISH_VIS_LOCALE,
                tr_TR: TURKISH_VIS_LOCALE,
            },
            ...(api.moment ? { moment: (date) => api.moment(date).locale(momentLocale) } : {}),
            ...(initialRange ? {
                start: initialRange.start,
                end: initialRange.end,
                timeAxis: timeAxisForView(activeView),
            } : {}),
            zoomKey: 'ctrlKey',
            zoomable: true,
            ...(options.visOptions || {}),
        });
    } catch (_) {
        renderFallback(container, items);
        return null;
    }

    instances.set(container, timeline);

    function syncToolbar() {
        if (!toolbar || !activeView) return;

        toolbar.label.textContent = viewRangeLabel(activeView, anchorDate);
        toolbar.buttons.forEach((button, view) => {
            button.classList.toggle('is-active', view === activeView);
            button.setAttribute('aria-pressed', view === activeView ? 'true' : 'false');
        });
    }

    function setTimelineWindow(animation = true) {
        if (!activeView) return;

        const range = rangeForView(activeView, anchorDate);

        try {
            timeline.setOptions({ timeAxis: timeAxisForView(activeView) });
            timeline.setWindow(range.start, range.end, { animation });
            timeline.redraw();
        } catch (_) {}

        syncToolbar();
    }

    function forceInitialDraw() {
        try {
            if (activeView) {
                setTimelineWindow(false);
            } else {
                timeline.fit({ animation: false });
            }

            timeline.redraw();
        } catch (_) {}
    }

    if (toolbar) {
        syncToolbar();

        toolbar.previous.addEventListener('click', () => {
            anchorDate = shiftAnchor(activeView, anchorDate, -1);
            setTimelineWindow();
        });

        toolbar.next.addEventListener('click', () => {
            anchorDate = shiftAnchor(activeView, anchorDate, 1);
            setTimelineWindow();
        });

        toolbar.buttons.forEach((button, view) => {
            button.addEventListener('click', () => {
                activeView = view;

                try {
                    const windowRange = timeline.getWindow();
                    anchorDate = new Date((windowRange.start.getTime() + windowRange.end.getTime()) / 2);
                } catch (_) {}

                setTimelineWindow();
            });
        });
    }

    requestAnimationFrame(() => {
        forceInitialDraw();
        requestAnimationFrame(forceInitialDraw);
        window.setTimeout(forceInitialDraw, 120);
        window.setTimeout(forceInitialDraw, 300);
    });

    if (typeof ResizeObserver === 'function') {
        const observer = new ResizeObserver(() => {
            try {
                timeline.redraw();
            } catch (_) {}
        });

        observer.observe(container);
        observers.set(container, observer);
    }

    return timeline;
}

export function initHistoryTimelines(root = document, ctx = null) {
    const containers = qsa(root, '[data-history-timeline]');

    containers.forEach((container) => {
        const items = container.dataset.historyTimelineSource
            ? parseSource(root, container.dataset.historyTimelineSource)
            : parseJson(container.dataset.historyTimelineItems, []);

        renderHistoryTimeline(container, items);
    });

    if (ctx && typeof ctx.cleanup === 'function') {
        ctx.cleanup(() => destroyHistoryTimelines(root));
    }
}
