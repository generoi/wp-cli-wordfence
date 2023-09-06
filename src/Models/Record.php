<?php

namespace GeneroWP\WpCliWordfence\Models;

class Record
{
    /**
     * @param Software[] $software
     * @param string[] $references
     * @param Copyright[] $copyrights
     */
    public function __construct(
        public string $id,
        public string $title,
        public array $software,
        public array $references,
        public string $published,
        public array $copyrights,
    )
    {
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function fromRecord(array $data): Record
    {
        return new self(
            $data['id'],
            $data['title'],
            array_map(fn (array $entry) => Software::fromRecord($entry), $data['software'] ?? []),
            $data['references'],
            $data['published'],
            Copyright::fromRecord($data['copyrights'] ?? []),
        );
    }

    public function isSoftware(string $slug, string $type = ''): bool
    {
        foreach ($this->software as $software) {
            if ($type && $software->type !== $type) {
                return false;
            }
            if ($software->slug === $slug) {
                return true;
            }
        }
        return false;
    }

    public function getMessage(): string
    {
        return $this->title;
    }
}
