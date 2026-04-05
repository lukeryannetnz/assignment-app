<?php

declare(strict_types=1);

namespace App\Domains\Foundation\Routing;

class DomainRouteRegistrar
{
    /**
     * @var list<string>
     */
    private const WEB_ROUTE_FILES = [
        'IdentityAccess',
        'CourseCatalog',
        'Enrollment',
        'Curriculum',
        'Tenancy',
        'Skills',
    ];

    public function registerWebRoutes(): void
    {
        foreach (self::WEB_ROUTE_FILES as $domain) {
            require base_path('domains/' . $domain . '/app/Routes/web.php');
        }
    }
}
