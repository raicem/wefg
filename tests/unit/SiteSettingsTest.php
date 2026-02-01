<?php declare(strict_types=1);

namespace Raicem\WEFG\Tests;

use PHPUnit\Framework\TestCase;
use Raicem\WEFG\SiteSettings;

class SiteSettingsTest extends TestCase
{
    public function testConstructorWithMinimalRequiredParameters()
    {
        $link = 'https://example.com';
        $settings = new SiteSettings($link);

        // Test required parameter
        $this->assertEquals($link, $settings->link);

        // Test default values
        $this->assertEquals($link, $settings->baseSiteUrl);
        $this->assertEquals($link, $settings->baseBlogUrl);
        $this->assertEquals('en-US', $settings->language);
        $this->assertEquals('', $settings->description);
        $this->assertMatchesRegularExpression('/WEFG WXR - \d{2}-\d{2}-\d{2}/', $settings->title);
        $this->assertMatchesRegularExpression('/[A-Za-z]{3}, \d{2} [A-Za-z]{3} \d{4} \d{2}:\d{2}:\d{2} \+0000/', $settings->pubDate);
        $this->assertEquals('1.2', $settings->wxrVersion);
    }

    public function testConstructorWithAllParameters()
    {
        $settings = new SiteSettings(
            link: 'https://example.com',
            title: 'Custom Title',
            description: 'Site Description',
            language: 'fr-FR',
            pubDate: 'Sun, 29 Dec 2024 19:58:14 +0000',
            wxrVersion: '1.2',
            baseSiteUrl: 'https://example.com/site',
            baseBlogUrl: 'https://example.com/blog'
        );

        $this->assertEquals('Custom Title', $settings->title);
        $this->assertEquals('https://example.com', $settings->link);
        $this->assertEquals('Site Description', $settings->description);
        $this->assertEquals('fr-FR', $settings->language);
        $this->assertEquals('https://example.com/site', $settings->baseSiteUrl);
        $this->assertEquals('https://example.com/blog', $settings->baseBlogUrl);
    }

    public function testBaseUrlsFallbackToLink()
    {
        $link = 'https://example.com';
        $settings = new SiteSettings($link);

        $this->assertEquals($link, $settings->baseSiteUrl);
        $this->assertEquals($link, $settings->baseBlogUrl);
    }

    public function testTitleGenerationWhenNull()
    {
        $link = 'https://example.com';
        $settings = new SiteSettings($link);
        
        // The title should be in format "WEFG WXR - dd-mm-yy"
        $expectedPattern = '/WEFG WXR - \d{2}-\d{2}-\d{2}/';
        $this->assertMatchesRegularExpression($expectedPattern, $settings->title);
    }
}
