<?php
declare(strict_types=1);

// /stats/api.php
require_once __DIR__ . '/lib.php';

$weeks = (int)($_GET['weeks'] ?? 13);
if ($weeks < 4) $weeks = 4;
if ($weeks > 26) $weeks = 26;

$data = stats_read_data();
$out = stats_aggregate_weekly_split($data, $weeks);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($out, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
