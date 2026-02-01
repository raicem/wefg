<?php declare(strict_types=1);

namespace Tests\Integration;

use Raicem\WEFG\Author;
use Raicem\WEFG\Meta;
use Raicem\WEFG\Post;
use Raicem\WEFG\SiteSettings;
use Raicem\WEFG\WXRFile;
use Tests\TestCase;

class ImportPostsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        shell_exec('composer run reset-test-db --quiet');
        shell_exec('./wp-test post delete 1 --force'); // Delete default "Hello world!" post
    }

    public function testItCanImportPosts()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'wxr_');

        if ( ! $tempFile ) {
            $this->fail("Failed to create a temporary file for the WXR!");
        }

        $wxrFile = new WXRFile(
            new SiteSettings(
                link: 'http://localhost:2626',
                title: 'Posts Test WXR',
                pubDate: 'Mon, 07 Jul 2025 10:00:00 +0000',
            )
        );

        // 1. Create an author
        $author = new Author(
            userLogin: 'testuser',
            userEmail: 'testuser@example.com',
            displayName: 'Test User',
        );
        $wxrFile->addAuthor($author);

        // 2. Create posts
        $post1 = new Post(
            title: 'Hello World',
            content: 'Welcome to WordPress. This is your first post.',
            excerpt: '',
            authorLogin: 'testuser',
            publishDate: '2025-07-07 10:00:00',
            slug: 'hello-world',
            status: 'publish',
            postId: 100
        );

        $post2 = new Post(
            title: 'Post with Taxonomies',
            content: 'This post has categories and tags.',
            excerpt: 'A short summary.',
            authorLogin: 'testuser',
            publishDate: '2025-07-07 11:00:00',
            slug: 'post-with-taxonomies',
            status: 'publish',
            postId: 101,
            categories: ['News', 'Updates'],
            tags: ['featured', 'important']
        );

        $wxrFile->addPost($post1);
        $wxrFile->addPost($post2);

        $wxrFile->save($tempFile);

        // 3. Import
        $importCommand = sprintf(
            './wp-test import --authors=create %s',
            escapeshellarg($tempFile)
        );
        shell_exec($importCommand);

        // 4. Verify
        $allPostsJson = shell_exec('./wp-test post list --format=json');
        $allPostsData = json_decode($allPostsJson, true);

        $this->assertCount(2, $allPostsData);

        $post1Json = shell_exec('./wp-test post get 100 --format=json');
        $post1Data = json_decode($post1Json, true);
        $this->assertNotNull($post1Data, 'Post with ID 100 not found.');
        $this->assertEquals('Hello World', $post1Data['post_title']);
        $this->assertStringContainsString('Welcome to WordPress', $post1Data['post_content']);

        $post2Json = shell_exec('./wp-test post get 101 --format=json');
        $post2Data = json_decode($post2Json, true);
        $this->assertNotNull($post2Data, 'Post with ID 101 not found.');
        $this->assertEquals('Post with Taxonomies', $post2Data['post_title']);
        $this->assertEquals('A short summary.', $post2Data['post_excerpt']);

        // Verify categories
        $post2Categories = shell_exec('./wp-test post term list ' . $post2Data['ID'] . ' category --format=json');
        $post2CatData = json_decode($post2Categories, true);
        $catNames = array_column($post2CatData, 'name');
        $this->assertContains('News', $catNames);
        $this->assertContains('Updates', $catNames);

        // Verify tags
        $post2Tags = shell_exec('./wp-test post term list ' . $post2Data['ID'] . ' post_tag --format=json');
        $post2TagData = json_decode($post2Tags, true);
        $tagNames = array_column($post2TagData, 'name');
        $this->assertContains('featured', $tagNames);
        $this->assertContains('important', $tagNames);

        unlink($tempFile);
    }

    public function testItCanImportPostsWithMeta()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'wxr_');

        if ( ! $tempFile ) {
            $this->fail("Failed to create a temporary file for the WXR!");
        }

        $wxrFile = new WXRFile(
            new SiteSettings(
                link: 'http://localhost:2626',
                title: 'Posts Meta Test WXR',
                pubDate: 'Mon, 07 Jul 2025 10:00:00 +0000',
            )
        );

        // 1. Create an author
        $author = new Author(
            userLogin: 'testuser',
            userEmail: 'testuser@example.com',
            displayName: 'Test User',
        );
        $wxrFile->addAuthor($author);

        // 2. Create a post with meta fields
        $post = new Post(
            title: 'Post with Meta',
            content: 'This post has custom meta fields.',
            excerpt: 'A post with meta.',
            authorLogin: 'testuser',
            publishDate: '2025-07-07 10:00:00',
            slug: 'post-with-meta',
            status: 'publish',
            postId: 200
        );

        // Add meta fields
        $post->meta[] = new Meta('my_custom_field', 'my_custom_value');
        $post->meta[] = new Meta('another_field', 'another_value');

        $wxrFile->addPost($post);
        $wxrFile->save($tempFile);

        // 3. Import
        $importCommand = sprintf(
            './wp-test import --authors=create %s',
            escapeshellarg($tempFile)
        );
        shell_exec($importCommand);

        // 4. Verify post meta
        $postMetaJson = shell_exec('./wp-test post meta list 200 --format=json');
        $postMetaData = json_decode($postMetaJson, true);

        // Extract meta keys and values
        $metaKeys = array_column($postMetaData, 'meta_key');
        $metaByKey = [];
        foreach ($postMetaData as $meta) {
            $metaByKey[$meta['meta_key']] = $meta['meta_value'];
        }

        $this->assertContains('my_custom_field', $metaKeys);
        $this->assertContains('another_field', $metaKeys);
        $this->assertEquals('my_custom_value', $metaByKey['my_custom_field']);
        $this->assertEquals('another_value', $metaByKey['another_field']);

        unlink($tempFile);
    }
}
