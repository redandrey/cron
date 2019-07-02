<?php declare(strict_types=1);

namespace Cron\Monitoring;

use Cron\Config\Config;
use Cron\State\GlobalState;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Http\Response;
use React\Http\Server as HttpServer;
use React\Socket\Server as SocketServer;

/**
 * Class Client
 */
class MonitoringClient
{
    /** @var Config */
    protected $config;

    /** @var GlobalState */
    protected $globalState;

    /** @var SocketServer */
    protected $socket;

    /** @var HttpServer */
    protected $httpServer;

    /** @var bool */
    protected $isStarted = false;

    /** @var TimerInterface */
    protected $visitCheckerTimer;

    /**
     * Client constructor.
     *
     * @param Config        $config
     * @param GlobalState   $globalState
     */
    public function __construct(
        Config $config,
        GlobalState $globalState
    ) {
        $this->config = $config;
        $this->globalState = $globalState;
    }

    /**
     * @return bool
     */
    public function isStarted(): bool
    {
        return $this->isStarted;
    }

    public function start(LoopInterface $loop): void
    {
        if ($this->isStarted) {
            throw new \RuntimeException('Monitoring client already started');
        }

        $socket = new SocketServer($this->config->getMonitoringSockAddr(), $loop);

        $server = new HttpServer(function (ServerRequestInterface $request) {
            if (! $this->isRequestAllowed()) {
                return new Response(401);
            }

            $this->globalState->getMonitoringClientState()->registerSupervisorVisit();
            echo sprintf("Monitoring client: request from %s\n", $request->getServerParams()['REMOTE_ADDR']);

            switch ($request->getUri()->getPath()) {
                case '/state':
                    return $this->handlerGetState();
                    break;
                case '/time':
                    return $this->handlerGetTime();
                default:
                    return new Response(404);
            }
        });

        $server->listen($socket);

        $this->isStarted = true;
        $this->globalState->getMonitoringClientState()->reset();
        $this->visitCheckerTimer = $this->createVisitCheckerTimer($loop);
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     *
     * @return bool
     */
    protected function isRequestAllowed(): bool
    {
        return true;
    }

    /**
     * @return Response
     */
    protected function handlerGetState(): Response
    {
        return new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['state' => 'OK'], JSON_PRETTY_PRINT)
        );
    }

    /**
     * @return Response
     */
    protected function handlerGetTime(): Response
    {
        return new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['microtime' => \microtime(true)], JSON_PRETTY_PRINT)
        );
    }

    protected function createVisitCheckerTimer(LoopInterface $loop): TimerInterface
    {
        $state = $this->globalState->getMonitoringClientState();
        $checker = static function () use ($state) {
            if ($state->isUnattended()) {
                echo sprintf(
                    "Monitoring client is unattended since %0.3f\n",
                    $state->getLastVisitMicrotime()
                );
            }
        };

        return $loop->addPeriodicTimer(
            $state->getUnattendedTimeout(),
            $checker
        );
    }
}
