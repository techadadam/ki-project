<?php
declare(strict_types=1);

// /stats/view.php
require_once __DIR__ . '/lib.php';

$data = stats_read_data();
$pages = array_keys($data['pages'] ?? []);
sort($pages);
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Statystyki odwiedzin</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial; margin: 20px; }
    .row { display:flex; gap:12px; flex-wrap:wrap; align-items:center; }
    select, button { padding:8px 10px; font-size:14px; }
    .card { border:1px solid #e5e5e5; border-radius:12px; padding:14px; max-width:1100px; }
    canvas { max-width:100%; }
    .muted { color:#666; font-size:13px; }
    .pill { display:inline-flex; gap:10px; align-items:center; padding:6px 10px; border:1px solid #e5e5e5; border-radius:999px; }
  </style>
</head>
<body>
  <h2>Odwiedziny – ostatni kwartał (tydzień po tygodniu)</h2>

  <div class="card">
    <div class="row">
      <label for="pageSel"><b>Strona/podstrona:</b></label>
      <select id="pageSel">
        <?php if (count($pages) === 0): ?>
          <option value="/">Brak danych (wdroż hit.php na stronach)</option>
        <?php else: ?>
          <?php foreach ($pages as $p): ?>
            <option value="<?= htmlspecialchars($p, ENT_QUOTES) ?>"><?= htmlspecialchars($p) ?></option>
          <?php endforeach; ?>
        <?php endif; ?>
      </select>

      <span class="pill">
        <label style="display:flex; gap:6px; align-items:center;">
          <input type="radio" name="mode" value="humans" checked />
          Bez botów
        </label>
        <label style="display:flex; gap:6px; align-items:center;">
          <input type="radio" name="mode" value="all" />
          Z botami
        </label>
        <label style="display:flex; gap:6px; align-items:center;">
          <input type="radio" name="mode" value="bots" />
          Same boty
        </label>
      </span>

      <button id="refreshBtn">Odśwież</button>
      <span class="muted" id="info"></span>
    </div>

    <div style="margin-top:14px;">
      <canvas id="chart" height="110"></canvas>
    </div>

    <p class="muted">
      Klasyfikacja: requesty bez parametru <code>js=1</code> oraz znane User-Agent botów trafiają do „botów”.
    </p>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
    const elSel = document.getElementById('pageSel');
    const elInfo = document.getElementById('info');
    const btn = document.getElementById('refreshBtn');
    const modeInputs = Array.from(document.querySelectorAll('input[name="mode"]'));

    let chart;
    let datasetCache = null;

    async function loadData() {
      const url = new URL('./api.php', window.location.href);
      url.searchParams.set('weeks', '13');
      const res = await fetch(url.toString(), { cache: 'no-store' });
      if (!res.ok) throw new Error('API error: ' + res.status);
      return await res.json();
    }

    function getMode() {
      const checked = modeInputs.find(i => i.checked);
      return checked ? checked.value : 'humans';
    }

    function pickSeries(pageObj, mode) {
      const labels = datasetCache.weeks;
      if (!pageObj) return { labels, series: labels.map(() => 0), total: 0 };

      const h = pageObj.weekly_h || labels.map(() => 0);
      const b = pageObj.weekly_b || labels.map(() => 0);
      const totalH = pageObj.total_h || 0;
      const totalB = pageObj.total_b || 0;

      if (mode === 'humans') return { labels, series: h, total: totalH };
      if (mode === 'bots') return { labels, series: b, total: totalB };

      const allSeries = h.map((v, idx) => v + (b[idx] || 0));
      return { labels, series: allSeries, total: (totalH + totalB) };
    }

    function render(selectedPage) {
      const mode = getMode();
      const pageObj = datasetCache.pages[selectedPage];
      const picked = pickSeries(pageObj, mode);

      elInfo.textContent = `Tryb: ${mode === 'humans' ? 'bez botów' : mode === 'bots' ? 'same boty' : 'z botami'} | Suma: ${picked.total}`;

      const ctx = document.getElementById('chart');
      if (chart) chart.destroy();

      chart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: picked.labels,
          datasets: [{
            label: `${selectedPage} (${mode})`,
            data: picked.series,
            tension: 0.25,
            fill: false
          }]
        },
        options: {
          responsive: true,
          plugins: { legend: { display: true } },
          scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
      });
    }

    async function refresh() {
      btn.disabled = true;
      try {
        if (!datasetCache) datasetCache = await loadData();
        render(elSel.value);
      } catch (e) {
        alert('Błąd: ' + e.message);
      } finally {
        btn.disabled = false;
      }
    }

    elSel.addEventListener('change', () => render(elSel.value));
    modeInputs.forEach(i => i.addEventListener('change', () => render(elSel.value)));
    btn.addEventListener('click', async () => { datasetCache = await loadData(); render(elSel.value); });

    refresh();
  </script>
</body>
</html>
