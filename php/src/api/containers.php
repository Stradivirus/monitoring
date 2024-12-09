<?php
use MongoDB\Client;

$client = new Client(getenv('MONGODB_URI'));
$collection = $client->monitoring->metrics;

// 최신 컨테이너 목록 가져오기
$containers = $collection->aggregate([
    [
        '$sort' => ['timestamp' => -1]
    ],
    [
        '$group' => [
            '_id' => '$container_id',
            'latestData' => ['$first' => '$$ROOT']
        ]
    ]
]);

$result = [];
foreach ($containers as $container) {
    $result[] = $container['latestData'];
}

echo json_encode($result);