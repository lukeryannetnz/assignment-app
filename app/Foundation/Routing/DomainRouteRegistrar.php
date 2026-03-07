<?php

declare(strict_types=1);

namespace App\Foundation\Routing;

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
    ];

    public function registerWebRoutes(): void
    {
        foreach (self::WEB_ROUTE_FILES as $domain) {
            require app_path('Domain/' . $domain . '/Routes/web.php');
        }
    }
}
