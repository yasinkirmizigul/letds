export default async function init(ctx) {
    const root = ctx?.root || document;
    const chartElement = root.querySelector('#serviceReviewTrendChart');
    const page = root.querySelector('[data-review-trend]');

    if (!chartElement || !page || typeof window.ApexCharts !== 'function') return;

    let trend = {};
    try {
        trend = JSON.parse(page.dataset.reviewTrend || '{}');
    } catch (_) {
        trend = {};
    }

    const chart = new window.ApexCharts(chartElement, {
        chart: {
            type: 'line',
            height: 260,
            toolbar: { show: false },
            fontFamily: 'inherit',
            background: 'transparent',
        },
        series: [
            { name: 'Ortalama puan', type: 'line', data: trend.averages || [] },
            { name: 'Yanıt', type: 'column', data: trend.counts || [] },
        ],
        colors: ['#006AE6', '#00A261'],
        stroke: { width: [3, 0], curve: 'smooth' },
        plotOptions: { bar: { columnWidth: '36%', borderRadius: 3 } },
        dataLabels: { enabled: false },
        xaxis: {
            categories: trend.labels || [],
            labels: { style: { colors: 'var(--bs-text-muted)' } },
            axisBorder: { color: 'var(--bs-border-color)' },
            axisTicks: { color: 'var(--bs-border-color)' },
        },
        yaxis: [
            { min: 0, max: 5, tickAmount: 5, labels: { style: { colors: 'var(--bs-text-muted)' } } },
            { opposite: true, min: 0, labels: { style: { colors: 'var(--bs-text-muted)' } } },
        ],
        grid: { borderColor: 'var(--bs-border-color)', strokeDashArray: 4 },
        legend: { labels: { colors: 'var(--bs-body-color)' } },
        tooltip: { theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light' },
    });

    chart.render();
}
