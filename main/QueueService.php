<?php

namespace AppBundle\Service;

use Symfony\Component\Cache\Adapter\RedisAdapter;

/**
 * Class QueueService
 *
 * @package AppBundle\Service
 *
 */
class QueueService
{

    const SERVICE_NAME = "queue_service";

    const QUEUE_PREFIX = 'queue_';
    const ASYNC_PROC_QUEUE = 0;
    const PERFORMANCE_QUEUE = 1;
    const ERROR_QUEUE = 2;
    const TEST_QUEUE = 3;
    const ASYNC_REPORT_QUEUE = 4;
    const REDIS_DBS = [ 5, 2, 3, 1, 5 ];
    const LIMITS = [1000, 10000, 1000, 1, 1000];

    // We don't need to pay the cost of trimming the queue everytime
    // we push something.
    // TRIM_PROBS are the inverses of the probabilities of trimming the
    // queue.
    // Example: 2 means a prob of 1/2, 97 means a probabiblity of 1/97.
    const TRIM_PROBS = [97, 97, 97, 1, 97];

    private $redisSessionsHost;

    private $redisSessionsPort;

    private $clients = [];

    public function __construct($redisSessionsHost, $redisSessionsPort)
    {
        $this->redisSessionsHost = $redisSessionsHost;
        $this->redisSessionsPort = $redisSessionsPort;
    }

    /**
     *
     * @param queue
     * @return array
     *
     */
    public function pop($queue)
    {
        $result = $this->client($queue)->lpop(self::QUEUE_PREFIX.$queue);
        return json_decode($result, true);
    }

    /**
     *
     * @param queue
     * @param int $timeout
     * @return array
     *
     */
    public function bpop($queue, $timeout)
    {
        $result = $this->client($queue)->blpop(self::QUEUE_PREFIX.$queue, $timeout);
        return json_decode($result[1], true);
    }

    /**
     *
     * @param queue
     * @param array $array
     *
     */
    public function push($queue, $array)
    {
        $json = json_encode($array);
        $this->client($queue)->rpush(self::QUEUE_PREFIX.$queue, $json);

        if (round(microtime(true) * 1000) % self::TRIM_PROBS[$queue] == 0) {
            $this->client($queue)->ltrim(self::QUEUE_PREFIX.$queue, 0, self::LIMITS[$queue]-1);
        }
    }

    /**
     *
     * @param queue
     *
     */
    public function clear($queue)
    {
        $this->client($queue)->del(self::QUEUE_PREFIX.$queue);
    }

    private function client($queue)
    {
        if (!array_key_exists($queue, $this->clients)) {
            $this->clients[$queue] =
                RedisAdapter::createConnection(
                    'redis://'.$this->redisSessionsHost.':'.$this->redisSessionsPort.'/'.self::REDIS_DBS[$queue]);
        }
        return $this->clients[$queue];
    }

}
