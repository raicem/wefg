<?php declare(strict_types=1);

namespace Tests\Integration;

use Raicem\WEFG\Author;
use Raicem\WEFG\Post;
use Raicem\WEFG\SiteSettings;
use Raicem\WEFG\WXRFile;
use Tests\TestCase;

class ImportPostPasswordTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testItCanImportPostsWithPasswords() {
        $tempFile = tempnam(sys_get_temp_dir(), 'wxr_test');

        $wxrFile = new WXRFile(
            new SiteSettings(
                title: 'Test Site',
                link: 'http://localhost',
                description: 'Test Description',
                pubDate: 'Sun, 29 Dec 2024 19:58:14 +0000',
            )
        );

        $author1 = new Author(
            userLogin: 'johndoe',
            userEmail: 'john@example.com',
        );

        $wxrFile->addAuthor($author1);

        // Post with password
        $protectedPost = new Post(
            title: 'Protected Post',
            content: 'This is protected content',
            excerpt: 'Protected excerpt',
            authorLogin: 'johndoe',
            publishDate: '2024-12-29 19:58:14',
            slug: 'protected-post',
            postId: 100,
            post_password: 'secret123'
        );

        // Post without password
        $publicPost = new Post(
            title: 'Public Post',
            content: 'This is public content',
            excerpt: 'Public excerpt',
            authorLogin: 'johndoe',
            publishDate: '2024-12-29 19:58:14',
            slug: 'public-post',
            postId: 101
        );

        $wxrFile->addPost($protectedPost);
        $wxrFile->addPost($publicPost);

        $wxrFile->save($tempFile);

        // Debug: Check the XML output
        $xmlContent = file_get_contents($tempFile);
        $this->assertStringContainsString('<wp:post_password><![CDATA[secret123]]></wp:post_password>', $xmlContent);
        $this->assertStringContainsString('<wp:post_password><![CDATA[]]></wp:post_password>', $xmlContent);

        $importCommand = sprintf(
            './wp-test import --authors=create %s', 
            escapeshellarg($tempFile)
        );

        $importOutput = shell_exec($importCommand);
        
        // Debug: Check import output
        if (strpos($importOutput, 'Error') !== false || strpos($importOutput, 'Failed') !== false) {
            $this->fail("Import failed: " . $importOutput);
        }

        unlink($tempFile);

        // Test protected post
        $protectedResult = shell_exec('./wp-test post list --post_type=post --name=protected-post --field=ID --format=json');
        $protectedId = json_decode(trim($protectedResult), true)[0] ?? null;
        
        if ($protectedId) {
            $protectedPassword = shell_exec("./wp-test post get {$protectedId} --field=post_password --format=json");
            $protectedPassword = trim(trim($protectedPassword), '"');
            $this->assertEquals('secret123', $protectedPassword);
        } else {
            $this->fail('Protected post not found after import');
        }

        // Test public post (should have empty password)
        $publicResult = shell_exec('./wp-test post list --post_type=post --name=public-post --field=ID --format=json');
        $publicId = json_decode(trim($publicResult), true)[0] ?? null;
        
        if ($publicId) {
            $publicPassword = shell_exec("./wp-test post get {$publicId} --field=post_password --format=json");
            $publicPassword = trim(trim($publicPassword), '"');
            $this->assertEquals('', $publicPassword);
        } else {
            $this->fail('Public post not found after import');
        }
    }
}
