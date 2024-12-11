<?php
require_once __DIR__ . '/../../vendor/autoload.php';

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

try {
    $controller = new \App\Controllers\MetricsController();
    $controller->streamMetrics();
} catch (\Exception $e) {
    echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
    exit;
}