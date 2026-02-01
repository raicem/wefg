<?php

namespace Tests\Unit;

use Raicem\WEFG\Comment;
use Raicem\WEFG\Meta;
use Tests\TestCase;

class CommentTest extends TestCase
{
    public function test_comment_can_be_created_with_required_fields()
    {
        $comment = new Comment(
            comment_author: 'John Doe',
            comment_author_email: 'john@example.com',
            comment_content: 'This is a test comment'
        );

        $this->assertEquals('John Doe', $comment->comment_author);
        $this->assertEquals('john@example.com', $comment->comment_author_email);
        $this->assertEquals('This is a test comment', $comment->comment_content);
        $this->assertEquals('1', $comment->comment_approved);
        $this->assertEquals('0', $comment->comment_parent);
        $this->assertEquals('0', $comment->comment_user_id);
        $this->assertEquals('', $comment->comment_type);
        $this->assertEquals('', $comment->comment_author_url);
        $this->assertEquals('', $comment->comment_author_IP);
        $this->assertEquals('', $comment->comment_agent);
        $this->assertNotEmpty($comment->comment_date);
        $this->assertNotEmpty($comment->comment_date_gmt);
        $this->assertNotEmpty($comment->comment_id);
        $this->assertEquals([], $comment->meta);
    }

    public function test_comment_can_be_created_with_all_fields()
    {
        $comment = new Comment(
            comment_author: 'Jane Doe',
            comment_author_email: 'jane@example.com',
            comment_content: 'Another test comment',
            comment_date: '2024-01-01 12:00:00',
            comment_date_gmt: '2024-01-01 12:00:00',
            comment_author_url: 'https://jane.example.com',
            comment_author_IP: '192.168.1.1',
            comment_agent: 'Mozilla/5.0',
            comment_type: 'pingback',
            comment_parent: '5',
            comment_user_id: '10',
            comment_approved: '0',
            comment_id: 123
        );

        $this->assertEquals('Jane Doe', $comment->comment_author);
        $this->assertEquals('jane@example.com', $comment->comment_author_email);
        $this->assertEquals('Another test comment', $comment->comment_content);
        $this->assertEquals('2024-01-01 12:00:00', $comment->comment_date);
        $this->assertEquals('2024-01-01 12:00:00', $comment->comment_date_gmt);
        $this->assertEquals('https://jane.example.com', $comment->comment_author_url);
        $this->assertEquals('192.168.1.1', $comment->comment_author_IP);
        $this->assertEquals('Mozilla/5.0', $comment->comment_agent);
        $this->assertEquals('pingback', $comment->comment_type);
        $this->assertEquals('5', $comment->comment_parent);
        $this->assertEquals('10', $comment->comment_user_id);
        $this->assertEquals('0', $comment->comment_approved);
        $this->assertEquals(123, $comment->comment_id);
    }

    public function test_comment_can_have_metadata()
    {
        $comment = new Comment(
            comment_author: 'John Doe',
            comment_author_email: 'john@example.com',
            comment_content: 'This is a test comment'
        );

        $comment->meta[] = new Meta('rating', '5');
        $comment->meta[] = new Meta('verified', 'yes');

        $this->assertCount(2, $comment->meta);
        $this->assertEquals('rating', $comment->meta[0]->meta_key);
        $this->assertEquals('5', $comment->meta[0]->meta_value);
        $this->assertEquals('verified', $comment->meta[1]->meta_key);
        $this->assertEquals('yes', $comment->meta[1]->meta_value);
    }
}
