<?php declare(strict_types=1);

namespace Cron\Config;

/**
 * Class Config
 */
class Config
{
    /** @var string */
    protected $monitoringSockAddr;

    /** @var string */
    protected $redisAddr;

    /** @var string */
    protected $redisJobQueueName;

    /**
     * Config constructor.
     */
    public function __construct()
    {
        $this->processEnvFiles();
        $this->monitoringSockAddr = getenv(EnvParams::MONITORING_SOCK_ADDR);
        $this->redisAddr = getenv(EnvParams::REDIS_ADDR);
        $this->redisJobQueueName = getenv(EnvParams::REDIS_JOB_QUEUE_NAME);
    }

    protected function processEnvFiles(): void
    {
        $env = new \Symfony\Component\Dotenv\Dotenv();
        $env->load(PROJECT_ROOT . '/.env');
    }

    /**
     * @return string
     */
    public function getMonitoringSockAddr(): string
    {
        return $this->monitoringSockAddr;
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
}
