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

    /** @var LoopInterface */
    protected $globalLoop;

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
            throw new \RuntimeException('Monitoring client: already started');
        }

        $this->globalLoop = $loop;
        $socket = new SocketServer($this->config->getMonitoringSockAddr(), $loop);

        $server = new HttpServer(function (ServerRequestInterface $request) {
            if (! $this->isRequestAllowed()) {
                return new Response(401);
            }

            $this->globalState->getMonitoringClientState()->registerSupervisorVisit();
            $this->log('request from %s', $request->getServerParams()['REMOTE_ADDR']);

            $this->restartVisitCheckerTimer();

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
        $this->log('STARTED at %s', $this->config->getMonitoringSockAddr());

        $this->globalState->getMonitoringClientState()->reset();
        $this->startVisitCheckerTimer();
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

    protected function startVisitCheckerTimer(): void
    {
        if ($this->visitCheckerTimer instanceof TimerInterface) {
            $this->log('??????');
            return;
        }

        $state = $this->globalState->getMonitoringClientState();
        $checker = function () use ($state) {
            if ($state->isUnattended()) {
                $this->log(
                    'I am unattended since %0.3f',
                    $state->getLastVisitMicrotime()
                );
            }
        };

        $this->visitCheckerTimer = $this->globalLoop->addPeriodicTimer(
            $state->getUnattendedTimeout(),
            $checker
        );
        $this->log('visit checker timer STARTed');
    }

    protected function cancelVisitCheckerTimer(): void
    {
        if (! $this->visitCheckerTimer instanceof TimerInterface) {
            return;
        }

        $this->globalLoop->cancelTimer($this->visitCheckerTimer);
        $this->visitCheckerTimer = null;
        $this->log('visit checker timer STOPped');
    }

    protected function restartVisitCheckerTimer(): void
    {
        $this->cancelVisitCheckerTimer();
        $this->startVisitCheckerTimer();
    }

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

        echo sprintf("%s | Monitoring client | %s\n", $now, $message);
    }
}
