<?php
declare(strict_types=1);

// /stats/hit.php
require_once __DIR__ . '/lib.php';

/**
 * Detekcja botów:
 * - jeśli request NIE ma parametru js=1, traktujemy jako bot/automat (crawler zwykle nie odpala JS)
 * - dodatkowo filtrujemy po User-Agent (lista znanych botów)
 *
 * Uwaga: użytkownicy z wyłączonym JS wpadną do botów.
 */

$page = (string)($_GET['p'] ?? '');
if ($page === '') {
  $page = (string)($_SERVER['HTTP_REFERER'] ?? '/');
}

$ua = strtolower((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
$hasJs = ((string)($_GET['js'] ?? '')) === '1';

$isBot = false;

// 1) brak JS -> bot
if (!$hasJs) $isBot = true;

// 2) znane boty po UA
$botPatterns = [
  'bot', 'spider', 'crawl', 'slurp',
  'facebookexternalhit', 'facebot',
  'googlebot', 'adsbot-google', 'bingbot', 'duckduckbot', 'yandexbot', 'baiduspider',
  'ahrefsbot', 'semrushbot', 'mj12bot', 'dotbot', 'screaming frog', 'sitebulb',
  'uptimerobot', 'pingdom', 'statuscake', 'datadog', 'newrelic',
  'lighthouse', 'pagespeed', 'gtmetrix'
];
foreach ($botPatterns as $p) {
  if ($ua !== '' && strpos($ua, $p) !== false) { $isBot = true; break; }
}

// 3) override do testów
if (isset($_GET['force']) && $_GET['force'] === 'human') $isBot = false;
if (isset($_GET['force']) && $_GET['force'] === 'bot') $isBot = true;

stats_increment($page, $isBot, 1);

header('Content-Type: text/plain; charset=utf-8');
http_response_code(204);
