<?php

declare(strict_types=1);

namespace Tests\Integration;

use Raicem\WEFG\Author;
use Raicem\WEFG\Post;
use Raicem\WEFG\SiteSettings;
use Raicem\WEFG\WXRFile;
use Tests\TestCase;

/**
 * Tests for post features: sticky, menu_order, and parent relationships
 */
class ImportPostFeaturesTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        shell_exec('composer run reset-test-db --quiet');
    }

    public function testItCanImportStickyPosts()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'wxr_');
        if (!$tempFile) {
            $this->fail("Failed to create a temporary file for the WXR!");
        }

        $wxrFile = new WXRFile(
            new SiteSettings(
                link: 'http://localhost:2626',
                title: 'Sticky Post Test WXR',
                pubDate: 'Mon, 07 Jul 2025 10:00:00 +0000',
            )
        );

        $author = new Author(
            userLogin: 'testuser',
            userEmail: 'testuser@example.com',
        );
        $wxrFile->addAuthor($author);

        $stickyPost = new Post(
            title: 'Sticky Post',
            content: 'This is a sticky post.',
            excerpt: '',
            authorLogin: 'testuser',
            publishDate: '2025-07-07 10:00:00',
            slug: 'sticky-post',
            status: 'publish',
            postId: 100,
            isSticky: 1
        );

        $normalPost = new Post(
            title: 'Normal Post',
            content: 'This is a normal post.',
            excerpt: '',
            authorLogin: 'testuser',
            publishDate: '2025-07-07 11:00:00',
            slug: 'normal-post',
            status: 'publish',
            postId: 101,
            isSticky: 0
        );

        $wxrFile->addPost($stickyPost);
        $wxrFile->addPost($normalPost);
        $wxrFile->save($tempFile);

        // Verify XML contains sticky flag
        $xml = $wxrFile->getAsString();
        $this->assertStringContainsString('<wp:is_sticky>1</wp:is_sticky>', $xml);
        $this->assertStringContainsString('<wp:is_sticky>0</wp:is_sticky>', $xml);

        // Import
        $importCommand = sprintf(
            './wp-test import --authors=create %s',
            escapeshellarg($tempFile)
        );
        shell_exec($importCommand);

        // Check if sticky post is actually sticky in WordPress
        $stickyCheck = shell_exec('./wp-test post list --post_type=post --post_status=publish --sticky=1 --format=json');
        $stickyData = json_decode($stickyCheck, true);
        
        $this->assertNotEmpty($stickyData, 'Should have at least one sticky post');
        $stickyTitles = array_column($stickyData, 'post_title');
        $this->assertContains('Sticky Post', $stickyTitles);

        unlink($tempFile);
    }

    public function testItCanImportPostsWithMenuOrder()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'wxr_');
        if (!$tempFile) {
            $this->fail("Failed to create a temporary file for the WXR!");
        }

        $wxrFile = new WXRFile(
            new SiteSettings(
                link: 'http://localhost:2626',
                title: 'Menu Order Test WXR',
                pubDate: 'Mon, 07 Jul 2025 10:00:00 +0000',
            )
        );

        $author = new Author(
            userLogin: 'testuser',
            userEmail: 'testuser@example.com',
        );
        $wxrFile->addAuthor($author);

        // Create pages with specific menu order (used for pages)
        $page1 = new Post(
            title: 'First Page',
            content: 'This is the first page.',
            excerpt: '',
            authorLogin: 'testuser',
            publishDate: '2025-07-07 10:00:00',
            slug: 'first-page',
            status: 'publish',
            postId: 100,
            post_type: 'page',
            menuOrder: 10
        );

        $page2 = new Post(
            title: 'Second Page',
            content: 'This is the second page.',
            excerpt: '',
            authorLogin: 'testuser',
            publishDate: '2025-07-07 11:00:00',
            slug: 'second-page',
            status: 'publish',
            postId: 101,
            post_type: 'page',
            menuOrder: 20
        );

        $wxrFile->addPost($page1);
        $wxrFile->addPost($page2);
        $wxrFile->save($tempFile);

        // Verify XML contains menu order
        $xml = $wxrFile->getAsString();
        $this->assertStringContainsString('<wp:menu_order><![CDATA[10]]></wp:menu_order>', $xml);
        $this->assertStringContainsString('<wp:menu_order><![CDATA[20]]></wp:menu_order>', $xml);

        // Import
        $importCommand = sprintf(
            './wp-test import --authors=create %s',
            escapeshellarg($tempFile)
        );
        shell_exec($importCommand);

        // Verify pages have correct menu order
        $pagesJson = shell_exec('./wp-test post list --post_type=page --orderby=menu_order --order=asc --format=json');
        $pagesData = json_decode($pagesJson, true);

        $this->assertCount(2, $pagesData);
        $this->assertEquals('First Page', $pagesData[0]['post_title']);
        $this->assertEquals(10, $pagesData[0]['menu_order']);
        $this->assertEquals('Second Page', $pagesData[1]['post_title']);
        $this->assertEquals(20, $pagesData[1]['menu_order']);

        unlink($tempFile);
    }

    public function testItCanImportPostsWithParentRelationships()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'wxr_');
        if (!$tempFile) {
            $this->fail("Failed to create a temporary file for the WXR!");
        }

        $wxrFile = new WXRFile(
            new SiteSettings(
                link: 'http://localhost:2626',
                title: 'Parent Child Test WXR',
                pubDate: 'Mon, 07 Jul 2025 10:00:00 +0000',
            )
        );

        $author = new Author(
            userLogin: 'testuser',
            userEmail: 'testuser@example.com',
        );
        $wxrFile->addAuthor($author);

        // Create parent page
        $parentPage = new Post(
            title: 'Parent Page',
            content: 'This is the parent page.',
            excerpt: '',
            authorLogin: 'testuser',
            publishDate: '2025-07-07 10:00:00',
            slug: 'parent-page',
            status: 'publish',
            postId: 100,
            post_type: 'page',
            postParent: 0
        );

        // Create child page referencing parent
        $childPage = new Post(
            title: 'Child Page',
            content: 'This is the child page.',
            excerpt: '',
            authorLogin: 'testuser',
            publishDate: '2025-07-07 11:00:00',
            slug: 'child-page',
            status: 'publish',
            postId: 101,
            post_type: 'page',
            postParent: 100
        );

        // Create grandchild page
        $grandchildPage = new Post(
            title: 'Grandchild Page',
            content: 'This is the grandchild page.',
            excerpt: '',
            authorLogin: 'testuser',
            publishDate: '2025-07-07 12:00:00',
            slug: 'grandchild-page',
            status: 'publish',
            postId: 102,
            post_type: 'page',
            postParent: 101
        );

        $wxrFile->addPost($parentPage);
        $wxrFile->addPost($childPage);
        $wxrFile->addPost($grandchildPage);
        $wxrFile->save($tempFile);

        // Verify XML contains parent relationships
        $xml = $wxrFile->getAsString();
        $this->assertStringContainsString('<wp:post_parent><![CDATA[0]]></wp:post_parent>', $xml);
        $this->assertStringContainsString('<wp:post_parent><![CDATA[100]]></wp:post_parent>', $xml);
        $this->assertStringContainsString('<wp:post_parent><![CDATA[101]]></wp:post_parent>', $xml);

        // Import
        $importCommand = sprintf(
            './wp-test import --authors=create %s',
            escapeshellarg($tempFile)
        );
        shell_exec($importCommand);

        // Verify parent relationships
        $pagesJson = shell_exec('./wp-test post list --post_type=page --format=json');
        $pagesData = json_decode($pagesJson, true);

        $this->assertCount(3, $pagesData);

        // Find each page and verify parent
        $pagesByTitle = [];
        foreach ($pagesData as $page) {
            $pagesByTitle[$page['post_title']] = $page;
        }

        $this->assertArrayHasKey('Parent Page', $pagesByTitle);
        $this->assertArrayHasKey('Child Page', $pagesByTitle);
        $this->assertArrayHasKey('Grandchild Page', $pagesByTitle);

        // Parent should have post_parent = 0
        $this->assertEquals(0, $pagesByTitle['Parent Page']['post_parent']);

        // Child should have parent as Parent Page's ID
        $parentId = $pagesByTitle['Parent Page']['ID'];
        $this->assertEquals($parentId, $pagesByTitle['Child Page']['post_parent']);

        // Grandchild should have parent as Child Page's ID
        $childId = $pagesByTitle['Child Page']['ID'];
        $this->assertEquals($childId, $pagesByTitle['Grandchild Page']['post_parent']);

        unlink($tempFile);
    }

    public function testParentRemappingWithNonSequentialIds()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'wxr_');
        if (!$tempFile) {
            $this->fail("Failed to create a temporary file for the WXR!");
        }

        $wxrFile = new WXRFile(
            new SiteSettings(
                link: 'http://localhost:2626',
                title: 'Non-Sequential ID Test WXR',
                pubDate: 'Mon, 07 Jul 2025 10:00:00 +0000',
            )
        );

        $author = new Author(
            userLogin: 'testuser',
            userEmail: 'testuser@example.com',
        );
        $wxrFile->addAuthor($author);

        // Use non-sequential IDs to test remapping
        $parentPage = new Post(
            title: 'Parent with ID 1000',
            content: 'Parent content.',
            excerpt: '',
            authorLogin: 'testuser',
            publishDate: '2025-07-07 10:00:00',
            slug: 'parent-1000',
            status: 'publish',
            postId: 1000,
            post_type: 'page',
            postParent: 0
        );

        $childPage = new Post(
            title: 'Child of 1000',
            content: 'Child content.',
            excerpt: '',
            authorLogin: 'testuser',
            publishDate: '2025-07-07 11:00:00',
            slug: 'child-of-1000',
            status: 'publish',
            postId: 2000,
            post_type: 'page',
            postParent: 1000
        );

        $wxrFile->addPost($parentPage);
        $wxrFile->addPost($childPage);
        $wxrFile->save($tempFile);

        // Import
        $importCommand = sprintf(
            './wp-test import --authors=create %s',
            escapeshellarg($tempFile)
        );
        shell_exec($importCommand);

        // Get pages and verify parent relationship was remapped
        $pagesJson = shell_exec('./wp-test post list --post_type=page --format=json');
        $pagesData = json_decode($pagesJson, true);

        $this->assertCount(2, $pagesData);

        // Find pages by title
        $pagesByTitle = [];
        foreach ($pagesData as $page) {
            $pagesByTitle[$page['post_title']] = $page;
        }

        // Verify parent relationship - child should have parent's new ID
        $parentNewId = $pagesByTitle['Parent with ID 1000']['ID'];
        $childParentId = $pagesByTitle['Child of 1000']['post_parent'];

        $this->assertEquals($parentNewId, $childParentId, 
            'Child should reference parent by its new WordPress ID, not the original 1000');

        unlink($tempFile);
    }

    public function testNumericPostIdFallback()
    {
        // Test that posts without explicit IDs get numeric IDs (not uniqid)
        $siteSettings = new SiteSettings(
            link: 'http://localhost:2626',
            title: 'Auto ID Test WXR',
            pubDate: 'Mon, 07 Jul 2025 10:00:00 +0000',
        );

        $wxrFile = new WXRFile($siteSettings);

        // Create posts without explicit postId
        $post1 = new Post(
            title: 'First Auto ID Post',
            content: 'Content 1',
            excerpt: '',
            authorLogin: 'admin',
            publishDate: '2025-07-07 10:00:00',
            slug: 'first-auto',
            postId: 0  // Will use fallback
        );

        $post2 = new Post(
            title: 'Second Auto ID Post',
            content: 'Content 2',
            excerpt: '',
            authorLogin: 'admin',
            publishDate: '2025-07-07 11:00:00',
            slug: 'second-auto',
            postId: 0  // Will use fallback
        );

        $wxrFile->addPost($post1);
        $wxrFile->addPost($post2);

        $xml = $wxrFile->getAsString();

        // Should have numeric IDs (1000 and 1001 based on counter starting at 1000)
        $this->assertStringContainsString('<wp:post_id><![CDATA[1000]]></wp:post_id>', $xml);
        $this->assertStringContainsString('<wp:post_id><![CDATA[1001]]></wp:post_id>', $xml);

        // Should NOT have alpha-numeric uniqid-style IDs
        $this->assertDoesNotMatchRegularExpression('/<wp:post_id><!\[CDATA\[[a-f0-9]{13}\]\]><\/wp:post_id>/', $xml);
    }

    public function testCombinedFeatures()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'wxr_');
        if (!$tempFile) {
            $this->fail("Failed to create a temporary file for the WXR!");
        }

        $wxrFile = new WXRFile(
            new SiteSettings(
                link: 'http://localhost:2626',
                title: 'Combined Features Test WXR',
                pubDate: 'Mon, 07 Jul 2025 10:00:00 +0000',
            )
        );

        $author = new Author(
            userLogin: 'testuser',
            userEmail: 'testuser@example.com',
        );
        $wxrFile->addAuthor($author);

        // Create a complex hierarchy with all features
        $parentPage = new Post(
            title: 'Featured Parent',
            content: 'This is a featured parent page.',
            excerpt: '',
            authorLogin: 'testuser',
            publishDate: '2025-07-07 10:00:00',
            slug: 'featured-parent',
            status: 'publish',
            postId: 100,
            post_type: 'page',
            postParent: 0,
            menuOrder: 1,
            isSticky: 1  // Pages can be sticky too
        );

        $childPage = new Post(
            title: 'Ordered Child',
            content: 'This is an ordered child page.',
            excerpt: '',
            authorLogin: 'testuser',
            publishDate: '2025-07-07 11:00:00',
            slug: 'ordered-child',
            status: 'publish',
            postId: 101,
            post_type: 'page',
            postParent: 100,
            menuOrder: 5,
            isSticky: 0
        );

        $wxrFile->addPost($parentPage);
        $wxrFile->addPost($childPage);
        $wxrFile->save($tempFile);

        $xml = $wxrFile->getAsString();

        // Verify all features are present in XML
        $this->assertStringContainsString('<wp:is_sticky>1</wp:is_sticky>', $xml); // Parent is sticky
        $this->assertStringContainsString('<wp:is_sticky>0</wp:is_sticky>', $xml); // Child is not sticky
        $this->assertStringContainsString('<wp:menu_order><![CDATA[1]]></wp:menu_order>', $xml); // Parent order
        $this->assertStringContainsString('<wp:menu_order><![CDATA[5]]></wp:menu_order>', $xml); // Child order
        $this->assertStringContainsString('<wp:post_parent><![CDATA[0]]></wp:post_parent>', $xml); // Parent has no parent
        $this->assertStringContainsString('<wp:post_parent><![CDATA[100]]></wp:post_parent>', $xml); // Child references parent

        // Import
        $importCommand = sprintf(
            './wp-test import --authors=create %s',
            escapeshellarg($tempFile)
        );
        shell_exec($importCommand);

        // Verify in WordPress
        $pagesJson = shell_exec('./wp-test post list --post_type=page --format=json');
        $pagesData = json_decode($pagesJson, true);

        $this->assertCount(2, $pagesData);

        // Find featured parent and verify it's sticky
        $stickyCheck = shell_exec('./wp-test post list --post_type=page --sticky=1 --format=json');
        $stickyData = json_decode($stickyCheck, true);
        
        $stickyTitles = array_column($stickyData, 'post_title');
        $this->assertContains('Featured Parent', $stickyTitles);

        // Verify child is not sticky
        $nonStickyJson = shell_exec('./wp-test post list --post_type=page --sticky=0 --format=json');
        $nonStickyData = json_decode($nonStickyJson, true);
        
        $nonStickyTitles = array_column($nonStickyData, 'post_title');
        $this->assertContains('Ordered Child', $nonStickyTitles);

        unlink($tempFile);
    }
}
