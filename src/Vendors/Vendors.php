<?php
declare(strict_types=1);

namespace OVKAC\Vendors;

interface Vendors
{
    /** @throws \Exception */
    public function load(): Vendors;

    public function find(string $url): string;

}