<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Raicem\WEFG\WXRFile;
use Raicem\WEFG\SiteSettings;
use Raicem\WEFG\Author;

class ImportAuthorsTest extends TestCase
{
    public function setUp(): void
    {
        shell_exec('composer run reset-test-db --quiet');

        // Start a WordPress site
        parent::setUp();
    }

    public function testItCanImportAuthorsIntoWordPress() {
        $tempFile = tempnam(sys_get_temp_dir(), 'wxr_');

        if ( ! $tempFile ) {
            $this->fail("Failed to create a temporary file for the WXR!");
        }

        $wxrFile = new WXRFile(
            new SiteSettings(
                link: 'http://localhost:2626',
                title: 'Example WXR',
                pubDate: 'Sun, 29 Dec 2024 19:58:14 +0000',
            )
        );

        $author1 = new Author(
            userLogin: 'johndoe',
            userEmail: 'john@example.com',
        );

        $author2 = new Author(
            userLogin: 'janedoe',
            userEmail: 'jane@example.com',
            displayName: 'Jane Doe',
            firstName: 'Jane',
            lastName: 'Doe',
        );

        $wxrFile->addAuthor($author1);
        $wxrFile->addAuthor($author2);

        $wxrFile->save($tempFile);

        $importCommand = sprintf(
            './wp-test import --authors=create %s', 
            escapeshellarg($tempFile)
        );

        shell_exec($importCommand);

        $result1 = shell_exec('./wp-test user get john@example.com --format=json');
        $result1 = json_decode($result1, true);

        $this->assertEquals($author1->userLogin, $result1['user_login']);

        $result2 = shell_exec('./wp-test user get jane@example.com --format=json');
        $result2 = json_decode($result2, true);

        $this->assertEquals($author2->userLogin, $result2['user_login']);
        $this->assertEquals($author2->displayName, $result2['display_name']);
    }
}
