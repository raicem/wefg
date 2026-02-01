<?php

namespace Tests\Integration;

use Raicem\WEFG\Post;
use Raicem\WEFG\SiteSettings;
use Raicem\WEFG\WXRFile;
use Tests\TestCase;

class ImportCustomTaxonomiesTest extends TestCase
{
    public function test_can_import_custom_taxonomies()
    {
        $post = new Post(
            'Test Post',
            'Test Content',
            'Test Excerpt',
            'admin',
            '2024-01-01 12:00:00',
            'test-post'
        );
        $post->terms['my_custom_taxonomy'] = ['my_term'];

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

        $this->assertStringContainsString('<category domain="my_custom_taxonomy" nicename="my_term"><![CDATA[my_term]]></category>', $xml);
    }
}
