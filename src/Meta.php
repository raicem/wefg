<?php

namespace Raicem\WEFG;

class Meta {
    public $meta_key;
    public $meta_value;

    public function __construct(
        string $meta_key,
        string $meta_value
    ) {
        $this->meta_key = $meta_key;
        $this->meta_value = $meta_value;
    }
}
