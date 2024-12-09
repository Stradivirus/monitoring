<?php

namespace App\DB;

use MongoDB\Client;

class MetricStorage
{
    private $collection;

    public function __construct()
    {
        $client = new Client(getenv('MONGODB_URI'));
        $this->collection = $client->monitoring->metrics;
    }

    public function store(array $metrics)
    {
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
                'timestamp' => new \MongoDB\BSON\UTCDateTime($metric['timestamp'] * 1000),
                'status' => $metric['status'],
                'labels' => $metric['labels']
            ];
        }, $metrics);

        return $this->collection->insertMany($documents);
    }

    public function getMetrics($containerId, $from, $to)
    {
        return $this->collection->find([
            'container_id' => $containerId,
            'timestamp' => [
                '$gte' => new \MongoDB\BSON\UTCDateTime($from * 1000),
                '$lte' => new \MongoDB\BSON\UTCDateTime($to * 1000)
            ]
        ]);
    }
}