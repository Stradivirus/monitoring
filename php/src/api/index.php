<?php

require_once __DIR__ . '/../../vendor/autoload.php';

$request = $_SERVER['*'];

// Add CORS header globally (can be adjusted for specific endpoints)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 라우팅 처리
switch (true) {
    case preg_match('/^\/api\/stream\/metrics$/', $request):
        header('Content-Type: text/event-stream'); // For EventSource
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        $controller = new \App\Controllers\MetricsController();
        $controller->streamMetrics();
        break;

    case preg_match('/^\/api\/containers$/', $request):
        require __DIR__ . '/containers.php';
        break;

    case preg_match('/^\/api\/containers\/([^\/]+)\/metrics$/', $request, $matches):
        $_GET['container_id'] = $matches[1];
        require __DIR__ . '/metrics.php';
        break;

    case preg_match('/^\/api\/metrics\/([^\/]+)$/', $request, $matches):
        $_GET['metric_type'] = $matches[1];
        require __DIR__ . '/metrics.php';
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
        break;
}
