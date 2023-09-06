<?php

namespace GeneroWP\WpCliWordfence\Models;

class AffectedVersion
{
    public function __construct(
        public string $fromVersion,
        public bool $fromInclusive,
        public string $toVersion,
        public bool $toInclusive,
    )
    {
    }

    /**
     * @param array<string,string|bool> $data
     */
    public static function fromRecord(array $data): AffectedVersion
    {
        return new self(
            $data['from_version'],
            $data['from_inclusive'],
            $data['to_version'],
            $data['to_inclusive'],
        );
    }
}
