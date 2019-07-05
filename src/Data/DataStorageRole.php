<?php declare(strict_types=1);

namespace Cron\Data;

use Cron\Common\AppRoleInterface;

/**
 * Interface DataInterface
 */
interface DataStorageRole extends AppRoleInterface
{
    public const EVENT_NEW_JOB = 'new_job';
    public const EVENT_NEED_JOBS = 'need_jobs';
}
