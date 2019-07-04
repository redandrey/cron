<?php declare(strict_types=1);

namespace Cron;

use Cron\Common\App;
use Cron\Data\RedisDataStorage;
use Cron\JobManager\JobManager;
use Cron\Monitoring\MonitoringClient;

require __DIR__ . '/../vendor/autoload.php';

define('PROJECT_ROOT', __DIR__ . '/..');

$app = new App('Cron worker manager for server '. gethostname());
$app->register(MonitoringClient::class);
$app->register(RedisDataStorage::class);
$app->register(JobManager::class);

$app->run();
