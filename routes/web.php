<?php

declare(strict_types=1);

use App\Foundation\Routing\DomainRouteRegistrar;

app(DomainRouteRegistrar::class)->registerWebRoutes();
