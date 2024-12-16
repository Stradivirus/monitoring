<?php

namespace App\DB;

use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\BSON\UTCDateTime;

class MetricStorage
{
    private Collection $collection;
    private const TTL_SECONDS = 259200; // 3일 (3 * 24 * 60 * 60)

    public function __construct()
    {
        try {
            $client = new Client(getenv('MONGODB_URI'));
            $this->collection = $client->monitoring->metrics;
            $this->ensureTTLIndex();
        } catch (\Exception $e) {
            error_log("Failed to initialize MetricStorage: " . $e->getMessage());
            throw $e;
        }
    }

    private function ensureTTLIndex(): void
    {
        try {
            $indexes = iterator_to_array($this->collection->listIndexes());
            $ttlIndexExists = false;

            foreach ($indexes as $index) {
                if (isset($index['expireAfterSeconds'])) {
                    $ttlIndexExists = true;
                    break;
                }
            }

            if (!$ttlIndexExists) {
                $this->collection->createIndex(
                    ['timestamp' => 1],
                    [
                        'expireAfterSeconds' => self::TTL_SECONDS,
                        'background' => true,
                        'name' => 'ttl_cleanup_index'
                    ]
                );
                error_log("Created TTL index for automatic cleanup after 3 days");
            }
        } catch (\Exception $e) {
            error_log("Failed to ensure TTL index: " . $e->getMessage());
            throw $e;
        }
    }

    public function store(array $metrics): bool
    {
        try {
            $documents = array_map(function ($metric) {
                return [
                    'container_id' => $metric['container_id'],
                    'name' => $metric['name'],
                    'image' => $metric['image'],
                    'metrics' => [
                        'cpu' => $metric['cpu_usage'],
                        'memory' => $metric['memory_usage'],
                        'network' => $metric['network_usage'],
                        'disk' => $metric['disk_usage']
                    ],
                    'timestamp' => new UTCDateTime($metric['timestamp'] * 1000),
                    'status' => $metric['status'],
                    'labels' => $metric['labels']
                ];
            }, $metrics);

            $result = $this->collection->insertMany($documents);
            error_log(sprintf(
                "[%s] Stored %d metrics successfully",
                date('Y-m-d H:i:s'),
                count($documents)
            ));

            return $result->getInsertedCount() === count($documents);
        } catch (\Exception $e) {
            error_log("Failed to store metrics: " . $e->getMessage());
            throw $e;
        }
    }

    public function getMetrics(string $containerId, int $from, int $to): array
    {
        try {
            $cursor = $this->collection->find(
                [
                    'container_id' => $containerId,
                    'timestamp' => [
                        '$gte' => new UTCDateTime($from * 1000),
                        '$lte' => new UTCDateTime($to * 1000)
                    ]
                ],
                [
                    'sort' => ['timestamp' => -1],
                    'limit' => 60 // 최근 60개 데이터로 제한
                ]
            );

            return iterator_to_array($cursor);
        } catch (\Exception $e) {
            error_log("Failed to get metrics for container {$containerId}: " . $e->getMessage());
            throw $e;
        }
    }

    public function cleanupOldData(): int
    {
        try {
            $threeDaysAgo = new UTCDateTime((time() - self::TTL_SECONDS) * 1000);
            $result = $this->collection->deleteMany([
                'timestamp' => ['$lt' => $threeDaysAgo]
            ]);

            $deletedCount = $result->getDeletedCount();
            error_log(sprintf(
                "[%s] Cleaned up %d old metric records",
                date('Y-m-d H:i:s'),
                $deletedCount
            ));

            return $deletedCount;
        } catch (\Exception $e) {
            error_log("Failed to cleanup old data: " . $e->getMessage());
            throw $e;
        }
    }

    public function getLatestMetrics(): array
    {
        try {
            $result = $this->collection->aggregate([
                [
                    '$sort' => ['timestamp' => -1]
                ],
                [
                    '$group' => [
                        '_id' => '$container_id',
                        'latest' => ['$first' => '$$ROOT']
                    ]
                ]
            ]);

            return array_map(function($doc) {
                return $doc['latest'];
            }, iterator_to_array($result));
        } catch (\Exception $e) {
            error_log("Failed to get latest metrics: " . $e->getMessage());
            throw $e;
        }
    }

    public function getMetricsByTimeRange(string $containerId, int $minutes = 60): array
    {
        try {
            $fromTime = new UTCDateTime((time() - ($minutes * 60)) * 1000);
            $cursor = $this->collection->find(
                [
                    'container_id' => $containerId,
                    'timestamp' => ['$gte' => $fromTime]
                ],
                [
                    'sort' => ['timestamp' => -1]
                ]
            );

            return iterator_to_array($cursor);
        } catch (\Exception $e) {
            error_log("Failed to get metrics by time range for container {$containerId}: " . $e->getMessage());
            throw $e;
        }
    }
}