<?php

namespace Raicem\WEFG;

class SiteSettings {
    public $link;
    public $title;
    public $description;
    public $language;
    public $pubDate;
    public $wxrVersion;
    public $baseSiteUrl;
    public $baseBlogUrl;

    public function __construct(
        string $link,
        ?string $title = null,
        string $description = '',
        string $language = 'en-US',
        ?string $pubDate = null,
        string $wxrVersion = '1.2',
        ?string $baseSiteUrl = null,
        ?string $baseBlogUrl = null
    ) {
        $this->link = $link;
        $this->title = $title ?? 'WEFG WXR - ' . date('d-m-y');
        $this->description = $description;
        $this->language = $language;
        $this->pubDate = $pubDate ?? date('D, d M Y H:i:s +0000');
        $this->wxrVersion = $wxrVersion;
        $this->baseSiteUrl = $baseSiteUrl ?? $link;
        $this->baseBlogUrl = $baseBlogUrl ?? $link;
    }
}
