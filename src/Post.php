<?php

namespace Raicem\WEFG;

class Post {
    public $attachment_url = null;
    public $title;
    public $content;
    public $excerpt;
    public $authorLogin;
    public $publishDate;
    public $slug;
    public $status;
    public $postId;
    public $categories;
    public $tags;
    public $terms;
    public $meta;
    public $comments;
    public $post_type;
    public $post_password;
    public $postDateGmt;
    public $postModified;
    public $postModifiedGmt;
    public $commentStatus;
    public $pingStatus;
    public $isSticky;
    public $postParent;
    public $menuOrder;
    public $link;
    public $guid;

    public function __construct(
        string $title,
        string $content,
        string $excerpt,
        string $authorLogin,
        string $publishDate,
        string $slug,
        string $status = 'publish',
        int $postId = 0,
        array $categories = [],
        array $tags = [],
        array $terms = [],
        array $meta = [],
        array $comments = [],
        string $post_type = 'post',
        string $post_password = '',
        string $postDateGmt = '',
        string $postModified = '',
        string $postModifiedGmt = '',
        string $commentStatus = 'open',
        string $pingStatus = 'open',
        int $isSticky = 0,
        int $postParent = 0,
        int $menuOrder = 0,
        string $link = '',
        string $guid = ''
    ) {
        $this->title = $title;
        $this->content = $content;
        $this->excerpt = $excerpt;
        $this->authorLogin = $authorLogin;
        $this->publishDate = $publishDate;
        $this->slug = $slug;
        $this->status = $status;
        $this->postId = $postId;
        $this->categories = $categories;
        $this->tags = $tags;
        $this->terms = $terms;
        $this->meta = $meta;
        $this->comments = $comments;
        $this->post_type = $post_type;
        $this->post_password = $post_password;
        $this->postDateGmt = $postDateGmt;
        $this->postModified = $postModified;
        $this->postModifiedGmt = $postModifiedGmt;
        $this->commentStatus = $commentStatus;
        $this->pingStatus = $pingStatus;
        $this->isSticky = $isSticky;
        $this->postParent = $postParent;
        $this->menuOrder = $menuOrder;
        $this->link = $link;
        $this->guid = $guid;
    }
}
