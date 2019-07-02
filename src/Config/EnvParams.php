<?php declare(strict_types=1);

namespace Cron\Config;

/**
 * Class EnvParams
 */
class EnvParams
{
    public const MONITORING_SOCK_ADDR    = 'MONITORING_SOCK_ADDR';
    public const MONITORING_IDLE_TIMEOUT = 'MONITORING_IDLE_TIMEOUT';

    /**
     * This class can't be instantiated
     */
    private function __construct()
    {
    }
}
