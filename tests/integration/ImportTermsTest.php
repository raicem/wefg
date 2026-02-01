<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

use Raicem\WEFG\WXRFile;
use Raicem\WEFG\SiteSettings;
use Raicem\WEFG\Author;
use Raicem\WEFG\Meta;
use Raicem\WEFG\Terms\Category;
use Raicem\WEFG\Terms\Tag;
use Raicem\WEFG\Terms\Term;

class ImportTermsTest extends TestCase
{
    public function setUp(): void
    {
        shell_exec('composer run reset-test-db --quiet');

        // Start a WordPress site
        parent::setUp();
    }

    public function testItCanImportCategories() {
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

        $wxrFile->addAuthor( $author1 );

        $childCategory = new Category(
            term_id: 2,
            category_nicename: 'category-2',
            category_parent: 'category-1',
            cat_name: 'Category 2',
            category_description: 'Category 2 description',
            term_meta: [
                new Meta( 'meta_key', 'meta_value' ),
            ],
        );

        $parentCategory = new Category(
            term_id: 1,
            category_nicename: 'category-1',
            cat_name: 'Category 1',
            category_description: 'Category 1 description',
        );

        $category3 = new Category(
            term_id: 3,
            category_nicename: 'category-3',
            cat_name: 'Category 3',
            category_description: 'Category 3 description',
        );

        $wxrFile->addCategories([$parentCategory, $childCategory, $category3]);

        $wxrFile->save($tempFile);

        $importCommand = sprintf(
            './wp-test import --authors=create %s', 
            escapeshellarg($tempFile)
        );

        shell_exec($importCommand);

        unlink($tempFile);

        // 1. Assert categories
        $result1 = shell_exec('./wp-test term get category category-1 --by=slug --format=json');
        $result1 = json_decode($result1, true);

        $this->assertEquals($parentCategory->cat_name, $result1['name']);
        $this->assertEquals($parentCategory->category_description, $result1['description']);

        $result2 = shell_exec('./wp-test term get category category-2 --by=slug --format=json');
        $result2 = json_decode($result2, true);
        $this->assertEquals($childCategory->cat_name, $result2['name']);
        $this->assertEquals($childCategory->category_description, $result2['description']);

        $result2meta = shell_exec(sprintf('./wp-test term meta get %s meta_key', $result2['term_id']));
        $this->assertEquals($childCategory->term_meta[0]->meta_value, trim($result2meta));

        $result3 = shell_exec('./wp-test term get category category-3 --by=slug --format=json');
        $result3 = json_decode($result3, true);
        $this->assertEquals($category3->cat_name, $result3['name']);
        $this->assertEquals($category3->category_description, $result3['description']);
    }

    public function testItCanImportTags() {
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

        $wxrFile->addAuthor( $author1 );

        $tag1 = new Tag(
            term_id: 4,
            tag_slug: 'tag-1',
            tag_name: 'Tag 1',
            tag_description: 'Tag 1 description',
            term_meta: [],
        );

        $tag2 = new Tag(
            term_id: 5,
            tag_slug: 'tag-2',
            tag_name: 'Tag 2',
            tag_description: 'Tag 2 description',
            term_meta: [
                new Meta( 'term_meta_key', 'term_meta_value' ),
            ],
        );

        $wxrFile->addTags([$tag1, $tag2]);

        $wxrFile->save($tempFile);

        $importCommand = sprintf(
            './wp-test import --authors=create %s', 
            escapeshellarg($tempFile)
        );

        shell_exec($importCommand);

        unlink($tempFile);

        $tag1Command = shell_exec('./wp-test term get post_tag tag-1 --by=slug --format=json');
        $tag1Result= json_decode($tag1Command, true);
        $this->assertEquals($tag1->tag_name, $tag1Result['name']);
        $this->assertEquals($tag1->tag_description, $tag1Result['description']);

        $tag2Command = shell_exec('./wp-test term get post_tag tag-2 --by=slug --format=json');
        $tag2Result = json_decode($tag2Command, true);
        $this->assertEquals($tag2->tag_name, $tag2Result['name']);
        $this->assertEquals($tag2->tag_description, $tag2Result['description']);

        $tag2MetaCommand = shell_exec(sprintf('./wp-test term meta get %s term_meta_key', $tag2Result['term_id']));
        $this->assertEquals($tag2->term_meta[0]->meta_value, trim($tag2MetaCommand));
    }

    public function testItCanImportTerms() {
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

        $wxrFile->addAuthor( $author1 );

        $term1 = new Term(
            term_id: 6,
            term_taxonomy: 'category',
            term_slug: 'term-1',
            term_name: 'Term 1',
            term_description: 'Term 1 description',
            term_meta: [
                new Meta( 'meta_key', 'meta_value' ),
            ],
        );

        $term2 = new Term(
            term_id: 7,
            term_taxonomy: 'post_tag',
            term_slug: 'term-2',
            term_name: 'Term 2',
            term_description: 'Term 2 description',
        );

        $wxrFile->addTerms([$term1, $term2]);

        $wxrFile->save($tempFile);

        $importCommand = sprintf(
            './wp-test import --authors=create %s', 
            escapeshellarg($tempFile)
        );

        shell_exec($importCommand);

        unlink($tempFile);

        $result1 = shell_exec('./wp-test term get category term-1 --by=slug --format=json');
        $result1 = json_decode($result1, true);

        $this->assertEquals($term1->term_name, $result1['name']);
        $this->assertEquals($term1->term_description, $result1['description']);

        $result1Meta = shell_exec(sprintf('./wp-test term meta get %s meta_key', $result1['term_id']));
        $this->assertEquals($term1->term_meta[0]->meta_value, trim($result1Meta));

        $result2 = shell_exec('./wp-test term get post_tag term-2 --by=slug --format=json');
        $result2 = json_decode($result2, true);
        $this->assertEquals($term2->term_name, $result2['name']);
        $this->assertEquals($term2->term_description, $result2['description']);
    }
}
