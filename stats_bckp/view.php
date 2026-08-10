<?php
// /stats/view.php — prosty podgląd statystyk z /stats/stats_pages.json

$storeFile = __DIR__ . '/stats_pages.json';

if (!file_exists($storeFile)) {
  header('Content-Type: text/plain; charset=utf-8');
  echo "Brak pliku stats_pages.json. Najpierw wejdź na stronę z wpiętym /stats/hit.js, aby nabić pierwsze wejście.";
  exit;
}

$data = json_decode(file_get_contents($storeFile), true);
if (!is_array($data)) {
  header('Content-Type: text/plain; charset=utf-8');
  echo "Błędny format stats_pages.json";
  exit;
}

$global = $data['global'] ?? ['total' => 0, 'human' => 0];
$pages = $data['pages'] ?? [];
$updatedAt = $data['updated_at'] ?? null;

// zamiana na listę + sort po total desc
$list = [];
foreach ($pages as $path => $counts) {
  $list[] = [
    'path' => $path,
    'total' => (int)($counts['total'] ?? 0),
    'human' => (int)($counts['human'] ?? 0),
  ];
}

usort($list, function($a, $b) {
  return $b['total'] <=> $a['total'];
});

// mały helper do HTML
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Statystyki wejść</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; padding: 16px; }
    .box { border: 1px solid #ddd; border-radius: 10px; padding: 12px 14px; margin-bottom: 14px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; }
    th { background: #fafafa; position: sticky; top: 0; }
    .muted { opacity: .7; font-size: 12px; }
    .num { text-align: right; font-variant-numeric: tabular-nums; }
    .path { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace; font-size: 13px; }
  </style>
</head>
<body>

  <div class="box">
    <h2 style="margin:0 0 8px 0;">Global</h2>
    <div>Wszystkie wejścia (total): <b><?= (int)$global['total'] ?></b></div>
    <div>Bez botów (human): <b><?= (int)$global['human'] ?></b></div>
    <div class="muted">Ostatnia aktualizacja: <?= h($updatedAt ?: 'brak') ?></div>
  </div>

  <div class="box">
    <h2 style="margin:0 0 8px 0;">Podstrony</h2>
    <div class="muted">Sortowanie: malejąco po total</div>
  </div>

  <table>
    <thead>
      <tr>
        <th>Ścieżka</th>
        <th class="num">Total</th>
        <th class="num">Human</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($list)): ?>
        <tr><td colspan="3" class="muted">Brak danych — wejdź na podstrony z wpiętym /stats/hit.js.</td></tr>
      <?php else: ?>
        <?php foreach ($list as $row): ?>
          <tr>
            <td class="path"><?= h($row['path']) ?></td>
            <td class="num"><?= (int)$row['total'] ?></td>
            <td class="num"><?= (int)$row['human'] ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

</body>
</html>
