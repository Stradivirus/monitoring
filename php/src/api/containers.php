<?php
use MongoDB\Client;

$client = new Client(getenv('MONGODB_URI'));
$collection = $client->monitoring->metrics;

try {
    // 최신 컨테이너 목록 가져오기 (성능 최적화된 쿼리)
    $containers = $collection->aggregate([
        [
            '$sort' => ['timestamp' => -1]
        ],
        [
            '$group' => [
                '_id' => '$container_id',
                'latest' => ['$first' => '$$ROOT'],
                'last_seen' => ['$max' => '$timestamp']
            ]
        ],
        [
            // 최근 1시간 이내의 컨테이너만 표시
            '$match' => [
                'last_seen' => [
                    '$gte' => new MongoDB\BSON\UTCDateTime((time() - 3600) * 1000)
                ]
            ]
        ],
        [
            '$replaceRoot' => ['newRoot' => '$latest']
        ]
    ])->toArray();

    $result = array_map(function($container) {
        return [
            'container_id' => $container['container_id'],
            'name' => $container['name'],
            'image' => $container['image'],
            'status' => $container['status'],
            'metrics' => [
                'cpu' => [
                    'percentage' => $container['metrics']['cpu']['percentage'] ?? 0,
                    'cpu_count' => $container['metrics']['cpu']['cpu_count'] ?? 1
                ],
                'memory' => [
                    'used' => $container['metrics']['memory']['used'] ?? 0,
                    'total' => $container['metrics']['memory']['total'] ?? 0,
                    'percentage' => $container['metrics']['memory']['percentage'] ?? 0
                ],
                'network' => $container['metrics']['network'] ?? [
                    'total' => ['rx_bytes' => 0, 'tx_bytes' => 0]
                ],
                'disk' => $container['metrics']['disk'] ?? [
                    'read_bytes' => 0,
                    'write_bytes' => 0
                ]
            ],
            'timestamp' => $container['timestamp']->toDateTime()->format('c'),
            'labels' => $container['labels'] ?? []
        ];
    }, $containers);

    header('Cache-Control: no-cache, must-revalidate');
    header('Content-Type: application/json');
    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Error fetching containers: ' . $e->getMessage()
    ]);
    error_log('Containers API Error: ' . $e->getMessage());
}