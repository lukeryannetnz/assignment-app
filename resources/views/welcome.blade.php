<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Course Management System</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

        <!-- Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500">
            <!-- Header -->
            @if (Route::has('login'))
                <header class="p-6 lg:p-8">
                    <nav class="flex justify-end gap-4">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="px-6 py-2.5 text-white font-medium rounded-lg bg-white/10 backdrop-blur-sm border border-white/20 hover:bg-white/20 transition-all duration-300 hover:-translate-y-0.5">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="px-6 py-2.5 text-white font-medium rounded-lg bg-white/10 backdrop-blur-sm border border-white/20 hover:bg-white/20 transition-all duration-300 hover:-translate-y-0.5">
                                Log in
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="px-6 py-2.5 text-white font-medium rounded-lg bg-white/10 backdrop-blur-sm border border-white/20 hover:bg-white/20 transition-all duration-300 hover:-translate-y-0.5">
                                    Register
                                </a>
                            @endif
                        @endauth
                    </nav>
                </header>
            @endif

            <!-- Main Content -->
            <main class="flex items-center justify-center px-6 py-12 lg:py-20">
                <div class="max-w-6xl w-full text-center">
                    <!-- Logo and Title -->
                    <div class="mb-16 animate-fade-in-down">
                        <!-- Logo SVG -->
                        <svg class="w-32 h-32 mx-auto mb-8 drop-shadow-lg" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <!-- Graduation Cap -->
                            <g filter="url(#shadow)">
                                <!-- Main cap -->
                                <path d="M60 25L15 45L60 65L105 45L60 25Z" fill="white" stroke="white" stroke-width="2" stroke-linejoin="round"/>

                                <!-- Cap base -->
                                <path d="M25 50V70C25 75 35 85 60 85C85 85 95 75 95 70V50" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none"/>

                                <!-- Tassel -->
                                <circle cx="105" cy="45" r="3" fill="white"/>
                                <line x1="105" y1="48" x2="105" y2="58" stroke="white" stroke-width="2" stroke-linecap="round"/>
                                <circle cx="105" cy="60" r="4" fill="white"/>

                                <!-- Book pages -->
                                <rect x="45" y="65" width="30" height="35" rx="2" fill="rgba(255,255,255,0.9)" stroke="white" stroke-width="2"/>
                                <line x1="50" y1="75" x2="70" y2="75" stroke="#818cf8" stroke-width="2" stroke-linecap="round"/>
                                <line x1="50" y1="82" x2="70" y2="82" stroke="#818cf8" stroke-width="2" stroke-linecap="round"/>
                                <line x1="50" y1="89" x2="65" y2="89" stroke="#818cf8" stroke-width="2" stroke-linecap="round"/>

                                <!-- Book spine -->
                                <line x1="60" y1="65" x2="60" y2="100" stroke="white" stroke-width="2.5"/>
                            </g>

                            <defs>
                                <filter id="shadow" x="0" y="0" width="120" height="120">
                                    <feDropShadow dx="0" dy="2" stdDeviation="3" flood-opacity="0.3"/>
                                </filter>
                            </defs>
                        </svg>

                        <h1 class="text-5xl lg:text-6xl font-bold text-white mb-4 drop-shadow-md">
                            Course Manager
                        </h1>
                        <p class="text-xl lg:text-2xl text-white/90 font-light">
                            Streamline Your Learning Journey
                        </p>
                    </div>

                    <!-- Feature Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12 animate-fade-in-up">
                        <!-- Card 1: Course Management -->
                        <div class="bg-white/95 backdrop-blur-sm p-8 rounded-2xl shadow-xl hover:shadow-2xl hover:-translate-y-2 transition-all duration-300">
                            <div class="w-12 h-12 mx-auto mb-4 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center">
                                <svg class="w-7 h-7 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">
                                Course Management
                            </h3>
                            <p class="text-gray-600 leading-relaxed">
                                Create, organize, and manage courses with ease. Track progress and monitor student engagement.
                            </p>
                        </div>

                        <!-- Card 2: Student Tracking -->
                        <div class="bg-white/95 backdrop-blur-sm p-8 rounded-2xl shadow-xl hover:shadow-2xl hover:-translate-y-2 transition-all duration-300">
                            <div class="w-12 h-12 mx-auto mb-4 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center">
                                <svg class="w-7 h-7 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">
                                Student Tracking
                            </h3>
                            <p class="text-gray-600 leading-relaxed">
                                Keep track of student enrollments, progress, and performance across all courses.
                            </p>
                        </div>

                        <!-- Card 3: Analytics -->
                        <div class="bg-white/95 backdrop-blur-sm p-8 rounded-2xl shadow-xl hover:shadow-2xl hover:-translate-y-2 transition-all duration-300">
                            <div class="w-12 h-12 mx-auto mb-4 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center">
                                <svg class="w-7 h-7 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">
                                Analytics & Reports
                            </h3>
                            <p class="text-gray-600 leading-relaxed">
                                Generate insightful reports and analytics to make data-driven decisions.
                            </p>
                        </div>
                    </div>

                    <!-- CTA Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-center items-center animate-fade-in-up-delay">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="px-8 py-4 bg-white text-indigo-600 font-semibold rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                                Go to Dashboard
                            </a>
                        @else
                            @if (Route::has('login'))
                                <a href="{{ route('login') }}" class="px-8 py-4 bg-white text-indigo-600 font-semibold rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                                    Get Started
                                </a>
                            @endif
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="px-8 py-4 bg-white/10 backdrop-blur-sm text-white font-semibold rounded-xl border-2 border-white hover:bg-white/20 hover:-translate-y-1 transition-all duration-300">
                                    Sign Up Free
                                </a>
                            @endif
                        @endauth
                    </div>
                </div>
            </main>
        </div>

        <style>
            @keyframes fadeInDown {
                from {
                    opacity: 0;
                    transform: translateY(-30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .animate-fade-in-down {
                animation: fadeInDown 0.8s ease-out;
            }

            .animate-fade-in-up {
                animation: fadeInUp 0.8s ease-out 0.2s both;
            }

            .animate-fade-in-up-delay {
                animation: fadeInUp 0.8s ease-out 0.4s both;
            }
        </style>
    </body>
</html>
