<?php
declare(strict_types=1);

namespace OVKAC\Vendors;

interface Vendors
{
    /** @throws \Exception */
    public function load(): Vendors;

    public function find(string $url): ?array;

    public function has(int $id): bool;

    public function name(): string;

    public function version(): int;
}