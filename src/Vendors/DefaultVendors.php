<?php
declare(strict_types=1);

namespace OVKAC\Vendors;

use OVKAC\Logger\Logger;

final class DefaultVendors implements Vendors
{
    private $logger;
    private $vendors;
    private $url;
    private $filePath;
    private $info;
    private $name;
    private $ttl;

    public function __construct(
        Logger $logger,
        string $name,
        string $filePath,
        string $url,
        int $ttl = 86400,
        bool $info = false
    )
    {
        $this->logger = $logger;
        $this->vendors = [];

        $this->url = $url;
        $this->name = $name;
        $this->filePath = $filePath;

        $this->ttl = $ttl;
        $this->info = $info;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function version(): int
    {
        return (int)($this->vendors['vendorListVersion'] ?? -1);
    }

    public function find(string $url): ?array
    {
        $subject = strtolower(
            parse_url($url, PHP_URL_HOST).parse_url($url, PHP_URL_PATH)
        );
        foreach ($this->vendors['vendors'] as $vendor) {
            foreach ($vendor['AdServers'] as $pattern) {
                if (preg_match($pattern, $subject)) {
                    return [
                        'id' => $vendor['id'],
                        'name' => $vendor['name']
                    ];
                }
            }
        }
        return null;
    }

    public function has(int $id): bool
    {
        foreach ($this->vendors['vendors'] as $vendor) {
            if ((int)($vendor['id'] ?? -1) === $id) {
                return true;
            };
        }
        return false;
    }

    public function load(): Vendors
    {
        $this->logger->debug('Load vendors: '.$this->name);

        if ( // re-fetch from remote
            $this->ttl === 0
            || !is_readable($this->filePath)
            || filemtime($this->filePath) < time() - $this->ttl
        ) {
            $this->logger->debug('Remote vendors: '.$this->url);

            $vendors = @json_decode(
                (string)file_get_contents($this->url),
                true
            );
            if (
                json_last_error() > 0
                && strlen(json_last_error_msg()) > 0
            ) {
                throw new \Exception(
                    'Decoding vendors json failed: ' . json_last_error_msg()
                );
            }
            if (
                !is_array($vendors)
                || !array_key_exists('vendors', $vendors)
            ) {
                throw new \Exception(
                    'Invalid vendors json'
                );
            }

            if ($this->info) {
                $vendors = $this->parseInfo($vendors);
            }

            if ($this->ttl > 0) {
                file_put_contents(
                    $this->filePath,
                    json_encode(
                        $vendors,
                        JSON_PRETTY_PRINT
                    )
                );
            }
        }
        elseif (is_readable($this->filePath)) {
            $vendors = @json_decode(
                file_get_contents($this->filePath),
                true
            );
            if (
                json_last_error() > 0
                && strlen(json_last_error_msg()) > 0
            ) {
                throw new \Exception(
                    'Decoding vendors json failed: ' . json_last_error_msg()
                );
            }
        }
        else {
            throw new \Exception(
                'Vendors json missing'
            );
        }

        if (!is_array($vendors)) {
            throw new \Exception(
                'Invalid vendors json'
            );
        }

        $this->vendors = $vendors;

        return $this;
    }

    private function parseInfo(array $vendors): array
    {
        if (
            !array_key_exists('vendors', $vendors)
            || !is_array($vendors['vendors'])
        ) {
            throw new \Exception(
                'Invalid vendors json'
            );
        }

        $sanitized = [];
        foreach ($vendors['vendors'] as $nr => $vendor) {
            if (
                isset($vendor['AdServers'])
                && is_array($vendor['AdServers'])
                && count($vendor['AdServers']) > 0
            ) {
                $temp = [];
                foreach ($vendor['AdServers'] as $k => $match) {
                    $x = preg_split("/\s/", $match);
                    if (count($x) > 1) {
                        foreach ($x as $y) {
                            $temp[] = $y;
                        }
                    }
                    else {
                        $temp[] = $match;
                    }
                }

                $temp = array_filter($temp, function($match) {
                    if (strpos($match, '.') === false) {
                        return false;
                    }
                    if (substr(trim($match), -1) === '.') {
                        return false;
                    }
                    return true;
                });

                $temp = array_values(
                    array_unique(
                        array_map(
                            function ($match) {
                                $match = trim($match);
                                $match = strtolower($match);
                                $match = rtrim($match, ')/,');
                                $match = str_replace('http://', '', $match);
                                $match = str_replace('https://', '', $match);
                                if ($match[0] === '.') {
                                    $match = '*' . $match;
                                }
                                $match = str_replace('.', '\.', $match);
                                $match = str_replace('*', '[a-z0-9\.\-]+', $match);
                                return '#^'.$match.'.*#';
                            },
                            $temp
                        )
                    )
                );

                if (count($temp) > 0) {
                    $vendor['AdServers'] = $temp;
                    $sanitized[] = $vendor;
                }
            }
        }
        $vendors['vendors'] = $sanitized;
        return $vendors;
    }
}