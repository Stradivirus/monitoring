<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$request = $_SERVER['REQUEST_URI'];

// CORS 헤더 설정 (Apache 설정과 중복되지 않도록 제거)
// header('Access-Control-Allow-Origin: *');
// header('Content-Type: application/json');

// 라우팅 처리
switch (true) {
    case preg_match('/^\/api\/stream\/metrics$/', $request):
        require __DIR__ . '/stream.php';
        break;

    case preg_match('/^\/api\/containers$/', $request):
        header('Content-Type: application/json');
        require __DIR__ . '/containers.php';
        break;
    
    case preg_match('/^\/api\/containers\/([^\/]+)\/metrics$/', $request):
        header('Content-Type: application/json');
        $_GET['container_id'] = $matches[1];
        require __DIR__ . '/metrics.php';
        break;
    
    case preg_match('/^\/api\/metrics\/([^\/]+)$/', $request):
        header('Content-Type: application/json');
        $_GET['metric_type'] = $matches[1];
        require __DIR__ . '/metrics.php';
        break;
    
    default:
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
        break;
}