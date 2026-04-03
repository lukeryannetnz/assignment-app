<?php

declare(strict_types=1);

namespace App\Domains\Foundation\Views;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;

class DomainViewRegistrar
{
    /**
     * @var array<string, string>
     */
    private const VIEW_PATHS = [
        'course-catalog' => 'domains/CourseCatalog/resources/views',
        'curriculum' => 'domains/Curriculum/resources/views',
        'identity-access' => 'domains/IdentityAccess/resources/views',
        'foundation' => 'domains/Foundation/resources/views',
    ];

    /**
     * @var array<string, string>
     */
    private const COMPONENT_PATHS = [
        'course-catalog' => 'domains/CourseCatalog/resources/components',
        'curriculum' => 'domains/Curriculum/resources/components',
        'identity-access' => 'domains/IdentityAccess/resources/components',
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
