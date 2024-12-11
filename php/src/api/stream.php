<?php

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

$controller = new \App\Controllers\MetricsController();
$controller->streamMetrics();