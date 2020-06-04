<?php
declare(strict_types=1);

namespace OVKAC\Vendors;

interface TcfVendors
{
    /** @throws \Exception */
    public function load(): TcfVendors;

    public function find(int $id): array;

    public function names(): array;

}