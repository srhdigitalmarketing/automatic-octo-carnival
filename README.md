# Automatic Octo Carnival

PHP application built with CodeIgniter 4.

## Setup

1. Install PHP and the extensions required by `composer.json`.
2. Run `composer install --no-dev`.
3. Copy `.env.example` to `.env` and configure the database credentials.
4. Provision the database from your private backup. The production dump is intentionally excluded from this repository.
5. Point the web server document root to `public/` and make `writable/` writable by PHP.

Keep `.env`, runtime logs, sessions, and database backups private.
