<?php

use GeneroWP\WpCliWordfence\Models\AffectedVersion;
use PHPUnit\Framework\TestCase;

class VersionTest extends TestCase
{
    public function testFixedVersion()
    {
        $affectedVersion = new AffectedVersion(
            '2.0.0',
            true,
            '2.0.1',
            true
        );

        $this->assertFalse($affectedVersion->isVersionAffected('1.0'), 'Versions older than start are not affected');
        $this->assertTrue($affectedVersion->isVersionAffected('2.0.0'), 'Versions equal to start are affected');
        $this->assertTrue($affectedVersion->isVersionAffected('2.0.1'), 'Versions equal to end are affected');
        $this->assertFalse($affectedVersion->isVersionAffected('2.0.2'), 'Versions after end are not affected');
    }

    public function testStarVersion()
    {
        $affectedVersion = new AffectedVersion(
            '*',
            true,
            '2.0.1',
            true
        );

        $this->assertTrue($affectedVersion->isVersionAffected('1.0'), 'Versions newer than start are affected');
        $this->assertTrue($affectedVersion->isVersionAffected('2.0.1'), 'Versions equal to end are affected');
        $this->assertFalse($affectedVersion->isVersionAffected('2.0.2'), 'Versions after are not affected');
    }
}
