<?php
declare(strict_types=1);
error_reporting(E_ALL | E_STRICT);

use OVKAC\Logger\SysLogger;
use OVKAC\Pdf\DefaultPdf;
use OVKAC\State\DefaultState;

require_once __DIR__ . '/../vendor/autoload.php';

$logger = new SysLogger(
    __DIR__.'/../var/log/app.log',
    substr(md5(microtime()), 0, 8)
);

$state = new DefaultState($logger);
$state->load();

try {
    (new DefaultPdf(
        sprintf(
            '%s://%s',
            $_SERVER['REQUEST_SCHEME'],
            $_SERVER['HTTP_HOST']
        ),
        __DIR__ . '/..',
        $state,
        $logger
    ))->create();
}
catch (\Error $e) {
    echo 'Allgemeiner Fehler';
}

