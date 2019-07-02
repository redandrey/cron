<?php declare(strict_types=1);

namespace Cron\State;

use Cron\Config\EnvParams;

/**
 * Class MonitoringState
 */
class MonitoringClientState
{
    /** @var int */
    protected $unattendedTimeout;

    /** @var float */
    protected $lastVisitMicrotime;

    /** @var int */
    protected $visitsCount = 0;

    /**
     * MonitoringState constructor.
     *
     * @param float $appStartAt
     */
    public function __construct(float $appStartAt)
    {
        $this->lastVisitMicrotime = $appStartAt;
        $this->unattendedTimeout = (int)getenv(EnvParams::MONITORING_IDLE_TIMEOUT);
    }

    public function reset(): void
    {
        $this->visitsCount = 0;
        $this->lastVisitMicrotime = \microtime(true);
    }

    public function registerSupervisorVisit(): void
    {
        $this->visitsCount++;
        $this->lastVisitMicrotime = \microtime(true);
    }

    /**
     * @return int
     */
    public function getUnattendedTimeout(): int
    {
        return $this->unattendedTimeout;
    }

    /**
     * @return float
     */
    public function getLastVisitMicrotime(): float
    {
        return $this->lastVisitMicrotime;
    }

    /**
     * @return int
     */
    public function getVisitsCount(): int
    {
        return $this->visitsCount;
    }

    public function isUnattended(): bool
    {
        return $this->unattendedTimeout < \microtime(true) - $this->lastVisitMicrotime;
    }
}
