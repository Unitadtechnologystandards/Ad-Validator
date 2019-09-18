<?php
declare(strict_types=1);

namespace OVKAC\Api;

use OVKAC\Logger\Logger;

final class HttpRequest implements Request
{
    private $logger;

    public function __construct(
        Logger $logger
    )
    {
        $this->logger = $logger;
    }

    public function isXhr(): bool
    {
        return (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
        );
    }

    public function posted(
        string $key,
        ?string $default
    )
    {
        return $_POST[$key] ?? $default;
    }

    public function got(
        string $key,
        ?string $default
    )
    {
        return $_GET[$key] ?? $default;
    }

    public function rawInput()
    {
        return file_get_contents('php://input');
    }
}