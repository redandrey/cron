<?php declare(strict_types=1);

namespace Cron\Config;

/**
 * Class Config
 */
class Config
{
    /** @var string */
    protected $monitoringClientSockAddr;

    /** @var int */
    protected $monitoringClientIdleTimeout;

    /** @var string */
    protected $redisAddr;

    /** @var string */
    protected $redisJobQueueName;

    /** @var int */
    protected $jobManagerPoolSize;

    /**
     * Config constructor.
     */
    public function __construct()
    {
        $this->processEnvFiles();

        $this->monitoringClientSockAddr = getenv(EnvParams::MONITORING_CLIENT_SOCK_ADDR);
        $this->monitoringClientIdleTimeout = (int)getenv(EnvParams::MONITORING_CLIENT_IDLE_TIMEOUT);

        $this->redisAddr = getenv(EnvParams::REDIS_ADDR);
        $this->redisJobQueueName = getenv(EnvParams::REDIS_JOB_QUEUE_NAME);

        $this->jobManagerPoolSize = (int)getenv(EnvParams::JOB_MANAGER_POOL_SIZE);
    }

    protected function processEnvFiles(): void
    {
        $env = new \Symfony\Component\Dotenv\Dotenv();
        $env->load(PROJECT_ROOT . '/.env');
    }

    /**
     * @return string
     */
    public function getMonitoringClientSockAddr(): string
    {
        return $this->monitoringClientSockAddr;
    }

    /**
     * @return string
     */
    public function getRedisAddr(): string
    {
        return $this->redisAddr;
    }

    /**
     * @return string
     */
    public function getRedisJobQueueName(): string
    {
        return $this->redisJobQueueName;
    }

    /**
     * @return int
     */
    public function getJobManagerPoolSize(): int
    {
        return $this->jobManagerPoolSize;
    }

    /**
     * @return int
     */
    public function getMonitoringClientIdleTimeout(): int
    {
        return $this->monitoringClientIdleTimeout;
    }
}
