@php
    $dynamicKeywords = cache()->remember('landing_keywords', 3600, function() {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('keywords')) {
                return [];
            }
            return \App\Models\Keyword::whereHas('productions', fn($q) => $q->published())
                ->take(5)
                ->pluck('name')
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    });

    if (empty($dynamicKeywords)) {
        $dynamicKeywords = ['Sistemas', 'Industrial', 'Naval', 'Investigación', 'Tesis'];
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>SKMS-Unimar | Decanato de Ingeniería y Afines</title>
        <link rel="icon" type="image/x-icon" href="{{ asset('images/logoIco.ico') }}">

        <!-- Google Fonts - Montserrat (Official UNIMAR Font) -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <script src="https://cdn.tailwindcss.com"></script>
            <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
        <header x-data="{ mobileMenuOpen: false }" class="w-full bg-white/90 backdrop-blur-md border-b border-gray-200/40 sticky top-0 z-50 transition-all duration-200">
            <div class="max-w-[1380px] mx-auto px-6 h-20 flex items-center justify-between">
                <!-- Brand Logo -->
                <a href="{{ url('/') }}" class="flex items-center space-x-3 group">
                    <img src="{{ asset('images/logo.svg') }}" alt="SKMS Unimar" class="w-12 h-12 object-contain transition-transform group-hover:scale-105">
                    <div class="flex flex-col">
                        <span class="text-base font-extrabold tracking-wider text-unimar-blue uppercase leading-none">SKMS</span>
                        <span class="text-sm font-semibold text-gray-500 mt-0.5 uppercase tracking-wider">Decanato de Ingeniería</span>
                    </div>
                </a>

                <!-- Navigation Links (Desktop) -->
                <nav class="hidden lg:flex items-center space-x-8">
                    <a href="{{ url('/') }}" class="text-base font-semibold text-unimar-blue border-b-2 border-unimar-blue pb-1">Inicio</a>
                    <a href="#especialidades" class="text-base font-semibold text-gray-600 hover:text-unimar-blue transition-colors">Carreras</a>
                    <a href="#lineas-investigacion" class="text-base font-semibold text-gray-600 hover:text-unimar-blue transition-colors">Líneas</a>
                    <a href="#publicaciones" class="text-base font-semibold text-gray-600 hover:text-unimar-blue transition-colors">Investigaciones</a>
                </nav>

                <!-- Auth Buttons (Desktop) -->
                <div class="hidden lg:flex items-center space-x-6">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-bold uppercase tracking-wider text-white bg-unimar-blue hover:bg-[#09356b] rounded-lg transition-all duration-200 shadow-md shadow-unimar-blue/10 hover:shadow-lg hover:shadow-unimar-blue/20">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-bold uppercase tracking-wider text-white bg-unimar-blue hover:bg-[#09356b] rounded-lg transition-all duration-200 shadow-md shadow-unimar-blue/10 hover:shadow-lg hover:shadow-unimar-blue/20">
                                Acceder
                            </a>
                        @endauth
                    @endif
                </div>

                <!-- Hamburger Menu Button (Mobile/Tablet) -->
                <div class="flex items-center lg:hidden">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="inline-flex items-center justify-center p-2 rounded-lg text-unimar-blue hover:bg-unimar-blue/5 focus:outline-none transition duration-150 ease-in-out">
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
                 class="lg:hidden border-t border-gray-200/40 bg-white/95 backdrop-blur-md px-6 py-5 space-y-4 shadow-inner" 
                 style="display: none;">
                <nav class="flex flex-col space-y-3">
                    <a href="{{ url('/') }}" @click="mobileMenuOpen = false" class="text-base font-semibold text-unimar-blue py-1">Inicio</a>
                    <a href="#especialidades" @click="mobileMenuOpen = false" class="text-base font-semibold text-gray-600 hover:text-unimar-blue transition-colors py-1">Carreras</a>
                    <a href="#lineas-investigacion" @click="mobileMenuOpen = false" class="text-base font-semibold text-gray-600 hover:text-unimar-blue transition-colors py-1">Líneas</a>
                    <a href="#publicaciones" @click="mobileMenuOpen = false" class="text-base font-semibold text-gray-600 hover:text-unimar-blue transition-colors py-1">Investigaciones</a>
                </nav>

                <div class="pt-4 border-t border-slate-200/50 flex flex-col space-y-2.5">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="w-full inline-flex items-center justify-center px-5 py-2.5 text-sm font-bold uppercase tracking-wider text-white bg-unimar-blue hover:bg-[#09356b] rounded-lg transition shadow-md">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="w-full inline-flex items-center justify-center px-5 py-2.5 text-sm font-bold uppercase tracking-wider text-white bg-unimar-blue hover:bg-[#09356b] rounded-lg transition shadow-md">
                                Acceder
                            </a>
                        @endauth
                    @endif
                </div>
            </div>
        </header>

        <!-- Dynamic Counts and Queries Setup -->
        @php
            // Safe dynamic counts for programs
            $counts = ['sistemas' => 0, 'industrial' => 0, 'naval' => 0];
            $publicaciones = [];
            $lineasInvestigacion = [];
            $programSistemas = null;
            $programIndustrial = null;
            $programNaval = null;
            
            try {
                if (class_exists(\App\Models\Production::class) && class_exists(\App\Models\AcademicProgram::class)) {
                    // Fetch live publications
                    $publicaciones = \App\Models\Production::where('workflow_state', 'published')
                        ->with(['academicProgram', 'academicPeriod'])
                        ->latest('published_at')
                        ->take(10)
                        ->get();

                    $programSistemas = \App\Models\AcademicProgram::where('code', 'ING-SIS')->first();
                    $programIndustrial = \App\Models\AcademicProgram::where('code', 'ING-IND')->first();
                    $programNaval = \App\Models\AcademicProgram::where('code', 'TEC-NAV')->first();

                    // Calculate live counts per program
                    $programs = \App\Models\AcademicProgram::all();
                    foreach ($programs as $prog) {
                        $code = strtolower($prog->code);
                        $count = \App\Models\Production::where('workflow_state', 'published')
                            ->where('academic_program_id', $prog->id)
                            ->count();

                        if (str_contains($code, 'sis') || str_contains(strtolower($prog->name), 'sistemas')) {
                            $counts['sistemas'] = $count;
                        } elseif (str_contains($code, 'ind') || str_contains(strtolower($prog->name), 'industrial')) {
                            $counts['industrial'] = $count;
                        } elseif (str_contains($code, 'nav') || str_contains(strtolower($prog->name), 'naval')) {
                            $counts['naval'] = $count;
                        }
                    }
                }

                if (class_exists(\App\Models\ResearchLine::class)) {
                    // Fetch live active research lines
                    $lineasInvestigacion = \App\Models\ResearchLine::where('is_active', true)
                        ->with('academicProgram')
                        ->get();
                }
            } catch (\Exception $e) {
                // Safe fallback if database tables do not exist yet
            }

            // Real counts display texts
            $displayCounts = [
                'sistemas' => $counts['sistemas'] . ' Publicaciones',
                'industrial' => $counts['industrial'] . ' Publicaciones',
                'naval' => $counts['naval'] . ' Publicaciones',
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
                

                <!-- Cohesive Title & Subtitle Block -->
                <div class="flex flex-col items-center space-y-2 mb-8 max-w-3xl">
                    <h1 class="text-5xl sm:text-6xl font-extrabold tracking-tight text-white leading-none">
                        SKMS
                    </h1>
                    <h2 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-white leading-tight">
                        Sistema de Gestión del Conocimiento Científico
                    </h2>
                    <p class="text-sm sm:text-base font-bold text-unimar-gold uppercase tracking-widest mt-1">
                        Decanato de Ingeniería y Afines
                    </p>
                </div>

                <!-- Tightly Integrated Search Bar (No empty vertical spaces) -->
                <div class="w-full max-w-2xl mb-6">
                    <form action="{{ route('catalog.index') }}" method="GET" class="relative group">
                        <input 
                            type="text" 
                            name="q" 
                            placeholder="Buscar trabajos especiales de grado, tesis y artículos..."
                            class="w-full h-16 pl-6 pr-16 text-gray-800 bg-white border border-white/10 rounded-lg focus:outline-none focus:ring-4 focus:ring-unimar-gold/40 focus:border-transparent transition-all duration-200 shadow-2xl text-base placeholder-gray-400 font-medium"
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
                <div class="flex flex-wrap items-center justify-center gap-2.5 text-sm text-white/80 max-w-xl">
                    <span class="font-semibold text-white/60">Filtros rápidos:</span>
                    @foreach($dynamicKeywords as $keyword)
                        <a href="{{ route('catalog.index') }}?q={{ urlencode($keyword) }}" class="px-3 py-1 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 hover:text-white transition-all">
                            {{ $keyword }}
                        </a>
                    @endforeach
                </div>

            </div>
        </section>

        <!-- Specialties Section -->
        <section id="especialidades" class="py-20 px-6 bg-unimar-matte border-b border-gray-200/30 z-10 relative">
            <div class="max-w-[1380px] mx-auto">
                <div class="text-center max-w-xl mx-auto mb-16">
                    <h2 class="text-2xl sm:text-3xl font-bold text-unimar-blue tracking-tight">Navegar por Especialidad</h2>
                    <div class="w-12 h-1 bg-unimar-gold mx-auto mt-4 rounded-full"></div>
                    <p class="text-base text-gray-500 mt-4 leading-relaxed font-medium">
                        Explora las investigaciones científicas y proyectos de grado agrupados por los programas de ingeniería del Decanato.
                    </p>
                </div>

                <!-- Career Cards Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- Sistemas -->
                    <x-program-card 
                        title="Ingeniería de Sistemas"
                        description="Computación, inteligencia artificial, desarrollo de software y sistemas de información de alto rendimiento."
                        :count="$displayCounts['sistemas']"
                        :link="route('catalog.index') . '?program=' . ($programSistemas?->id ?? '')"
                    >
                        <x-slot name="icon">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                            </svg>
                        </x-slot>
                    </x-program-card>

                    <!-- Industrial -->
                    <x-program-card 
                        title="Ingeniería Industrial"
                        description="Optimización de recursos corporativos, gestión de procesos de producción, logística e higiene."
                        :count="$displayCounts['industrial']"
                        :link="route('catalog.index') . '?program=' . ($programIndustrial?->id ?? '')"
                    >
                        <x-slot name="icon">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </x-slot>
                    </x-program-card>

                    <!-- Tecnología Naval -->
                    <x-program-card 
                        title="Tecnología Naval"
                        description="Diseño y construcción de embarcaciones, sistemas de propulsión marina, mantenimiento naval y operaciones marítimas."
                        :count="$displayCounts['naval']"
                        :link="route('catalog.index') . '?program=' . ($programNaval?->id ?? '')"
                    >
                        <x-slot name="icon">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8V22m0 0H9m3 0h3M12 8a3 3 0 100-6 3 3 0 000 6zm-7 6a7 7 0 0014 0"></path>
                            </svg>
                        </x-slot>
                    </x-program-card>
                </div>
            </div>
        </section>

        <!-- Research Lines Section (Líneas de Investigación Activas) -->
        <section id="lineas-investigacion" class="py-20 px-6 bg-unimar-matte border-b border-gray-200/30 z-10 relative">
            <div class="max-w-[1380px] mx-auto">
                <div class="text-center max-w-xl mx-auto mb-16">
                    <h2 class="text-2xl sm:text-3xl font-bold text-unimar-blue tracking-tight">Líneas de Investigación Activas</h2>
                    <div class="w-12 h-1 bg-unimar-gold mx-auto mt-4 rounded-full"></div>
                    <p class="text-base text-gray-500 mt-4 leading-relaxed font-medium">
                        Áreas metodológicas y temáticas oficiales que guían el desarrollo de proyectos y trabajos científicos en el Decanato.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    @if(count($lineasInvestigacion) > 0)
                        @foreach($lineasInvestigacion as $linea)
                            <x-research-line-card 
                                :program="$linea->academicProgram ? $linea->academicProgram->name : 'Programa Académico'"
                                :title="$linea->name"
                                :description="$linea->description ?? 'Sin descripción registrada actualmente.'"
                                :link="route('catalog.index') . '?line=' . $linea->id"
                            />
                        @endforeach
                    @else
                        <!-- Mockup Fallback Research Lines (Shown only if database table has 0 active lines) -->
                        <x-research-line-card 
                            program="Ingeniería de Sistemas"
                            title="Software Inteligente y Sistemas Distribuidos"
                            description="Investigación centrada en aprendizaje automático, procesamiento de lenguaje natural (NLP), arquitecturas en la nube y optimización de software bajo conectividad variable."
                            link="#"
                        />

                        <x-research-line-card 
                            program="Ingeniería Industrial"
                            title="Gestión de Operaciones y Eco-Eficiencia Industrial"
                            description="Desarrollo de cadenas de suministro verdes, optimización de flujos de producción en la manufactura regional y simulación de sistemas logísticos humanitarios."
                            link="#"
                        />

                        <x-research-line-card 
                            program="Tecnología Naval"
                            title="Sistemas de Propulsión y Diseño de Buques"
                            description="Investigación en diseño hidrodinámico, eficiencia energética de motores marinos, estabilidad de embarcaciones costeras y materiales de construcción naval."
                            link="#"
                        />
                    @endif
                </div>
            </div>
        </section>

        <!-- Latest Publications Section -->
        <section id="publicaciones" class="py-20 px-6 z-10 relative bg-unimar-matte">
            <div class="max-w-[1380px] mx-auto">
                <div class="text-center max-w-xl mx-auto mb-16">
                    <h2 class="text-2xl sm:text-3xl font-bold text-unimar-blue tracking-tight">Últimas Publicaciones Aprobadas</h2>
                    <div class="w-12 h-1 bg-unimar-gold mx-auto mt-4 rounded-full"></div>
                    <p class="text-base text-gray-500 mt-4 leading-relaxed font-medium">
                        Investigaciones de excelencia que han completado exitosamente todo el flujo de revisión y aprobación académica del Decanato.
                    </p>
                </div>

                <div x-data="{ 
                    activeCard: 0, 
                    totalCards: {{ count($publicaciones) > 0 ? count($publicaciones) : 3 }},
                    cardsPerPage: 3,
                    updateCardsPerPage() {
                        if (window.innerWidth >= 1024) {
                            this.cardsPerPage = 3;
                        } else if (window.innerWidth >= 640) {
                            this.cardsPerPage = 2;
                        } else {
                            this.cardsPerPage = 1;
                        }
                    },
                    next() {
                        if (this.activeCard < this.totalCards - this.cardsPerPage) {
                            this.activeCard++;
                        } else {
                            this.activeCard = 0;
                        }
                    },
                    prev() {
                        if (this.activeCard > 0) {
                            this.activeCard--;
                        } else {
                            this.activeCard = Math.max(0, this.totalCards - this.cardsPerPage);
                        }
                    }
                }" x-init="updateCardsPerPage(); window.addEventListener('resize', () => updateCardsPerPage())" class="space-y-6">

                    <!-- Carousel Controls (Buttons on top/right) -->
                    <div class="flex justify-end space-x-3 mb-4">
                        <button type="button" @click="prev()" class="w-11 h-11 rounded-full border border-slate-200 bg-white hover:bg-slate-50 hover:text-unimar-blue transition flex items-center justify-center text-slate-600 shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <button type="button" @click="next()" class="w-11 h-11 rounded-full border border-slate-200 bg-white hover:bg-slate-50 hover:text-unimar-blue transition flex items-center justify-center text-slate-600 shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Carousel Window -->
                    <div class="relative overflow-hidden w-full px-1">
                        <div class="flex transition-transform duration-500 ease-out -mx-4" :style="'transform: translateX(-' + (activeCard * (100 / cardsPerPage)) + '%)'">
                            @if(count($publicaciones) > 0)
                                @foreach($publicaciones as $pub)
                                    <div class="px-4 shrink-0" :style="'width: ' + (100 / cardsPerPage) + '%'">
                                        <x-publication-card 
                                            :title="$pub->title"
                                            :author="$pub->authors ?? 'Investigador'"
                                            :tutor="$pub->tutor ?? 'No asignado'"
                                            :program="$pub->academicProgram ? $pub->academicProgram->name : 'Ingeniería'"
                                            :period="$pub->academicPeriod ? $pub->academicPeriod->name : 'Periodo Activo'"
                                            :link="$pub->show_url"
                                            :showPdf="$pub->hasMedia('documento')"
                                            :pdfLink="$pub->pdf_url"
                                        />
                                    </div>
                                @endforeach
                            @else
                                <!-- Mockup fallback cards -->
                                <div class="px-4 shrink-0" :style="'width: ' + (100 / cardsPerPage) + '%'">
                                    <x-publication-card 
                                        title="Optimización de Algoritmos de Búsqueda sobre Conectividad Variable en el Valle del Espíritu Santo"
                                        author="Jesús Regnault"
                                        tutor="Prof. Alejandro Silva"
                                        program="Ingeniería de Sistemas"
                                        period="Período 2025-I"
                                        link="#"
                                    />
                                </div>
                                <div class="px-4 shrink-0" :style="'width: ' + (100 / cardsPerPage) + '%'">
                                    <x-publication-card 
                                        title="Diseño e Implementación de un Modelo de Simulación Logística para la Distribución Regional de Insumos"
                                        author="Mariana Mouhamed"
                                        tutor="Ing. Santiago Rodríguez"
                                        program="Ingeniería Industrial"
                                        period="Período 2024-II"
                                        link="#"
                                    />
                                </div>
                                <div class="px-4 shrink-0" :style="'width: ' + (100 / cardsPerPage) + '%'">
                                    <x-publication-card 
                                        title="Propuesta de Diseño Hidrodinámico de Embarcaciones Pesqueras de Bajo Calado para la Isla de Margarita"
                                        author="Ricardo Bermúdez"
                                        tutor="Prof. Luis M. Marcano"
                                        program="Tecnología Naval"
                                        period="Período 2024-II"
                                        link="#"
                                    />
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Institutional Footer -->
        <footer class="w-full bg-white border-t border-gray-200/40 py-12 px-6 mt-auto z-10 relative">
            <div class="max-w-[1380px] mx-auto grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
                <!-- Left: Logo & Location -->
                <div class="md:col-span-6 flex flex-col items-start space-y-3">
                    <div class="flex items-center space-x-3">
                        <img src="{{ asset('images/logo.svg') }}" alt="SKMS Unimar" class="w-[39px] h-[39px] object-contain">
                        <span class="text-sm font-extrabold tracking-wider text-unimar-blue uppercase">Universidad de Margarita</span>
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
                        SKMS | Decanato de Ingeniería y Afines
                    </p>
                </div>
            </div>
        </footer>

    </body>
</html>
