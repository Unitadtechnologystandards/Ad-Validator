<?php
declare(strict_types=1);

namespace OVKAC\Formats;

use OVKAC\Logger\Logger;

final class DefaultFormats implements Formats
{
    private $logger;
    private $list;
    private $version;
    private $fetched;
    private $url;
    private $filePath;

//    private $ttl = 86400;

    public function __construct(
        Logger $logger,
        string $filePath,
        string $url
    )
    {
        $this->logger = $logger;
        $this->list = [];
        $this->version = null;

        $this->url = $url;
        $this->filePath = $filePath;
    }

    public function has(string $format): bool
    {
        return isset($this->list[$format]);
    }

    public function get(string $format): ?Format
    {
        return $this->list[$format] ?? null;
    }

    public function load(): Formats
    {
        $this->logger->debug('Load formats');

//        if (
//            !is_readable($this->filePath)
//            || filemtime($this->filePath) < time() - $this->ttl
//        ) {
//            // TODO if filemtime older than X, re-fetch from remote
//        }

        if (is_readable($this->filePath)) {
            $this->fetched = filemtime($this->filePath);
            $formats = @json_decode(
                file_get_contents($this->filePath),
                true
            );
            if (json_last_error() > 0 && strlen(json_last_error_msg()) > 0) {
                throw new \Exception('Decoding formats json failed: ' . json_last_error_msg());
            }
        }
        else {
            throw new \Exception('Formats json missing');
        }
        if (!is_array($formats) || !array_key_exists('formats', $formats)) {
            throw new \Exception('Invalid formats json');
        }

        $this->version = $formats['version'] ?? '?';

        foreach ($formats['formats'] as $id => $format) {
            $this->list[(string)$id] = new DefaultFormat(
                (string)$id,
                (string)($format['description'] ?? $id),
                (int)($format['initialLoad'] ?? -1),
                (int)($format['subLoad'] ?? -1),
                (int)($format['width'] ?? -1),
                (int)($format['height'] ?? -1)
            );
        }

        $this->list['0'] = new DefaultFormat(
            '0',
            'Benutzer',
            100,
            100,
            640,
            480
        );

        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'version' => $this->version,
            'fetched' => $this->fetched,
            'list' => array_values($this->list),
        ];
    }
}