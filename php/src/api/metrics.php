<?php
use MongoDB\Client;

$client = new Client(getenv('MONGODB_URI'));
$collection = $client->monitoring->metrics;

if (isset($_GET['container_id'])) {
    // 특정 컨테이너의 메트릭 조회
    $metrics = $collection->find(
        ['container_id' => $_GET['container_id']],
        [
            'sort' => ['timestamp' => -1],
            'limit' => 60 // 최근 60개 데이터
        ]
    );
} else if (isset($_GET['metric_type'])) {
    // 특정 메트릭 타입의 전체 데이터 조회
    $metrics = $collection->find(
        [],
        [
            'sort' => ['timestamp' => -1],
            'limit' => 60 // 최근 60개 데이터
        ]
    );
}

$result = [];
foreach ($metrics as $metric) {
    $result[] = $metric;
}

echo json_encode($result);