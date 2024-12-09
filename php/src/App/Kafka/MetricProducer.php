<?php

namespace App\Kafka;

class MetricProducer
{
    private $producer;
    private $topic;

    public function __construct()
    {
        $conf = new \RdKafka\Conf();
        $conf->set('metadata.broker.list', getenv('KAFKA_BROKER'));

        $this->producer = new \RdKafka\Producer($conf);
        $this->topic = $this->producer->newTopic('metrics.docker');
    }

    public function send(array $metrics)
    {
        foreach ($metrics as $metric) {
            $this->topic->produce(
                RD_KAFKA_PARTITION_UA,
                0,
                json_encode($metric),
                $metric['container_id']
            );
        }
        
        $this->producer->flush(1000);
    }
}