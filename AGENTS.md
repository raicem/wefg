# AGENTS.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This Is

A PHP 8+ library (zero runtime dependencies) for programmatically generating WordPress WXR (WordPress eXtended RSS) export files. Users include it via Composer, describe their content using the provided classes, and generate a WXR file for importing into WordPress.

## Commands

```bash
# Start MySQL for integration tests
docker compose up -d

# Set up WordPress test environment (first time only)
composer setup-wp-tests

# Run all tests (unit + integration)
vendor/bin/phpunit

# Run unit tests only (no MySQL/WordPress needed)
vendor/bin/phpunit --testsuite unit

# Run integration tests only
vendor/bin/phpunit --testsuite integration

# Run a single test file
vendor/bin/phpunit tests/unit/PostTest.php

# Run a single test method
vendor/bin/phpunit --filter testMethodName

# Reset test database between integration test debugging
composer reset-test-db
```

## Architecture

**WXRFile** (`src/WXRFile.php`) is the central orchestrator. It builds a DOMDocument representing the WXR XML, with methods to add authors, posts, categories, tags, and terms. It handles CDATA wrapping and hierarchical category sorting.

**Content models** are simple data classes using PHP 8 constructor promotion:

- `Post` — core content type; holds title, content, taxonomies, meta, comments
- `Attachment` — extends Post, adds `attachment_url`, forces `post_type='attachment'`
- `Author`, `Comment`, `Meta`, `SiteSettings` — supporting data classes

**Taxonomy models** live in `src/terms/`: `Category` (hierarchical), `Tag` (flat), `Term` (custom taxonomies with metadata).

## Development Approach

This project uses **integration test-driven development**. When implementing a new feature:

1. Read `tmp/export.php` to understand what WordPress generates when exporting
2. Read `tmp/class-wp-importer.php` to understand what WordPress expects when importing
3. Write tests defining the expected behavior
4. Write the implementation

Integration tests generate a WXR file, import it into a real WordPress instance via WP-CLI, then verify the import succeeded. The `wp-test` script is a WP-CLI wrapper pointing at `tests/wordpress-test/`.

## Key Details

- Namespace: `Raicem\WEFG`, PSR-4 autoloaded from `src/`
- WXR format version: 1.2
- Integration tests require MySQL via `docker compose up -d` (port 33060, root/secret)
- `tests/TestCase.php` is the base test class used by integration tests

## Gotchas

- **Attachment URLs must not use `localhost`**: WordPress's `download_url()` rejects `http://localhost` as an invalid URL, causing all attachment imports to silently fail. Use `http://127.0.0.1` instead when serving files locally.

- **Non-ASCII slugs**: Post-level taxonomy fields (`categories`, `tags`, `terms`) accept either plain strings or `['name' => ..., 'slug' => ...]` arrays. When the display name contains non-ASCII characters (e.g., Turkish ü, ş, ç), always pass the array form with a pre-computed ASCII slug. The naive `strtolower(str_replace(...))` fallback does not transliterate, so `Müze` becomes `müze` instead of `muze`, breaking WordPress import matching. The muzeler export command uses `asciiSlug()` to produce correct slugs.
