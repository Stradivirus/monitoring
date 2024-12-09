<?php

namespace App\Collectors;

use GuzzleHttp\Client;
use App\Kafka\MetricProducer;
use App\DB\MetricStorage;

class DockerCollector
{
    private $client;
    private $producer;
    private $storage;
    private $baseUrl;

    public function __construct()
    {
        $this->baseUrl = "http://unix:/var/run/docker.sock";
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'curl' => [
                CURLOPT_UNIX_SOCKET_PATH => '/var/run/docker.sock'
            ]
        ]);
        $this->producer = new MetricProducer();
        $this->storage = new MetricStorage();
    }

    public function collect()
    {
        try {
            $metrics = $this->collectMetrics();
            
            // Kafka로 전송
            $this->producer->send($metrics);
            
            // MongoDB에 저장
            $this->storage->store($metrics);
            
            return true;
        } catch (\Exception $e) {
            error_log("Error in metric collection: " . $e->getMessage());
            return false;
        }
    }

    private function collectMetrics()
    {
        $containers = $this->getContainers();
        $metrics = [];

        foreach ($containers as $container) {
            try {
                $stats = $this->getContainerStats($container['Id']);
                
                $metrics[] = [
                    'container_id' => $container['Id'],
                    'name' => ltrim($container['Names'][0], '/'),
                    'image' => $container['Image'],
                    'status' => $container['State'],
                    'cpu_usage' => $this->calculateCPUUsage($stats),
                    'memory_usage' => $this->calculateMemoryUsage($stats),
                    'network_usage' => $this->calculateNetworkUsage($stats),
                    'disk_usage' => $this->calculateDiskUsage($stats),
                    'timestamp' => time(),
                    'labels' => $container['Labels'] ?? []
                ];
            } catch (\Exception $e) {
                error_log("Error collecting metrics for container {$container['Id']}: " . $e->getMessage());
                continue;
            }
        }

        return $metrics;
    }

    private function getContainers()
    {
        $response = $this->client->get('/containers/json');
        return json_decode($response->getBody(), true);
    }

    private function getContainerStats($containerId)
    {
        $response = $this->client->get("/containers/{$containerId}/stats?stream=false");
        return json_decode($response->getBody(), true);
    }

    private function calculateCPUUsage($stats)
    {
        try {
            $cpuDelta = $stats['cpu_stats']['cpu_usage']['total_usage'] - 
                        $stats['precpu_stats']['cpu_usage']['total_usage'];
            $systemDelta = $stats['cpu_stats']['system_cpu_usage'] - 
                          $stats['precpu_stats']['system_cpu_usage'];
            $cpuCount = $stats['cpu_stats']['online_cpus'] ?? 1;

            if ($systemDelta > 0 && $cpuCount > 0) {
                return [
                    'percentage' => ($cpuDelta / $systemDelta) * $cpuCount * 100,
                    'cpu_count' => $cpuCount,
                    'total_usage' => $stats['cpu_stats']['cpu_usage']['total_usage'],
                    'system_usage' => $stats['cpu_stats']['system_cpu_usage']
                ];
            }
        } catch (\Exception $e) {
            error_log("Error calculating CPU usage: " . $e->getMessage());
        }
        
        return [
            'percentage' => 0,
            'cpu_count' => 0,
            'total_usage' => 0,
            'system_usage' => 0
        ];
    }

    private function calculateMemoryUsage($stats)
    {
        try {
            $memoryUsage = $stats['memory_stats']['usage'];
            $memoryLimit = $stats['memory_stats']['limit'];
            $memoryCache = $stats['memory_stats']['stats']['cache'] ?? 0;
            
            $actualUsage = $memoryUsage - $memoryCache;
            
            return [
                'used' => $actualUsage,
                'total' => $memoryLimit,
                'cache' => $memoryCache,
                'percentage' => ($actualUsage / $memoryLimit) * 100,
                'raw_usage' => $memoryUsage
            ];
        } catch (\Exception $e) {
            error_log("Error calculating memory usage: " . $e->getMessage());
            return [
                'used' => 0,
                'total' => 0,
                'cache' => 0,
                'percentage' => 0,
                'raw_usage' => 0
            ];
        }
    }

    private function calculateNetworkUsage($stats)
    {
        try {
            $networks = $stats['networks'] ?? [];
            $totalRx = 0;
            $totalTx = 0;
            $networkData = [];

            foreach ($networks as $networkName => $network) {
                $totalRx += $network['rx_bytes'];
                $totalTx += $network['tx_bytes'];
                
                $networkData[$networkName] = [
                    'rx_bytes' => $network['rx_bytes'],
                    'tx_bytes' => $network['tx_bytes'],
                    'rx_packets' => $network['rx_packets'],
                    'tx_packets' => $network['tx_packets'],
                    'rx_errors' => $network['rx_errors'] ?? 0,
                    'tx_errors' => $network['tx_errors'] ?? 0
                ];
            }

            return [
                'total' => [
                    'rx_bytes' => $totalRx,
                    'tx_bytes' => $totalTx
                ],
                'networks' => $networkData
            ];
        } catch (\Exception $e) {
            error_log("Error calculating network usage: " . $e->getMessage());
            return [
                'total' => [
                    'rx_bytes' => 0,
                    'tx_bytes' => 0
                ],
                'networks' => []
            ];
        }
    }

    private function calculateDiskUsage($stats)
    {
        try {
            $blkio_stats = $stats['blkio_stats'] ?? [];
            $disk_usage = [
                'read_bytes' => 0,
                'write_bytes' => 0,
                'read_ops' => 0,
                'write_ops' => 0
            ];

            if (isset($blkio_stats['io_service_bytes_recursive'])) {
                foreach ($blkio_stats['io_service_bytes_recursive'] as $stat) {
                    switch ($stat['op']) {
                        case 'Read':
                            $disk_usage['read_bytes'] += $stat['value'];
                            break;
                        case 'Write':
                            $disk_usage['write_bytes'] += $stat['value'];
                            break;
                    }
                }
            }

            return $disk_usage;
        } catch (\Exception $e) {
            error_log("Error calculating disk usage: " . $e->getMessage());
            return [
                'read_bytes' => 0,
                'write_bytes' => 0,
                'read_ops' => 0,
                'write_ops' => 0
            ];
        }
    }
}