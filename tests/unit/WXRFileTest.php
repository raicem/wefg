<?php declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Raicem\WEFG\WXRFile;
use Raicem\WEFG\SiteSettings;
use Raicem\WEFG\Author;
use Raicem\WEFG\Terms\Category;

final class WXRFileTest extends TestCase {
    public function testItCanCreateASkeletonXRFile() {
        $wxrFile = $this->newWXRFile();
        $generated = $wxrFile->getAsString();

        $expected = file_get_contents('tests/unit/data/skeleton-wxr.xml');

        $this->assertXmlStringEqualsXmlString( 
            $expected, 
            $generated,
        );
    }

    public function testItCanAddAuthorsToTheWXRFile() {
        $wxrFile = $this->newWXRFile();

        $wxrFile->addAuthor(
            new Author(
                userLogin: 'raicem',
                userEmail: 'cem@cemunalan.com.tr',
            )
        );

        $wxrFile->addAuthor(
            new Author(
                userLogin: 'raicem2',
                userEmail: 'cem2@cemunalan.com.tr',
                displayName: 'Cem Ünalan',
                firstName: 'Cem',
                lastName: 'Ünalan',
            )
        );

        $wxrFile->addAuthor(
            new Author(
                userLogin: 'raicem3',
            )
        );

        $generated = $wxrFile->getAsString();
        $expected = file_get_contents('tests/unit/data/authors-wxr.xml');

        $this->assertXmlStringEqualsXmlString( 
            $expected, 
            $generated,
        );
    }

    public function testItCanSortCategories() {
        $wxrFile = $this->newWXRFile();

        $categories = [
            new Category(term_id: 2, category_parent: 'category-1', category_nicename: 'category-2', cat_name: 'Category 2'),
            new Category(term_id: 1, category_nicename: 'category-1', cat_name: 'Category 1'),
            new Category(term_id: 5, category_parent: 'category-4', category_nicename: 'category-5', cat_name: 'Category 5'),
            new Category(term_id: 4, category_parent: 'category-3', category_nicename: 'category-4', cat_name: 'Category 4'),
            new Category(term_id: 3, category_parent: 'category-2', category_nicename: 'category-3', cat_name: 'Category 3'),
            new Category(term_id: 6, category_nicename: 'category-6', cat_name: 'Category 6'),
        ];

        $sortedCategories = $wxrFile->sortCategories($categories);
        $sortedCategories = array_values(array_map(fn (Category $category) => $category->term_id, $sortedCategories));

        $this->assertEquals([1, 6, 2, 3, 4, 5], $sortedCategories);
    }

    public function testItCanAddPostsToTheWXRFile() {
        $wxrFile = $this->newWXRFile();

        $wxrFile->addPost(
            new \Raicem\WEFG\Post(
                title: 'Test Post',
                content: 'Test Content',
                excerpt: 'Test Excerpt',
                authorLogin: 'testuser',
                publishDate: '2024-12-29 19:58:14',
                postId: 123,
                slug: 'test-post',
                categories: ['Category 1'],
                tags: ['Tag 1']
            )
        );

        $generated = $wxrFile->getAsString();
        $expected = file_get_contents('tests/unit/data/posts-wxr.xml');

        $this->assertXmlStringEqualsXmlString( 
            $expected, 
            $generated,
        );
    }

    public function testItUsesExplicitSlugsForArrayCategories() {
        $wxrFile = $this->newWXRFile();

        $wxrFile->addPost(
            new \Raicem\WEFG\Post(
                title: 'Test Post',
                content: 'Test Content',
                excerpt: 'Test Excerpt',
                authorLogin: 'testuser',
                publishDate: '2024-12-29 19:58:14',
                postId: 124,
                slug: 'test-post-2',
                categories: [
                    ['name' => 'Arkeoloji Müzeleri', 'slug' => 'arkeoloji-muzeleri'],
                ],
                tags: [
                    ['name' => 'Müze Etiketi', 'slug' => 'muze-etiketi'],
                ],
                terms: [
                    'museum_list' => [
                        ['name' => 'Özel Liste', 'slug' => 'ozel-liste'],
                    ],
                ],
            )
        );

        $generated = $wxrFile->getAsString();
        $xml = new \SimpleXMLElement($generated);
        $item = $xml->channel->item;

        $categories = [];
        foreach ($item->category as $cat) {
            $categories[] = [
                'domain' => (string) $cat['domain'],
                'nicename' => (string) $cat['nicename'],
                'name' => (string) $cat,
            ];
        }

        $this->assertContains(
            ['domain' => 'category', 'nicename' => 'arkeoloji-muzeleri', 'name' => 'Arkeoloji Müzeleri'],
            $categories
        );
        $this->assertContains(
            ['domain' => 'post_tag', 'nicename' => 'muze-etiketi', 'name' => 'Müze Etiketi'],
            $categories
        );
        $this->assertContains(
            ['domain' => 'museum_list', 'nicename' => 'ozel-liste', 'name' => 'Özel Liste'],
            $categories
        );
    }

    private function newWXRFile(): WXRFile {
        return new WXRFile(
            new SiteSettings(
                link: 'http://localhost:2626',
                title: 'Example WXR',
                pubDate: 'Sun, 29 Dec 2024 19:58:14 +0000',
            )
        );
    }
}
