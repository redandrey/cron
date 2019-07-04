<?php declare(strict_types=1);

namespace Cron\JobManager;

use Cron\Common\AbstractAppRole;
use Cron\Common\Role;
use Cron\Data\DataStorageInterface;
use React\EventLoop\TimerInterface;

/**
 * Class JobQueueManager
 */
class JobManager extends AbstractAppRole implements JobManagerInterface
{
    protected const POLL_INTERVAL = 1;

    /** @var DataStorageInterface */
    protected $dataStorage;

    /** @var TimerInterface */
    protected $pollTimer;

    /** @var bool  */
    protected $isPaused = false;

    /**
     * TODO this is a temporary solution. Remove when `Timer->pause()` will be realized
     *
     * @var bool
     */
    protected $isInProgress = false;

    public static function role(): Role
    {
        return Role::jobManager();
    }

    public static function dependsOn(): array
    {
        return [
            Role::dataStorage(),
        ];
    }

    protected function init(): void
    {
        $loop = $this->app->getLoop();
        $this->dataStorage = $this->app->getRole(Role::dataStorage());

        $this->pollTimer = $loop->addPeriodicTimer(self::POLL_INTERVAL, function () {
            $this->onPollTimer();
        });
    }

    protected function onPollTimer(): void
    {
        if ($this->isPaused || $this->isInProgress) {
            return;
        }

        // TODO pause poll timer
        $this->isInProgress = true;

        $promise = $this->dataStorage->getJob();
        $promise->then(function ($data) {
            if ($data !== null) {
                $this->fireNewJobEvent($data);
            }
        });
        $promise->always(function () {
            $this->isInProgress = false;
            // TODO resume poll timer
        });
    }

    protected function fireNewJobEvent($job): void
    {
        $this->log('New job `%s` received from a data storage', $job);
    }
}
