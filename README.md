# Book List

**Contributors:** shamimmoeen  
**Tags:** book list, crud, rest api, shortcodes  
**Requires at least:** 6.0  
**Tested up to:** 6.3  
**Requires PHP:** 7.4  
**Stable tag:** 1.0.0  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html  

A WordPress plugin to manage a list of books and their authors via shortcodes and a custom REST API.

## Description

**Book List** is a portfolio/demo WordPress plugin that showcases best-practice plugin development techniques including:

- Custom database table creation on activation (`wp_books`)
- **CRUD operations** via both a frontend UI and REST API
- **Two shortcodes** for adding and displaying books
- **Custom REST API** built with `WP_REST_Controller` (OOP) under the namespace `book-list/v1`
- Search, pagination, and sorting on the book list
- WordPress object **caching** for improved performance
- Full **security**: nonce verification, capability checks, input sanitization, output escaping
- **WordPress Coding Standards** enforced via PHP_CodeSniffer

## Shortcodes

| Shortcode      | Description                                      |
|----------------|--------------------------------------------------|
| `[book_form]`  | Displays a form to add a new book (requires `publish_posts` capability) |
| `[book_list]`  | Displays a searchable, paginated table of all books |

## REST API Endpoints

Base namespace: `/wp-json/book-list/v1`

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| `GET`  | `/books` | Retrieve books (supports `search`, `page`, `per_page`, `orderby`, `order`) | No |
| `POST` | `/books` | Add a new book (`book_name`, `author_name` required, 2–50 chars each) | Yes (`publish_posts`) |

## Installation

1. Upload the `book-list` folder to `/wp-content/plugins/`
2. Activate the plugin via the **Plugins** screen in WordPress
3. Place `[book_form]` and `[book_list]` on any page or post

## Development

```bash
# Install PHP dependencies (coding standards)
composer install

# Lint code
composer lint

# Auto-fix code style
composer format

# Install Node dependencies (i18n, readme generation)
npm install

# Generate .pot translation file
npm run i18n

# Convert readme.txt to README.md
npm run readme
