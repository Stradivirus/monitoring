<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Kafka\MetricConsumer;

// MongoDB 연결 테스트
try {
    $client = new MongoDB\Client(getenv('MONGODB_URI'));
    $client->monitoring->command(['ping' => 1]);
    echo "MongoDB connection successful\n";
} catch (Exception $e) {
    echo "MongoDB connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Kafka 연결 테스트
try {
    $conf = new RdKafka\Conf();
    $conf->set('metadata.broker.list', getenv('KAFKA_BROKER'));
    $producer = new RdKafka\Producer($conf);
    $producer->addBrokers(getenv('KAFKA_BROKER'));
    echo "Kafka connection successful\n";
} catch (Exception $e) {
    echo "Kafka connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Starting metric consumer service...\n";

// 컨슈머 시작
$consumer = new MetricConsumer();
$consumer->start();