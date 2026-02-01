<?php

namespace Raicem\WEFG;

class Attachment extends Post
{
    public $attachment_url;
    public $parent;

    public function __construct(
        string $title,
        ?string $attachment_url,
        ?Post $parent = null,
        int $postId = 0,
        string $slug = ''
    ) {
        parent::__construct(
            $title,
            '',
            '',
            '',
            '',
            $slug,
            'attachment',
            $postId,
            [],
            [],
            [],
            [],
            [],
            'attachment',
            '',
            '',
            '',
            '',
            'open',
            'open',
            0,
            $parent ? $parent->postId : 0
        );
        $this->attachment_url = $attachment_url;
        $this->parent = $parent;
    }
}
