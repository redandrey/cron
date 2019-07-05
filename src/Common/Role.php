<?php declare(strict_types=1);

namespace Cron\Common;

use Cron\Data\DataStorageRole;
use Cron\JobManager\JobManagerRole;
use Cron\Monitoring\MonitoringClientRole;

/**
 * Class BlockType
 */
class Role
{
    protected const BASE_ROLE_INTERFACE = AppRoleInterface::class;

    protected const MONITORING_CLIENT = MonitoringClientRole::class;
    protected const DATA_STORAGE      = DataStorageRole::class;
    protected const JOB_MANAGER       = JobManagerRole::class;

    protected const ROLES = [
        self::MONITORING_CLIENT => 'Monitoring Client',
        self::DATA_STORAGE      => 'Data Storage',
        self::JOB_MANAGER       => 'Job Manager',
    ];

    /** @var string */
    protected $type;

    public static function isExists(string $type): bool
    {
        return array_key_exists($type, self::ROLES);
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function getName(): string
    {
        return self::ROLES[$this->type];
    }

    /**
     * @param string $className
     *
     * @return bool
     */
    public static function isConcreteRoleClass(string $className): bool
    {
        try {
            $reflection = new \ReflectionClass($className);

            return $reflection->isInstantiable()
                && in_array(self::BASE_ROLE_INTERFACE, class_implements($className), true);
        } catch (\ReflectionException $e) {
            // $className value is not a class name
            return false;
        }
    }

    /**
     * @param string $type
     */
    protected function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * @return Role
     */
    public static function monitoringClient(): self
    {
        return new self(self::MONITORING_CLIENT);
    }

    /**
     * @return Role
     */
    public static function dataStorage(): self
    {
        return new self(self::DATA_STORAGE);
    }

    /**
     * @return Role
     */
    public static function jobManager(): self
    {
        return new self(self::JOB_MANAGER);
    }
}
