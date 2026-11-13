
(() => {
  const CHARTJS_CDN = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';

  const ensureChartJsLoaded = () =>
    new Promise((resolve) => {
      if (window.Chart) {
        resolve(window.Chart);
        return;
      }
      const script = document.createElement('script');
      script.src = CHARTJS_CDN;
      script.async = true;
      script.onload = () => resolve(window.Chart || null);
      script.onerror = () => resolve(null);
      document.head.appendChild(script);
    });

  let salesBarChart = null;

  /* ===========  Compute 5-Batch Sales Totals for Selected Day ================ */
  const computeBatchTotals = () => {
    const batchLabels = ['8-10AM', '10-12PM', '12-2PM', '2-4PM', '4-6PM'];
    const batchTotals = [0, 0, 0, 0, 0];

    const api = window.MajesticOrderRecords;
    const dayRecords = api && typeof api.getDayRecords === 'function'
      ? api.getDayRecords()
      : (window.MAJESTIC_RECORDS_DATA && Array.isArray(window.MAJESTIC_RECORDS_DATA.records)
          ? window.MAJESTIC_RECORDS_DATA.records
          : []);

    dayRecords.forEach((rec) => {
      if (Number(rec.status_id) === 7) return;
      const isPaid = (rec.payment_status === 'Paid' || rec.payment_status === 'Remitted') && !rec.is_utang;
      if (!isPaid) return;

      const amount = parseFloat(rec.total_price) || 0;
      const slotIdx = Math.max(0, Math.min(4, (Number(rec.slot_id) || 1) - 1));
      batchTotals[slotIdx] += amount;
    });

    const grandTotal = batchTotals.reduce((a, b) => a + b, 0);
    const totalBadge = document.getElementById('chartDayTotalText');
    if (totalBadge) {
      totalBadge.textContent = `P${grandTotal.toFixed(2)}`;
    }

    return { batchLabels, batchTotals };
  };

  /* =========== Update Live Chart.js Bar Chart on Order_Records.php ================ */
  const renderOrUpdateDashboardChart = async () => {
    const ChartLib = await ensureChartJsLoaded();
    let canvas = document.getElementById('dailySalesChartCanvas');
    const chartCard = document.getElementById('dailySalesChartCard') || document.querySelector('.chart-card');

    if (!ChartLib || !chartCard) return;

    if (!canvas) {
      const legacyPlot = chartCard.querySelector('.chart-plot-area');
      const legacyLabels = chartCard.querySelector('.chart-labels-row');
      if (legacyPlot) legacyPlot.style.display = 'none';
      if (legacyLabels) legacyLabels.style.display = 'none';

      const wrap = document.createElement('div');
      wrap.className = 'chart-canvas-wrap';
      wrap.style.position = 'relative';
      wrap.style.width = '100%';
      wrap.style.height = '76px';
      canvas = document.createElement('canvas');
      canvas.id = 'dailySalesChartCanvas';
      wrap.appendChild(canvas);
      chartCard.appendChild(wrap);
    }

    const isDark = document.body.classList.contains('dark-mode');
    const textColor = isDark ? '#e2e8f0' : '#0f172a';
    const gridColor = isDark ? 'rgba(148, 163, 184, 0.2)' : 'rgba(15, 23, 42, 0.12)';
    const barBg = isDark ? '#38bdf8' : '#0284c7';
    const barBorder = isDark ? '#7dd3fc' : '#0369a1';

    const { batchLabels, batchTotals } = computeBatchTotals();

    if (salesBarChart) {
      salesBarChart.data.labels = batchLabels;
      salesBarChart.data.datasets[0].data = batchTotals;
      salesBarChart.data.datasets[0].backgroundColor = barBg;
      salesBarChart.data.datasets[0].borderColor = barBorder;
      salesBarChart.options.scales.x.ticks.color = textColor;
      salesBarChart.options.scales.y.ticks.color = textColor;
      salesBarChart.options.scales.y.grid.color = gridColor;
      salesBarChart.update();
      return;
    }

    salesBarChart = new ChartLib(canvas.getContext('2d'), {
      type: 'bar',
      data: {
        labels: batchLabels,
        datasets: [
          {
            label: 'Paid Cash Sales (P)',
            data: batchTotals,
            backgroundColor: barBg,
            borderColor: barBorder,
            borderWidth: 1.5,
            borderRadius: 4,
            maxBarThickness: 22
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: (ctxItem) => ` P${Number(ctxItem.raw || 0).toFixed(2)}`
            }
          }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { color: textColor, font: { size: 9, weight: '700' } }
          },
          y: {
            beginAtZero: true,
            grid: { color: gridColor },
            ticks: {
              color: textColor,
              font: { size: 8, weight: '600' },
              maxTicksLimit: 3,
              callback: (val) => `P${val}`
            }
          }
        }
      }
    });
  };

  /* =========== Open Standalone Daily_Sales_Report.php Page ================ */
  const openDailySalesReportWindow = () => {
    const api = window.MajesticOrderRecords;
    const dateInput = document.getElementById('filterDateInput');
    const selectedDate = (api && typeof api.getSelectedDate === 'function')
      ? api.getSelectedDate()
      : (dateInput ? dateInput.value : new Date().toISOString().slice(0, 10));

    const reportUrl = `Daily_Sales_Report.php?date=${encodeURIComponent(selectedDate || '')}`;
    const reportWin = window.open(reportUrl, '_blank');
    if (!reportWin && api && typeof api.showStatus === 'function') {
      api.showStatus('Popup blocked: Please allow popups for localhost to open Daily_Sales_Report.php.', 'error');
    }
  };

  /* ===========  Hook Up Events ================ */
  window.addEventListener('majestic:records-updated', () => {
    renderOrUpdateDashboardChart();
  });

  document.addEventListener('DOMContentLoaded', () => {
    renderOrUpdateDashboardChart();
    const btnReport = document.getElementById('btnGenerateDailyReport');
    if (btnReport) {
      btnReport.addEventListener('click', openDailySalesReportWindow);
    }
  });
})();