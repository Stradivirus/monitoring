<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once __DIR__ . '/../../vendor/autoload.php';

$request = $_SERVER['REQUEST_URI'];

// 라우팅 처리
switch (true) {
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

    case preg_match('/^\/api\/stream\/metrics$/', $request):
        $controller = new \App\Controllers\MetricsController();
        $controller->streamMetrics();
        break;
    
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
        break;
}