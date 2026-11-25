// Charts for the admin Analytics page (data comes from the #analyticsData JSON tag).
(function () {
    // Share bars: width comes from data-share (percent), so the Blade file needs no inline styles.
    document.querySelectorAll('[data-share]').forEach(function (bar) {
        bar.style.width = Math.max(parseFloat(bar.dataset.share) || 0, 0) + '%';
    });

    var dataTag = document.getElementById('analyticsData');
    if (!dataTag || typeof Chart === 'undefined') {
        return;
    }
    var data = JSON.parse(dataTag.textContent);

    var BLUE = '#2a78d6';
    var GRID = 'rgba(0, 0, 0, .06)';
    var TEXT = '#52514e';

    var money = function (v) {
        return Math.round(v).toLocaleString('de-DE') + ' RSD';
    };

    Chart.defaults.color = TEXT;
    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;

    // Shared options: one y-axis, recessive grid, tooltip on hover anywhere in the column.
    function options(formatValue) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: function (ctx) { return ' ' + formatValue(ctx.parsed.y); } } }
            },
            scales: {
                x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 12 } },
                y: { beginAtZero: true, grid: { color: GRID }, border: { display: false },
                     ticks: { precision: 0, callback: formatValue } }
            }
        };
    }

    function bar(id, labels, values, formatValue) {
        var el = document.getElementById(id);
        if (!el) return;
        new Chart(el, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: BLUE,
                    borderRadius: { topLeft: 4, topRight: 4 },
                    borderSkipped: 'bottom',
                    maxBarThickness: 28,
                    categoryPercentage: 0.8
                }]
            },
            options: options(formatValue)
        });
    }

    function line(id, labels, values, formatValue) {
        var el = document.getElementById(id);
        if (!el) return;
        new Chart(el, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    borderColor: BLUE,
                    backgroundColor: 'rgba(42, 120, 214, .08)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.25,
                    pointRadius: values.length > 40 ? 0 : 3,
                    pointHoverRadius: 5,
                    pointBackgroundColor: BLUE
                }]
            },
            options: options(formatValue)
        });
    }

    var count = function (v) { return v; };

    // Long periods have a value for most points, so a line reads better there; short ones are sparse, so bars.
    (data.timeline.labels.length > 31 ? line : bar)('revenueChart', data.timeline.labels, data.timeline.revenue, money);
    bar('ordersChart', data.timeline.labels, data.timeline.orders, count);
    bar('weekdayChart', data.weekdays.labels, data.weekdays.orders, count);
    bar('sizeChart',
        data.sizes.map(function (s) { return String(s.size); }),
        data.sizes.map(function (s) { return s.units; }),
        function (v) { return v + ' pcs'; });

    // Orders outside sales: one stacked bar per day / month, one color per status, with a legend.
    var STATUS_COLORS = { unconfirmed: '#9ec5fe', 'new': BLUE, cancelled: '#e07b39', expired: '#343a40' };
    var otherEl = document.getElementById('otherOrdersChart');
    if (otherEl && data.other) {
        var opts = options(count);
        opts.plugins.legend = { display: true, position: 'bottom', labels: { boxWidth: 12, boxHeight: 12 } };
        opts.plugins.tooltip = { callbacks: { label: function (ctx) { return ' ' + ctx.dataset.label + ': ' + ctx.parsed.y; } } };
        opts.scales.x.stacked = true;
        opts.scales.y.stacked = true;

        new Chart(otherEl, {
            type: 'bar',
            data: {
                labels: data.other.labels,
                datasets: Object.keys(data.other.series).map(function (status) {
                    return {
                        label: status.charAt(0).toUpperCase() + status.slice(1),
                        data: data.other.series[status],
                        backgroundColor: STATUS_COLORS[status] || TEXT,
                        maxBarThickness: 28,
                        categoryPercentage: 0.8
                    };
                })
            },
            options: opts
        });
    }

    // Visitors: page views and visits, two lines with a legend.
    var visitsEl = document.getElementById('visitsChart');
    if (visitsEl && data.visits) {
        var vOpts = options(count);
        vOpts.plugins.legend = { display: true, position: 'bottom', labels: { boxWidth: 12, boxHeight: 12 } };
        vOpts.plugins.tooltip = { callbacks: { label: function (ctx) { return ' ' + ctx.dataset.label + ': ' + ctx.parsed.y; } } };

        var dense = data.visits.labels.length > 40;
        var series = function (label, values, color, fill) {
            return {
                label: label,
                data: values,
                borderColor: color,
                backgroundColor: fill,
                borderWidth: 2,
                fill: !!fill,
                tension: 0.25,
                pointRadius: dense ? 0 : 3,
                pointHoverRadius: 5,
                pointBackgroundColor: color
            };
        };

        new Chart(visitsEl, {
            type: 'line',
            data: {
                labels: data.visits.labels,
                datasets: [
                    series('Page views', data.visits.views, BLUE, 'rgba(42, 120, 214, .08)'),
                    series('Visits', data.visits.visits, '#e07b39', null)
                ]
            },
            options: vOpts
        });
    }
})();
