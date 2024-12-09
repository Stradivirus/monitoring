<?php

namespace App\Collectors;

class MetricScheduler
{
    private $collector;
    private $interval;

    public function __construct(DockerCollector $collector, int $interval = 600)
    {
        $this->collector = $collector;
        $this->interval = $interval; // 기본 10분(600초)
    }

    public function start()
    {
        while (true) {
            try {
                $this->collector->collect();
                echo "Metrics collected at: " . date('Y-m-d H:i:s') . "\n";
            } catch (\Exception $e) {
                error_log("Error collecting metrics: " . $e->getMessage());
            }
            
            sleep($this->interval);
        }
    }
}