<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class WXRFileTest extends TestCase {
    public function testItCanCreateAnEmptyWXRFile() {
        $wxrFile = new Raicem\WEFG\WXRFile();
        $this->assertInstanceOf(Raicem\WEFG\WXRFile::class, $wxrFile);
    }
}
