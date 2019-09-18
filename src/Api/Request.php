<?php
declare(strict_types=1);

namespace OVKAC\Api;

interface Request
{
    public function isXhr(): bool;

    public function posted(
        string $key,
        ?string $default
    );

    public function got(
        string $key,
        ?string $default
    );

    public function rawInput();
}