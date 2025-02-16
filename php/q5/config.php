<?php
require 'vendor/autoload.php';

$redis = new Predis\Client([
    'scheme' => 'tcp',
    'host'   => '127.0.0.1',
    'port'   => 6379,
]);

$logger = new Monolog\Logger('file_processor');
$logger->pushHandler(new Monolog\Handler\StreamHandler('logs/processing.log'));

define('UPLOAD_DIR', __DIR__.'/uploads');
define('PROCESSED_DIR', __DIR__.'/processed');
define('FAILED_DIR', __DIR__.'/failed');

@mkdir(UPLOAD_DIR, 0755, true);
@mkdir(PROCESSED_DIR, 0755, true);
@mkdir(FAILED_DIR, 0755, true);