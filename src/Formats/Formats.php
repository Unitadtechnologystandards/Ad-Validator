<?php
declare(strict_types=1);

namespace OVKAC\Formats;

interface Formats extends \JsonSerializable
{
    /** @throws \Exception */
    public function load(): Formats;

    public function has(string $format): bool;

    public function get(string $format): ?Format;
}