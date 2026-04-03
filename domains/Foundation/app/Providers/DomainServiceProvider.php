<?php

declare(strict_types=1);

namespace App\Domains\Foundation\Providers;

use App\Domains\Foundation\Views\DomainViewRegistrar;
use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    public function boot(DomainViewRegistrar $viewRegistrar): void
    {
        $viewRegistrar->register();

        $this->loadMigrationsFrom([
            base_path('domains/IdentityAccess/database/migrations'),
            base_path('domains/CourseCatalog/database/migrations'),
            base_path('domains/Curriculum/database/migrations'),
            base_path('domains/Enrollment/database/migrations'),
            base_path('domains/Tenancy/database/migrations'),
            base_path('domains/Foundation/database/migrations'),
        ]);
    }
}
