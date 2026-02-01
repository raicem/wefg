# WEFG — WordPress Export File Generator

A PHP library for programmatically creating WXR (WordPress eXtended RSS) files. Use it to migrate content from any source — a legacy CMS, a database, an API — into WordPress.

WEFG generates a standard WXR XML file that can be imported using the built-in [WordPress Importer](https://wordpress.org/plugins/wordpress-importer/) plugin.

## Installation

```bash
composer require raicem/wefg
```

Requires PHP 8.0+ and the DOM extension (included with most PHP installations).

## Quick Start

```php
use Raicem\WEFG\Post;
use Raicem\WEFG\SiteSettings;
use Raicem\WEFG\WXRFile;

$settings = new SiteSettings(
    link: 'https://example.com',
    title: 'My Site',
    description: 'A WordPress site',
    language: 'en-US'
);

$wxr = new WXRFile($settings);

$wxr->addPost(new Post(
    title: 'Hello World',
    content: '<p>Welcome to my site.</p>',
    excerpt: 'A welcome post',
    authorLogin: 'admin',
    publishDate: '2024-01-01 12:00:00',
    slug: 'hello-world'
));

$wxr->save('export.xml');
```

Then import `export.xml` via **Tools > Import > WordPress** in wp-admin, or with WP-CLI:

```bash
wp import export.xml --authors=create
```

## Usage

### Authors

```php
use Raicem\WEFG\Author;

$wxr->addAuthor(new Author(
    login: 'johndoe',
    email: 'john@example.com',
    display_name: 'John Doe',
    first_name: 'John',
    last_name: 'Doe',
    author_id: 1
));
```

### Posts and Pages

```php
use Raicem\WEFG\Post;
use Raicem\WEFG\Meta;

$post = new Post(
    title: 'My Post',
    content: '<p>Post content here.</p>',
    excerpt: 'Short summary',
    authorLogin: 'johndoe',
    publishDate: '2024-06-15 09:00:00',
    slug: 'my-post',
    status: 'publish',
    postId: 42,
    post_type: 'post',
    meta: [
        new Meta('custom_field', 'value'),
    ]
);

$wxr->addPost($post);
```

Use `post_type: 'page'` for pages, or any custom post type string.

### Categories, Tags, and Custom Taxonomies

Define taxonomy terms at the file level, then reference them on posts.

```php
use Raicem\WEFG\Terms\Category;
use Raicem\WEFG\Terms\Tag;
use Raicem\WEFG\Terms\Term;

// File-level definitions
$wxr->addCategory(new Category(1, 'tutorials', 'Tutorials'));
$wxr->addTag(new Tag(1, 'php', 'PHP'));
$wxr->addTerms([
    new Term(1, 'location', 'new-york', 'New York', ''),
]);

// Reference on a post
$post = new Post(
    title: 'Learn PHP',
    content: '...',
    excerpt: '',
    authorLogin: 'admin',
    publishDate: '2024-01-01 12:00:00',
    slug: 'learn-php',
    categories: ['Tutorials'],
    tags: ['PHP'],
    terms: [
        'location' => ['New York'],
    ]
);
```

**Non-ASCII names:** When a category, tag, or term name contains non-ASCII characters, pass an array with explicit `name` and `slug` keys. The default slug derivation does not transliterate, so `Müze` would become `müze` instead of `muze`, breaking WordPress matching.

```php
$post = new Post(
    // ...
    categories: [
        ['name' => 'Arkeoloji Müzeleri', 'slug' => 'arkeoloji-muzeleri'],
    ],
    terms: [
        'location' => [
            ['name' => 'İstanbul', 'slug' => 'istanbul'],
        ],
    ],
);
```

### Attachments

```php
use Raicem\WEFG\Attachment;

$post = new Post(
    title: 'My Post',
    content: '...',
    excerpt: '',
    authorLogin: 'admin',
    publishDate: '2024-01-01 12:00:00',
    slug: 'my-post',
    postId: 1,
    meta: [
        new Meta('_thumbnail_id', '1001'), // References the attachment postId
    ]
);

$attachment = new Attachment(
    title: 'Featured Image',
    attachment_url: 'http://example.com/images/photo.jpg',
    parent: $post,
    postId: 1001,
    slug: 'my-post-image'
);

$wxr->addPost($post);
$wxr->addPost($attachment);
```

During import, WordPress downloads the file from `attachment_url` and sets it as the post's featured image via the `_thumbnail_id` meta.

**Important:** WordPress's importer rejects `http://localhost` URLs. Use `http://127.0.0.1` when serving files locally.

### Comments

```php
use Raicem\WEFG\Comment;
use Raicem\WEFG\Meta;

$comment = new Comment(
    comment_author: 'Jane',
    comment_author_email: 'jane@example.com',
    comment_content: 'Great post!',
    comment_date: '2024-01-15 10:30:00',
    comment_approved: '1',
    comment_id: 101
);

// Optional metadata
$comment->meta[] = new Meta('rating', '5');

// Reply to a comment
$reply = new Comment(
    comment_author: 'Admin',
    comment_author_email: 'admin@example.com',
    comment_content: 'Thanks!',
    comment_parent: '101'
);

$post->comments = [$comment, $reply];
```

## Testing

```bash
# Unit tests (no external dependencies)
vendor/bin/phpunit --testsuite unit

# All tests including integration (requires MySQL via Docker)
docker compose up -d
composer setup-wp-tests   # first time only
vendor/bin/phpunit
```

## License

GPL-2.0-or-later
