<?php

namespace Raicem\WEFG;

class Comment {
    public $comment_author;
    public $comment_author_email;
    public $comment_content;
    public $comment_date;
    public $comment_date_gmt;
    public $comment_author_url;
    public $comment_author_IP;
    public $comment_agent;
    public $comment_type;
    public $comment_parent;
    public $comment_user_id;
    public $comment_approved;
    public $comment_id;
    public $meta;

    public function __construct(
        string $comment_author,
        string $comment_author_email,
        string $comment_content,
        string $comment_date = '',
        string $comment_date_gmt = '',
        string $comment_author_url = '',
        string $comment_author_IP = '',
        string $comment_agent = '',
        string $comment_type = '',
        string $comment_parent = '0',
        string $comment_user_id = '0',
        string $comment_approved = '1',
        int $comment_id = 0,
        array $meta = []
    ) {
        $this->comment_author = $comment_author;
        $this->comment_author_email = $comment_author_email;
        $this->comment_content = $comment_content;
        $this->comment_date = $comment_date ?: date('Y-m-d H:i:s');
        $this->comment_date_gmt = $comment_date_gmt ?: gmdate('Y-m-d H:i:s');
        $this->comment_author_url = $comment_author_url;
        $this->comment_author_IP = $comment_author_IP;
        $this->comment_agent = $comment_agent;
        $this->comment_type = $comment_type;
        $this->comment_parent = $comment_parent;
        $this->comment_user_id = $comment_user_id;
        $this->comment_approved = $comment_approved;
        $this->comment_id = $comment_id ?: random_int(1, 999999);
        $this->meta = $meta;
    }
}
