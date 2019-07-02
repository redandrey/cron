<?php declare(strict_types=1);

namespace Cron\State;

/**
 * Class GlobalState
 */
class GlobalState
{
    /** @var float */
    protected $appStartedMicrotime;

    /** @var MonitoringClientState */
    protected $monitoringClientState;

    /**
     * GlobalState constructor.
     */
    public function __construct()
    {
        $this->appStartedMicrotime = \microtime(true);
        $this->monitoringClientState = new MonitoringClientState($this->appStartedMicrotime);
    }

    /**
     * @return float
     */
    public function getAppStartedMicrotime(): float
    {
        return $this->appStartedMicrotime;
    }

    /**
     * @return MonitoringClientState
     */
    public function getMonitoringClientState(): MonitoringClientState
    {
        return $this->monitoringClientState;
    }


}
