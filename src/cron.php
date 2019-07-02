<?php declare(strict_types=1);

namespace Cron;

use Cron\Config\Config;
use Cron\Monitoring\MonitoringClient;
use Cron\State\GlobalState;
use React\EventLoop\Factory;

require __DIR__ . '/../vendor/autoload.php';

define('PROJECT_ROOT', __DIR__ . '/..');

$config = new Config();
$globalState = new GlobalState();

$loop = Factory::create();

$monitoringClient = new MonitoringClient($config, $globalState);
$monitoringClient->start($loop);

echo $config->getMonitoringSockAddr();
echo "\n\n";

$loop->run();

echo "EXIT\n";
