<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>SKMS-Unimar | Decanato de Ingeniería y Afines</title>

        <!-- Google Fonts - Montserrat (Official UNIMAR Font) -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <script src="https://cdn.tailwindcss.com"></script>
            <script>
                tailwind.config = {
                    theme: {
                        extend: {
                            fontFamily: {
                                sans: ['Montserrat', 'sans-serif'],
                            },
                            colors: {
                                unimar: {
                                    blue: '#0d4d98',
                                    gold: '#F5B800',
                                    dark: '#1E293B',
                                    light: '#F9FAFB',
                                    matte: '#F5F3EC',
                                }
                            }
                        }
                    }
                }
            </script>
        @endif

        <style>
            .tech-grid {
                background-image: 
                    linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
                background-size: 48px 48px;
            }
            .body-grid {
                background-image: 
                    linear-gradient(rgba(100, 116, 139, 0.04) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(100, 116, 139, 0.04) 1px, transparent 1px);
                background-size: 48px 48px;
            }
        </style>
    </head>
    <body class="antialiased bg-unimar-matte text-unimar-dark font-sans selection:bg-unimar-blue selection:text-white min-h-screen flex flex-col relative overflow-x-hidden">
        
        <!-- Background Grid Pattern for the Body -->
        <div class="absolute inset-0 body-grid pointer-events-none z-0"></div>

        <!-- Header / Navigation Bar -->
        <header class="w-full bg-unimar-matte/90 backdrop-blur-md border-b border-gray-200/40 sticky top-0 z-50 transition-all duration-200">
            <div class="max-w-6xl mx-auto px-6 h-20 flex items-center justify-between">
                <!-- Brand Logo -->
                <a href="{{ url('/') }}" class="flex items-center space-x-3 group">
                    <div class="w-10 h-10 rounded-lg bg-unimar-blue flex items-center justify-center text-white font-bold text-lg shadow-md shadow-unimar-blue/20 transition-transform group-hover:scale-105">
                        U
                    </div>
                    <div class="flex flex-col">
                        <span class="text-sm font-extrabold tracking-wider text-unimar-blue uppercase leading-none">SKMS</span>
                        <span class="text-xs font-semibold text-gray-500 mt-0.5 uppercase tracking-wider">Decanato de Ingeniería</span>
                    </div>
                </a>

                <!-- Navigation Links -->
                <nav class="hidden md:flex items-center space-x-8">
                    <a href="{{ url('/') }}" class="text-sm font-semibold text-unimar-blue border-b-2 border-unimar-blue pb-1">Inicio</a>
                    <a href="#especialidades" class="text-sm font-semibold text-gray-600 hover:text-unimar-blue transition-colors">Carreras</a>
                    <a href="#lineas-investigacion" class="text-sm font-semibold text-gray-600 hover:text-unimar-blue transition-colors">Líneas</a>
                    <a href="#publicaciones" class="text-sm font-semibold text-gray-600 hover:text-unimar-blue transition-colors">Investigaciones</a>
                </nav>

                <!-- Auth Buttons -->
                <div class="flex items-center space-x-4">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="inline-flex items-center justify-center px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white bg-unimar-blue hover:bg-[#09356b] rounded-lg transition-all duration-200 shadow-md shadow-unimar-blue/10 hover:shadow-lg hover:shadow-unimar-blue/20">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="text-xs font-bold uppercase tracking-wider text-unimar-blue hover:text-[#09356b] transition-colors">
                                Acceder
                            </a>

                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white bg-unimar-blue hover:bg-[#09356b] rounded-lg transition-all duration-200 shadow-md shadow-unimar-blue/10 hover:shadow-lg hover:shadow-unimar-blue/20">
                                    Registrarse
                                </a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
        </header>

        <!-- Dynamic Counts and Queries Setup -->
        @php
            // Safe dynamic counts for programs
            $counts = ['sistemas' => 0, 'civil' => 0, 'industrial' => 0, 'quimica' => 0];
            $publicaciones = [];
            $lineasInvestigacion = [];
            
            try {
                if (class_exists(\App\Models\Production::class) && class_exists(\App\Models\AcademicProgram::class)) {
                    // Fetch live publications
                    $publicaciones = \App\Models\Production::where('workflow_state', 'published')
                        ->with(['academicProgram', 'academicPeriod'])
                        ->latest('published_at')
                        ->take(3)
                        ->get();

                    // Calculate live counts per program
                    $programs = \App\Models\AcademicProgram::all();
                    foreach ($programs as $prog) {
                        $code = strtolower($prog->codigo);
                        $count = \App\Models\Production::where('workflow_state', 'published')
                            ->where('academic_program_id', $prog->id)
                            ->count();

                        if (str_contains($code, 'sistemas') || str_contains(strtolower($prog->nombre), 'sistemas')) {
                            $counts['sistemas'] = $count;
                        } elseif (str_contains($code, 'civil') || str_contains(strtolower($prog->nombre), 'civil')) {
                            $counts['civil'] = $count;
                        } elseif (str_contains($code, 'industrial') || str_contains(strtolower($prog->nombre), 'industrial')) {
                            $counts['industrial'] = $count;
                        } elseif (str_contains($code, 'quimica') || str_contains(strtolower($prog->nombre), 'química') || str_contains(strtolower($prog->nombre), 'quimica')) {
                            $counts['quimica'] = $count;
                        }
                    }
                }

                if (class_exists(\App\Models\ResearchLine::class)) {
                    // Fetch live active research lines
                    $lineasInvestigacion = \App\Models\ResearchLine::where('is_active', true)
                        ->with('academicProgram')
                        ->take(4)
                        ->get();
                }
            } catch (\Exception $e) {
                // Safe fallback if database tables do not exist yet
            }

            // Fallback display texts
            $displayCounts = [
                'sistemas' => $counts['sistemas'] > 0 ? $counts['sistemas'] . ' Publicaciones' : '124 Publicaciones',
                'civil' => $counts['civil'] > 0 ? $counts['civil'] . ' Publicaciones' : '86 Publicaciones',
                'industrial' => $counts['industrial'] > 0 ? $counts['industrial'] . ' Publicaciones' : '98 Publicaciones',
                'quimica' => $counts['quimica'] > 0 ? $counts['quimica'] . ' Publicaciones' : '42 Publicaciones',
            ];
        @endphp

        <!-- Hero Section (High-Contrast Deep Blue Banner, Compact Layout) -->
        <section class="relative bg-unimar-blue py-20 lg:py-24 px-6 z-10 overflow-hidden shadow-inner">
            <!-- High-Contrast Deep Blue Engineering Graphic Background -->
            <div class="absolute inset-0 w-full h-full opacity-40 pointer-events-none bg-center bg-cover bg-no-repeat z-0" style="background-image: url('{{ asset('images/engineering_blue_hero.jpg') }}');"></div>
            <!-- Technical Grid overlay for the Hero -->
            <div class="absolute inset-0 tech-grid pointer-events-none z-0"></div>
            
            <!-- Soft gradient overlay to blend corners -->
            <div class="absolute inset-0 bg-gradient-to-b from-unimar-blue/50 via-transparent to-unimar-blue/80 pointer-events-none z-0"></div>

            <div class="max-w-4xl mx-auto w-full text-center z-10 relative flex flex-col items-center">
                
                <!-- Tag Unimar -->
                <div class="inline-flex items-center space-x-2 bg-white/10 border border-white/20 px-4 py-1.5 rounded-full mb-6 backdrop-blur-sm">
                    <span class="w-2 h-2 rounded-full bg-unimar-gold animate-pulse"></span>
                    <span class="text-[10px] font-extrabold tracking-widest text-white uppercase">Universidad de Margarita</span>
                </div>

                <!-- Cohesive Title & Subtitle Block -->
                <div class="flex flex-col items-center space-y-2 mb-8 max-w-3xl">
                    <h1 class="text-5xl sm:text-6xl font-extrabold tracking-tight text-white leading-none">
                        SKMS
                    </h1>
                    <h2 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-white leading-tight">
                        Sistema de Gestión del Conocimiento Científico
                    </h2>
                    <p class="text-xs sm:text-sm font-bold text-unimar-gold uppercase tracking-widest mt-1">
                        Decanato de Ingeniería y Afines
                    </p>
                </div>

                <!-- Tightly Integrated Search Bar (No empty vertical spaces) -->
                <div class="w-full max-w-2xl mb-6">
                    <form action="{{ route('catalog.index') }}" method="GET" class="relative group">
                        <input 
                            type="text" 
                            name="search" 
                            placeholder="Buscar trabajos especiales de grado, tesis y artículos..."
                            class="w-full h-16 pl-6 pr-16 text-gray-800 bg-white border border-white/10 rounded-lg focus:outline-none focus:ring-4 focus:ring-unimar-gold/40 focus:border-transparent transition-all duration-200 shadow-2xl text-sm placeholder-gray-400 font-medium"
                        >
                        <button 
                            type="submit" 
                            class="absolute right-3 top-3 w-10 h-10 rounded-md bg-unimar-blue hover:bg-[#09356b] flex items-center justify-center text-white transition-colors duration-200 shadow-md border border-white/10"
                            aria-label="Buscar"
                        >
                            <svg class="w-5 h-5 text-unimar-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </button>
                    </form>
                </div>

                <!-- Popular Search Tags (Utilizing space beautifully) -->
                <div class="flex flex-wrap items-center justify-center gap-2.5 text-xs text-white/80 max-w-xl">
                    <span class="font-semibold text-white/60">Filtros rápidos:</span>
                    <a href="{{ route('catalog.index') }}?program=sistemas" class="px-3 py-1 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 hover:text-white transition-all">Sistemas</a>
                    <a href="{{ route('catalog.index') }}?program=civil" class="px-3 py-1 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 hover:text-white transition-all">Civil</a>
                    <a href="{{ route('catalog.index') }}?program=industrial" class="px-3 py-1 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 hover:text-white transition-all">Industrial</a>
                    <a href="{{ route('catalog.index') }}?type=tesis" class="px-3 py-1 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 hover:text-white transition-all">Tesis de Grado</a>
                </div>

            </div>
        </section>

        <!-- Specialties Section -->
        <section id="especialidades" class="py-20 px-6 bg-unimar-matte border-b border-gray-200/30 z-10 relative">
            <div class="max-w-6xl mx-auto">
                <div class="text-center max-w-xl mx-auto mb-16">
                    <h2 class="text-2xl sm:text-3xl font-bold text-unimar-blue tracking-tight">Navegar por Especialidad</h2>
                    <div class="w-12 h-1 bg-unimar-gold mx-auto mt-4 rounded-full"></div>
                    <p class="text-xs text-gray-500 mt-4 leading-relaxed font-medium">
                        Explora las investigaciones científicas y proyectos de grado agrupados por los programas de ingeniería del Decanato.
                    </p>
                </div>

                <!-- Career Cards Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                    <!-- Sistemas -->
                    <div class="bg-white p-8 rounded-xl border border-gray-100 shadow-[0_10px_30px_rgba(13,77,152,0.03)] hover:shadow-[0_15px_35px_rgba(13,77,152,0.06)] hover:-translate-y-1 transition-all duration-300 group flex flex-col items-start">
                        <div class="w-12 h-12 rounded-lg bg-unimar-blue/5 text-unimar-blue flex items-center justify-center mb-6 group-hover:bg-unimar-blue group-hover:text-white transition-colors duration-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-extrabold text-unimar-blue mb-2">Ingeniería de Sistemas</h3>
                        <p class="text-sm text-gray-500 leading-relaxed mb-6 font-medium">Computación, inteligencia artificial, desarrollo de software y sistemas de información de alto rendimiento.</p>
                        <div class="flex items-center justify-between w-full mt-auto pt-4 border-t border-gray-50">
                            <span class="text-[11px] font-extrabold text-unimar-gold bg-unimar-blue/5 px-2.5 py-1 rounded-full uppercase tracking-wider">
                                {{ $displayCounts['sistemas'] }}
                            </span>
                            <a href="{{ route('catalog.index') }}?program=sistemas" class="text-sm font-bold text-unimar-blue hover:text-unimar-gold flex items-center">
                                Explorar
                                <svg class="w-3.5 h-3.5 ml-1 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- Civil -->
                    <div class="bg-white p-8 rounded-xl border border-gray-100 shadow-[0_10px_30px_rgba(13,77,152,0.03)] hover:shadow-[0_15px_35px_rgba(13,77,152,0.06)] hover:-translate-y-1 transition-all duration-300 group flex flex-col items-start">
                        <div class="w-12 h-12 rounded-lg bg-unimar-blue/5 text-unimar-blue flex items-center justify-center mb-6 group-hover:bg-unimar-blue group-hover:text-white transition-colors duration-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-extrabold text-unimar-blue mb-2">Ingeniería Civil</h3>
                        <p class="text-sm text-gray-500 leading-relaxed mb-6 font-medium">Diseño estructural, vialidad, hidráulica, geotecnia y planificación urbana sostenible.</p>
                        <div class="flex items-center justify-between w-full mt-auto pt-4 border-t border-gray-50">
                            <span class="text-[11px] font-extrabold text-unimar-gold bg-unimar-blue/5 px-2.5 py-1 rounded-full uppercase tracking-wider">
                                {{ $displayCounts['civil'] }}
                            </span>
                            <a href="{{ route('catalog.index') }}?program=civil" class="text-sm font-bold text-unimar-blue hover:text-unimar-gold flex items-center">
                                Explorar
                                <svg class="w-3.5 h-3.5 ml-1 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- Industrial -->
                    <div class="bg-white p-8 rounded-xl border border-gray-100 shadow-[0_10px_30px_rgba(13,77,152,0.03)] hover:shadow-[0_15px_35px_rgba(13,77,152,0.06)] hover:-translate-y-1 transition-all duration-300 group flex flex-col items-start">
                        <div class="w-12 h-12 rounded-lg bg-unimar-blue/5 text-unimar-blue flex items-center justify-center mb-6 group-hover:bg-unimar-blue group-hover:text-white transition-colors duration-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-extrabold text-unimar-blue mb-2">Ingeniería Industrial</h3>
                        <p class="text-sm text-gray-500 leading-relaxed mb-6 font-medium">Optimización de recursos corporativos, gestión de procesos de producción, logística e higiene.</p>
                        <div class="flex items-center justify-between w-full mt-auto pt-4 border-t border-gray-50">
                            <span class="text-[11px] font-extrabold text-unimar-gold bg-unimar-blue/5 px-2.5 py-1 rounded-full uppercase tracking-wider">
                                {{ $displayCounts['industrial'] }}
                            </span>
                            <a href="{{ route('catalog.index') }}?program=industrial" class="text-sm font-bold text-unimar-blue hover:text-unimar-gold flex items-center">
                                Explorar
                                <svg class="w-3.5 h-3.5 ml-1 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- Química -->
                    <div class="bg-white p-8 rounded-xl border border-gray-100 shadow-[0_10px_30px_rgba(13,77,152,0.03)] hover:shadow-[0_15px_35px_rgba(13,77,152,0.06)] hover:-translate-y-1 transition-all duration-300 group flex flex-col items-start">
                        <div class="w-12 h-12 rounded-lg bg-unimar-blue/5 text-unimar-blue flex items-center justify-center mb-6 group-hover:bg-unimar-blue group-hover:text-white transition-colors duration-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-extrabold text-unimar-blue mb-2">Ingeniería Química</h3>
                        <p class="text-sm text-gray-500 leading-relaxed mb-6 font-medium">Diseño de procesos químicos industriales, termodinámica y desarrollo de nuevos materiales.</p>
                        <div class="flex items-center justify-between w-full mt-auto pt-4 border-t border-gray-50">
                            <span class="text-[11px] font-extrabold text-unimar-gold bg-unimar-blue/5 px-2.5 py-1 rounded-full uppercase tracking-wider">
                                {{ $displayCounts['quimica'] }}
                            </span>
                            <a href="{{ route('catalog.index') }}?program=quimica" class="text-sm font-bold text-unimar-blue hover:text-unimar-gold flex items-center">
                                Explorar
                                <svg class="w-3.5 h-3.5 ml-1 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Research Lines Section (Líneas de Investigación Activas) -->
        <section id="lineas-investigacion" class="py-20 px-6 bg-unimar-matte border-b border-gray-200/30 z-10 relative">
            <div class="max-w-6xl mx-auto">
                <div class="text-center max-w-xl mx-auto mb-16">
                    <h2 class="text-2xl sm:text-3xl font-bold text-unimar-blue tracking-tight">Líneas de Investigación Activas</h2>
                    <div class="w-12 h-1 bg-unimar-gold mx-auto mt-4 rounded-full"></div>
                    <p class="text-xs text-gray-500 mt-4 leading-relaxed font-medium">
                        Áreas metodológicas y temáticas oficiales que guían el desarrollo de proyectos y trabajos científicos en el Decanato.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    @if(count($lineasInvestigacion) > 0)
                        @foreach($lineasInvestigacion as $linea)
                            <!-- Dynamic Research Line Card -->
                            <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-[0_8px_25px_rgba(13,77,152,0.02)] hover:shadow-[0_12px_30px_rgba(13,77,152,0.05)] hover:-translate-y-1 transition-all duration-300 flex flex-col border-t-4 border-unimar-blue">
                                <span class="text-[11px] font-extrabold uppercase tracking-wider text-unimar-gold mb-3 block">
                                    {{ $linea->academicProgram ? $linea->academicProgram->nombre : 'Programa Académico' }}
                                </span>
                                <h3 class="text-base font-extrabold text-unimar-blue mb-3 leading-snug">
                                    {{ $linea->name }}
                                </h3>
                                <p class="text-sm text-gray-500 leading-relaxed mb-4 font-medium line-clamp-4">
                                    {{ $linea->description ?? 'Sin descripción registrada actualmente.' }}
                                </p>
                                <a href="{{ route('catalog.index') }}?line={{ $linea->id }}" class="text-sm font-bold text-unimar-blue hover:text-unimar-gold flex items-center mt-auto">
                                    Ver Proyectos
                                    <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </a>
                            </div>
                        @endforeach
                    @else
                        <!-- Mockup Fallback Research Lines (Shown only if database table has 0 active lines) -->
                        <!-- Line 1: Sistemas -->
                        <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-[0_8px_25px_rgba(13,77,152,0.02)] hover:shadow-[0_12px_30px_rgba(13,77,152,0.05)] hover:-translate-y-1 transition-all duration-300 flex flex-col border-t-4 border-unimar-blue">
                            <span class="text-[11px] font-extrabold uppercase tracking-wider text-unimar-gold mb-3 block">
                                Ingeniería de Sistemas
                            </span>
                            <h3 class="text-base font-extrabold text-unimar-blue mb-3 leading-snug">
                                Software Inteligente y Sistemas Distribuidos
                            </h3>
                            <p class="text-sm text-gray-500 leading-relaxed mb-4 font-medium line-clamp-4">
                                Investigación centrada en aprendizaje automático, procesamiento de lenguaje natural (NLP), arquitecturas en la nube y optimización de software bajo conectividad variable.
                            </p>
                            <a href="#" class="text-sm font-bold text-unimar-blue hover:text-unimar-gold flex items-center mt-auto">
                                Ver Proyectos
                                <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>

                        <!-- Line 2: Civil -->
                        <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-[0_8px_25px_rgba(13,77,152,0.02)] hover:shadow-[0_12px_30px_rgba(13,77,152,0.05)] hover:-translate-y-1 transition-all duration-300 flex flex-col border-t-4 border-unimar-blue">
                            <span class="text-[11px] font-extrabold uppercase tracking-wider text-unimar-gold mb-3 block">
                                Ingeniería Civil
                            </span>
                            <h3 class="text-base font-extrabold text-unimar-blue mb-3 leading-snug">
                                Infraestructura Vial y Estructuras Sismorresistentes
                            </h3>
                            <p class="text-sm text-gray-500 leading-relaxed mb-4 font-medium line-clamp-4">
                                Modelado y optimización sismo-resistente de edificaciones costeras, diseño geométrico de vías terrestres, y mezclas asfálticas modificadas para climas tropicales.
                            </p>
                            <a href="#" class="text-sm font-bold text-unimar-blue hover:text-unimar-gold flex items-center mt-auto">
                                Ver Proyectos
                                <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>

                        <!-- Line 3: Industrial -->
                        <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-[0_8px_25px_rgba(13,77,152,0.02)] hover:shadow-[0_12px_30px_rgba(13,77,152,0.05)] hover:-translate-y-1 transition-all duration-300 flex flex-col border-t-4 border-unimar-blue">
                            <span class="text-[11px] font-extrabold uppercase tracking-wider text-unimar-gold mb-3 block">
                                Ingeniería Industrial
                            </span>
                            <h3 class="text-base font-extrabold text-unimar-blue mb-3 leading-snug">
                                Gestión de Operaciones y Eco-Eficiencia Industrial
                            </h3>
                            <p class="text-sm text-gray-500 leading-relaxed mb-4 font-medium line-clamp-4">
                                Desarrollo de cadenas de suministro verdes, optimización de flujos de producción en la manufactura regional y simulación de sistemas logísticos humanitarios.
                            </p>
                            <a href="#" class="text-sm font-bold text-unimar-blue hover:text-unimar-gold flex items-center mt-auto">
                                Ver Proyectos
                                <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>

                        <!-- Line 4: Química -->
                        <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-[0_8px_25px_rgba(13,77,152,0.02)] hover:shadow-[0_12px_30px_rgba(13,77,152,0.05)] hover:-translate-y-1 transition-all duration-300 flex flex-col border-t-4 border-unimar-blue">
                            <span class="text-[11px] font-extrabold uppercase tracking-wider text-unimar-gold mb-3 block">
                                Ingeniería Química
                            </span>
                            <h3 class="text-base font-extrabold text-unimar-blue mb-3 leading-snug">
                                Bioprocesos y Tecnología de Materiales Sustentables
                            </h3>
                            <p class="text-sm text-gray-500 leading-relaxed mb-4 font-medium line-clamp-4">
                                Extracción supercrítica de aceites esenciales de plantas nativas, síntesis de biopolímeros degradables a partir de residuos marinos y tratamiento de aguas.
                            </p>
                            <a href="#" class="text-sm font-bold text-unimar-blue hover:text-unimar-gold flex items-center mt-auto">
                                Ver Proyectos
                                <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <!-- Latest Publications Section -->
        <section id="publicaciones" class="py-20 px-6 z-10 relative bg-unimar-matte">
            <div class="max-w-6xl mx-auto">
                <div class="text-center max-w-xl mx-auto mb-16">
                    <h2 class="text-2xl sm:text-3xl font-bold text-unimar-blue tracking-tight">Últimas Publicaciones Aprobadas</h2>
                    <div class="w-12 h-1 bg-unimar-gold mx-auto mt-4 rounded-full"></div>
                    <p class="text-xs text-gray-500 mt-4 leading-relaxed font-medium">
                        Investigaciones de excelencia que han completado exitosamente todo el flujo de revisión y aprobación académica del Decanato.
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    @if(count($publicaciones) > 0)
                        @foreach($publicaciones as $pub)
                            <!-- Dynamic Publication Card (Completely dynamic, real values) -->
                            <div class="bg-white p-8 rounded-xl border border-gray-100 shadow-[0_10px_30px_rgba(13,77,152,0.03)] flex flex-col items-start hover:shadow-[0_15px_35px_rgba(13,77,152,0.06)] hover:-translate-y-1 transition-all duration-300">
                                <div class="flex items-center justify-between w-full mb-6">
                                    <span class="px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider bg-green-50 text-green-700 rounded-full border border-green-100">
                                        Publicado
                                    </span>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                                        {{ $pub->academicPeriod ? $pub->academicPeriod->nombre : 'Periodo Activo' }}
                                    </span>
                                </div>
                                <div class="flex items-start space-x-4 mb-4">
                                    <div class="w-10 h-10 rounded bg-unimar-blue/5 text-unimar-blue flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-base font-extrabold text-unimar-blue line-clamp-2 hover:text-[#09356b] transition-colors leading-snug">
                                        {{ $pub->title }}
                                    </h3>
                                </div>
                                <div class="text-sm text-gray-500 space-y-1.5 mb-6 font-medium">
                                    <p><strong class="font-semibold text-gray-700">Autor:</strong> {{ $pub->authors ?? 'Investigador' }}</p>
                                    <p><strong class="font-semibold text-gray-700">Tutor:</strong> {{ $pub->tutor ?? 'No asignado' }}</p>
                                    <p><strong class="font-semibold text-gray-700">Programa:</strong> {{ $pub->academicProgram ? $pub->academicProgram->nombre : 'Ingeniería' }}</p>
                                </div>
                                <a href="{{ route('productions.show', $pub->id) }}" class="text-sm font-bold text-unimar-blue hover:text-unimar-gold flex items-center mt-auto">
                                    Ver Detalles
                                    <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </a>
                            </div>
                        @endforeach
                    @else
                        <!-- Mockup fallback cards (Montserrat, UNIMAR blue, only shown if database has 0 published works) -->
                        <!-- Card 1: Sistemas -->
                        <div class="bg-white p-8 rounded-xl border border-gray-100 shadow-[0_10px_30px_rgba(13,77,152,0.03)] flex flex-col items-start hover:shadow-[0_15px_35px_rgba(13,77,152,0.06)] hover:-translate-y-1 transition-all duration-300">
                            <div class="flex items-center justify-between w-full mb-6">
                                <span class="px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider bg-green-50 text-green-700 rounded-full border border-green-100">
                                    Publicado
                                </span>
                                <span class="text-[10px] font-bold text-gray-400">
                                    Período 2025-I
                                </span>
                            </div>
                            <div class="flex items-start space-x-4 mb-4">
                                <div class="w-10 h-10 rounded bg-unimar-blue/5 text-unimar-blue flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-base font-extrabold text-unimar-blue line-clamp-2 leading-snug">
                                    Optimización de Algoritmos de Búsqueda sobre Conectividad Variable en el Valle del Espíritu Santo
                                </h3>
                            </div>
                            <div class="text-sm text-gray-500 space-y-1.5 mb-6 font-medium">
                                <p><strong class="font-semibold text-gray-700">Autor:</strong> Jesús Regnault</p>
                                <p><strong class="font-semibold text-gray-700">Tutor:</strong> Prof. Alejandro Silva</p>
                                <p><strong class="font-semibold text-gray-700">Programa:</strong> Ingeniería de Sistemas</p>
                            </div>
                            <a href="#" class="text-sm font-bold text-unimar-blue hover:text-unimar-gold flex items-center mt-auto">
                                Ver Detalles
                                <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>

                        <!-- Card 2: Civil -->
                        <div class="bg-white p-8 rounded-xl border border-gray-100 shadow-[0_10px_30px_rgba(13,77,152,0.03)] flex flex-col items-start hover:shadow-[0_15px_35px_rgba(13,77,152,0.06)] hover:-translate-y-1 transition-all duration-300">
                            <div class="flex items-center justify-between w-full mb-6">
                                <span class="px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider bg-green-50 text-green-700 rounded-full border border-green-100">
                                    Publicado
                                </span>
                                <span class="text-[10px] font-bold text-gray-400">
                                    Período 2024-II
                                </span>
                            </div>
                            <div class="flex items-start space-x-4 mb-4">
                                <div class="w-10 h-10 rounded bg-unimar-blue/5 text-unimar-blue flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-base font-extrabold text-unimar-blue line-clamp-2 leading-snug">
                                    Propuesta de Diseño Estructural del Puente El Toporo utilizando Modelado Numérico Avanzado
                                </h3>
                            </div>
                            <div class="text-sm text-gray-500 space-y-1.5 mb-6 font-medium">
                                <p><strong class="font-semibold text-gray-700">Autor:</strong> María Valentina Gómez</p>
                                <p><strong class="font-semibold text-gray-700">Tutor:</strong> Ing. Carlos Rodríguez</p>
                                <p><strong class="font-semibold text-gray-700">Programa:</strong> Ingeniería Civil</p>
                            </div>
                            <a href="#" class="text-sm font-bold text-unimar-blue hover:text-unimar-gold flex items-center mt-auto">
                                Ver Detalles
                                <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>

                        <!-- Card 3: Industrial -->
                        <div class="bg-white p-8 rounded-xl border border-gray-100 shadow-[0_10px_30px_rgba(13,77,152,0.03)] flex flex-col items-start hover:shadow-[0_15px_35px_rgba(13,77,152,0.06)] hover:-translate-y-1 transition-all duration-300">
                            <div class="flex items-center justify-between w-full mb-6">
                                <span class="px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider bg-green-50 text-green-700 rounded-full border border-green-100">
                                    Publicado
                                </span>
                                <span class="text-[10px] font-bold text-gray-400">
                                    Período 2024-II
                                </span>
                            </div>
                            <div class="flex items-start space-x-4 mb-4">
                                <div class="w-10 h-10 rounded bg-unimar-blue/5 text-unimar-blue flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-base font-extrabold text-unimar-blue line-clamp-2 leading-snug">
                                    Diseño de un Sistema de Gestión del Conocimiento Científico para el Decanato de Ingeniería
                                </h3>
                            </div>
                            <div class="text-sm text-gray-500 space-y-1.5 mb-6 font-medium">
                                <p><strong class="font-semibold text-gray-700">Autor:</strong> Ricardo Bermúdez</p>
                                <p><strong class="font-semibold text-gray-700">Tutor:</strong> Prof. Luis M. Marcano</p>
                                <p><strong class="font-semibold text-gray-700">Programa:</strong> Ingeniería de Sistemas</p>
                            </div>
                            <a href="#" class="text-sm font-bold text-unimar-blue hover:text-unimar-gold flex items-center mt-auto">
                                Ver Detalles
                                <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <!-- Institutional Footer -->
        <footer class="w-full bg-unimar-matte border-t border-gray-200/40 py-12 px-6 mt-auto z-10 relative">
            <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
                <!-- Left: Logo & Location -->
                <div class="md:col-span-6 flex flex-col items-start space-y-3">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded bg-unimar-blue flex items-center justify-center text-white font-bold text-sm">
                            U
                        </div>
                        <span class="text-xs font-extrabold tracking-wider text-unimar-blue uppercase">Universidad de Margarita</span>
                    </div>
                    <p class="text-[10px] text-gray-500 max-w-sm leading-relaxed font-medium">
                        Av. Concepción Mariño, Sector El Toporo, El Valle del Espíritu Santo, Edo. Nueva Esparta, Venezuela.
                    </p>
                </div>

                <!-- Right: Copyright & Legal -->
                <div class="md:col-span-6 flex flex-col md:items-end space-y-2">
                    <p class="text-[10px] text-gray-500 text-left md:text-right font-medium">
                        © Copyright 2001-2026 Universidad de Margarita, RIF: J-30660040-0. Isla de Margarita - Venezuela.
                    </p>
                    <p class="text-[9px] text-gray-400 text-left md:text-right uppercase tracking-wider font-extrabold">
                        SKMS | Decanato de Ingeniería y Afines
                    </p>
                </div>
            </div>
        </footer>

    </body>
</html>
