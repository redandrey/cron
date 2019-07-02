<?php declare(strict_types=1);

namespace Cron\Config;

/**
 * Class Config
 */
class Config
{
    /** @var string */
    protected $monitoringSockAddr;

    /**
     * Config constructor.
     */
    public function __construct()
    {
        $this->processEnvFiles();
        $this->monitoringSockAddr = getenv(EnvParams::MONITORING_SOCK_ADDR);
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
}
