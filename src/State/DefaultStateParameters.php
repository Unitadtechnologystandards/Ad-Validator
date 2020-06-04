<?php
declare(strict_types=1);

namespace OVKAC\State;

final class DefaultStateParameters implements StateParameters
{
    private $format;
    private $customInit;
    private $customSub;
    private $archiveName;
    private $source;
    private $assets;
    private $workDir;
    private $customWidth;
    private $customHeight;
    private $iabMode;
    private $iframeMode;
    private $tcfVendors;

    public function __construct()
    {
        $this->archiveName = '';
        $this->customInit = 100;
        $this->customSub = 100;
        $this->customWidth = 640;
        $this->customHeight = 480;
        $this->format = '';
        $this->source = '';
        $this->assets = [];
        $this->workDir = '';
        $this->iabMode = 500;
        $this->iframeMode = 'friendly';
        $this->tcfVendors = '';
    }

    public function format(): string
    {
        return $this->format;
    }

    public function setFormat(string $format): void
    {
        $this->format = $format;
    }

    public function setCustomInit(int $value): void
    {
        $this->customInit = $value;
    }

    public function setCustomSub(int $value): void
    {
        $this->customSub = $value;
    }

    public function setCustomWidth(int $value): void
    {
        $this->customWidth = $value;
    }

    public function setCustomHeight(int $value): void
    {
        $this->customHeight = $value;
    }

    public function setIabMode(int $value): void
    {
        $this->iabMode = $value;
    }

    public function setIframeMode(string $value): void
    {
        $this->iframeMode = $value;
    }

    public function setTcfVendors(string $url): void
    {
        $this->tcfVendors = $url;
    }

    public function customInit(): int
    {
        return $this->customInit;
    }

    public function customSub(): int
    {
        return $this->customSub;
    }

    public function setSource(string $source): void
    {
        $this->source = $source;
    }

    public function setAssets(array $assets): void
    {
        $this->assets = $assets;
    }

    public function setArchiveName(string $archiveName): void
    {
        $this->archiveName = $archiveName;
    }

    public function setWorkDir(string $workDir): void
    {
        $this->workDir = $workDir;
    }

    public function workDir(): string
    {
        return $this->workDir;
    }

    public function source(): string
    {
        return $this->source;
    }

    public function archiveName(): string
    {
        return $this->archiveName;
    }

    public function assets(): array
    {
        return $this->assets;
    }

    public function customWidth(): int
    {
        return $this->customWidth;
    }

    public function customHeight(): int
    {
        return $this->customHeight;
    }

    public function iabMode(): int
    {
        return $this->iabMode;
    }

    public function iframeMode(): string
    {
        return $this->iframeMode;
    }

    public function tcfVendors(): string
    {
        return $this->tcfVendors;
    }

    public function jsonSerialize()
    {
        return [
            'format' => $this->format,
            'customInit' => $this->customInit,
            'customSub' => $this->customSub,
            'customWidth' => $this->customWidth,
            'customHeight' => $this->customHeight,
            'archiveName' => $this->archiveName,
            'source' => $this->source,
            'assets' => $this->assets,
            'iabMode' => $this->iabMode,
            'iframeMode' => $this->iframeMode,
            'tcfVendors' => $this->tcfVendors,
            'workDir' => $this->workDir,
        ];
    }
}