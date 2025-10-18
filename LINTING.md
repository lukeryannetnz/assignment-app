# Code Quality & Linting

This project uses the most popular PHP linters to ensure code quality and strict typing.

## Tools Installed

### 1. **PHPStan** (with Larastan)
- **Purpose**: Static analysis tool for detecting bugs and enforcing strict types
- **Level**: 9 (maximum strictness)
- **Features**:
  - Detects type errors before runtime
  - Enforces return type declarations
  - Checks for unused variables and dead code
  - Laravel-specific rules via Larastan

### 2. **PHP_CodeSniffer (PHPCS)**
- **Purpose**: Code style checker
- **Standard**: PSR-12 (official PHP coding standard)
- **Features**:
  - Enforces consistent code formatting
  - Checks for strict_types declarations
  - Validates spacing, braces, naming conventions
  - Auto-fixable issues via PHPCBF

## Available Commands

### Run All Linters
```bash
composer lint
```
This runs both PHPCS and PHPStan.

### Run Code Style Checker Only
```bash
composer phpcs
```

### Auto-Fix Code Style Issues
```bash
composer lint:fix
# or
composer phpcbf
```

### Run Static Analysis Only
```bash
composer phpstan
```

### Run Tests (with linting)
```bash
composer test
```
**Note**: The `test` command now automatically runs linters **before** tests!

## Configuration Files

- **phpstan.neon** - PHPStan configuration
- **phpcs.xml** - PHP_CodeSniffer configuration

## What Gets Checked

### Directories
- `app/` - Application code
- `database/` - Migrations, seeders, factories
- `routes/` - Route definitions
- `tests/` - Test files

### Excluded
- `bootstrap/cache/`
- `storage/`
- `vendor/`
- `node_modules/`
- `*.blade.php` (Blade templates - handled by formatter)

## Common Issues & How to Fix

### 1. Missing strict_types declaration
**Error**: `Missing required strict_types declaration`

**Fix**: Add this as the first line after `<?php`:
```php
<?php

declare(strict_types=1);

namespace App\Models;
```

### 2. Missing return type hints
**Error**: `Method has no return type specified`

**Fix**: Add return type to methods:
```php
// Before
public function index() {
    return view('courses');
}

// After
use Illuminate\View\View;

public function index(): View {
    return view('courses');
}
```

### 3. Missing property types
**Error**: `Property has no type specified`

**Fix**: Add types to properties:
```php
// Before
protected $fillable = ['name'];

// After
/** @var array<int, string> */
protected array $fillable = ['name'];
```

### 4. Code style issues
**Error**: `Expected 1 space before "=>"; 0 found`

**Fix**: Run auto-fixer:
```bash
composer lint:fix
```

## Integrating with CI/CD

The linters automatically run before tests via:
```json
"test": [
    "@lint",
    "@php artisan config:clear --ansi",
    "@php artisan test"
]
```

This ensures:
1. Code style is checked (PHPCS)
2. Static analysis passes (PHPStan)
3. Tests are executed

If any linter fails, tests won't run!

## IDE Integration

### PHPStorm
1. Settings → PHP → Quality Tools → PHP_CodeSniffer
2. Configure path to `vendor/bin/phpcs`
3. Enable "Show sniff name"
4. Settings → PHP → Quality Tools → PHPStan
5. Configure path to `vendor/bin/phpstan`

### VS Code
Install extensions:
- PHP Intelephense
- PHPStan
- PHP_CodeSniffer

## Adjusting Strictness

### Reduce PHPStan Level
Edit `phpstan.neon`:
```yaml
parameters:
    level: 6  # Change from 9 to 6 (less strict)
```

Levels range from 0-9:
- **0-4**: Basic checks
- **5-7**: Recommended for most projects
- **8-9**: Very strict (current setting)

### Disable strict_types Requirement
Edit `phpcs.xml`, remove:
```xml
<rule ref="Generic.PHP.RequireStrictTypes"/>
```

## Best Practices

1. **Run linters before committing**: `composer lint`
2. **Use auto-fix when possible**: `composer lint:fix`
3. **Add type hints to all methods**: Use `@param` and `@return` PHPDoc
4. **Keep strict_types enabled**: Helps catch type errors early
5. **Review linter output carefully**: Don't blindly ignore warnings

## Why Strict Typing Matters

```php
// Without strict_types - silent bugs
function add($a, $b) {
    return $a + $b;
}

add("5", 3);  // Returns 8 (unexpected string coercion!)

// With strict_types - fails fast
declare(strict_types=1);

function add(int $a, int $b): int {
    return $a + $b;
}

add("5", 3);  // TypeError: must be of type int, string given
```

Strict typing helps you:
- Catch bugs at development time, not production
- Make code more predictable and maintainable
- Enable better IDE autocomplete
- Improve performance (no type juggling)
