<?php

namespace Tests\Integration;

use Raicem\WEFG\Comment;
use Raicem\WEFG\Meta;
use Raicem\WEFG\Post;
use Raicem\WEFG\SiteSettings;
use Raicem\WEFG\WXRFile;
use Tests\TestCase;

class ImportCommentsTest extends TestCase
{
    public function test_can_import_comments()
    {
        $post = new Post(
            'Test Post',
            'Test Content',
            'Test Excerpt',
            'admin',
            '2024-01-01 12:00:00',
            'test-post'
        );
        $post->comments[] = new Comment(
            comment_author: 'John Doe',
            comment_author_email: 'test@test.com',
            comment_content: 'This is a comment'
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

        $xml = $wxrFile->getAsString();

        $this->assertStringContainsString('<wp:comment_author><![CDATA[John Doe]]></wp:comment_author>', $xml);
    }

    public function test_can_import_comments_with_metadata()
    {
        $post = new Post(
            'Test Post',
            'Test Content',
            'Test Excerpt',
            'admin',
            '2024-01-01 12:00:00',
            'test-post'
        );

        $comment = new Comment(
            comment_author: 'John Doe',
            comment_author_email: 'test@test.com',
            comment_content: 'This is a comment',
            comment_date: '2024-01-01 12:00:00',
            comment_date_gmt: '2024-01-01 12:00:00',
            comment_author_url: 'https://johndoe.com',
            comment_approved: '1',
            comment_id: 123
        );

        // Add comment metadata
        $comment->meta[] = new Meta('custom_rating', '5');
        $comment->meta[] = new Meta('verified_purchase', 'yes');

        $post->comments[] = $comment;

        $siteSettings = new SiteSettings(
            'Test Site',
            'http://example.com',
            'A test site',
            'en-US',
            '1.2'
        );

        $wxrFile = new WXRFile($siteSettings);
        $wxrFile->addPost($post);

        $xml = $wxrFile->getAsString();

        // Test basic comment fields
        $this->assertStringContainsString('<wp:comment_id>123</wp:comment_id>', $xml);
        $this->assertStringContainsString('<wp:comment_author><![CDATA[John Doe]]></wp:comment_author>', $xml);
        $this->assertStringContainsString('<wp:comment_author_email><![CDATA[test@test.com]]></wp:comment_author_email>', $xml);
        $this->assertStringContainsString('<wp:comment_author_url><![CDATA[https://johndoe.com]]></wp:comment_author_url>', $xml);
        $this->assertStringContainsString('<wp:comment_content><![CDATA[This is a comment]]></wp:comment_content>', $xml);
        $this->assertStringContainsString('<wp:comment_approved><![CDATA[1]]></wp:comment_approved>', $xml);
        $this->assertStringContainsString('<wp:comment_date><![CDATA[2024-01-01 12:00:00]]></wp:comment_date>', $xml);
        $this->assertStringContainsString('<wp:comment_date_gmt><![CDATA[2024-01-01 12:00:00]]></wp:comment_date_gmt>', $xml);

        // Test comment metadata
        $this->assertStringContainsString('<wp:commentmeta>', $xml);
        $this->assertStringContainsString('<wp:meta_key><![CDATA[custom_rating]]></wp:meta_key>', $xml);
        $this->assertStringContainsString('<wp:meta_value><![CDATA[5]]></wp:meta_value>', $xml);
        $this->assertStringContainsString('<wp:meta_key><![CDATA[verified_purchase]]></wp:meta_key>', $xml);
        $this->assertStringContainsString('<wp:meta_value><![CDATA[yes]]></wp:meta_value>', $xml);
    }
}
