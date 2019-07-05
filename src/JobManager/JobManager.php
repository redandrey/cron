<?php declare(strict_types=1);

namespace Cron\JobManager;

use Cron\Common\AbstractAppRole;
use Cron\Common\Role;
use Cron\Data\DataStorageRole;
use React\EventLoop\TimerInterface;

/**
 * Class JobQueueManager
 */
class JobManager extends AbstractAppRole implements JobManagerRole
{
    protected const POLL_INTERVAL = 1; // TODO move to the config

    /** @var DataStorageRole */
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
    protected $isReadingData = false;

    /** @var \SplObjectStorage<Process> */
    protected $processPool;

    /** @var int */
    protected $processPoolSize;

    /** @var int  */
    protected $processedJobsCounter = 0;

    /** @inheritDoc */
    public static function role(): Role
    {
        return Role::jobManager();
    }

    /** @inheritDoc */
    public static function dependsOn(): array
    {
        return [
            Role::dataStorage(),
        ];
    }

    /** @inheritDoc */
    protected function init(): void
    {
        $loop = $this->app->getLoop();
        $this->dataStorage = $this->app->getRole(Role::dataStorage());
        $this->processPool = new \SplObjectStorage();
        $this->processPoolSize = $this->config->getJobManagerPoolSize();

        $this->pollTimer = $loop->addPeriodicTimer(self::POLL_INTERVAL, function () {
            $this->onPollTimer();
        });
    }

    public function getState(): array
    {
        $state = [
            'jobs_processed'    => $this->processedJobsCounter,
            'pool_size_max'     => $this->processPoolSize,
            'pool_size_current' => $this->processPool->count(),
            'current_processes' => [],
        ];

        /** @var Process $process */
        foreach ($this->processPool as $process) {
            $state['current_processes'][] = [
                'id'             => $process->getId(),
                'job_id'         => $process->getJob()->getId(),
                'started_at'     => $process->getStartedAt(),
                'execution_time' => $process->getExecutionTime(),
            ];
        }

        return $state;
    }

    protected function onPollTimer(): void
    {
        if ($this->isPaused || $this->isReadingData) {
            return;
        }

        $freeSlotsCount = $this->getFreeSlotsCount();
        if ($freeSlotsCount === 0) {
            $this->log('no free slots');
            return;
        }

        $this->log('%d free slots in the process pool', $freeSlotsCount);

        // TODO pause poll timer
        $this->isReadingData = true;

        $promise = $this->dataStorage->getJob();
        $promise->then(function ($data) {
            if ($data !== null) {
                $this->startNewProcess($data);
            }
        });
        $promise->always(function () {
            $this->isReadingData = false;
            // TODO resume poll timer
        });
    }

    protected function getFreeSlotsCount(): int
    {
        return $this->processPoolSize - $this->processPool->count();
    }

    protected function startNewProcess($jobId): void
    {
        $job = new Job($jobId);
        $process = new Process($job, ++$this->processedJobsCounter);

        $process->on('exit', function (int $exitCode, ?int $terminationSignal) use ($process) {
            if ($terminationSignal === null && $exitCode === 0) {
                $this->processFinishedSuccessfully($process);
            } else {
                $this->processFailed($process);
            }
        });

        $this->processPool->attach($process);
        $process->start($this->app->getLoop());

        $this->log(
            'New process #%s began to execute the job `%s`',
            $process->getId(),
            $process->getJob()->getId()
        );
    }

    /**
     * @param Process $process
     */
    protected function processFinishedSuccessfully(Process $process): void
    {
        $this->log(
            'Process #%s successfully completed the job `%s`',
            $process->getId(),
            $process->getJob()->getId()
        );
        $this->processPool->detach($process);
    }

    /**
     * @param Process $process
     */
    protected function processFailed(Process $process): void
    {
        $this->log(
            'Process #%s FAILED to complete the job `%s`',
            $process->getId(),
            $process->getJob()->getId()
        );
        $this->processPool->detach($process);

        // TODO view the number of attempts to complete the job and return it to the data storage
    }
}
