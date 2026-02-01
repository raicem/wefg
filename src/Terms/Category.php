<?php

namespace Raicem\WEFG\Terms;

class Category 
{
    public $term_id;
    public $category_nicename;
    public $cat_name;
    public $category_parent;
    public $category_description;
    public $term_meta;

    public function __construct(
        int $term_id,
        string $category_nicename,
        string $cat_name,
        ?string $category_parent = null,
        ?string $category_description = null,
        ?array $term_meta = null
    ) {
        $this->term_id = $term_id;
        $this->category_nicename = $category_nicename;
        $this->cat_name = $cat_name;
        $this->category_parent = $category_parent;
        $this->category_description = $category_description;
        $this->term_meta = $term_meta;
    }
}
