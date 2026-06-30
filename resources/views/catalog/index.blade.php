@php
    // Determine active role from session or default to the user's first role
    $activeRole = session('active_dashboard_role', auth()->user()->getRoleNames()->first() ?? 'Estudiante');
    $roles = auth()->user()->getRoleNames()->toArray();
@endphp

<x-dashboard-layout :roles="$roles" :activeRole="$activeRole">
    <div class="py-6 space-y-8">
        
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200/60 pb-5">
            <div>
                <h2 class="font-extrabold text-2xl text-slate-800 leading-tight">
                    Catálogo de Producción Científica
                </h2>
                <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider mt-0.5">
                    Acceso abierto a la producción académica de UNIMAR
                </p>
            </div>
        </div>

        <!-- Search and Filters Form -->
        <form id="catalog-search-form" action="{{ route('catalog.index') }}" method="GET">
            <div class="flex flex-col lg:flex-row gap-8">
                
                <!-- Left Column: Sidebar Filters Card -->
                <aside class="w-full lg:w-1/4 space-y-6 shrink-0">
                    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-[0_10px_30px_rgba(13,77,152,0.02)] space-y-5">
                        <h3 class="font-bold text-sm text-slate-800 border-b border-slate-100 pb-3 uppercase tracking-wider">
                            Filtros de Búsqueda
                        </h3>

                        <!-- Academic Program Filter -->
                        <div class="space-y-1.5">
                            <label for="program-filter" class="block text-[10px] font-bold text-slate-450 uppercase tracking-wider">
                                Programa Académico
                            </label>
                            <select id="program-filter" name="program" onchange="this.form.submit()" class="w-full text-xs rounded-xl border-slate-200 focus:ring-[#0d4d98] focus:border-[#0d4d98] cursor-pointer bg-white text-slate-700">
                                <option value="">Todos los Programas</option>
                                @foreach($programs as $p)
                                    <option value="{{ $p->id }}" {{ request('program') == $p->id ? 'selected' : '' }}>
                                        {{ $p->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Research Line Filter -->
                        <div class="space-y-1.5">
                            <label for="line-filter" class="block text-[10px] font-bold text-slate-450 uppercase tracking-wider">
                                Línea de Investigación
                            </label>
                            <select id="line-filter" name="line" onchange="this.form.submit()" class="w-full text-xs rounded-xl border-slate-200 focus:ring-[#0d4d98] focus:border-[#0d4d98] cursor-pointer bg-white text-slate-700">
                                <option value="">Todas las Líneas</option>
                                @foreach($lines as $l)
                                    <option value="{{ $l->id }}" {{ request('line') == $l->id ? 'selected' : '' }}>
                                        {{ $l->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Production Type Filter -->
                        <div class="space-y-1.5">
                            <label for="type-filter" class="block text-[10px] font-bold text-slate-450 uppercase tracking-wider">
                                Tipo de Producción
                            </label>
                            <select id="type-filter" name="type" onchange="this.form.submit()" class="w-full text-xs rounded-xl border-slate-200 focus:ring-[#0d4d98] focus:border-[#0d4d98] cursor-pointer bg-white text-slate-700">
                                <option value="">Todos los Tipos</option>
                                @foreach($productionTypes as $type)
                                    <option value="{{ $type->id }}" {{ request('type') == $type->id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Tutor Filter -->
                        <div class="space-y-1.5">
                            <label for="tutor-filter" class="block text-[10px] font-bold text-slate-450 uppercase tracking-wider">
                                Tutor Académico
                            </label>
                            <select id="tutor-filter" name="tutor" onchange="this.form.submit()" class="w-full text-xs rounded-xl border-slate-200 focus:ring-[#0d4d98] focus:border-[#0d4d98] cursor-pointer bg-white text-slate-700">
                                <option value="">Todos los Tutores</option>
                                @foreach($tutors as $t)
                                    <option value="{{ $t }}" {{ request('tutor') == $t ? 'selected' : '' }}>
                                        {{ $t }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Year Filter -->
                        <div class="space-y-1.5">
                            <label for="year-filter" class="block text-[10px] font-bold text-slate-450 uppercase tracking-wider">
                                Año de Publicación
                            </label>
                            <select id="year-filter" name="year" onchange="this.form.submit()" class="w-full text-xs rounded-xl border-slate-200 focus:ring-[#0d4d98] focus:border-[#0d4d98] cursor-pointer bg-white text-slate-700">
                                <option value="">Todos los Años</option>
                                @for($y = date('Y'); $y >= 2016; $y--)
                                    <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>
                                        {{ $y }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <!-- Form Actions -->
                        <div class="pt-2 space-y-2">
                            <button type="submit" id="btn-apply-filters" class="w-full py-2.5 bg-[#0d4d98] hover:bg-[#0b3d78] text-white rounded-xl text-xs font-bold uppercase tracking-wider transition shadow-sm hover:shadow-md">
                                Aplicar Filtros
                            </button>
                            @if(request()->anyFilled(['q', 'program', 'line', 'year', 'type', 'tutor']))
                                <a href="{{ route('catalog.index') }}" id="btn-clear-filters" class="w-full py-2.5 bg-slate-50 hover:bg-slate-100 text-slate-700 text-center rounded-xl text-xs font-bold border border-slate-200/60 transition block">
                                    Limpiar Filtros
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Editorial Sidebar Badge -->
                    <div class="p-5 bg-gradient-to-br from-[#0d4d98]/5 to-transparent border border-slate-200/50 rounded-2xl text-center space-y-3">
                        <div class="w-10 h-10 bg-[#0d4d98]/10 text-[#0d4d98] rounded-xl flex items-center justify-center mx-auto">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                        </div>
                        <div class="space-y-1">
                            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Preservación Digital</h4>
                            <p class="text-[10px] text-slate-450 leading-normal">Toda la producción científica de UNIMAR está catalogada bajo el estándar internacional Dublin Core y es compatible con el protocolo de interoperabilidad OAI-PMH.</p>
                        </div>
                    </div>
                </aside>

                <!-- Right Column: Search Results -->
                <section class="w-full lg:w-3/4 space-y-6">
                    
                    <!-- Search Bar Card -->
                    <div x-data="catalogSearch()" class="bg-white border border-slate-200 rounded-2xl p-4 shadow-[0_10px_30px_rgba(13,77,152,0.02)] relative">
                        <div class="relative flex items-center">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-4 pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input type="text" id="search-input" name="q" x-model="query" @blur="closeDropdown()" @focus="if (query.trim().length >= 3) showDropdown = true" value="{{ request('q') }}" placeholder="Buscar por título, resumen, autores o palabras clave..." class="block w-full ps-11 pe-24 py-3.5 text-xs border-slate-250 rounded-xl bg-slate-50 text-slate-800 focus:ring-[#0d4d98] focus:border-[#0d4d98] placeholder-slate-400">
                            
                            <!-- Spinner de carga -->
                            <div x-show="isLoading" class="absolute end-24 me-2 flex items-center text-slate-400" x-cloak>
                                <svg class="animate-spin h-4 w-4 text-[#0d4d98]" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>

                            <div class="absolute inset-y-1.5 end-1.5 flex items-center">
                                <button type="submit" id="btn-search" class="px-5 py-2.5 bg-[#0d4d98] hover:bg-[#0b3d78] text-white text-[10px] font-extrabold uppercase rounded-lg transition shadow-sm hover:shadow-md">
                                    Buscar
                                </button>
                            </div>
                        </div>

                        <!-- Dropdown de Sugerencias -->
                        <div x-show="showDropdown && results.length > 0" class="absolute left-4 right-4 top-full mt-2 bg-white border border-slate-200 rounded-xl shadow-lg z-50 overflow-hidden divide-y divide-slate-100 max-h-60 overflow-y-auto" x-cloak>
                            <template x-for="item in results" :key="item.id">
                                <a :href="'/productions/' + item.id" class="block p-3 hover:bg-slate-50 transition text-left">
                                    <span class="block text-xs font-semibold text-slate-800" x-text="item.title"></span>
                                    <span class="block text-[9px] text-[#0d4d98] font-bold uppercase tracking-wider mt-1" x-text="item.production_type ? item.production_type.name : 'Trabajo Científico'"></span>
                                </a>
                            </template>
                        </div>
                    </div>

                    <!-- Results Statistics -->
                    <div class="flex items-center justify-between text-xs font-bold text-slate-450 uppercase tracking-wider px-1">
                        <div>
                            @if(request('q'))
                                Resultados para "<span class="text-slate-800 normal-case">{{ request('q') }}</span>":
                            @else
                                Obras científicas publicadas:
                            @endif
                            <span class="text-slate-800 font-extrabold ml-1">{{ $productions->total() }}</span>
                        </div>
                    </div>

                    <!-- Results List -->
                    <div class="space-y-4">
                        @forelse($productions as $production)
                            <article class="bg-white border border-slate-200 rounded-2xl p-6 shadow-[0_10px_30px_rgba(13,77,152,0.02)] hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 flex flex-col justify-between">
                                <div>
                                    <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3 border-b border-slate-100 pb-3">
                                        <span class="px-2.5 py-0.5 text-[9px] font-extrabold rounded-full bg-[#0d4d98]/10 text-[#0d4d98] border border-[#0d4d98]/20 uppercase tracking-wider self-start">
                                            {{ $production->productionType->name ?? 'Trabajo Científico' }}
                                        </span>
                                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">
                                            Publicado: {{ $production->published_at ? $production->published_at->format('d/m/Y') : 'N/A' }}
                                        </span>
                                    </header>

                                    <h4 class="text-lg font-bold text-slate-950 hover:text-[#0d4d98] transition mb-3 leading-snug">
                                        <a href="{{ route('productions.show', $production) }}">{{ $production->title }}</a>
                                    </h4>

                                    <p class="text-xs text-slate-600 mb-4 leading-relaxed line-clamp-3 text-justify">
                                        {{ $production->abstract }}
                                    </p>

                                    <!-- Metadata attributes grid -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-[11px] text-slate-500 border-t border-slate-100 pt-4 mb-4">
                                        <div class="flex items-center truncate">
                                            <svg class="w-4 h-4 mr-2 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            <span class="truncate">Autores: <strong class="text-slate-700 font-semibold">{{ $production->authors }}</strong></span>
                                        </div>
                                        <div class="flex items-center truncate">
                                            <svg class="w-4 h-4 mr-2 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                            </svg>
                                            <span class="truncate">Tutor: <strong class="text-slate-700 font-semibold">{{ $production->tutor }}</strong></span>
                                        </div>
                                        <div class="flex items-center truncate">
                                            <svg class="w-4 h-4 mr-2 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                            </svg>
                                            <span class="truncate">Programa: <strong class="text-slate-700 font-semibold">{{ $production->academicProgram->name ?? 'N/A' }}</strong></span>
                                        </div>
                                        <div class="flex items-center truncate">
                                            <svg class="w-4 h-4 mr-2 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 002-2h-2"></path>
                                            </svg>
                                            <span class="truncate">Línea: <strong class="text-slate-700 font-semibold">{{ $production->researchLine->name ?? 'N/A' }}</strong></span>
                                        </div>
                                    </div>

                                    <!-- Keywords Tags -->
                                    @if($production->keywords && $production->keywords->isNotEmpty())
                                        <div class="flex flex-wrap gap-1.5 mb-4">
                                            @foreach($production->keywords as $keyword)
                                                <span class="px-2 py-0.5 bg-slate-100 text-slate-550 text-[10px] rounded-md font-bold font-mono uppercase">
                                                    #{{ $keyword->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <!-- Card Footer Actions -->
                                <div class="flex items-center justify-between border-t border-slate-100 pt-4 mt-2">
                                    <a href="{{ route('productions.show', $production) }}" class="inline-flex items-center text-xs font-bold text-[#0d4d98] hover:underline">
                                        Ver detalles
                                        <svg class="w-3 h-3 ms-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </a>

                                    @if($production->hasMedia('documento'))
                                        <a href="{{ route('productions.document', $production) }}" target="_blank" class="inline-flex items-center px-3.5 py-2 bg-slate-50 hover:bg-[#0d4d98] hover:text-white rounded-xl text-xs font-bold text-slate-700 border border-slate-200/60 shadow-sm transition">
                                            <svg class="w-3.5 h-3.5 mr-1.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                            </svg>
                                            Visualizar PDF
                                        </a>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <!-- Empty State Card -->
                            <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center shadow-[0_10px_30px_rgba(13,77,152,0.03)] space-y-4 max-w-xl mx-auto">
                                <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                    </svg>
                                </div>
                                <div class="space-y-1">
                                    <h5 class="text-base font-bold text-slate-850">
                                        No se encontraron trabajos científicos
                                    </h5>
                                    <p class="text-xs text-slate-400 max-w-md mx-auto leading-relaxed">
                                        Intenta ajustar los criterios de búsqueda o los filtros laterales para encontrar lo que necesitas.
                                    </p>
                                </div>
                            </div>
                        @endforelse
                    </div>

                    <!-- Pagination -->
                    <div class="mt-8">
                        {{ $productions->appends(request()->query())->links() }}
                    </div>
                </section>
            </div>
        </form>
    </div>
</x-dashboard-layout>
