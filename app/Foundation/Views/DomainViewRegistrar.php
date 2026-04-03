<?php

declare(strict_types=1);

namespace App\Foundation\Views;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;

class DomainViewRegistrar
{
    /**
     * @var array<string, string>
     */
    private const VIEW_PATHS = [
        'course-catalog' => 'resources/domains/course-catalog/views',
        'curriculum' => 'resources/domains/curriculum/views',
        'identity-access' => 'resources/domains/identity-access/views',
        'foundation' => 'resources/domains/foundation/views',
    ];

    /**
     * @var array<string, string>
     */
    private const COMPONENT_PATHS = [
        'course-catalog' => 'resources/domains/course-catalog/components',
        'curriculum' => 'resources/domains/curriculum/components',
        'identity-access' => 'resources/domains/identity-access/components',
    ];

    public function register(): void
    {
        foreach (self::VIEW_PATHS as $namespace => $relativePath) {
            View::addNamespace($namespace, base_path($relativePath));
        }

        foreach (self::COMPONENT_PATHS as $prefix => $relativePath) {
            Blade::anonymousComponentPath(base_path($relativePath), $prefix);
        }
    }
}
