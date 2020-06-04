<?php
declare(strict_types=1);

namespace OVKAC\Vendors;

use OVKAC\Logger\Logger;

final class DefaultTcfVendors implements TcfVendors
{
    private $logger;
    private $vendors;
    private $url;
    private $filePath;
    private $fileName;
    private $customVendorsUrl;

    private $ttl = 86400;

    public function __construct(
        Logger $logger,
        string $customVendorsUrl,
        string $filePath,
        string $fileName,
        string $url
    )
    {
        $this->logger = $logger;
        $this->vendors = [];
        $this->customVendorsUrl = $customVendorsUrl;
        $this->url = $url;
        $this->filePath = $filePath;
        $this->fileName = $fileName;
    }

    public function names(): array
    {
        $names = [];
        /** @var Vendors $vendor */
        foreach ($this->vendors as $vendor) {
            $names[] = [
                'name' => $vendor->name(),
                'version' => $vendor->version()
            ];
        }
        return $names;
    }

    public function find(int $id): array
    {
        $found = [];
        /** @var Vendors $vendor */
        foreach ($this->vendors as $vendor) {
            if ($vendor->has($id)) {
                $found[] = $vendor->name();
            }
        }
        return $found;
    }

    public function load(): TcfVendors
    {
        $this->logger->debug('Load tcf vendors');

        $file = $this->filePath . '/' . $this->fileName;

        if ( // re-fetch from remote
            strlen($this->url)
            && (
                !is_readable($file)
                || filemtime($file) < time() - $this->ttl
            )
        ) {
            $this->logger->debug('Remote tcf vendors');

            $vendors = @json_decode(
                (string)file_get_contents($this->url),
                true
            );
            if (
                json_last_error() > 0
                && strlen(json_last_error_msg()) > 0
            ) {
                throw new \Exception(
                    'Decoding tcf vendors json failed: ' . json_last_error_msg()
                );
            }
            if (!is_array($vendors)) {
                throw new \Exception(
                    'Invalid tcf vendors json'
                );
            }
            file_put_contents(
                $file,
                json_encode(
                    $vendors,
                    JSON_PRETTY_PRINT
                )
            );
        }
        elseif (is_readable($file)) {
            $vendors = @json_decode(
                file_get_contents($file),
                true
            );
            if (
                json_last_error() > 0
                && strlen(json_last_error_msg()) > 0
            ) {
                throw new \Exception(
                    'Decoding tcf vendors json failed: ' . json_last_error_msg()
                );
            }
        }
        else {
            throw new \Exception(
                'Tcf vendors config missing'
            );
        }

        if (strlen($this->customVendorsUrl) > 0) {
            $this->vendors[] = (new DefaultVendors(
                $this->logger,
                '*',
                '',
                $this->customVendorsUrl,
                0
            ))->load();
        }

        foreach ($vendors as $vendor) {
            $name = (string)($vendor['name'] ?? '');
            $url = (string)($vendor['url'] ?? '');
            $ttl = (int)($vendor['ttl'] ?? 86400);
            if (strlen($name) > 0 && strlen($url) > 0) {
                $this->vendors[] = (new DefaultVendors(
                    $this->logger,
                    $name,
                    sprintf(
                        '%s/tcf_%s.json',
                        $this->filePath,
                        $name
                    ),
                    $url,
                    $ttl
                ))->load();
            }
        }

        return $this;
    }
}