<?php

namespace App\Kafka;

use MongoDB\Client;

class MetricConsumer
{
    private $consumer;
    private $collection;
    
    public function __construct()
    {
        // Kafka 설정
        $conf = new \RdKafka\Conf();
        $conf->set('metadata.broker.list', getenv('KAFKA_BROKER'));
        $conf->set('group.id', 'metric-consumer-group');
        $conf->set('auto.offset.reset', 'latest');

        // MongoDB 설정
        $client = new Client(getenv('MONGODB_URI'));
        $this->collection = $client->monitoring->metrics;

        // Consumer 초기화
        $this->consumer = new \RdKafka\KafkaConsumer($conf);
        $this->consumer->subscribe(['metrics.docker']);
    }

    public function start()
    {
        echo "Starting Kafka consumer...\n";
        
        while (true) {
            $message = $this->consumer->consume(120 * 1000); // 2분 타임아웃

            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    $this->processMessage($message);
                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    echo "No more messages; will wait for more\n";
                    continue 2;
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    echo "Timed out\n";
                    continue 2;
                default:
                    throw new \Exception($message->errstr(), $message->err);
            }
        }
    }

    private function processMessage($message)
    {
        $metric = json_decode($message->payload, true);
        
        // MongoDB 문서 형식으로 변환
        $document = [
            'container_id' => $metric['container_id'],
            'name' => $metric['name'],
            'image' => $metric['image'],
            'metrics' => [
                'cpu' => $metric['cpu_usage'],
                'memory' => $metric['memory_usage'],
                'network' => $metric['network_usage'],
                'disk' => $metric['disk_usage']
            ],
            'timestamp' => new \MongoDB\BSON\UTCDateTime($metric['timestamp'] * 1000),
            'status' => $metric['status'],
            'labels' => $metric['labels']
        ];

        try {
            $this->collection->insertOne($document);
            echo sprintf(
                "[%s] Stored metric for container: %s (CPU: %.2f%%, Memory: %.2f%%)\n",
                date('Y-m-d H:i:s'),
                $metric['name'],
                $metric['cpu_usage']['percentage'],
                $metric['memory_usage']['percentage']
            );
        } catch (\Exception $e) {
            error_log("Error storing metric: " . $e->getMessage());
        }
    }
}