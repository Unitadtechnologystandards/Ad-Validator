<?php
declare(strict_types=1);
error_reporting(E_ALL | E_STRICT);
//ini_set('display_errors', '1');

use OVKAC\Api\DefaultApi;
use OVKAC\Api\JsonResponse;
use OVKAC\Api\HttpRequest;
use OVKAC\Logger\SysLogger;

require_once __DIR__.'/../../vendor/autoload.php';

$logger = new SysLogger(
    __DIR__.'/../../var/log/app.log',
    substr(md5(microtime()), 0, 8)
);

(new DefaultApi(
    __DIR__ . '/../..',
    $logger,
    new HttpRequest(
        $logger
    ),
    new JsonResponse(
        $logger
    )
))->execute();