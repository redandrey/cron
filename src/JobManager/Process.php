<?php declare(strict_types=1);

namespace Cron\JobManager;

/**
 * Class Process
 */
class Process extends \React\ChildProcess\Process
{
    /** @var int */
    protected $id;

    /** @var Job */
    protected $job;

    /** @var float */
    protected $startedAt;

    /**
     * Process constructor.
     *
     * @param Job $job
     * @param int $id
     */
    public function __construct(Job $job, int $id)
    {
        $this->job = $job;
        $this->id = $id;
        $this->startedAt= microtime(true);
        parent::__construct('exec sleep 3');
    }

    /**
     * @return Job
     */
    public function getJob(): Job
    {
        return $this->job;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return float
     */
    public function getStartedAt(): float
    {
        return $this->startedAt;
    }

    /**
     * @return float
     */
    public function getExecutionTime(): float
    {
        return microtime(true) - $this->startedAt;
    }
}
