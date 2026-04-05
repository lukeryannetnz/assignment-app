<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['domains/Foundation/resources/css/app.css', 'domains/Foundation/resources/js/app.js'])
    </head>
    <body class="bg-slate-950 font-sans antialiased text-slate-100">
        <div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(34,211,238,0.18),_transparent_32%),linear-gradient(180deg,_#020617_0%,_#0f172a_100%)]">
            @include('identity-access::layouts.navigation')

            @isset($header)
                <header class="border-b border-white/10 bg-slate-950/80 shadow-lg shadow-cyan-950/10 backdrop-blur">
                    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main class="text-slate-100">
                @isset($slot)
                    {{ $slot }}
                @else
                    @yield('content')
                @endisset
            </main>
        </div>
    </body>
</html>
