<?php

namespace App\Controllers;

class MetricsController {
    private $kafkaConsumer;
    
    public function __construct() {
        $conf = new \RdKafka\Conf();
        $conf->set('metadata.broker.list', getenv('KAFKA_BROKER'));
        $conf->set('group.id', 'dashboard-consumer-' . uniqid());
        $conf->set('auto.offset.reset', 'latest');
        
        $this->kafkaConsumer = new \RdKafka\KafkaConsumer($conf);
        $this->kafkaConsumer->subscribe(['metrics.docker']);
    }
    
    public function streamMetrics() {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        
        while (true) {
            $message = $this->kafkaConsumer->consume(1000);
            
            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    $data = json_decode($message->payload, true);
                    echo "data: " . json_encode($data) . "\n\n";
                    flush();
                    break;
                    
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    echo ": heartbeat\n\n";  // Keep connection alive
                    flush();
                    break;
                    
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    continue;
                    
                default:
                    echo "data: " . json_encode(['error' => $message->errstr()]) . "\n\n";
                    flush();
                    break;
            }
        }
    }
}