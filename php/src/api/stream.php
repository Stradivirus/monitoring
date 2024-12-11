<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Controllers\MetricsController;

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

$controller = new MetricsController();
$controller->streamMetrics();