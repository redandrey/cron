<?php declare(strict_types=1);

namespace Cron\Data;

use Clue\React\Redis\Client;
use Clue\React\Redis\Factory as RedisFactory;
use Cron\Common\AbstractAppRole;
use Cron\Common\Role;
use React\Promise\Deferred;
use React\Promise\Promise;

/**
 * Class RedisDataManager
 */
class RedisDataStorage extends AbstractAppRole implements DataStorageRole
{
    /** @var Client */
    protected $client;

    /** @var string */
    protected $jobQueueName;

    /** @var float */
    protected $jobQueueWasEmptyAt = 0.0;

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
    }

    /** @inheritDoc */
    public function getState(): array
    {
        return [
            'requests_processed' => $this->requestsProcessed,
            'requests_failed'    => $this->requestsFailed,
        ];
    }

    /** @inheritDoc */
    public function getJob(): Promise
    {
        $this->log('Job requested');

        $deferred = new Deferred();

        /** @var Promise $promise */
        /** @noinspection PhpUndefinedMethodInspection */
        $promise = $this->client->rpop($this->jobQueueName);
        $promise->then(function ($data) use ($deferred) {
            $this->requestsProcessed++;
            $deferred->resolve($data);
        });
        $promise->otherwise(function ($data) use ($deferred) {
            $this->log('Fail getting a job from the the queue `%s`: %s', $this->jobQueueName, $data);
            $this->requestsFailed++;
            $deferred->resolve();
        });

        return $deferred->promise();
    }
}
