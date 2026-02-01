<?php

namespace Tests\Integration;

use Raicem\WEFG\Post;
use Raicem\WEFG\SiteSettings;
use Raicem\WEFG\WXRFile;
use Tests\TestCase;

class ImportCustomPostTypesTest extends TestCase
{
    public function test_can_import_custom_post_types()
    {
        $customPost = new Post(
            'Test Custom Post',
            'Test Content',
            'Test Excerpt',
            'admin',
            '2024-01-01 12:00:00',
            'test-custom-post',
            post_type: 'my_custom_post_type'
        );

        $siteSettings = new SiteSettings(
            'Test Site',
            'http://example.com',
            'A test site',
            'en-US',
            '1.2'
        );

        $wxrFile = new WXRFile($siteSettings);
        $wxrFile->addPost($customPost);

        $this->assertCount(1, $wxrFile->posts);

        $xml = $wxrFile->getAsString();

        $this->assertStringContainsString('<wp:post_type><![CDATA[my_custom_post_type]]></wp:post_type>', $xml);
    }
}
