<?php

namespace GeneroWP\WpCliWordfence\Models;

class Copyright
{
    public function __construct(
        public string $slug,
        public string $notice,
        public string $license,
        public string $licenseUrl,
    ) {
    }

    /**
     * @param array<string,mixed> $data
     * @return Copyright[]
     */
    public static function fromRecord(array $data): array
    {
        $copyrights = [];
        foreach ($data as $key => $value) {
            if ($key === 'message') {
                continue;
            }
            $copyrights[] = new self(
                $key,
                $value['notice'] ?? '',
                $value['license'] ?? '',
                $value['license_url'] ?? '',
            );
        }

        return $copyrights;
    }

    public function getNotice(): string
    {
        return implode(
            PHP_EOL,
            array_filter([$this->notice, $this->license, $this->licenseUrl])
        );
    }
}
