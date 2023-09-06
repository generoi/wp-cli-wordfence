<?php

namespace GeneroWP\WpCliWordfence\Models;

class Software
{
    /**
     * @param AffectedVersion[] $affectedVersions
     * @param string[] $patchedVersions
     */
    public function __construct(
        public string $type,
        public string $name,
        public string $slug,
        public array $affectedVersions,
        public bool $patched,
        public array $patchedVersions,
    )
    {
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function fromRecord(array $data): Software
    {
        return new self(
            $data['type'],
            $data['name'],
            $data['slug'],
            array_map(fn ($version) => AffectedVersion::fromRecord($version), $data['affected_versions']),
            $data['patched'],
            $data['patched_versions'],
        );
    }
}
