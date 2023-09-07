<?php

namespace GeneroWP\WpCliWordfence\Models;

class AffectedVersion
{
    public function __construct(
        public string $fromVersion,
        public bool $fromInclusive,
        public string $toVersion,
        public bool $toInclusive,
    ) {
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

    public function isVersionAffected(string $activeVersion): bool
    {
        $fromOperator = $this->fromInclusive ? '<' : '<=';
        $toOperator = $this->toInclusive ? '>' : '>=';
        $fromVersion = $this->fromVersion === '*' ? 0 : $this->fromVersion;
        $toVersion = $this->toVersion === '*' ? PHP_INT_MAX : $this->toVersion;

        $isOlderThanVulnerableVersion = version_compare($activeVersion, $fromVersion, $fromOperator);
        if ($isOlderThanVulnerableVersion) {
            return false;
        }

        $isNewerThanPatchedVersion = version_compare($activeVersion, $toVersion, $toOperator);
        if ($isNewerThanPatchedVersion) {
            return false;
        }
        return true;
    }
}
