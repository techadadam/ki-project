<?php
declare(strict_types=1);

/**
 * /stats/lib.php
 * Statystyki: ludzie vs boty
 * Kompatybilne z PHP 7.x
 */

// Polyfill dla PHP < 8 (brak str_ends_with)
if (!function_exists('stats_str_ends_with')) {
  function stats_str_ends_with(string $haystack, string $needle): bool {
    if ($needle === '') return true;
    $len = strlen($needle);
    return substr($haystack, -$len) === $needle;
  }
}

function stats_data_file(): string {
  return __DIR__ . '/stats-data.json';
}

function stats_read_data(): array {
  $file = stats_data_file();
  if (!file_exists($file)) {
    return [
      'version' => 2,
      'createdAt' => gmdate('c'),
      'updatedAt' => gmdate('c'),
      'pages' => []
    ];
  }

  $json = @file_get_contents($file);
  $data = json_decode($json ?: '', true);

  if (!is_array($data)) {
    return [
      'version' => 2,
      'createdAt' => gmdate('c'),
      'updatedAt' => gmdate('c'),
      'pages' => []
    ];
  }

  if (!isset($data['version'])) $data['version'] = 2;
  if (!isset($data['pages']) || !is_array($data['pages'])) $data['pages'] = [];

  return $data;
}

function stats_normalize_page(string $page): string {
  $page = trim($page);
  if ($page === '') return '/';

  // pełny URL -> bierzemy path
  if (preg_match('~^https?://~i', $page)) {
    $parts = parse_url($page);
    $page = $parts['path'] ?? '/';
  } else {
    $parts = parse_url($page);
    $page = $parts['path'] ?? $page;
  }

  if ($page === '' || $page[0] !== '/') $page = '/' . $page;

  $page = preg_replace('~/+~', '/', $page);
  if ($page !== '/' && stats_str_ends_with($page, '/')) $page = rtrim($page, '/');

  return $page;
}

function stats_today_key(): string {
  // date_default_timezone_set('Europe/Warsaw'); // opcjonalnie
  return date('Y-m-d');
}

function stats_week_key(DateTimeImmutable $dt): string {
  // ISO week: 2026-W06
  return $dt->format('o-\WW');
}

function stats_last_n_weeks(int $n): array {
  $out = [];
  $now = new DateTimeImmutable('today');
  for ($i = $n - 1; $i >= 0; $i--) {
    $out[] = stats_week_key($now->modify("-{$i} weeks"));
  }
  return $out;
}

/**
 * Struktura danych (v2):
 * pages[page] = [
 *   totals => ['h'=>int,'b'=>int],
 *   daily  => ['YYYY-MM-DD' => ['h'=>int,'b'=>int]]
 * ]
 *
 * Migracja: jeśli spotkamy stare daily jako int, traktujemy jako 'h' (ludzie).
 */
function stats_increment(string $page, bool $isBot, int $delta = 1): void {
  $page = stats_normalize_page($page);
  $day = stats_today_key();

  $file = stats_data_file();
  $fp = @fopen($file, 'c+'); // utworzy plik jeśli nie istnieje
  if (!$fp) return;

  if (!flock($fp, LOCK_EX)) {
    fclose($fp);
    return;
  }

  $contents = stream_get_contents($fp);
  $data = json_decode($contents ?: '', true);
  if (!is_array($data)) {
    $data = ['version' => 2, 'createdAt' => gmdate('c'), 'updatedAt' => gmdate('c'), 'pages' => []];
  }

  if (!isset($data['version'])) $data['version'] = 2;
  if (!isset($data['pages']) || !is_array($data['pages'])) $data['pages'] = [];

  if (!isset($data['pages'][$page]) || !is_array($data['pages'][$page])) {
    $data['pages'][$page] = [
      'totals' => ['h' => 0, 'b' => 0],
      'daily' => []
    ];
  }

  // ensure totals
  if (!isset($data['pages'][$page]['totals']) || !is_array($data['pages'][$page]['totals'])) {
    $data['pages'][$page]['totals'] = ['h' => 0, 'b' => 0];
  }
  if (!isset($data['pages'][$page]['totals']['h'])) $data['pages'][$page]['totals']['h'] = 0;
  if (!isset($data['pages'][$page]['totals']['b'])) $data['pages'][$page]['totals']['b'] = 0;

  // ensure daily
  if (!isset($data['pages'][$page]['daily']) || !is_array($data['pages'][$page]['daily'])) {
    $data['pages'][$page]['daily'] = [];
  }

  // migracja starego formatu daily (int)
  if (isset($data['pages'][$page]['daily'][$day]) && is_int($data['pages'][$page]['daily'][$day])) {
    $old = (int)$data['pages'][$page]['daily'][$day];
    $data['pages'][$page]['daily'][$day] = ['h' => $old, 'b' => 0];
  }

  if (!isset($data['pages'][$page]['daily'][$day]) || !is_array($data['pages'][$page]['daily'][$day])) {
    $data['pages'][$page]['daily'][$day] = ['h' => 0, 'b' => 0];
  }
  if (!isset($data['pages'][$page]['daily'][$day]['h'])) $data['pages'][$page]['daily'][$day]['h'] = 0;
  if (!isset($data['pages'][$page]['daily'][$day]['b'])) $data['pages'][$page]['daily'][$day]['b'] = 0;

  if ($isBot) {
    $data['pages'][$page]['daily'][$day]['b'] += $delta;
    $data['pages'][$page]['totals']['b'] += $delta;
  } else {
    $data['pages'][$page]['daily'][$day]['h'] += $delta;
    $data['pages'][$page]['totals']['h'] += $delta;
  }

  $data['updatedAt'] = gmdate('c');

  // limit danych dziennych: ~420 dni
  $keepDays = 420;
  $cutoff = (new DateTimeImmutable('today'))->modify("-{$keepDays} days");
  foreach ($data['pages'] as $p => $obj) {
    $daily = $obj['daily'] ?? null;
    if (!is_array($daily)) continue;
    foreach (array_keys($daily) as $d) {
      $dt = DateTimeImmutable::createFromFormat('Y-m-d', (string)$d);
      if ($dt && $dt < $cutoff) unset($data['pages'][$p]['daily'][$d]);
    }
  }

  ftruncate($fp, 0);
  rewind($fp);
  fwrite($fp, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
  fflush($fp);

  flock($fp, LOCK_UN);
  fclose($fp);
}

/**
 * Zwraca agregację tygodniową dla ludzi i botów osobno.
 */
function stats_aggregate_weekly_split(array $data, int $weeks = 13): array {
  $weekKeys = stats_last_n_weeks($weeks);
  $result = [
    'weeks' => $weekKeys,
    'pages' => []
  ];

  $pages = $data['pages'] ?? [];
  if (!is_array($pages)) return $result;

  foreach ($pages as $page => $obj) {
    $seriesH = array_fill_keys($weekKeys, 0);
    $seriesB = array_fill_keys($weekKeys, 0);

    $daily = $obj['daily'] ?? [];
    if (is_array($daily)) {
      foreach ($daily as $day => $val) {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', (string)$day);
        if (!$dt) continue;
        $wk = stats_week_key($dt);
        if (!array_key_exists($wk, $seriesH)) continue;

        // migracja starego daily=int => ludzie
        if (is_int($val)) {
          $seriesH[$wk] += (int)$val;
          continue;
        }

        if (is_array($val)) {
          $seriesH[$wk] += (int)($val['h'] ?? 0);
          $seriesB[$wk] += (int)($val['b'] ?? 0);
        }
      }
    }

    $totals = $obj['totals'] ?? [];
    $totalH = (int)($totals['h'] ?? 0);
    $totalB = (int)($totals['b'] ?? 0);

    // fallback jeśli totals nie istnieją (stare dane): policz z daily
    if (!isset($obj['totals']) || !is_array($obj['totals'])) {
      $totalH = 0; $totalB = 0;
      if (is_array($daily)) {
        foreach ($daily as $val) {
          if (is_int($val)) { $totalH += (int)$val; continue; }
          if (is_array($val)) { $totalH += (int)($val['h'] ?? 0); $totalB += (int)($val['b'] ?? 0); }
        }
      }
    }

    $result['pages'][$page] = [
      'total_h' => $totalH,
      'total_b' => $totalB,
      'weekly_h' => array_values($seriesH),
      'weekly_b' => array_values($seriesB)
    ];
  }

  return $result;
}
