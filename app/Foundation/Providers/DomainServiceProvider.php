<?php

declare(strict_types=1);

namespace App\Foundation\Providers;

use App\Foundation\Views\DomainViewRegistrar;
use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    public function boot(DomainViewRegistrar $viewRegistrar): void
    {
        $viewRegistrar->register();

        $this->loadMigrationsFrom([
            database_path('migrations/IdentityAccess'),
            database_path('migrations/CourseCatalog'),
            database_path('migrations/Curriculum'),
            database_path('migrations/Enrollment'),
            database_path('migrations/Tenancy'),
            database_path('migrations/Foundation'),
        ]);
    }
}
