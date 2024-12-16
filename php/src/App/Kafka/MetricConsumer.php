<?php

namespace App\Kafka;

use MongoDB\Client;

class MetricConsumer
{
    private $consumer;
    private $collection;
    private const CONSUME_TIMEOUT = 60000; // 60초
    private const MAX_RETRY_ATTEMPTS = 3;
    
    public function __construct()
    {
        // Kafka 설정
        $conf = new \RdKafka\Conf();
        
        // 브로커 설정
        $conf->set('metadata.broker.list', getenv('KAFKA_BROKER'));
        $conf->set('group.id', 'metric-consumer-group');
        $conf->set('auto.offset.reset', 'latest');
        
        // 성능 최적화 설정
        $conf->set('enable.auto.commit', 'false');
        $conf->set('max.poll.interval.ms', '300000');
        $conf->set('session.timeout.ms', '30000');
        $conf->set('heartbeat.interval.ms', '3000');
        
        // 에러 핸들러 설정
        $conf->setErrorCb(function ($kafka, $err, $reason) {
            error_log(sprintf(
                "Kafka error: %s (reason: %s)",
                rd_kafka_err2str($err),
                $reason
            ));
        });

        // MongoDB 설정
        try {
            $client = new Client(getenv('MONGODB_URI'));
            $this->collection = $client->monitoring->metrics;
        } catch (\Exception $e) {
            error_log("MongoDB connection error: " . $e->getMessage());
            throw $e;
        }

        // Consumer 초기화
        $this->consumer = new \RdKafka\KafkaConsumer($conf);
        $this->consumer->subscribe(['metrics.docker']);
    }

    public function start()
    {
        echo "Starting Kafka consumer...\n";
        
        while (true) {
            try {
                $message = $this->consumer->consume(self::CONSUME_TIMEOUT);

                switch ($message->err) {
                    case RD_KAFKA_RESP_ERR_NO_ERROR:
                        $this->processMessage($message);
                        $this->consumer->commit($message);
                        break;

                    case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                        echo "Reached end of partition, waiting for more messages...\n";
                        continue 2;

                    case RD_KAFKA_RESP_ERR__TIMED_OUT:
                        echo "Consumer timeout, continuing...\n";
                        continue 2;

                    default:
                        error_log("Kafka error: " . rd_kafka_err2str($message->err));
                        sleep(1);
                        break;
                }
            } catch (\Exception $e) {
                error_log("Consumer error: " . $e->getMessage());
                sleep(1);
            }
        }
    }

    private function processMessage($message)
    {
        $retryCount = 0;
        $processed = false;

        while (!$processed && $retryCount < self::MAX_RETRY_ATTEMPTS) {
            try {
                $metric = json_decode($message->payload, true);
                
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

                $this->collection->insertOne($document);
                
                echo sprintf(
                    "[%s] Stored metric for container: %s (CPU: %.2f%%, Memory: %.2f%%)\n",
                    date('Y-m-d H:i:s'),
                    $metric['name'],
                    $metric['cpu_usage']['percentage'],
                    $metric['memory_usage']['percentage']
                );
                
                $processed = true;
            } catch (\Exception $e) {
                $retryCount++;
                error_log(sprintf(
                    "Error processing message (attempt %d/%d): %s",
                    $retryCount,
                    self::MAX_RETRY_ATTEMPTS,
                    $e->getMessage()
                ));
                
                if ($retryCount < self::MAX_RETRY_ATTEMPTS) {
                    sleep(1);
                }
            }
        }

        if (!$processed) {
            error_log("Failed to process message after " . self::MAX_RETRY_ATTEMPTS . " attempts");
        }
    }
}