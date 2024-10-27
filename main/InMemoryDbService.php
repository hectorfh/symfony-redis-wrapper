<?php

namespace AppBundle\Service;

use Predis\Collection\Iterator;
use Symfony\Component\Cache\Adapter\RedisAdapter;

/**
 * Class InMemoryDbService
 *
 * @package AppBundle\Service
 *
 */
class InMemoryDbService
{

    const SERVICE_NAME = "in_memory_db_service";

    const SESSIONS_DB = 0;
    const PERFORMANCE_DB = 1;
    const IAP_DB = 2;
    const USER_SESSIONS_DB = 3;
    const ASYNC_PROC_DB = 4;
    const CACHE_DB = 5;
    const TESTS_TIMER = 6;
    const THROTTLE_DB = 7;
    const FEATURE_TOGGLES_DB = 8;

    const REDIS_DBS = [ 1, 2, 3, 4, 5, 6, 7, 8, 9 ];

    const PREFIXES = [ 'sess', 'perf', 'iap', 'user_sess', 'async_proc', 'cache_db', 'tests_timer', 'throttle', 'ft' ];

    private $redisSessionsHost;

    private $redisSessionsPort;

    private $clients = [];

    public function __construct($redisSessionsHost, $redisSessionsPort)
    {
        $this->redisSessionsHost = $redisSessionsHost;
        $this->redisSessionsPort = $redisSessionsPort;
    }

    public function get($db, $key)
    {
        return $this->client($db)->get($this->prefixed($db, $key));
    }

    public function set($db, $key, $value, $ttl)
    {
        $this->client($db)->setex($this->prefixed($db, $key), $ttl, $value);
    }

    public function delete($db, $key)
    {
        $this->client($db)->del($this->prefixed($db, $key));
    }

    public function scan($db, $pattern, $delete = false)
    {

        $keys = [];
        $pattern = $this->prefixed($db, $pattern.'*');
        foreach (new Iterator\Keyspace($this->client($db), $pattern) as $key) {
            $keys[] = $key;
        }

        if (empty($keys)) {
            return [];
        }

        $values = $this->client($db)->mget($keys);

        $keyValues = [];
        foreach ($values as $i => $v) {
            $key = $keys[$i];
            $keyValues[$key] = $v;
            if ($delete) {
                $this->client($db)->del($key);
            }
        }

        return $keyValues;
    }

    private function client($db)
    {
        if (!array_key_exists($db, $this->clients)) {
            $this->clients[$db] =
                RedisAdapter::createConnection(
                    'redis://'.$this->redisSessionsHost.':'.$this->redisSessionsPort.'/'.self::REDIS_DBS[$db]);
        }
        return $this->clients[$db];
    }

    private function prefixed($db, $key) {
        return self::PREFIXES[$db].'_'.$key;
    }

}
