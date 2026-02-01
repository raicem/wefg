<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Raicem\WEFG\WXRFile;
use Raicem\WEFG\SiteSettings;

class SaveToFileTest extends TestCase {
    public function testItCanSaveToFile() {
        $tempFile = tempnam(sys_get_temp_dir(), 'wxr_');

        $wxrFile = new WXRFile(
            new SiteSettings(
                link: 'http://localhost:2626',
                title: 'Example WXR',
                pubDate: 'Sun, 29 Dec 2024 19:58:14 +0000',
            )
        );

        $wxrFile->save($tempFile);

        $this->assertFileExists($tempFile);

        unlink($tempFile);
    }
}
