<?php

use GeneroWP\WpCliWordfence\Models\AffectedVersion;
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
    }

    public function testVersion()
    {
        $affectedVersion = new AffectedVersion(
            '2.0.0',
            true,
            '2.0.1',
            true
        );

        $this->assertFalse($this->scanner->isAffectedVersion('1.0', $affectedVersion), 'Versions older than start are not affected');
        $this->assertTrue($this->scanner->isAffectedVersion('2.0.0', $affectedVersion), 'Versions equal to start are affected');
        $this->assertTrue($this->scanner->isAffectedVersion('2.0.1', $affectedVersion), 'Versions equal to end are affected');
        $this->assertFalse($this->scanner->isAffectedVersion('2.0.2', $affectedVersion), 'Versions after end are not affected');
    }

    public function testStarVersion()
    {
        $scanner = new VulnerabilityScanner(
            new WordfenceApi([], __DIR__ . '/fixtures/vulnerabilities.scanner.json')
        );

        $affectedVersion = new AffectedVersion(
            '*',
            true,
            '2.0.1',
            true
        );

        $this->assertTrue($this->scanner->isAffectedVersion('1.0', $affectedVersion), 'Versions newer than start are affected');
        $this->assertTrue($this->scanner->isAffectedVersion('2.0.1', $affectedVersion), 'Versions equal to end are affected');
        $this->assertFalse($this->scanner->isAffectedVersion('2.0.2', $affectedVersion), 'Versions after are not affected');
    }
}
