<?php

declare(strict_types=1);

namespace App\Http\Controllers\IdentityAccess\Auth;

use App\Domain\Tenancy\Models\Tenant;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('identity-access.auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $password = $request->input('password');
        $name = $request->input('name');
        $email = $request->input('email');

        assert(is_string($password));
        assert(is_string($name));
        assert(is_string($email));

        /** @var User $user */
        $user = DB::transaction(static function () use ($name, $email, $password): User {
            $tenant = Tenant::create([
                'name' => sprintf('%s Tenant', $name),
                'status' => 'active',
                'plan_tier' => 'enterprise_pilot',
                'hierarchy_depth_limit' => 4,
            ]);

            return User::create([
                'tenant_id' => $tenant->id,
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
