<?php declare(strict_types=1);

namespace Cron\Common;

use Cron\Config\Config;
use React\EventLoop\LoopInterface;

/**
 * Class AbstractAppBlock
 */
abstract class AbstractAppRole implements AppRoleInterface
{
    /** @var Role */
    protected $blockType;

    /** @var Config */
    protected $config;

    /** @var LoopInterface */
    protected $loop;

    /** @var App */
    protected $app;

    /**
     * Client constructor.
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app    = $app;
        $this->config = $app->getConfig();
        $this->loop   = $app->getLoop();

        $this->init();
    }

    /**
     * Start application block
     */
    abstract protected function init(): void;

    /**
     * @TODO make a logger
     *
     * @param string $message
     * @param mixed  ...$params
     */
    protected function log(string $message, ...$params): void
    {
        $message = vsprintf($message, $params);
        try {
            $now = (new \DateTimeImmutable())->format('H:m:s');
        } catch (\Exception $e) {
            $now = '??:??:??';
        }

        echo sprintf("%s | %20s | %s\n", $now, static::role()->getName(), $message);
    }
}
