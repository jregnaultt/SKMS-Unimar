<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'Biblioteca Pública | SKMS-Unimar' }}</title>
        <link rel="icon" type="image/x-icon" href="{{ asset('images/logoIco.ico') }}">

        <!-- Google Fonts - Montserrat (Official UNIMAR Font) -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Extra head elements (for Google Scholar and JSON-LD structured data) -->
        {{ $head ?? '' }}

        <style>
            .body-grid {
                background-image: 
                    linear-gradient(rgba(100, 116, 139, 0.04) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(100, 116, 139, 0.04) 1px, transparent 1px);
                background-size: 48px 48px;
            }
        </style>
    </head>
    <body class="antialiased bg-slate-50 text-slate-800 font-sans selection:bg-[#0d4d98] selection:text-white min-h-screen flex flex-col relative overflow-x-hidden">
        
        <!-- Background Grid Pattern -->
        <div class="absolute inset-0 body-grid pointer-events-none z-0"></div>

        <!-- Header / Navigation Bar -->
        <header x-data="{ mobileMenuOpen: false }" class="w-full bg-white/90 backdrop-blur-md border-b border-gray-200/40 sticky top-0 z-50 transition-all duration-200">
            <div class="max-w-[1380px] mx-auto px-6 h-20 flex items-center justify-between">
                <!-- Brand Logo -->
                <a href="{{ url('/') }}" class="flex items-center space-x-3 group">
                    <img src="{{ asset('images/logo.svg') }}" alt="SKMS Unimar" class="w-12 h-12 object-contain transition-transform group-hover:scale-105">
                    <div class="flex flex-col">
                        <span class="text-base font-extrabold tracking-wider text-[#0d4d98] uppercase leading-none">SKMS</span>
                        <span class="text-xs font-semibold text-gray-500 mt-0.5 uppercase tracking-wider">Biblioteca Pública</span>
                    </div>
                </a>

                <!-- Navigation Links (Desktop) -->
                <nav class="hidden md:flex items-center space-x-8">
                    <a href="{{ url('/') }}" class="text-sm font-semibold text-gray-650 hover:text-[#0d4d98] transition-colors">Inicio</a>
                    <a href="{{ route('catalog.index') }}" class="text-sm font-semibold text-[#0d4d98] border-b-2 border-[#0d4d98] pb-1">Catálogo de Publicaciones</a>
                </nav>

                <!-- Auth Buttons (Desktop) -->
                <div class="hidden md:flex items-center space-x-6">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-bold uppercase tracking-wider text-white bg-[#0d4d98] hover:bg-[#09356b] rounded-lg transition-all duration-200 shadow-md shadow-[#0d4d98]/10 hover:shadow-lg">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-bold uppercase tracking-wider text-white bg-[#0d4d98] hover:bg-[#09356b] rounded-lg transition-all duration-200 shadow-md shadow-[#0d4d98]/10 hover:shadow-lg hover:shadow-[#0d4d98]/20">
                                Acceder
                            </a>
                        @endauth
                    @endif
                </div>

                <!-- Hamburger Menu Button (Mobile/Tablet) -->
                <div class="flex items-center md:hidden">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="inline-flex items-center justify-center p-2 rounded-lg text-[#0d4d98] hover:bg-[#0d4d98]/5 focus:outline-none transition duration-150 ease-in-out">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{'hidden': mobileMenuOpen, 'inline-flex': !mobileMenuOpen }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{'hidden': !mobileMenuOpen, 'inline-flex': mobileMenuOpen }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Menu Dropdown -->
            <div x-show="mobileMenuOpen" 
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-2"
                 class="md:hidden border-t border-gray-200/40 bg-white/95 backdrop-blur-md px-6 py-5 space-y-4 shadow-inner" 
                 style="display: none;">
                <nav class="flex flex-col space-y-3">
                    <a href="{{ url('/') }}" @click="mobileMenuOpen = false" class="text-base font-semibold text-gray-650">Inicio</a>
                    <a href="{{ route('catalog.index') }}" @click="mobileMenuOpen = false" class="text-base font-semibold text-[#0d4d98] py-1">Catálogo</a>
                </nav>

                <div class="pt-4 border-t border-slate-200/50 flex flex-col space-y-2.5">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="w-full inline-flex items-center justify-center px-5 py-2.5 text-sm font-bold uppercase tracking-wider text-white bg-[#0d4d98] hover:bg-[#09356b] rounded-lg transition shadow-md">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="w-full inline-flex items-center justify-center px-5 py-2.5 text-sm font-bold uppercase tracking-wider text-white bg-[#0d4d98] hover:bg-[#09356b] rounded-lg transition shadow-md">
                                Acceder
                            </a>
                        @endauth
                    @endif
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 w-full max-w-[1380px] mx-auto px-6 py-8 z-10 relative">
            {{ $slot }}
        </main>

        <!-- Institutional Footer -->
        <footer class="w-full bg-white border-t border-gray-200/40 py-12 px-6 mt-auto z-10 relative">
            <div class="max-w-[1380px] mx-auto grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
                <!-- Left: Logo & Location -->
                <div class="md:col-span-6 flex flex-col items-start space-y-3">
                    <div class="flex items-center space-x-3">
                        <img src="{{ asset('images/logo.svg') }}" alt="SKMS Unimar" class="w-[39px] h-[39px] object-contain">
                        <span class="text-sm font-extrabold tracking-wider text-[#0d4d98] uppercase">Universidad de Margarita</span>
                    </div>
                    <p class="text-xs text-gray-500 max-w-sm leading-relaxed font-medium">
                        Av. Concepción Mariño, Sector El Toporo, El Valle del Espíritu Santo, Edo. Nueva Esparta, Venezuela.
                    </p>
                </div>

                <!-- Right: Copyright & Legal -->
                <div class="md:col-span-6 flex flex-col md:items-end space-y-2">
                    <p class="text-xs text-gray-500 text-left md:text-right font-medium">
                        © Copyright 2001-2026 Universidad de Margarita, RIF: J-30660040-0. Isla de Margarita - Venezuela.
                    </p>
                    <p class="text-[9px] text-gray-400 text-left md:text-right uppercase tracking-wider font-extrabold">
                        SKMS | Biblioteca Científica de Acceso Abierto
                    </p>
                </div>
            </div>
        </footer>

    </body>
</html>
