<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100 leading-tight">
                {{ __('Catálogo de Producción Científica') }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('Acceso abierto a la producción académica de UNIMAR') }}
            </p>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Form wrapping the search and filters so they are submitted together -->
            <form id="catalog-search-form" action="{{ route('catalog.index') }}" method="GET">
                <div class="flex flex-col lg:flex-row gap-6">
                    
                    <!-- Sidebar: Filters -->
                    <aside class="w-full lg:w-1/4 space-y-6 shrink-0">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6 shadow-sm">
                            <h3 class="font-bold text-base text-gray-900 dark:text-gray-100 border-b border-gray-100 dark:border-gray-700 pb-3 mb-4">
                                {{ __('Filtros de Búsqueda') }}
                            </h3>

                            <!-- Academic Program Filter -->
                            <div class="mb-4">
                                <label for="program-filter" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                                    {{ __('Programa Académico') }}
                                </label>
                                <select id="program-filter" name="program" onchange="this.form.submit()" class="w-full text-sm rounded-lg border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">{{ __('Todos los Programas') }}</option>
                                    @foreach($programs as $p)
                                        <option value="{{ $p->id }}" {{ request('program') == $p->id ? 'selected' : '' }}>
                                            {{ $p->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Research Line Filter -->
                            <div class="mb-4">
                                <label for="line-filter" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                                    {{ __('Línea de Investigación') }}
                                </label>
                                <select id="line-filter" name="line" onchange="this.form.submit()" class="w-full text-sm rounded-lg border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">{{ __('Todas las Líneas') }}</option>
                                    @foreach($lines as $l)
                                        <option value="{{ $l->id }}" {{ request('line') == $l->id ? 'selected' : '' }}>
                                            {{ $l->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Year Filter -->
                            <div class="mb-6">
                                <label for="year-filter" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                                    {{ __('Año de Publicación') }}
                                </label>
                                <select id="year-filter" name="year" onchange="this.form.submit()" class="w-full text-sm rounded-lg border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">{{ __('Todos los Años') }}</option>
                                    @for($y = date('Y'); $y >= 2016; $y--)
                                        <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>
                                            {{ $y }}
                                        </option>
                                    @endfor
                                </select>
                            </div>

                            <!-- Form Actions -->
                            <div class="space-y-2">
                                <button type="submit" id="btn-apply-filters" class="w-full inline-flex items-center justify-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition duration-150 shadow-sm">
                                    {{ __('Aplicar Filtros') }}
                                </button>
                                @if(request()->anyFilled(['q', 'program', 'line', 'year']))
                                    <a href="{{ route('catalog.index') }}" id="btn-clear-filters" class="w-full inline-flex items-center justify-center px-4 py-2 bg-gray-100 dark:bg-gray-700 border border-transparent rounded-lg font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none transition duration-150">
                                        {{ __('Limpiar Filtros') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </aside>

                    <!-- Main Content: Search results -->
                    <section class="w-full lg:w-3/4 space-y-6">
                        
                        <!-- Search Bar -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 shadow-sm">
                            <div class="relative">
                                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none text-gray-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                                <input type="text" id="search-input" name="q" value="{{ request('q') }}" placeholder="{{ __('Buscar por título, resumen, autores o palabras clave...') }}" class="block w-full ps-10 pe-24 py-3 text-sm border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                                <div class="absolute inset-y-1.5 end-1.5 flex items-center">
                                    <button type="submit" id="btn-search" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold uppercase rounded-lg transition duration-150">
                                        {{ __('Buscar') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Results stats -->
                        <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                            <div>
                                @if(request('q'))
                                    {{ __('Resultados para') }} "<span class="font-semibold text-gray-700 dark:text-gray-300">{{ request('q') }}</span>":
                                @else
                                    {{ __('Trabajos científicos publicados:') }}
                                @endif
                                <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $productions->total() }}</span>
                            </div>
                        </div>

                        <!-- Results List -->
                        <div class="space-y-4">
                            @forelse($productions as $production)
                                <article class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/50 rounded-2xl p-6 shadow-sm hover:shadow-md transition duration-200">
                                    <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                                        <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-indigo-50 dark:bg-indigo-950/30 text-indigo-700 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900/50 self-start">
                                            {{ $production->productionType->name ?? __('Trabajo Científico') }}
                                        </span>
                                        <span class="text-xs text-gray-400">
                                            {{ __('Publicado') }}: {{ $production->published_at ? $production->published_at->format('d/m/Y') : 'N/A' }}
                                        </span>
                                    </header>

                                    <h4 class="text-lg font-bold text-gray-900 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400 transition mb-2">
                                        <a href="{{ route('productions.show', $production) }}">{{ $production->title }}</a>
                                    </h4>

                                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-4 line-clamp-3">
                                        {{ $production->abstract }}
                                    </p>

                                    <!-- Meta attributes -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs text-gray-500 dark:text-gray-400 border-t border-gray-50 dark:border-gray-700/50 pt-3 mb-4">
                                        <div class="flex items-center truncate">
                                            <svg class="w-4 h-4 mr-2 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                            <span class="truncate">{{ __('Autores') }}: <strong>{{ $production->authors }}</strong></span>
                                        </div>
                                        <div class="flex items-center truncate">
                                            <svg class="w-4 h-4 mr-2 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                            <span class="truncate">{{ __('Tutor') }}: <strong>{{ $production->tutor }}</strong></span>
                                        </div>
                                        <div class="flex items-center truncate">
                                            <svg class="w-4 h-4 mr-2 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                            <span class="truncate">{{ __('Programa') }}: <strong>{{ $production->academicProgram->name ?? 'N/A' }}</strong></span>
                                        </div>
                                        <div class="flex items-center truncate">
                                            <svg class="w-4 h-4 mr-2 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 002-2h-2"></path></svg>
                                            <span class="truncate">{{ __('Línea') }}: <strong>{{ $production->researchLine->name ?? 'N/A' }}</strong></span>
                                        </div>
                                    </div>

                                    <!-- Keywords -->
                                    @if($production->keywords && $production->keywords->isNotEmpty())
                                        <div class="flex flex-wrap gap-1.5 mb-4">
                                            @foreach($production->keywords as $keyword)
                                                <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-[10px] rounded-md font-medium">
                                                    #{{ $keyword->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif

                                    <!-- Card Actions -->
                                    <div class="flex items-center justify-between border-t border-gray-50 dark:border-gray-700/50 pt-3">
                                        <a href="{{ route('productions.show', $production) }}" class="inline-flex items-center text-xs font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                                            {{ __('Ver detalles') }}
                                            <svg class="w-3 h-3 ms-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                        </a>

                                        @if($production->hasMedia('documento'))
                                            <a href="{{ route('productions.document', $production) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 bg-gray-50 hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-xs font-semibold rounded-lg border border-gray-200 dark:border-gray-600 transition">
                                                <svg class="w-3.5 h-3.5 mr-1.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                                {{ __('Visualizar PDF') }}
                                            </a>
                                        @endif
                                    </div>
                                </article>
                            @empty
                                <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/50 rounded-2xl p-12 text-center shadow-sm">
                                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                    </svg>
                                    <h5 class="text-base font-bold text-gray-900 dark:text-gray-100 mb-1">
                                        {{ __('No se encontraron trabajos científicos') }}
                                    </h5>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                                        {{ __('Intenta ajustar los criterios de búsqueda o los filtros laterales para encontrar lo que necesitas.') }}
                                    </p>
                                </div>
                            @endforelse
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $productions->appends(request()->query())->links() }}
                        </div>
                    </section>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
