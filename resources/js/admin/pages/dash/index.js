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
    return document.documentElement.classList.contains('dark');
}

function chartTheme() {
    return isDarkMode() ? 'dark' : 'light';
}

function destroyCharts(charts) {
    charts.forEach((chart) => {
        try {
            chart.destroy();
        } catch (_) {}
    });
}

function createMonthlyChart(element, payload) {
    if (!element || !window.ApexCharts) return null;

    const labels = Array.isArray(payload?.labels) ? payload.labels : [];
    const series = Array.isArray(payload?.series) ? payload.series : [];
    const foreground = cssVar('--foreground', '#111827');
    const muted = cssVar('--muted-foreground', '#6b7280');
    const border = cssVar('--border', '#e5e7eb');

    return new window.ApexCharts(element, {
        chart: {
            type: 'area',
            height: 340,
            toolbar: { show: false },
            zoom: { enabled: false },
            fontFamily: 'inherit',
            foreColor: foreground,
        },
        theme: {
            mode: chartTheme(),
        },
        series,
        colors: [
            cssVar('--color-primary', '#3e97ff'),
            '#17c653',
            '#f1416c',
            '#7239ea',
            '#f6b100',
        ],
        dataLabels: { enabled: false },
        stroke: {
            curve: 'smooth',
            width: 3,
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.24,
                opacityTo: 0.03,
                stops: [0, 90, 100],
            },
        },
        markers: {
            size: 4,
            strokeWidth: 0,
            hover: {
                sizeOffset: 2,
            },
        },
        legend: {
            show: true,
            position: 'top',
            horizontalAlign: 'left',
            labels: {
                colors: foreground,
            },
        },
        grid: {
            borderColor: border,
            strokeDashArray: 4,
            padding: {
                left: 8,
                right: 8,
            },
        },
        xaxis: {
            categories: labels,
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: {
                style: {
                    colors: muted,
                    fontSize: '12px',
                },
            },
        },
        yaxis: {
            min: 0,
            labels: {
                style: {
                    colors: muted,
                    fontSize: '12px',
                },
            },
        },
        tooltip: {
            theme: chartTheme(),
            shared: true,
            intersect: false,
        },
        noData: {
            text: 'Veri bulunamadı',
            style: {
                color: muted,
            },
        },
    });
}

function createActionChart(element, payload) {
    if (!element || !window.ApexCharts) return null;

    const labels = Array.isArray(payload?.labels) ? payload.labels : [];
    const series = Array.isArray(payload?.series) ? payload.series : [];
    const total = Number(payload?.total || 0);
    const foreground = cssVar('--foreground', '#111827');
    const muted = cssVar('--muted-foreground', '#6b7280');
    const background = cssVar('--background', '#ffffff');

    return new window.ApexCharts(element, {
        chart: {
            type: 'donut',
            height: 300,
            fontFamily: 'inherit',
            foreColor: foreground,
        },
        theme: {
            mode: chartTheme(),
        },
        series,
        labels,
        colors: [
            '#f6b100',
            '#f1416c',
            '#17c653',
            '#3e97ff',
            '#43ced7',
        ],
        legend: {
            position: 'bottom',
            fontSize: '13px',
            labels: {
                colors: foreground,
            },
            itemMargin: {
                vertical: 6,
            },
        },
        stroke: {
            width: 2,
            colors: [background],
        },
        dataLabels: {
            enabled: false,
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '72%',
                    labels: {
                        show: true,
                        value: {
                            offsetY: 6,
                            fontSize: '24px',
                            fontWeight: 700,
                            color: foreground,
                        },
                        total: {
                            show: true,
                            label: 'Odak isi',
                            fontSize: '13px',
                            color: muted,
                            formatter: () => String(total),
                        },
                    },
                },
            },
        },
        tooltip: {
            theme: chartTheme(),
            y: {
                formatter: (value) => `${value} kayıt`,
            },
        },
        noData: {
            text: 'Veri bulunamadı',
            style: {
                color: muted,
            },
        },
    });
}

function createScheduleChart(element, payload) {
    if (!element || !window.ApexCharts) return null;

    const labels = Array.isArray(payload?.labels) ? payload.labels : [];
    const seriesData = Array.isArray(payload?.series) ? payload.series : [];
    const foreground = cssVar('--foreground', '#111827');
    const muted = cssVar('--muted-foreground', '#6b7280');
    const border = cssVar('--border', '#e5e7eb');

    return new window.ApexCharts(element, {
        chart: {
            type: 'bar',
            height: 240,
            toolbar: { show: false },
            fontFamily: 'inherit',
            foreColor: foreground,
        },
        theme: {
            mode: chartTheme(),
        },
        series: [{
            name: 'Randevu',
            data: seriesData,
        }],
        colors: [cssVar('--color-primary', '#3e97ff')],
        plotOptions: {
            bar: {
                borderRadius: 10,
                columnWidth: '42%',
            },
        },
        dataLabels: { enabled: false },
        grid: {
            borderColor: border,
            strokeDashArray: 4,
        },
        xaxis: {
            categories: labels,
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: {
                style: {
                    colors: muted,
                    fontSize: '12px',
                },
            },
        },
        yaxis: {
            min: 0,
            labels: {
                style: {
                    colors: muted,
                    fontSize: '12px',
                },
            },
        },
        tooltip: {
            theme: chartTheme(),
            y: {
                formatter: (value) => `${value} randevu`,
            },
        },
        noData: {
            text: 'Veri bulunamadı',
            style: {
                color: muted,
            },
        },
    });
}

export default function init(ctx) {
    const root = resolveRoot(ctx);
    if (!root) return;

    const monthlyPayload = parseJson(root.dataset.monthlyChart, { labels: [], series: [] });
    const actionPayload = parseJson(root.dataset.actionChart, { labels: [], series: [], total: 0 });
    const schedulePayload = parseJson(root.dataset.scheduleChart, { labels: [], series: [] });

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
