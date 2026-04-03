<?php

declare(strict_types=1);

use App\Domains\Foundation\Routing\DomainRouteRegistrar;

app(DomainRouteRegistrar::class)->registerWebRoutes();
