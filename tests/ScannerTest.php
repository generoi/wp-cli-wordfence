<?php

use GeneroWP\WpCliWordfence\Models\Record;
use GeneroWP\WpCliWordfence\VulnerabilityException;
use GeneroWP\WpCliWordfence\VulnerabilityScanner;
use GeneroWP\WpCliWordfence\WordfenceApi;
use PHPUnit\Framework\TestCase;

class ScannerTest extends TestCase
{
    public VulnerabilityScanner $scanner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scanner = new VulnerabilityScanner(
            new WordfenceApi()
        );

        wp_cache_set('plugins', [
            '' => [
                'opening-hours/opening-hours.php' => [
                    'Version' => '1.3',
                ],
            ]
        ], 'plugins');
    }

    public function testFixedVersion()
    {
        $record = $vulnerability = null;
        foreach ($this->scanner->next() as $record => $vulnerability) {
            if ($record->id === '0004db27-9ea6-4387-ab1d-b95558784ed9') {
                break;
            }
        }

        $this->assertInstanceOf(Record::class, $record);
        $this->assertJsonStringEqualsJsonFile(__DIR__ . '/fixtures/opening-hours-vulnerability.json', json_encode($record));

        $this->assertInstanceOf(VulnerabilityException::class, $vulnerability);
        $this->assertEquals('* < 1.3 < 1.37', $vulnerability->getMessage());
    }
}
