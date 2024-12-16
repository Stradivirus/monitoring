<?php

namespace App\Collectors;

use MongoDB\Client;

class MetricScheduler
{
    private $collector;
    private $interval;
    private $lastRunTime;
    private $mongoClient;

    public function __construct(DockerCollector $collector, int $interval = 60) // 기본값 60초로 변경
    {
        $this->collector = $collector;
        $this->interval = $interval;
        $this->lastRunTime = 0;
        $this->mongoClient = new Client(getenv('MONGODB_URI'));
        
        // TTL 인덱스 생성 (3일)
        $this->setupTTLIndex();
    }

    private function setupTTLIndex()
    {
        try {
            $collection = $this->mongoClient->monitoring->metrics;
            
            // 기존 TTL 인덱스 확인
            $indexes = iterator_to_array($collection->listIndexes());
            $ttlIndexExists = false;
            
            foreach ($indexes as $index) {
                if (isset($index['expireAfterSeconds'])) {
                    $ttlIndexExists = true;
                    break;
                }
            }
            
            // TTL 인덱스가 없으면 생성
            if (!$ttlIndexExists) {
                $collection->createIndex(
                    ['timestamp' => 1],
                    [
                        'expireAfterSeconds' => 259200, // 3일 (3 * 24 * 60 * 60)
                        'background' => true
                    ]
                );
                echo "Created TTL index for automatic data cleanup after 3 days\n";
            }
        } catch (\Exception $e) {
            error_log("Failed to setup TTL index: " . $e->getMessage());
        }
    }

    public function start()
    {
        echo "Starting metric collection scheduler (Interval: {$this->interval} seconds)\n";
        
        while (true) {
            try {
                $startTime = microtime(true);
                
                // 메트릭 수집 실행
                $this->collector->collect();
                
                $endTime = microtime(true);
                $executionTime = round($endTime - $startTime, 2);
                
                echo sprintf(
                    "[%s] Metrics collected successfully (Execution time: %s seconds)\n",
                    date('Y-m-d H:i:s'),
                    $executionTime
                );

                // 다음 실행까지 남은 시간 계산
                $sleepTime = max(0, $this->interval - $executionTime);
                
                if ($sleepTime > 0) {
                    echo "Waiting {$sleepTime} seconds until next collection...\n";
                    sleep($sleepTime);
                }
                
            } catch (\Exception $e) {
                error_log(sprintf(
                    "[%s] Error collecting metrics: %s\n",
                    date('Y-m-d H:i:s'),
                    $e->getMessage()
                ));
                
                // 에러 발생 시에도 정해진 간격은 지키도록 함
                sleep($this->interval);
            }
        }
    }

    public function cleanupOldData()
    {
        try {
            $collection = $this->mongoClient->monitoring->metrics;
            $threeDaysAgo = new \MongoDB\BSON\UTCDateTime((time() - 259200) * 1000);
            
            $result = $collection->deleteMany([
                'timestamp' => ['$lt' => $threeDaysAgo]
            ]);
            
            echo sprintf(
                "[%s] Cleaned up %d old metric records\n",
                date('Y-m-d H:i:s'),
                $result->getDeletedCount()
            );
            
        } catch (\Exception $e) {
            error_log("Failed to cleanup old data: " . $e->getMessage());
        }
    }
}