<?php

namespace Raicem\WEFG\Terms;

class Term
{
    public $term_id;
    public $term_taxonomy;
    public $term_slug;
    public $term_name;
    public $term_description;
    public $term_parent;
    public $term_meta;

    public function __construct(
        int $term_id,
        string $term_taxonomy,
        string $term_slug,
        string $term_name,
        string $term_description,
        ?string $term_parent = null,
        ?array $term_meta = []
    ) {
        $this->term_id = $term_id;
        $this->term_taxonomy = $term_taxonomy;
        $this->term_slug = $term_slug;
        $this->term_name = $term_name;
        $this->term_description = $term_description;
        $this->term_parent = $term_parent;
        $this->term_meta = $term_meta;
    }
}
