<?php
declare(strict_types=1);

namespace OVKAC\State;

interface StateParameters extends \JsonSerializable
{
    public function format(): string;

    public function setFormat(string $format): void;

    public function setSource(string $source): void;

    public function setAssets(array $assets): void;

    public function setArchiveName(string $archiveName): void;

    public function setWorkDir(string $workDir): void;

    public function workDir(): string;

    public function source(): string;

    public function archiveName(): string;

    public function assets(): array;

    public function setCustomInit(int $value): void;

    public function setCustomSub(int $value): void;

    public function customInit(): int;

    public function customSub(): int;

    public function customWidth(): int;

    public function customHeight(): int;

    public function setCustomWidth(int $value): void;

    public function setCustomHeight(int $value): void;

    public function setIabMode(int $param): void;

    public function iabMode(): int;

    public function setIframeMode(string $param): void;

    public function setTcfVendors(string $url): void;

    public function tcfVendors(): string;

    public function iframeMode(): string;
}