<?php declare(strict_types=1);

namespace Cron\Common;

/**
 * Interface AppRoleInterface
 */
interface AppRoleInterface
{
    /**
     * @return Role
     */
    public static function role(): Role;

    /**
     * @return Role[]
     */
    public static function dependsOn(): array;

    /**
     * @return array
     */
    public function getState(): array;
}
