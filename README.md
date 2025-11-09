# Course Management Application

A Laravel-based course management system for organizing and managing educational courses.

## Context

This application was created as part of the Coursera **Master Full-Stack Web Development with Laravel & PHP** course.

## About This Project

This is a course management application built with Laravel 12 and PHP 8.2+. The application provides functionality for managing educational courses, including course creation, organization, and administration.

## Requirements

- PHP 8.2 or higher
- Composer
- Node.js and npm
- Docker and Docker Compose (for MariaDB)
- MariaDB

## Installation

```bash
composer setup
```

This will:
- Install PHP dependencies
- Copy `.env.example` to `.env`
- Generate application key
- Run database migrations
- Install npm packages
- Build frontend assets

## Development

### Starting the Database

First, start the MariaDB container using Docker Compose:

```bash
docker-compose up -d
```

### Starting Development Servers

Then start the development servers:

```bash
composer dev
```

This runs Laravel's development server, queue worker, logs viewer (Pail), and Vite concurrently.

## Testing

Run the full test suite with linting:

```bash
composer test
```

This command:
1. Runs PHP_CodeSniffer for code style validation
2. Runs PHPStan for static analysis
3. Clears configuration cache
4. Executes PHPUnit tests

## Coding Standards

This project follows strict coding standards to ensure code quality and consistency:

### Strict Types

All PHP files **must** include strict type declarations:

```php
<?php

declare(strict_types=1);
```

This is enforced by PHP_CodeSniffer configuration.

### Code Style

The project uses **PSR-12** coding standard with additional strict rules configured in `phpcs.xml`:

- **PSR-12**: Full compliance with PSR-12 coding standard
- **Strict types**: Required in all PHP files (enforced as warning)
- **Array syntax**: Short array syntax required (`[]` instead of `array()`)
- **Line length**: 120 character soft limit, 150 character hard limit
- **Unused parameters**: Detected and flagged
- **TODO comments**: Tracked for cleanup

Run code style checks:

```bash
composer lint
```

Auto-fix code style issues:

```bash
composer lint:fix
```

### Static Analysis

The project uses **PHPStan** at **level 9** (maximum strictness) with Larastan for Laravel-specific analysis.

Configuration highlights from `phpstan.neon`:
- **Level 9**: Most strict analysis level
- **Missing type hints**: Required for all callables
- **Uninitialized properties**: Checked and flagged
- **PHPDoc validation**: Type hints treated as uncertain (native types preferred)

Run static analysis:

```bash
composer phpstan
```
