<?php
declare(strict_types=1);

namespace OVKAC\Formats;

final class DefaultFormat implements Format
{
    private $id;
    private $description;
    private $maxSizeInit;
    private $maxSizeSubload;
    private $width;
    private $height;

    public function __construct(
        string $id,
        string $description,
        int $maxSizeInit,
        int $maxSizeSubload,
        int $width,
        int $height
    )
    {
        $this->id = $id;
        $this->description = $description;
        $this->maxSizeInit = $maxSizeInit;
        $this->maxSizeSubload = $maxSizeSubload;
        $this->width = $width;
        $this->height = $height;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'maxSizeInit' => $this->maxSizeInit,
            'maxSizeSubload' => $this->maxSizeSubload,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }
}