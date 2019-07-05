<?php declare(strict_types=1);

namespace Cron\Common;

/**
 * Interface StateAwareInterface
 */
interface StateAwareInterface
{
    /**
     * @return array
     */
    public function getState(): array;
}
