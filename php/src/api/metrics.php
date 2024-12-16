<?php
use MongoDB\Client;

$client = new Client(getenv('MONGODB_URI'));
$collection = $client->monitoring->metrics;

try {
    if (isset($_GET['container_id'])) {
        // 특정 컨테이너의 메트릭 조회 (최근 60분 데이터)
        $containerId = $_GET['container_id'];
        $fromTime = new MongoDB\BSON\UTCDateTime((time() - 3600) * 1000); // 1시간 전

        $metrics = $collection->find(
            [
                'container_id' => $containerId,
                'timestamp' => ['$gte' => $fromTime]
            ],
            [
                'sort' => ['timestamp' => -1],
                'limit' => 60 // 최대 60개 데이터 포인트
            ]
        );

        $result = [];
        foreach ($metrics as $metric) {
            $result[] = [
                'container_id' => $metric['container_id'],
                'name' => $metric['name'],
                'image' => $metric['image'],
                'status' => $metric['status'],
                'metrics' => [
                    'cpu' => [
                        'percentage' => $metric['metrics']['cpu']['percentage'] ?? 0,
                        'cpu_count' => $metric['metrics']['cpu']['cpu_count'] ?? 1
                    ],
                    'memory' => [
                        'used' => $metric['metrics']['memory']['used'] ?? 0,
                        'total' => $metric['metrics']['memory']['total'] ?? 0,
                        'percentage' => $metric['metrics']['memory']['percentage'] ?? 0
                    ],
                    'network' => $metric['metrics']['network'] ?? [
                        'total' => ['rx_bytes' => 0, 'tx_bytes' => 0]
                    ],
                    'disk' => $metric['metrics']['disk'] ?? [
                        'read_bytes' => 0,
                        'write_bytes' => 0
                    ]
                ],
                'timestamp' => $metric['timestamp']->toDateTime()->format('c')
            ];
        }
    } else if (isset($_GET['metric_type'])) {
        // 특정 메트릭 타입의 전체 데이터 조회
        $metrics = $collection->aggregate([
            [
                '$sort' => ['timestamp' => -1]
            ],
            [
                '$group' => [
                    '_id' => '$container_id',
                    'latest' => ['$first' => '$$ROOT']
                ]
            ]
        ]);

        $result = [];
        foreach ($metrics as $metric) {
            $result[] = $metric['latest'];
        }
    }

    header('Content-Type: application/json');
    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Error fetching metrics: ' . $e->getMessage()
    ]);
    error_log('Metrics API Error: ' . $e->getMessage());
}