<?php declare(strict_types=1);

namespace Cron\JobManager;

/**
 * Class Job
 */
class Job
{
    /** @var string */
    protected $id;

    /** @var self[] */
    //protected $nested = [];

    /**
     * Job constructor.
     *
     * @param string $id
     */
    public function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
}
