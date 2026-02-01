<?php

namespace Raicem\WEFG;

use Raicem\WEFG\Terms\Category;
use Raicem\WEFG\Terms\Tag;
use Raicem\WEFG\Terms\Term;

class WXRFile {
    private $wxr;
    private $channel;
    public $posts = [];
    private int $postIdCounter = 1000;

    public function __construct(SiteSettings $siteSettings) {
        $wxr = new \DomDocument('1.0', 'UTF-8');

        $rss = $wxr->createElement('rss');
        $rss->setAttribute('version', '2.0');
        $rss->setAttribute('xmlns:excerpt', 'http://wordpress.org/export/1.2/excerpt/');
        $rss->setAttribute('xmlns:content', 'http://purl.org/rss/1.0/modules/content/');
        $rss->setAttribute('xmlns:wfw', 'http://wellformedweb.org/CommentAPI/');
        $rss->setAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');
        $rss->setAttribute('xmlns:wp', 'http://wordpress.org/export/1.2/');

        $wxr->appendChild($rss);

        $channel = $wxr->createElement('channel');
        $channel->appendChild($wxr->createElement('title', $siteSettings->title));
        $channel->appendChild($wxr->createElement('link', $siteSettings->link));
        $channel->appendChild($wxr->createElement('description', $siteSettings->description));
        $channel->appendChild($wxr->createElement('pubDate', $siteSettings->pubDate));
        $channel->appendChild($wxr->createElement('language', $siteSettings->language));
        $channel->appendChild($wxr->createElement('wp:wxr_version', $siteSettings->wxrVersion));
        $channel->appendChild($wxr->createElement('wp:base_site_url', $siteSettings->baseSiteUrl));
        $channel->appendChild($wxr->createElement('wp:base_blog_url', $siteSettings->baseBlogUrl));

        $rss->appendChild($channel);

        $this->wxr = $wxr;
        $this->channel = $channel;
    }

    public function addAuthor(Author $author): void {
        $authorElement = $this->wxr->createElement('wp:author');

        if ($author->authorId !== null) {
            $authorElement->appendChild($this->wxr->createElement('wp:author_id', (string) $author->authorId));
        }

        // Create CDATA section and wrap it in the author_login element
        $authorElement->appendChild($this->elementWithCDATA('wp:author_login', $author->userLogin));

        if (!empty($author->userEmail)) {
            $authorElement->appendChild($this->elementWithCDATA('wp:author_email', $author->userEmail));
        }

        if (!empty($author->displayName)) {
            $authorElement->appendChild($this->elementWithCDATA('wp:author_display_name', $author->displayName));
        }

        if (!empty($author->firstName)) {
            $authorElement->appendChild($this->elementWithCDATA('wp:author_first_name', $author->firstName));
        }

        if (!empty($author->lastName)) {
            $authorElement->appendChild($this->elementWithCDATA('wp:author_last_name', $author->lastName));
        }

        $this->channel->appendChild($authorElement);
    }

    public function addPost(Post $post): void {
        $this->posts[] = $post;

        $postElement = $this->wxr->createElement('item');

        $postElement->appendChild($this->elementWithCDATA('title', $post->title));

        if ($post->link !== '') {
            $postElement->appendChild($this->wxr->createElement('link', $post->link));
        }

        if ($post->publishDate !== '') {
            $pubDate = (new \DateTime($post->publishDate))->format(\DateTime::RSS);
            $postElement->appendChild($this->wxr->createElement('pubDate', $pubDate));
        }

        $postElement->appendChild($this->elementWithCDATA('dc:creator', $post->authorLogin));

        if ($post->guid !== '') {
            $guidElement = $this->wxr->createElement('guid', $post->guid);
            $guidElement->setAttribute('isPermaLink', 'false');
            $postElement->appendChild($guidElement);
        }

        $postElement->appendChild($this->wxr->createElement('description'));
        $postElement->appendChild($this->elementWithCDATA('content:encoded', $post->content));
        $postElement->appendChild($this->elementWithCDATA('excerpt:encoded', $post->excerpt));
        $postElement->appendChild($this->elementWithCDATA('wp:post_id', (string) ($post->postId ?: $this->postIdCounter++)));
        $postElement->appendChild($this->elementWithCDATA('wp:post_date', $post->publishDate));
        $postElement->appendChild($this->elementWithCDATA('wp:post_date_gmt', $post->postDateGmt));
        $postElement->appendChild($this->elementWithCDATA('wp:post_modified', $post->postModified));
        $postElement->appendChild($this->elementWithCDATA('wp:post_modified_gmt', $post->postModifiedGmt));
        $postElement->appendChild($this->elementWithCDATA('wp:comment_status', $post->commentStatus));
        $postElement->appendChild($this->elementWithCDATA('wp:ping_status', $post->pingStatus));
        $postElement->appendChild($this->elementWithCDATA('wp:post_name', $post->slug));
        $postElement->appendChild($this->elementWithCDATA('wp:status', $post->status));
        $postElement->appendChild($this->elementWithCDATA('wp:post_parent', (string) $post->postParent));
        $postElement->appendChild($this->elementWithCDATA('wp:menu_order', (string) $post->menuOrder));
        $postElement->appendChild($this->elementWithCDATA('wp:post_type', $post->post_type));
        $postElement->appendChild($this->elementWithCDATA('wp:post_password', $post->post_password));
        $postElement->appendChild($this->wxr->createElement('wp:is_sticky', (string) $post->isSticky));

        if (isset($post->attachment_url)) {
            $postElement->appendChild($this->elementWithCDATA('wp:attachment_url', $post->attachment_url));
        }

        foreach ($post->categories as $category) {
            $name = is_array($category) ? $category['name'] : $category;
            $slug = is_array($category) ? $category['slug'] : strtolower(str_replace(' ', '-', $category));
            $categoryElement = $this->elementWithCDATA('category', $name);
            $categoryElement->setAttribute('domain', 'category');
            $categoryElement->setAttribute('nicename', $slug);
            $postElement->appendChild($categoryElement);
        }

        foreach ($post->tags as $tag) {
            $name = is_array($tag) ? $tag['name'] : $tag;
            $slug = is_array($tag) ? $tag['slug'] : strtolower(str_replace(' ', '-', $tag));
            $tagElement = $this->elementWithCDATA('category', $name);
            $tagElement->setAttribute('domain', 'post_tag');
            $tagElement->setAttribute('nicename', $slug);
            $postElement->appendChild($tagElement);
        }

        foreach ($post->terms as $taxonomy => $terms) {
            foreach ($terms as $term) {
                $name = is_array($term) ? $term['name'] : $term;
                $slug = is_array($term) ? $term['slug'] : strtolower(str_replace(' ', '-', $term));
                $termElement = $this->elementWithCDATA('category', $name);
                $termElement->setAttribute('domain', $taxonomy);
                $termElement->setAttribute('nicename', $slug);
                $postElement->appendChild($termElement);
            }
        }

        foreach ($post->meta as $meta) {
            $metaElement = $this->wxr->createElement('wp:postmeta');
            $metaElement->appendChild($this->elementWithCDATA('wp:meta_key', $meta->meta_key));
            $metaElement->appendChild($this->elementWithCDATA('wp:meta_value', $meta->meta_value));
            $postElement->appendChild($metaElement);
        }

        foreach ($post->comments as $comment) {
            $commentElement = $this->wxr->createElement('wp:comment');
            $commentElement->appendChild($this->wxr->createElement('wp:comment_id', (string) $comment->comment_id));
            $commentElement->appendChild($this->elementWithCDATA('wp:comment_author', $comment->comment_author));
            $commentElement->appendChild($this->elementWithCDATA('wp:comment_author_email', $comment->comment_author_email));
            $commentElement->appendChild($this->elementWithCDATA('wp:comment_author_url', $comment->comment_author_url));
            $commentElement->appendChild($this->elementWithCDATA('wp:comment_author_IP', $comment->comment_author_IP));
            $commentElement->appendChild($this->elementWithCDATA('wp:comment_date', $comment->comment_date));
            $commentElement->appendChild($this->elementWithCDATA('wp:comment_date_gmt', $comment->comment_date_gmt));
            $commentElement->appendChild($this->elementWithCDATA('wp:comment_content', $comment->comment_content));
            $commentElement->appendChild($this->elementWithCDATA('wp:comment_approved', $comment->comment_approved));
            $commentElement->appendChild($this->elementWithCDATA('wp:comment_type', $comment->comment_type));
            $commentElement->appendChild($this->elementWithCDATA('wp:comment_parent', $comment->comment_parent));
            $commentElement->appendChild($this->elementWithCDATA('wp:comment_user_id', $comment->comment_user_id));
            
            // Add comment metadata
            foreach ($comment->meta as $meta) {
                $commentMetaElement = $this->wxr->createElement('wp:commentmeta');
                $commentMetaElement->appendChild($this->elementWithCDATA('wp:meta_key', $meta->meta_key));
                $commentMetaElement->appendChild($this->elementWithCDATA('wp:meta_value', $meta->meta_value));
                $commentElement->appendChild($commentMetaElement);
            }
            
            $postElement->appendChild($commentElement);
        }

        $this->channel->appendChild($postElement);
    }

    public function addCategories(array $categories): void {
        $categories = $this->sortCategories($categories);

        foreach ($categories as $category) {
            $this->addCategory($category);
        }
    }

    /**
     * @param array<Category> $categories
     */
    public function sortCategories(array $categories): array {
        $sortedCategories = [];
        $queue = new \SplQueue();

        foreach ($categories as $category) {
            if (! $category->category_parent) {
                $sortedCategories[$category->category_nicename] = $category;

                continue;
            }

            if ( isset($sortedCategories[$category->category_parent]) ) {
                $sortedCategories[$category->category_nicename] = $category;

                continue;
            }

            $queue->enqueue($category);
        }

        while ( ! $queue->isEmpty() ) {
            $category = $queue->dequeue();

            if ( isset($sortedCategories[$category->category_parent]) ) {
                $sortedCategories[$category->category_nicename] = $category;

                continue;
            }

            $queue->enqueue($category);
        }

        return $sortedCategories;
    }

    private function addCategory(Category $category): void {
        $categoryElement = $this->wxr->createElement('wp:category');
        $categoryElement->appendChild($this->wxr->createElement('wp:term_id', $category->term_id));
        $categoryElement->appendChild($this->elementWithCDATA('wp:category_nicename', $category->category_nicename));
        $categoryElement->appendChild($this->elementWithCDATA('wp:cat_name', $category->cat_name));

        if ($category->category_parent) {
            $categoryElement->appendChild($this->elementWithCDATA('wp:category_parent', $category->category_parent));
        }

        if ($category->category_description) {
            $categoryElement->appendChild($this->elementWithCDATA('wp:category_description', $category->category_description));
        }

        if ($category->term_meta && count($category->term_meta) > 0) {
            $this->addTermMeta($categoryElement, $category->term_meta);
        }

        $this->channel->appendChild($categoryElement);
    }

    public function addTags(array $tags): void {
        foreach ($tags as $tag) {
            $this->addTag($tag);
        }
    }

    private function addTag(Tag $tag): void {
        $tagElement = $this->wxr->createElement('wp:tag');
        $tagElement->appendChild($this->wxr->createElement('wp:term_id', $tag->term_id));
        $tagElement->appendChild($this->elementWithCDATA('wp:tag_slug', $tag->tag_slug));
        $tagElement->appendChild($this->elementWithCDATA('wp:tag_name', $tag->tag_name));

        if ($tag->tag_description) {
            $tagElement->appendChild($this->elementWithCDATA('wp:tag_description', $tag->tag_description));
        }

        if ($tag->term_meta && count($tag->term_meta) > 0) {
            $this->addTermMeta($tagElement, $tag->term_meta);
        }

        $this->channel->appendChild($tagElement);
    }

    public function addTerms(array $terms): void {
        foreach ($terms as $term) {
            $this->addTerm($term);
        }
    }

    private function addTerm(Term $term): void {
        $termElement = $this->wxr->createElement('wp:term');
        
        // Add term_id as a child element
        $termElement->appendChild($this->wxr->createElement('wp:term_id', $term->term_id));
        
        // Add term_taxonomy as a child element with CDATA
        $termElement->appendChild($this->elementWithCDATA('wp:term_taxonomy', $term->term_taxonomy));
        
        // Add term_slug as a child element with CDATA
        $termElement->appendChild($this->elementWithCDATA('wp:term_slug', $term->term_slug));

        if ($term->term_parent) {
            $termElement->appendChild($this->elementWithCDATA('wp:term_parent', $term->term_parent));
        }

        if ($term->term_name) {
            $termElement->appendChild($this->elementWithCDATA('wp:term_name', $term->term_name));
        }

        if ($term->term_description) {
            // Term description should also use CDATA for consistency
            $termElement->appendChild($this->elementWithCDATA('wp:term_description', $term->term_description));
        }

        if ($term->term_meta && count($term->term_meta) > 0) {
            $this->addTermMeta($termElement, $term->term_meta);
        }

        $this->channel->appendChild($termElement);
    }

    /**
     * @param Meta[] $metaValues
     */
    private function addTermMeta($term, array $metaValues): void {
        foreach ($metaValues as $metaValue) {
            $termMetaElement = $this->wxr->createElement('wp:termmeta');
            $termMetaElement->appendChild($this->elementWithCDATA('wp:meta_key', $metaValue->meta_key));
            $termMetaElement->appendChild($this->elementWithCDATA('wp:meta_value', $metaValue->meta_value));
            $term->appendChild($termMetaElement);
        }
    }

    private function elementWithCDATA(string $tag, string $value): \DomElement {
        $element = $this->wxr->createElement($tag);
        $element->appendChild($this->wxr->createCDATASection($value));

        return $element;
    }

    public function save(?string $path = null): void {
        $this->wxr->formatOutput = true;

        $path = $path ?? 'wordpress-export-' . date('Y-m-d_H-i-s') . '.xml';

        $this->wxr->save($path);
    }

    public function get(): \DomDocument {
        return $this->wxr;
    }

    public function getAsString(): string {
        return $this->wxr->saveXML();
    }
}
