<?php
declare(strict_types=1);

namespace OVKAC\Api;

use OVKAC\Formats\Formats;
use OVKAC\Logger\Logger;
use OVKAC\State\State;

final class JsonResponse implements Response
{
    private $logger;

    public function __construct(
        Logger $logger
    )
    {
        $this->logger = $logger;
    }

    public function render(
        State $state,
        Formats $formats
    ): void
    {
        $json = json_encode(
            [
                'state' => $state->data(),
                'formats' => $formats,
                'status' => $state->status()
            ],
            JSON_PRETTY_PRINT
        );

        header('Content-Type: application/json');
        echo $json;
    }

    public function badRequest(string $string)
    {
        header('HTTP/1.1 400 Bad Request');
        echo $string;
        exit;
    }
}