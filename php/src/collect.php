<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Collectors\DockerCollector;
use App\Collectors\MetricScheduler;

// MongoDB 연결 테스트
try {
    $client = new MongoDB\Client(getenv('MONGODB_URI'));
    $client->monitoring->command(['ping' => 1]);
    echo "MongoDB 연결 성공\n";
} catch (Exception $e) {
    echo "MongoDB 연결 실패: " . $e->getMessage() . "\n";
    exit(1);
}

// Kafka 연결 테스트
try {
    $conf = new RdKafka\Conf();
    $conf->set('metadata.broker.list', getenv('KAFKA_BROKER'));
    $producer = new RdKafka\Producer($conf);
    $producer->addBrokers(getenv('KAFKA_BROKER'));
    echo "Kafka 연결 성공\n";
} catch (Exception $e) {
    echo "Kafka 연결 실패: " . $e->getMessage() . "\n";
    exit(1);
}

// Docker 소켓 테스트
try {
    $client = new GuzzleHttp\Client([
        'base_uri' => 'http://unix:/var/run/docker.sock',
        'curl' => [
            CURLOPT_UNIX_SOCKET_PATH => '/var/run/docker.sock'
        ]
    ]);
    $response = $client->get('/containers/json');
    echo "Docker 소켓 연결 성공\n";
} catch (Exception $e) {
    echo "Docker 소켓 연결 실패: " . $e->getMessage() . "\n";
    exit(1);
}

$collector = new DockerCollector();
$scheduler = new MetricScheduler($collector);
echo "메트릭 수집 시작...\n";
$scheduler->start();