<?php

namespace Tests\Integration;

use Raicem\WEFG\Attachment;
use Raicem\WEFG\Post;
use Raicem\WEFG\SiteSettings;
use Raicem\WEFG\WXRFile;
use Tests\TestCase;

class ImportAttachmentsTest extends TestCase
{
    public function test_can_import_attachments()
    {
        $post = new Post(
            'Test Post',
            'Test Content',
            'Test Excerpt',
            'admin',
            '2024-01-01 12:00:00',
            'test-post'
        );

        $attachment = new Attachment(
            'Test Attachment',
            'http://example.com/attachment.jpg',
            $post
        );

        $siteSettings = new SiteSettings(
            'Test Site',
            'http://example.com',
            'A test site',
            'en-US',
            '1.2'
        );

        $wxrFile = new WXRFile($siteSettings);
        $wxrFile->addPost($post);
        $wxrFile->addPost($attachment);

        $this->assertCount(2, $wxrFile->posts);

        $xml = $wxrFile->getAsString();

        $this->assertStringContainsString('<wp:attachment_url><![CDATA[http://example.com/attachment.jpg]]></wp:attachment_url>', $xml);
    }
}
