<?php

namespace App\Kafka;

class MetricProducer
{
    private $producer;
    private $topic;
    private const MAX_RETRY_ATTEMPTS = 3;
    private const FLUSH_TIMEOUT_MS = 10000; // 10초

    public function __construct()
    {
        $conf = new \RdKafka\Conf();
        
        // 브로커 설정
        $conf->set('metadata.broker.list', getenv('KAFKA_BROKER'));
        
        // 성능 최적화 설정
        $conf->set('compression.codec', 'snappy');
        $conf->set('queue.buffering.max.messages', '100000');
        $conf->set('queue.buffering.max.ms', '5000');
        $conf->set('batch.num.messages', '10000');
        
        // 에러 핸들러 설정
        $conf->setErrorCb(function ($kafka, $err, $reason) {
            error_log(sprintf(
                "Kafka producer error: %s (reason: %s)",
                rd_kafka_err2str($err),
                $reason
            ));
        });

        // 전송 완료 콜백
        $conf->setDrMsgCb(function ($kafka, $message) {
            if ($message->err) {
                error_log(sprintf(
                    "Message delivery failed: %s",
                    rd_kafka_err2str($message->err)
                ));
            }
        });

        $this->producer = new \RdKafka\Producer($conf);
        $this->producer->addBrokers(getenv('KAFKA_BROKER'));
        $this->topic = $this->producer->newTopic('metrics.docker');
    }

    public function send(array $metrics): bool
    {
        $success = true;
        $retryCount = 0;

        while ($retryCount < self::MAX_RETRY_ATTEMPTS) {
            try {
                foreach ($metrics as $metric) {
                    $this->topic->produce(
                        RD_KAFKA_PARTITION_UA,
                        0,
                        json_encode($metric),
                        $metric['container_id']
                    );
                }

                // 메시지 큐 비우기
                $result = $this->producer->flush(self::FLUSH_TIMEOUT_MS);
                
                if ($result === RD_KAFKA_RESP_ERR_NO_ERROR) {
                    echo sprintf(
                        "[%s] Successfully sent %d metrics to Kafka\n",
                        date('Y-m-d H:i:s'),
                        count($metrics)
                    );
                    break;
                }

                error_log("Failed to flush messages, retrying...");
                $retryCount++;
                
                if ($retryCount < self::MAX_RETRY_ATTEMPTS) {
                    sleep(1);
                }
            } catch (\Exception $e) {
                error_log(sprintf(
                    "Error sending metrics (attempt %d/%d): %s",
                    $retryCount + 1,
                    self::MAX_RETRY_ATTEMPTS,
                    $e->getMessage()
                ));
                
                $retryCount++;
                $success = false;
                
                if ($retryCount < self::MAX_RETRY_ATTEMPTS) {
                    sleep(1);
                }
            }
        }

        if ($retryCount >= self::MAX_RETRY_ATTEMPTS) {
            error_log("Failed to send metrics after " . self::MAX_RETRY_ATTEMPTS . " attempts");
            return false;
        }

        return $success;
    }
}