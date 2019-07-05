<?php declare(strict_types=1);

namespace Cron\Data;

use Clue\React\Redis\Client;
use Clue\React\Redis\Factory as RedisFactory;
use Cron\Common\AbstractAppRole;
use Cron\Common\Role;
use React\Promise\Promise;

/**
 * Class RedisDataManager
 */
class RedisDataStorage extends AbstractAppRole implements DataStorageRole
{
    protected const READ_EMPTY_JOB_QUEUE_TIMEOUT = 1; // TODO move to the config

    /** @var Client */
    protected $client;

    /** @var string */
    protected $jobQueueName;

    /** @var float */
    protected $jobQueueWasEmptyAt;

    /** @var int  */
    protected $requestsProcessed = 0;

    /** @var int  */
    protected $requestsFailed = 0;

    /** @inheritDoc */
    public static function role(): Role
    {
        return Role::dataStorage();
    }

    /** @inheritDoc */
    public static function dependsOn(): array
    {
        return [];
    }

    /** @inheritDoc */
    protected function init(): void
    {
        $factory = new RedisFactory($this->loop);
        $this->client = $factory->createLazyClient($this->config->getRedisAddr()); // TODO change to `createClient`
        $this->jobQueueName = $this->config->getRedisJobQueueName();

        $this->app->on(self::EVENT_NEED_JOBS, function (int $howMany = 1) {
            $this->getJobsFromQueue($howMany);
        });
    }

    /** @inheritDoc */
    public function getState(): array
    {
        return [
            'requests_processed' => $this->requestsProcessed,
            'requests_failed'    => $this->requestsFailed,
        ];
    }

    /**
     * @param int $howMany  How many jobs required
     */
    protected function getJobsFromQueue(int $howMany): void
    {
        $this->log('%d new job(s) requested', $howMany);

        // do not try to read empty queue too frequently
        if ($this->isJobQueueWasEmptyRecently()) {
            return;
        }

        /** @var Promise $promise */
        /** @noinspection PhpUndefinedMethodInspection */
        $promise = $this->client->rpop($this->jobQueueName);
        $promise->then(function ($data) use ($howMany) {
            $this->requestsProcessed++;
            if ($data === null) {
                // queue is empty
                $this->jobQueueWasEmptyAt = microtime(true);
                $this->log('job queue is empty');
                return;
            }
            $howMany--;
            $this->log('new job fetched, %d job(s) left to be fetched', $howMany);

            $this->app->emit(self::EVENT_NEW_JOB, [$data]);
            if ($howMany > 0) {
                $this->getJobsFromQueue($howMany);
            }
        });
        $promise->otherwise(function ($data) {
            $this->requestsFailed++;
            $this->log('Fail getting a job from the the queue `%s`: %s', $this->jobQueueName, $data);
        });
    }

    /**
     * @return bool
     */
    protected function isJobQueueWasEmptyRecently(): bool
    {
        return $this->jobQueueWasEmptyAt !== null
            && self::READ_EMPTY_JOB_QUEUE_TIMEOUT > microtime(true) - $this->jobQueueWasEmptyAt;
    }
}
