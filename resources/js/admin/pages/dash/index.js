import { initHistoryTimelines } from '../../helpers/history-timeline';

function resolveRoot(ctx) {
    if (ctx.root?.matches?.('[data-page="dash.index"]')) {
        return ctx.root;
    }

    return ctx.root?.querySelector?.('[data-page="dash.index"]')
        || document.querySelector('[data-page="dash.index"]');
}

function parseJson(value, fallback) {
    try {
        return value ? JSON.parse(value) : fallback;
    } catch {
        return fallback;
    }
}

function cssVar(name, fallback) {
    const rootValue = getComputedStyle(document.documentElement).getPropertyValue(name)?.trim();
    const bodyValue = getComputedStyle(document.body).getPropertyValue(name)?.trim();

    return rootValue || bodyValue || fallback;
}

function isDarkMode() {
    return document.documentElement.classList.contains('dark')
        || document.documentElement.dataset.ktThemeMode === 'dark'
        || document.body.dataset.ktThemeMode === 'dark';
}

function destroyCharts(charts) {
    charts.forEach((chart) => {
        try {
            chart.destroy();
        } catch (_) {}
    });
}

const SVG_NS = 'http://www.w3.org/2000/svg';

const CHART_COLORS = [
    '#3e97ff',
    '#17c653',
    '#f1416c',
    '#7239ea',
    '#f6b100',
    '#0ea5e9',
    '#14b8a6',
    '#f97316',
];

const numberFormatter = new Intl.NumberFormat('tr-TR');

function toNumber(value) {
    const number = Number(value);

    return Number.isFinite(number) ? number : 0;
}

function formatNumber(value) {
    return numberFormatter.format(toNumber(value));
}

function htmlElement(tag, attributes = {}, text = null) {
    const element = document.createElement(tag);

    Object.entries(attributes).forEach(([name, value]) => {
        if (value === null || value === undefined) return;

        if (name === 'class') {
            element.className = value;
            return;
        }

        if (name === 'style' && typeof value === 'object') {
            Object.assign(element.style, value);
            return;
        }

        element.setAttribute(name, value);
    });

    if (text !== null && text !== undefined) {
        element.textContent = text;
    }

    return element;
}

function svgElement(tag, attributes = {}, text = null) {
    const element = document.createElementNS(SVG_NS, tag);

    Object.entries(attributes).forEach(([name, value]) => {
        if (value === null || value === undefined) return;
        element.setAttribute(name, value);
    });

    if (text !== null && text !== undefined) {
        element.textContent = text;
    }

    return element;
}

function append(parent, children) {
    children.filter(Boolean).forEach((child) => parent.appendChild(child));

    return parent;
}

function makeNativeChart(element, draw) {
    if (!element) return null;

    return {
        render() {
            draw(element);
        },
        destroy() {
            element.replaceChildren();
        },
    };
}

function chartShell() {
    return htmlElement('div', {
        class: 'd-flex flex-column gap-4 w-100 h-100',
        style: {
            minHeight: 'inherit',
        },
    });
}

function legend(items) {
    const container = htmlElement('div', {
        class: 'd-flex flex-wrap align-items-center gap-3',
    });

    items.forEach((item) => {
        const marker = htmlElement('span', {
            style: {
                width: '0.65rem',
                height: '0.65rem',
                borderRadius: '999px',
                backgroundColor: item.color,
                display: 'inline-block',
                flex: '0 0 auto',
            },
        });
        const label = htmlElement('span', {
            class: 'd-inline-flex align-items-center gap-2 fs-8 fw-semibold',
            style: {
                color: cssVar('--muted-foreground', '#6b7280'),
            },
        });

        append(label, [marker, document.createTextNode(item.label)]);
        container.appendChild(label);
    });

    return container;
}

function emptyState(height = 240) {
    return htmlElement('div', {
        class: 'd-flex align-items-center justify-content-center rounded-3 border border-dashed',
        style: {
            minHeight: `${height}px`,
            color: cssVar('--muted-foreground', '#6b7280'),
        },
    }, 'Veri bulunamadı');
}

function normalizeSeries(series) {
    return (Array.isArray(series) ? series : [])
        .map((item, index) => ({
            name: item?.name || `Seri ${index + 1}`,
            data: (Array.isArray(item?.data) ? item.data : []).map(toNumber),
            color: CHART_COLORS[index % CHART_COLORS.length],
        }))
        .filter((item) => item.data.length > 0);
}

function horizontalGrid(svg, dimensions, steps, maxValue) {
    const muted = cssVar('--muted-foreground', '#6b7280');
    const border = cssVar('--border', '#e5e7eb');
    const { left, right, top, bottom, width, height } = dimensions;
    const innerHeight = height - top - bottom;
    const innerWidth = width - left - right;

    for (let index = 0; index <= steps; index += 1) {
        const ratio = index / steps;
        const y = top + (innerHeight * ratio);
        const value = Math.round(maxValue * (1 - ratio));

        svg.appendChild(svgElement('line', {
            x1: left,
            x2: left + innerWidth,
            y1: y,
            y2: y,
            stroke: border,
            'stroke-dasharray': '5 5',
            'stroke-width': '1',
        }));

        svg.appendChild(svgElement('text', {
            x: left - 12,
            y: y + 4,
            fill: muted,
            'font-size': '12',
            'text-anchor': 'end',
        }, formatNumber(value)));
    }
}

function pointFor(value, index, length, dimensions, maxValue) {
    const { left, right, top, bottom, width, height } = dimensions;
    const innerWidth = width - left - right;
    const innerHeight = height - top - bottom;
    const ratio = length > 1 ? index / (length - 1) : 0;

    return {
        x: left + (innerWidth * ratio),
        y: top + innerHeight - ((toNumber(value) / maxValue) * innerHeight),
    };
}

function linePath(points) {
    return points
        .map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x.toFixed(2)} ${point.y.toFixed(2)}`)
        .join(' ');
}

function areaPath(points, dimensions) {
    if (!points.length) return '';

    const baseline = dimensions.height - dimensions.bottom;
    const first = points[0];
    const last = points[points.length - 1];

    return [
        `M ${first.x.toFixed(2)} ${baseline}`,
        linePath(points).replace(/^M /, 'L '),
        `L ${last.x.toFixed(2)} ${baseline}`,
        'Z',
    ].join(' ');
}

function createMonthlyChart(element, payload) {
    return makeNativeChart(element, (target) => {
        const labels = Array.isArray(payload?.labels) ? payload.labels : [];
        const series = normalizeSeries(payload?.series);
        const hasData = labels.length > 0 && series.some((item) => item.data.some((value) => value > 0));

        target.replaceChildren();

        if (!hasData) {
            target.appendChild(emptyState(300));
            return;
        }

        const shell = chartShell();
        const dimensions = { width: 720, height: 320, left: 54, right: 28, top: 24, bottom: 42 };
        const maxValue = Math.max(1, ...series.flatMap((item) => item.data));
        const svg = svgElement('svg', {
            viewBox: `0 0 ${dimensions.width} ${dimensions.height}`,
            role: 'img',
            'aria-label': 'Aylık aktivite grafiği',
            preserveAspectRatio: 'xMidYMid meet',
            style: 'width:100%;height:100%;min-height:285px;display:block;',
        });
        const muted = cssVar('--muted-foreground', '#6b7280');
        const labelInterval = Math.max(1, Math.ceil(labels.length / 8));

        horizontalGrid(svg, dimensions, 4, maxValue);

        labels.forEach((label, index) => {
            const shouldShow = index === labels.length - 1 || index % labelInterval === 0;

            if (!shouldShow) return;

            const point = pointFor(0, index, labels.length, dimensions, maxValue);

            svg.appendChild(svgElement('text', {
                x: point.x,
                y: dimensions.height - 12,
                fill: muted,
                'font-size': '12',
                'text-anchor': 'middle',
            }, String(label)));
        });

        series.forEach((item) => {
            const points = labels.map((_, index) => pointFor(item.data[index] || 0, index, labels.length, dimensions, maxValue));
            const area = svgElement('path', {
                d: areaPath(points, dimensions),
                fill: item.color,
                opacity: isDarkMode() ? '0.12' : '0.08',
            });
            const line = svgElement('path', {
                d: linePath(points),
                fill: 'none',
                stroke: item.color,
                'stroke-width': '3',
                'stroke-linecap': 'round',
                'stroke-linejoin': 'round',
            });

            svg.appendChild(area);
            svg.appendChild(line);

            points.forEach((point, index) => {
                const circle = svgElement('circle', {
                    cx: point.x,
                    cy: point.y,
                    r: '4',
                    fill: item.color,
                    stroke: cssVar('--background', '#ffffff'),
                    'stroke-width': '2',
                });

                circle.appendChild(svgElement('title', {}, `${item.name}: ${formatNumber(item.data[index] || 0)}`));
                svg.appendChild(circle);
            });
        });

        append(shell, [
            legend(series.map((item) => ({ label: item.name, color: item.color }))),
            svg,
        ]);
        target.appendChild(shell);
    });
}

function polarPoint(cx, cy, radius, angle) {
    const radians = ((angle - 90) * Math.PI) / 180;

    return {
        x: cx + (radius * Math.cos(radians)),
        y: cy + (radius * Math.sin(radians)),
    };
}

function arcPath(cx, cy, radius, startAngle, endAngle) {
    const start = polarPoint(cx, cy, radius, endAngle);
    const end = polarPoint(cx, cy, radius, startAngle);
    const largeArc = endAngle - startAngle <= 180 ? '0' : '1';

    return [
        'M',
        start.x.toFixed(2),
        start.y.toFixed(2),
        'A',
        radius,
        radius,
        0,
        largeArc,
        0,
        end.x.toFixed(2),
        end.y.toFixed(2),
    ].join(' ');
}

function createActionChart(element, payload) {
    return makeNativeChart(element, (target) => {
        const labels = Array.isArray(payload?.labels) ? payload.labels : [];
        const values = (Array.isArray(payload?.series) ? payload.series : []).map(toNumber);
        const items = values
            .map((value, index) => ({
                label: labels[index] || `Kayıt ${index + 1}`,
                value,
                color: CHART_COLORS[(index + 4) % CHART_COLORS.length],
            }))
            .filter((item) => item.value > 0);
        const total = Math.max(toNumber(payload?.total), items.reduce((sum, item) => sum + item.value, 0));

        target.replaceChildren();

        if (!items.length || total <= 0) {
            target.appendChild(emptyState(260));
            return;
        }

        const shell = chartShell();
        const foreground = cssVar('--foreground', '#111827');
        const muted = cssVar('--muted-foreground', '#6b7280');
        const track = isDarkMode() ? 'rgba(255,255,255,.08)' : 'rgba(15,23,42,.08)';
        const svg = svgElement('svg', {
            viewBox: '0 0 320 240',
            role: 'img',
            'aria-label': 'Odak işleri dağılımı',
            preserveAspectRatio: 'xMidYMid meet',
            style: 'width:100%;height:220px;display:block;',
        });
        const cx = 160;
        const cy = 108;
        const radius = 76;
        const strokeWidth = 22;
        let cursor = 0;

        svg.appendChild(svgElement('circle', {
            cx,
            cy,
            r: radius,
            fill: 'none',
            stroke: track,
            'stroke-width': strokeWidth,
        }));

        items.forEach((item) => {
            const slice = (item.value / total) * 360;
            const end = cursor + slice;

            if (slice >= 359.99) {
                const circle = svgElement('circle', {
                    cx,
                    cy,
                    r: radius,
                    fill: 'none',
                    stroke: item.color,
                    'stroke-width': strokeWidth,
                    'stroke-linecap': 'round',
                });

                circle.appendChild(svgElement('title', {}, `${item.label}: ${formatNumber(item.value)} kayıt`));
                svg.appendChild(circle);
                cursor = end;
                return;
            }

            const path = svgElement('path', {
                d: arcPath(cx, cy, radius, cursor, end),
                fill: 'none',
                stroke: item.color,
                'stroke-width': strokeWidth,
                'stroke-linecap': 'round',
            });

            path.appendChild(svgElement('title', {}, `${item.label}: ${formatNumber(item.value)} kayıt`));
            svg.appendChild(path);
            cursor = end;
        });

        svg.appendChild(svgElement('text', {
            x: cx,
            y: cy - 4,
            fill: foreground,
            'font-size': '28',
            'font-weight': '700',
            'text-anchor': 'middle',
        }, formatNumber(total)));
        svg.appendChild(svgElement('text', {
            x: cx,
            y: cy + 20,
            fill: muted,
            'font-size': '13',
            'text-anchor': 'middle',
        }, 'Odak işi'));

        append(shell, [
            svg,
            legend(items.map((item) => ({ label: `${item.label} (${formatNumber(item.value)})`, color: item.color }))),
        ]);
        target.appendChild(shell);
    });
}

function createScheduleChart(element, payload) {
    return makeNativeChart(element, (target) => {
        const labels = Array.isArray(payload?.labels) ? payload.labels : [];
        const values = (Array.isArray(payload?.series) ? payload.series : []).map(toNumber);
        const hasData = labels.length > 0 && values.some((value) => value > 0);

        target.replaceChildren();

        if (!hasData) {
            target.appendChild(emptyState(210));
            return;
        }

        const shell = chartShell();
        const dimensions = { width: 640, height: 220, left: 44, right: 22, top: 18, bottom: 38 };
        const maxValue = Math.max(1, ...values);
        const svg = svgElement('svg', {
            viewBox: `0 0 ${dimensions.width} ${dimensions.height}`,
            role: 'img',
            'aria-label': 'Randevu yoğunluğu grafiği',
            preserveAspectRatio: 'xMidYMid meet',
            style: 'width:100%;height:100%;min-height:200px;display:block;',
        });
        const muted = cssVar('--muted-foreground', '#6b7280');
        const primary = cssVar('--color-primary', '#3e97ff');
        const innerWidth = dimensions.width - dimensions.left - dimensions.right;
        const innerHeight = dimensions.height - dimensions.top - dimensions.bottom;
        const step = innerWidth / Math.max(labels.length, 1);
        const barWidth = Math.min(44, step * 0.42);

        horizontalGrid(svg, dimensions, 3, maxValue);

        labels.forEach((label, index) => {
            const value = values[index] || 0;
            const barHeight = (value / maxValue) * innerHeight;
            const x = dimensions.left + (step * index) + ((step - barWidth) / 2);
            const y = dimensions.top + innerHeight - barHeight;
            const fill = index % 2 === 0 ? primary : '#17c653';
            const rect = svgElement('rect', {
                x,
                y,
                width: barWidth,
                height: Math.max(barHeight, value > 0 ? 4 : 0),
                rx: '8',
                fill,
                opacity: isDarkMode() ? '0.92' : '0.88',
            });

            rect.appendChild(svgElement('title', {}, `${label}: ${formatNumber(value)} randevu`));
            svg.appendChild(rect);
            svg.appendChild(svgElement('text', {
                x: x + (barWidth / 2),
                y: dimensions.height - 12,
                fill: muted,
                'font-size': '12',
                'text-anchor': 'middle',
            }, String(label)));
        });

        append(shell, [svg]);
        target.appendChild(shell);
    });
}

export default function init(ctx) {
    const root = resolveRoot(ctx);
    if (!root) return;

    const monthlyPayload = parseJson(root.dataset.monthlyChart, { labels: [], series: [] });
    const actionPayload = parseJson(root.dataset.actionChart, { labels: [], series: [], total: 0 });
    const schedulePayload = parseJson(root.dataset.scheduleChart, { labels: [], series: [] });

    initHistoryTimelines(root, ctx);

    let charts = [];
    let rerenderFrame = null;

    const renderCharts = () => {
        destroyCharts(charts);
        charts = [
            createMonthlyChart(root.querySelector('#dashboardMonthlyChart'), monthlyPayload),
            createActionChart(root.querySelector('#dashboardActionChart'), actionPayload),
            createScheduleChart(root.querySelector('#dashboardScheduleChart'), schedulePayload),
        ].filter(Boolean);

        charts.forEach((chart) => chart.render());
    };

    const scheduleRender = () => {
        if (rerenderFrame) {
            cancelAnimationFrame(rerenderFrame);
        }

        rerenderFrame = requestAnimationFrame(() => {
            rerenderFrame = null;
            renderCharts();
        });
    };

    renderCharts();

    const observer = new MutationObserver((mutations) => {
        if (!mutations.some((mutation) => mutation.type === 'attributes')) {
            return;
        }

        scheduleRender();
    });

    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class', 'data-kt-theme-mode'],
    });

    ctx.cleanup(() => {
        observer.disconnect();

        if (rerenderFrame) {
            cancelAnimationFrame(rerenderFrame);
        }

        destroyCharts(charts);
    });
}
