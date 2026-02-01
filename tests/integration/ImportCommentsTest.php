<?php

declare(strict_types=1);

namespace Tests\Integration;

use Raicem\WEFG\Author;
use Raicem\WEFG\Comment;
use Raicem\WEFG\Meta;
use Raicem\WEFG\Post;
use Raicem\WEFG\SiteSettings;
use Raicem\WEFG\WXRFile;
use Tests\TestCase;

class ImportCommentsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        shell_exec('composer run reset-test-db --quiet');
    }

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

    public function testItCanImportCommentsIntoWordPress()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'wxr_');
        if (!$tempFile) {
            $this->fail("Failed to create a temporary file for the WXR!");
        }

        $wxrFile = new WXRFile(
            new SiteSettings(
                link: 'http://localhost:2626',
                title: 'Comments Test WXR',
                pubDate: 'Mon, 07 Jul 2025 10:00:00 +0000',
            )
        );

        // Create author
        $author = new Author(
            userLogin: 'testuser',
            userEmail: 'testuser@example.com',
        );
        $wxrFile->addAuthor($author);

        // Create post with comments
        $post = new Post(
            title: 'Post with Comments',
            content: 'This post has comments.',
            excerpt: '',
            authorLogin: 'testuser',
            publishDate: '2025-07-07 10:00:00',
            slug: 'post-with-comments',
            status: 'publish',
            postId: 100
        );

        $comment1 = new Comment(
            comment_author: 'Alice Smith',
            comment_author_email: 'alice@example.com',
            comment_content: 'Great post!',
            comment_date: '2025-07-07 11:00:00',
            comment_date_gmt: '2025-07-07 11:00:00',
            comment_author_url: 'https://alice.example.com',
            comment_approved: '1',
            comment_id: 1
        );

        $comment2 = new Comment(
            comment_author: 'Bob Jones',
            comment_author_email: 'bob@example.com',
            comment_content: 'Thanks for sharing!',
            comment_date: '2025-07-07 12:00:00',
            comment_date_gmt: '2025-07-07 12:00:00',
            comment_approved: '1',
            comment_id: 2
        );

        $post->comments[] = $comment1;
        $post->comments[] = $comment2;

        $wxrFile->addPost($post);
        $wxrFile->save($tempFile);

        // Import
        $importCommand = sprintf(
            './wp-test import --authors=create %s',
            escapeshellarg($tempFile)
        );
        shell_exec($importCommand);

        // Get the post ID
        $postJson = shell_exec('./wp-test post list --post_type=post --name=post-with-comments --format=json');
        $this->assertNotNull($postJson, 'Post list command should return output');
        $postData = json_decode($postJson, true);
        $this->assertNotNull($postData, 'Should be able to decode post JSON');
        $this->assertNotEmpty($postData, 'Post should exist after import');
        $postId = $postData[0]['ID'];

        // Verify comments were imported
        $commentsJson = shell_exec("./wp-test comment list {$postId} --format=json 2>&1");
        $this->assertNotNull($commentsJson, 'Comment list command should return output');
        $commentsData = json_decode($commentsJson, true);
        $this->assertNotNull($commentsData, 'Should be able to decode comments JSON');

        $this->assertCount(2, $commentsData, 'Should have 2 comments');

        // Check comment details
        $authors = array_column($commentsData, 'comment_author');
        $this->assertContains('Alice Smith', $authors);
        $this->assertContains('Bob Jones', $authors);

        unlink($tempFile);
    }

    public function testItCanImportCommentsWithMetaIntoWordPress()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'wxr_');
        if (!$tempFile) {
            $this->fail("Failed to create a temporary file for the WXR!");
        }

        $wxrFile = new WXRFile(
            new SiteSettings(
                link: 'http://localhost:2626',
                title: 'Comments Meta Test WXR',
                pubDate: 'Mon, 07 Jul 2025 10:00:00 +0000',
            )
        );

        $author = new Author(
            userLogin: 'testuser',
            userEmail: 'testuser@example.com',
        );
        $wxrFile->addAuthor($author);

        $post = new Post(
            title: 'Post with Meta Comments',
            content: 'This post has comments with meta.',
            excerpt: '',
            authorLogin: 'testuser',
            publishDate: '2025-07-07 10:00:00',
            slug: 'post-with-meta-comments',
            status: 'publish',
            postId: 200
        );

        $comment = new Comment(
            comment_author: 'Reviewer',
            comment_author_email: 'reviewer@example.com',
            comment_content: 'Excellent product!',
            comment_date: '2025-07-07 11:00:00',
            comment_date_gmt: '2025-07-07 11:00:00',
            comment_approved: '1',
            comment_id: 10
        );

        $comment->meta[] = new Meta('rating', '5');
        $comment->meta[] = new Meta('verified', 'true');

        $post->comments[] = $comment;
        $wxrFile->addPost($post);
        $wxrFile->save($tempFile);

        // Import
        $importCommand = sprintf(
            './wp-test import --authors=create %s',
            escapeshellarg($tempFile)
        );
        shell_exec($importCommand);

        // Get post and comment
        $postJson = shell_exec('./wp-test post list --post_type=post --name=post-with-meta-comments --format=json');
        $this->assertNotNull($postJson, 'Post list command should return output');
        $postData = json_decode($postJson, true);
        $this->assertNotNull($postData, 'Should be able to decode post JSON');
        $postId = $postData[0]['ID'];

        $commentsJson = shell_exec("./wp-test comment list {$postId} --format=json 2>&1");
        $this->assertNotNull($commentsJson, 'Comment list command should return output');
        $commentsData = json_decode($commentsJson, true);
        $this->assertNotNull($commentsData, 'Should be able to decode comments JSON');
        $commentId = $commentsData[0]['comment_ID'];

        // Check comment meta
        $metaJson = shell_exec("./wp-test comment meta list {$commentId} --format=json 2>&1");
        $this->assertNotNull($metaJson, 'Comment meta list command should return output');
        $metaData = json_decode($metaJson, true);
        $this->assertNotNull($metaData, 'Should be able to decode meta JSON');

        $metaKeys = array_column($metaData, 'meta_key');
        $this->assertContains('rating', $metaKeys);
        $this->assertContains('verified', $metaKeys);

        // Get meta values
        $metaByKey = [];
        foreach ($metaData as $meta) {
            $metaByKey[$meta['meta_key']] = $meta['meta_value'];
        }
        $this->assertEquals('5', $metaByKey['rating']);
        $this->assertEquals('true', $metaByKey['verified']);

        unlink($tempFile);
    }

    public function testItCanImportNestedComments()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'wxr_');
        if (!$tempFile) {
            $this->fail("Failed to create a temporary file for the WXR!");
        }

        $wxrFile = new WXRFile(
            new SiteSettings(
                link: 'http://localhost:2626',
                title: 'Nested Comments Test WXR',
                pubDate: 'Mon, 07 Jul 2025 10:00:00 +0000',
            )
        );

        $author = new Author(
            userLogin: 'testuser',
            userEmail: 'testuser@example.com',
        );
        $wxrFile->addAuthor($author);

        $post = new Post(
            title: 'Post with Nested Comments',
            content: 'This post has nested comments.',
            excerpt: '',
            authorLogin: 'testuser',
            publishDate: '2025-07-07 10:00:00',
            slug: 'post-with-nested-comments',
            status: 'publish',
            postId: 300
        );

        $parentComment = new Comment(
            comment_author: 'Parent User',
            comment_author_email: 'parent@example.com',
            comment_content: 'This is the parent comment',
            comment_date: '2025-07-07 11:00:00',
            comment_date_gmt: '2025-07-07 11:00:00',
            comment_approved: '1',
            comment_id: 100,
            comment_parent: '0'
        );

        $childComment = new Comment(
            comment_author: 'Child User',
            comment_author_email: 'child@example.com',
            comment_content: 'This is a reply to the parent',
            comment_date: '2025-07-07 12:00:00',
            comment_date_gmt: '2025-07-07 12:00:00',
            comment_approved: '1',
            comment_id: 101,
            comment_parent: '100'
        );

        $post->comments[] = $parentComment;
        $post->comments[] = $childComment;

        $wxrFile->addPost($post);
        $wxrFile->save($tempFile);

        // Import
        $importCommand = sprintf(
            './wp-test import --authors=create %s',
            escapeshellarg($tempFile)
        );
        shell_exec($importCommand);

        // Get post and comments
        $postJson = shell_exec('./wp-test post list --post_type=post --name=post-with-nested-comments --format=json');
        $this->assertNotNull($postJson, 'Post list command should return output');
        $postData = json_decode($postJson, true);
        $this->assertNotNull($postData, 'Should be able to decode post JSON');
        $postId = $postData[0]['ID'];

        $commentsJson = shell_exec("./wp-test comment list {$postId} --format=json 2>&1");
        $this->assertNotNull($commentsJson, 'Comment list command should return output');
        $commentsData = json_decode($commentsJson, true);
        $this->assertNotNull($commentsData, 'Should be able to decode comments JSON');

        $this->assertCount(2, $commentsData);

        // Find the child comment and verify it has a parent
        $childCommentData = null;
        foreach ($commentsData as $c) {
            if ($c['comment_author'] === 'Child User') {
                $childCommentData = $c;
                break;
            }
        }

        $this->assertNotNull($childCommentData, 'Child comment should exist');
        $this->assertGreaterThan(0, $childCommentData['comment_parent'], 'Child comment should have a parent');

        unlink($tempFile);
    }
}
