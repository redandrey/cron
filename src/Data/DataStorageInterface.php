<?php declare(strict_types=1);

namespace Cron\Data;

use Cron\Common\AppRoleInterface;
use React\Promise\Promise;

/**
 * Interface DataInterface
 */
interface DataStorageInterface extends AppRoleInterface
{
    /**
     * @return Promise
     */
    public function getJob(): Promise;
}
