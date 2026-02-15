<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Tenancy\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Nette\ArgumentOutOfRangeException;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantContext
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            throw new ArgumentOutOfRangeException('User is required.');
        }

        if ($user->tenant_id === null) {
            abort(403, 'User does not belong to a tenant.');
        }

        app(TenantContext::class)->setTenantId((int) $user->tenant_id);

        return $next($request);
    }
}
