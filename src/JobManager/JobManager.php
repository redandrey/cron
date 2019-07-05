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
    protected const NEW_JOBS_REQUEST_INTERVAL = 2; // TODO move to the config

    /** @var DataStorageRole */
    protected $dataStorage;

    /** @var TimerInterface */
    protected $pollTimer;

    /** @var bool  */
    protected $isPaused = false;

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
        $this->dataStorage = $this->app->getRole(Role::dataStorage());
        $this->processPool = new \SplObjectStorage();
        $this->processPoolSize = $this->config->getJobManagerPoolSize();

        $this->pollTimer = $this->app->getLoop()->addPeriodicTimer(
            self::NEW_JOBS_REQUEST_INTERVAL,
            function () {
                $this->onPollTimer();
            }
        );

        $this->app->on(DataStorageRole::EVENT_NEW_JOB, function ($data) {
            $this->onNewJobReceived($data);
        });
    }

    /** @inheritDoc */
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

    /**
     * @return int
     */
    protected function getFreeSlotsCount(): int
    {
        return $this->processPoolSize - $this->processPool->count();
    }

    /**
     *
     */
    protected function onPollTimer(): void
    {
        if ($this->isPaused) {
            return;
        }

        $this->emitNeedJobsEvent();
    }

    /**
     * @param $data
     */
    protected function onNewJobReceived($data): void
    {
        try {
            $job = new Job($data);
        } catch (\Throwable $e) {
            $this->log($e->getMessage());
            // TODO mark the job as corrupted and move it back to a storage
            return;
        }

        $this->attachProcess($job);
    }

    /**
     * @param Job $job
     */
    protected function attachProcess(Job $job): void
    {
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
        $this->detachProcess($process);
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
        $this->detachProcess($process);

        // TODO view the number of attempts to complete the job and return it to the data storage
    }

    /**
     * @param Process $process
     */
    protected function detachProcess(Process $process): void
    {
        // TODO restart the poll timer

        $this->processPool->detach($process);
        $this->emitNeedJobsEvent();

        gc_collect_cycles(); // without this line, memory slowly leaks. Don't understand why
    }

    protected function emitNeedJobsEvent(): void
    {
        $freeSlotsCount = $this->getFreeSlotsCount();
        if ($freeSlotsCount <= 0) {
            $this->log('no free slots');
            return;
        }

        $this->log('%d free slots in the process pool', $freeSlotsCount);


        $this->app->getLoop()->futureTick(function () {
            $this->app->emit(DataStorageRole::EVENT_NEED_JOBS, [$this->getFreeSlotsCount()]);
        });
    }
}
