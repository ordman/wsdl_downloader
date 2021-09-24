<?php

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Ordman\WsdlDownloader\WsdlParser;

require_once 'vendor/autoload.php';

$logger = new Logger('app');
$logger->pushHandler(new StreamHandler('php://output', Logger::INFO));
(new WsdlParser('/tmp', $logger))->handle($argv);
