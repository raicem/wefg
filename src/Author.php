<?php

namespace Raicem\WEFG;

class Author {
    public $userLogin;
    public $userEmail;
    public $displayName;
    public $firstName;
    public $lastName;
    public $authorId;

    public function __construct(
        string $userLogin,
        ?string $userEmail = null,
        ?string $displayName = null,
        ?string $firstName = null,
        ?string $lastName = null,
        ?int $authorId = null
    ) {
        $this->userLogin = $userLogin;
        $this->userEmail = $userEmail;
        $this->displayName = $displayName;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->authorId = $authorId;
    }
}
