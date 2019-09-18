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

    private $ttl = 86400;

    public function __construct(
        Logger $logger,
        string $filePath,
        string $url
    )
    {
        $this->logger = $logger;
        $this->vendors = [];

        $this->url = $url;
        $this->filePath = $filePath;
    }

    public function find(string $url): string
    {
        $subject = strtolower(
            parse_url($url, PHP_URL_HOST).parse_url($url, PHP_URL_PATH)
        );
        foreach ($this->vendors['vendors'] as $vendor) {
            foreach ($vendor['AdServers'] as $pattern) {
                if (preg_match($pattern, $subject)) {
                    return $vendor['name'];
                }
            }
        }
        return '';
    }

    public function load(): Vendors
    {
        $this->logger->debug('Load vendors');

        if ( // re-fetch from remote
            !is_readable($this->filePath)
            || filemtime($this->filePath) < time() - $this->ttl
        ) {
            $this->logger->debug('Remote vendors');

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
            $vendors = $this->sanitize($vendors);
            file_put_contents(
                $this->filePath,
                json_encode($vendors, JSON_PRETTY_PRINT)
            );
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

        if (
            !is_array($vendors)
            || !array_key_exists('vendors', $vendors)
        ) {
            throw new \Exception(
                'Invalid vendors json'
            );
        }

        $this->vendors = $vendors;

        return $this;
    }

    private function sanitize(array $vendors): array
    {
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