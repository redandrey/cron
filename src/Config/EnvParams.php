<?php declare(strict_types=1);

namespace Cron\Config;

/**
 * Class EnvParams
 */
class EnvParams
{
    public const MONITORING_CLIENT_SOCK_ADDR    = 'MONITORING_CLIENT_SOCK_ADDR';
    public const MONITORING_CLIENT_IDLE_TIMEOUT = 'MONITORING_CLIENT_IDLE_TIMEOUT';

    public const REDIS_ADDR = 'REDIS_ADDR';
    public const REDIS_JOB_QUEUE_NAME = 'REDIS_JOB_QUEUE_NAME';

    public const JOB_MANAGER_POOL_SIZE = 'JOB_MANAGER_POOL_SIZE';

    /**
     * This class can't be instantiated
     */
    private function __construct()
    {
    }
}
