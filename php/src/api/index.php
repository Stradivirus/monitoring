<?php

require_once __DIR__ . '/../../vendor/autoload.php';

$request = $_SERVER['REQUEST_URI'];

// stream 엔드포인트가 아닐 때만 JSON 헤더 설정
if (!preg_match('/^\/api\/stream\/metrics$/', $request)) {
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');
}

// 라우팅 처리
switch (true) {
    case preg_match('/^\/api\/stream\/metrics$/', $request):
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