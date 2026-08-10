<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$storeFile = __DIR__ . '/stats_pages.json';

// Bot detection (po UA)
$ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
$isBot = (bool)preg_match(
  '/bot|spider|crawl|slurp|bingpreview|facebookexternalhit|facebot|embedly|crawler|wget|curl|python-requests|headless|lighthouse|pagespeed|ahrefs|semrush|mj12|dotbot|yandex|baiduspider|sogou|duckduckbot/i',
  $ua
);

// Strona przekazana z JS (np. /index.html albo /html/drzwi.html)
$page = $_GET['p'] ?? '/';
$page = parse_url($page, PHP_URL_PATH) ?: '/';

// Prosta sanityzacja (żeby nikt nie wysyłał śmieci)
if (strlen($page) > 200) $page = substr($page, 0, 200);
if ($page[0] !== '/') $page = '/' . $page;

if (!file_exists($storeFile)) {
  file_put_contents($storeFile, json_encode([
    'global' => ['total' => 0, 'human' => 0],
    'pages' => [],
    'updated_at' => null
  ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

$fp = fopen($storeFile, 'c+');
if (!$fp) {
  http_response_code(500);
  echo json_encode(['error' => 'Cannot open stats store']);
  exit;
}

flock($fp, LOCK_EX);
rewind($fp);
$raw = stream_get_contents($fp);
$data = json_decode($raw ?: '', true);

if (!is_array($data)) {
  $data = ['global' => ['total' => 0, 'human' => 0], 'pages' => [], 'updated_at' => null];
}
if (!isset($data['global'])) $data['global'] = ['total' => 0, 'human' => 0];
if (!isset($data['pages']) || !is_array($data['pages'])) $data['pages'] = [];

if (!isset($data['pages'][$page])) {
  $data['pages'][$page] = ['total' => 0, 'human' => 0];
}

// Zlicz globalnie
$data['global']['total'] = (int)$data['global']['total'] + 1;
// Zlicz per strona
$data['pages'][$page]['total'] = (int)$data['pages'][$page]['total'] + 1;

$countedHuman = false;

if (!$isBot) {
  $data['global']['human'] = (int)$data['global']['human'] + 1;
  $data['pages'][$page]['human'] = (int)$data['pages'][$page]['human'] + 1;
  $countedHuman = true;
}

$data['updated_at'] = date('c');

// Zapis
rewind($fp);
ftruncate($fp, 0);
fwrite($fp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
fflush($fp);
flock($fp, LOCK_UN);
fclose($fp);

echo json_encode([
  'page' => $page,
  'is_bot' => $isBot,
  'counted_human_now' => $countedHuman,
  'global' => $data['global'],
  'this_page' => $data['pages'][$page]
], JSON_UNESCAPED_UNICODE);
