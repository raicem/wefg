<?php

namespace Raicem\WEFG\Terms;

class Tag
{
    public $term_id;
    public $tag_slug;
    public $tag_name;
    public $tag_description;
    public $term_meta;

    public function __construct(
        int $term_id,
        string $tag_slug,
        string $tag_name,
        ?string $tag_description = null,
        ?array $term_meta = null
    ) {
        $this->term_id = $term_id;
        $this->tag_slug = $tag_slug;
        $this->tag_name = $tag_name;
        $this->tag_description = $tag_description;
        $this->term_meta = $term_meta;
    }
}
