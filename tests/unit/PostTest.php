<?php declare(strict_types=1);

namespace Raicem\WEFG\Tests;

use PHPUnit\Framework\TestCase;
use Raicem\WEFG\Post;

class PostTest extends TestCase
{
    public function testConstructorWithMinimalRequiredParameters()
    {
        $post = new Post(
            title: 'Test Title',
            content: 'Test Content',
            excerpt: 'Test Excerpt',
            authorLogin: 'testuser',
            publishDate: '2024-12-29 19:58:14',
            slug: 'test-post'
        );

        $this->assertEquals('Test Title', $post->title);
        $this->assertEquals('Test Content', $post->content);
        $this->assertEquals('Test Excerpt', $post->excerpt);
        $this->assertEquals('testuser', $post->authorLogin);
        $this->assertEquals('2024-12-29 19:58:14', $post->publishDate);
        $this->assertEquals('test-post', $post->slug);
        $this->assertEquals('publish', $post->status);
        $this->assertEquals('post', $post->post_type); // Default post type
        $this->assertEquals([], $post->categories);
        $this->assertEquals([], $post->tags);
        $this->assertEquals([], $post->meta);
        $this->assertEquals([], $post->comments);
    }

    public function testConstructorWithCustomPostType()
    {
        $post = new Post(
            title: 'Custom Post',
            content: 'Custom Content',
            excerpt: 'Custom Excerpt',
            authorLogin: 'testuser',
            publishDate: '2024-12-29 19:58:14',
            slug: 'custom-post',
            post_type: 'my_custom_type'
        );

        $this->assertEquals('my_custom_type', $post->post_type);
    }
}
