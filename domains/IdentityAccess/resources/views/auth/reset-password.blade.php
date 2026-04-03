<x-identity-access::guest-layout>
    <form method="POST" action="{{ route('identity-access.auth.password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <x-identity-access::input-label for="email" :value="__('Email')" />
            <x-identity-access::text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-identity-access::input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-identity-access::input-label for="password" :value="__('Password')" />
            <x-identity-access::text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-identity-access::input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-identity-access::input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-identity-access::text-input id="password_confirmation" class="block mt-1 w-full"
                                type="password"
                                name="password_confirmation" required autocomplete="new-password" />

            <x-identity-access::input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-identity-access::primary-button>
                {{ __('Reset Password') }}
            </x-identity-access::primary-button>
        </div>
    </form>
</x-identity-access::guest-layout>
