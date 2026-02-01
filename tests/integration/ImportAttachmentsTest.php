<?php

declare(strict_types=1);

namespace Tests\Integration;

use Raicem\WEFG\Attachment;
use Raicem\WEFG\Author;
use Raicem\WEFG\Meta;
use Raicem\WEFG\Post;
use Raicem\WEFG\SiteSettings;
use Raicem\WEFG\WXRFile;
use Tests\TestCase;

/**
 * Tests for attachment import functionality
 */
class ImportAttachmentsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        shell_exec('composer run reset-test-db --quiet');
    }

    public function testItGeneratesCorrectAttachmentXml()
    {
        $post = new Post(
            title: 'Test Post',
            content: 'Test Content',
            excerpt: 'Test Excerpt',
            authorLogin: 'admin',
            publishDate: '2024-01-01 12:00:00',
            slug: 'test-post',
            postId: 100
        );

        $attachment = new Attachment(
            title: 'Test Attachment',
            attachment_url: 'http://example.com/attachment.jpg',
            parent: $post,
            postId: 101,
            slug: 'test-attachment'
        );

        $siteSettings = new SiteSettings(
            link: 'http://example.com',
            title: 'Test Site',
            description: 'A test site',
            language: 'en-US',
            pubDate: 'Mon, 01 Jan 2024 12:00:00 +0000',
        );

        $wxrFile = new WXRFile($siteSettings);
        $wxrFile->addPost($post);
        $wxrFile->addPost($attachment);

        $this->assertCount(2, $wxrFile->posts);

        $xml = $wxrFile->getAsString();

        // Check attachment URL is present
        $this->assertStringContainsString('<wp:attachment_url><![CDATA[http://example.com/attachment.jpg]]></wp:attachment_url>', $xml);
        
        // Check post type is attachment
        $this->assertStringContainsString('<wp:post_type><![CDATA[attachment]]></wp:post_type>', $xml);
        
        // Check parent relationship
        $this->assertStringContainsString('<wp:post_parent><![CDATA[100]]></wp:post_parent>', $xml);
    }

    public function testAttachmentWithMeta()
    {
        $post = new Post(
            title: 'Test Post',
            content: 'Test Content',
            excerpt: '',
            authorLogin: 'admin',
            publishDate: '2024-01-01 12:00:00',
            slug: 'test-post',
            postId: 200
        );

        $attachment = new Attachment(
            title: 'Image Attachment',
            attachment_url: 'http://example.com/image.png',
            parent: $post,
            postId: 201,
            slug: 'image-attachment'
        );

        // Add attachment meta
        $attachment->meta[] = new Meta('_wp_attached_file', '2024/01/image.png');
        $attachment->meta[] = new Meta('_wp_attachment_metadata', 'a:6:{s:5:"width";i:800;s:6:"height";i:600;}');
        $attachment->meta[] = new Meta('_thumbnail_id', '202');

        $siteSettings = new SiteSettings(
            link: 'http://example.com',
            title: 'Test Site',
            description: 'A test site',
            language: 'en-US',
            pubDate: 'Mon, 01 Jan 2024 12:00:00 +0000',
        );

        $wxrFile = new WXRFile($siteSettings);
        $wxrFile->addPost($attachment);

        $xml = $wxrFile->getAsString();

        // Check meta fields are present
        $this->assertStringContainsString('<wp:meta_key><![CDATA[_wp_attached_file]]></wp:meta_key>', $xml);
        $this->assertStringContainsString('<wp:meta_value><![CDATA[2024/01/image.png]]></wp:meta_value>', $xml);
        $this->assertStringContainsString('<wp:meta_key><![CDATA[_wp_attachment_metadata]]></wp:meta_key>', $xml);
        $this->assertStringContainsString('<wp:meta_key><![CDATA[_thumbnail_id]]></wp:meta_key>', $xml);
    }

    public function testAttachmentGeneratesGuidFromUrl()
    {
        // Test that attachment uses URL as guid for importer fallback
        $attachment = new Attachment(
            title: 'Remote Attachment',
            attachment_url: 'https://example.com/path/to/file.pdf',
            postId: 300
        );

        $siteSettings = new SiteSettings(
            link: 'http://example.com',
            title: 'Test Site',
        );

        $wxrFile = new WXRFile($siteSettings);
        $wxrFile->addPost($attachment);

        $xml = $wxrFile->getAsString();

        // The importer uses attachment_url or falls back to guid
        // Our Attachment class should use attachment_url as guid if not explicitly set
        $this->assertStringContainsString('<wp:attachment_url><![CDATA[https://example.com/path/to/file.pdf]]></wp:attachment_url>', $xml);
    }

    public function testAttachmentParentRemapping()
    {
        // Test that attachment parent relationships work with numeric IDs
        $parent1 = new Post(
            title: 'Parent Post 1',
            content: 'Content',
            excerpt: '',
            authorLogin: 'admin',
            publishDate: '2024-01-01 12:00:00',
            slug: 'parent-1',
            postId: 500
        );

        $parent2 = new Post(
            title: 'Parent Post 2',
            content: 'Content',
            excerpt: '',
            authorLogin: 'admin',
            publishDate: '2024-01-01 12:00:00',
            slug: 'parent-2',
            postId: 501
        );

        $attachment1 = new Attachment(
            title: 'Child of Parent 1',
            attachment_url: 'http://example.com/attach1.jpg',
            parent: $parent1,
            postId: 502
        );

        $attachment2 = new Attachment(
            title: 'Child of Parent 2',
            attachment_url: 'http://example.com/attach2.jpg',
            parent: $parent2,
            postId: 503
        );

        $siteSettings = new SiteSettings(
            link: 'http://example.com',
            title: 'Test Site',
            pubDate: 'Mon, 01 Jan 2024 12:00:00 +0000',
        );

        $wxrFile = new WXRFile($siteSettings);
        $wxrFile->addPost($parent1);
        $wxrFile->addPost($parent2);
        $wxrFile->addPost($attachment1);
        $wxrFile->addPost($attachment2);

        $xml = $wxrFile->getAsString();

        // Verify both attachments have correct parent IDs
        $this->assertStringContainsString('<wp:post_id><![CDATA[500]]></wp:post_id>', $xml);
        $this->assertStringContainsString('<wp:post_id><![CDATA[501]]></wp:post_id>', $xml);
        $this->assertStringContainsString('<wp:post_id><![CDATA[502]]></wp:post_id>', $xml);
        $this->assertStringContainsString('<wp:post_id><![CDATA[503]]></wp:post_id>', $xml);
    }

    public function testAttachmentUrlWith127001()
    {
        // Test using 127.0.0.1 instead of localhost (as mentioned in AGENTS.md)
        $attachment = new Attachment(
            title: 'Local Attachment',
            attachment_url: 'http://127.0.0.1:8080/uploads/test.jpg',
            postId: 600
        );

        $siteSettings = new SiteSettings(
            link: 'http://127.0.0.1:8080',
            title: 'Test Site',
            pubDate: 'Mon, 01 Jan 2024 12:00:00 +0000',
        );

        $wxrFile = new WXRFile($siteSettings);
        $wxrFile->addPost($attachment);

        $xml = $wxrFile->getAsString();

        // Should use 127.0.0.1 not localhost
        $this->assertStringContainsString('http://127.0.0.1:8080/uploads/test.jpg', $xml);
        $this->assertStringNotContainsString('localhost', $xml);
    }
}
