<?php
declare(strict_types=1);

namespace OVKAC\State;

use OVKAC\Formats\Format;

final class DefaultStateResults implements StateResults
{
    /** @var \DateTime */
    private $created;
    private $workDir;
    private $format;
    private $profiling;
    private $archiveName;
    private $previewUrl;

    public function __construct()
    {
        $this->created = null;
        $this->workDir = '';
        $this->format = null;
        $this->profiling = null;
        $this->archiveName = '';
        $this->previewUrl = 'about:blank';
    }

    public function setWorkDir(string $workDir): void
    {
        $this->workDir = $workDir;
    }

    public function workDir(): string
    {
        return $this->workDir;
    }

    public function setFormat(?Format $format): void
    {
        $this->format = $format;
    }

    public function setArchiveName(string $archiveName): void
    {
        $this->archiveName = $archiveName;
    }

    public function create(): void
    {
        $this->created = new \DateTime;
    }

    public function setProfiling(array $profiling): void
    {
        $this->profiling = $profiling;
    }

    public function setPreviewUrl(string $previewUrl): void
    {
        $this->previewUrl = $previewUrl;
    }

    public function jsonSerialize()
    {
        return [
            'created' => $this->created !== null
                ? $this->created->format('d.m.Y H:i:s')
                : '',
            'workDir' => $this->workDir,
            'archiveName' => $this->archiveName,
            'format' => $this->format,
            'previewUrl' => $this->previewUrl,
            'profiling' => $this->profiling,
        ];
    }
}