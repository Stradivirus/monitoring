<?php

namespace App\Controllers;

class MetricsController {
    private $kafkaConsumer;
    
    public function __construct() {
        $conf = new \RdKafka\Conf();
        $conf->set('metadata.broker.list', getenv('KAFKA_BROKER'));
        $conf->set('group.id', 'dashboard-consumer-group'); // 고정 그룹 ID 사용
        $conf->set('auto.offset.reset', 'latest');
        
        $this->kafkaConsumer = new \RdKafka\KafkaConsumer($conf);
        $this->kafkaConsumer->subscribe(['metrics.docker']);
    }
    
    public function streamMetrics() {
        // SSE 헤더 설정
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        
        while (true) {
            try {
                $message = $this->kafkaConsumer->consume(1000);
                
                switch ($message->err) {
                    case RD_KAFKA_RESP_ERR_NO_ERROR:
                        $rawData = json_decode($message->payload, true);
                        
                        // 데이터 구조 변환
                        $formattedData = [
                            'container_id' => $rawData['container_id'],
                            'name' => $rawData['name'],
                            'image' => $rawData['image'],
                            'status' => $rawData['status'],
                            'metrics' => [
                                'cpu' => [
                                    'percentage' => $rawData['cpu_usage']['percentage'],
                                    'cpu_count' => $rawData['cpu_usage']['cpu_count'],
                                    'total_usage' => $rawData['cpu_usage']['total_usage'],
                                    'system_usage' => $rawData['cpu_usage']['system_usage']
                                ],
                                'memory' => [
                                    'used' => $rawData['memory_usage']['used'],
                                    'total' => $rawData['memory_usage']['total'],
                                    'cache' => $rawData['memory_usage']['cache'],
                                    'percentage' => $rawData['memory_usage']['percentage']
                                ],
                                'network' => $rawData['network_usage'],
                                'disk' => $rawData['disk_usage']
                            ],
                            'timestamp' => $rawData['timestamp'],
                            'labels' => $rawData['labels'] ?? []
                        ];
                        
                        echo "data: " . json_encode($formattedData) . "\n\n";
                        flush();
                        break;
                        
                    case RD_KAFKA_RESP_ERR__TIMED_OUT:
                        // 60초마다 heartbeat 전송
                        echo ": heartbeat\n\n";
                        flush();
                        break;
                        
                    case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                        // 파티션 끝에 도달하면 계속 진행
                        continue 2;
                        
                    default:
                        // 에러 발생 시 클라이언트에 알림
                        $errorData = [
                            'error' => true,
                            'message' => $message->errstr()
                        ];
                        echo "data: " . json_encode($errorData) . "\n\n";
                        flush();
                        
                        // 심각한 에러인 경우 잠시 대기 후 재시도
                        if ($message->err !== RD_KAFKA_RESP_ERR__TIMED_OUT) {
                            sleep(5);
                        }
                        break;
                }
            } catch (\Exception $e) {
                // 예외 발생 시 클라이언트에 알림
                $errorData = [
                    'error' => true,
                    'message' => 'Internal server error: ' . $e->getMessage()
                ];
                echo "data: " . json_encode($errorData) . "\n\n";
                flush();
                
                // 로그 기록
                error_log("StreamMetrics Error: " . $e->getMessage());
                
                // 잠시 대기 후 재시도
                sleep(5);
            }
        }
    }
}