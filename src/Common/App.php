<?php declare(strict_types=1);

namespace Cron\Common;

use Cron\Config\Config;
use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use React\EventLoop\Factory as LoopFactory;
use React\EventLoop\LoopInterface;

/**
 * Class App
 */
class App implements EventEmitterInterface, StateAwareInterface
{
    /**
     * EVENT EMITTER TRAIT
     */
    use EventEmitterTrait;

    /** @var string  */
    protected $name;

    /** @var float */
    protected $startedAt;

    /** @var Config */
    protected $config;

    /** @var LoopInterface */
    protected $loop;

    /** @var AppRoleInterface[] */
    protected $roles = [];

    /**
     * App constructor.
     *
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->startedAt = microtime(true);

        $this->config = new Config();
        $this->loop = LoopFactory::create();
    }

    public function run(): void
    {
        $this->loop->run();
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /** @inheritDoc */
    public function getState(): array
    {
        $state = [
            'name'       => $this->name,
            'host'       => gethostname(),
            'started_at' => $this->startedAt,
            'memory'     => [
                'current' => memory_get_usage(),
                'peak'    => memory_get_peak_usage(),
            ],
            'roles'      => [],
        ];

        foreach ($this->roles as $roleType => $roleInstance) {
            $role = $roleInstance::role();
            $dependsOn = array_map(
                static function (Role $requiredRole) { return $requiredRole->getType(); },
                $roleInstance::dependsOn()
            );

            $state['roles'][] = [
                'name'       => $role->getName(),
                'type'       => $role->getType(),
                'instance'   => get_class($roleInstance),
                'depends_on' => $dependsOn,
                'state'      => $roleInstance->getState(),
            ];
        }

        return $state;
    }

    /**
     * @return LoopInterface
     */
    public function getLoop(): LoopInterface
    {
        return $this->loop;
    }

    /**
     * @param string $concreteRoleClassName Concrete role class name
     */
    public function register(string $concreteRoleClassName): void
    {
        if (! Role::isConcreteRoleClass($concreteRoleClassName)) {
            throw new \InvalidArgumentException(sprintf(
                '`%s` is not an application role class',
                $concreteRoleClassName
            ));
        }

        /** @var AppRoleInterface $concreteRoleClassName */
        $role = $concreteRoleClassName::role();

        if ($this->hasRole($role)) {
            throw new \LogicException(sprintf(
                'Can not register class `%s`: the role `%s` already registered in the application',
                $concreteRoleClassName,
                $role->getName()
            ));
        }

        foreach ($concreteRoleClassName::dependsOn() as $dependency) {
            if (! $this->hasRole($dependency)) {
                throw new \LogicException(sprintf(
                    'Can not register `%s`: please register the `%s` first',
                    $role->getName(),
                    $dependency->getName()
                ));
            }
        }

        $roleInstance = new $concreteRoleClassName($this);
        $this->roles[$role->getType()] = $roleInstance;

        // TODO add logging
    }

    public function hasRole(Role $type): bool
    {
        return array_key_exists($type->getType(), $this->roles);
    }

    public function getRole(Role $type): AppRoleInterface
    {
        if (! $this->hasRole($type)) {
            throw new \InvalidArgumentException(sprintf(
                'Requested block `%s` not registered in the application',
                $type
            ));
        }

        return $this->roles[$type->getType()];
    }
}
