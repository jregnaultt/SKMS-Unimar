<x-public-layout>
    <x-slot name="head">
        <!-- Google Scholar Metadata -->
        <meta name="citation_title" content="{{ $production->title }}">
        @foreach(explode(',', $production->authors) as $author)
            <meta name="citation_author" content="{{ trim($author) }}">
        @endforeach
        <meta name="citation_publication_date" content="{{ $production->published_at ? $production->published_at->format('Y/m/d') : '' }}">
        <meta name="citation_pdf_url" content="{{ route('catalog.download-public-pdf', $production->uuid) }}">
        <meta name="citation_publisher" content="Decanato de Ingeniería, Universidad de Margarita">
        <meta name="citation_language" content="es">
        @if($production->keywords->isNotEmpty())
            <meta name="citation_keywords" content="{{ $production->keywords->pluck('name')->implode(', ') }}">
        @endif

        <!-- Schema.org Structured Data (JSON-LD) for Google Rich Results -->
        <script type="application/ld+json">
        {
          "@@context": "https://schema.org",
          "@@type": "ScholarlyArticle",
          "mainEntityOfPage": {
            "@@type": "WebPage",
            "@@id": "{{ route('catalog.show-public', $production->uuid) }}"
          },
          "headline": {{ json_encode($production->title) }},
          "description": {{ json_encode($production->abstract) }},
          "datePublished": "{{ $production->published_at ? $production->published_at->toIso8601String() : '' }}",
          "inLanguage": "es",
          "author": [
            @foreach(explode(',', $production->authors) as $index => $author)
              {
                "@@type": "Person",
                "name": "{{ trim($author) }}"
              }{{ $loop->last ? '' : ',' }}
            @endforeach
          ],
          "publisher": {
            "@@type": "Organization",
            "name": "Universidad de Margarita",
            "url": "https://unimar.edu.ve"
          }
        }
        </script>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto space-y-6">
        
        <!-- Navigation Back Link & Workspace redirection -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <a href="{{ route('catalog.index') }}" class="inline-flex items-center text-sm font-bold text-slate-550 hover:text-[#0d4d98] transition">
                <svg aria-hidden="true" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Volver al Catálogo
            </a>

            @auth
                @if(auth()->user()->hasRole(['Coordinador', 'Super Admin', 'Decano']) || $production->users->contains(auth()->id()))
                    <a href="{{ route('productions.show', $production) }}" class="inline-flex items-center px-4 py-2 bg-slate-100 hover:bg-slate-200 text-[#0d4d98] text-xs font-bold uppercase tracking-wider rounded-xl transition border border-[#0d4d98]/20">
                        <svg aria-hidden="true" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                        </svg>
                        Gestionar en Panel de Trabajo
                    </a>
                @endif
            @endauth
        </div>

        <!-- Main Card Details -->
        <article class="bg-white border border-slate-200 rounded-3xl p-6 md:p-10 shadow-sm space-y-8 relative overflow-hidden">
            
            <!-- Category and Date Badge -->
            <header class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 pb-5">
                <span class="px-3.5 py-1 text-xs font-extrabold rounded-full bg-[#0d4d98]/10 text-[#0d4d98] border border-[#0d4d98]/20 uppercase tracking-wider">
                    {{ $production->productionType->name ?? 'Trabajo Científico' }}
                </span>
                <div class="flex items-center text-xs text-slate-400 font-bold uppercase tracking-wider">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Publicado el: {{ $production->published_at ? $production->published_at->format('d/m/Y') : 'N/A' }}
                </div>
            </header>

            <!-- Production Title -->
            <div class="space-y-3">
                <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 leading-snug">
                    {{ $production->title }}
                </h1>
                
                <!-- DOI Identifier if exists -->
                @if($production->doi)
                    <div class="inline-flex items-center text-xs font-bold bg-slate-50 text-slate-600 border border-slate-200 px-3 py-1 rounded-lg">
                        <span class="text-slate-400 mr-1.5">DOI:</span>
                        <a href="https://doi.org/{{ $production->doi }}" target="_blank" class="hover:text-[#0d4d98] hover:underline">{{ $production->doi }}</a>
                    </div>
                @endif
            </div>

            <!-- Authors and Tutor Metadata Grid -->
            <section class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50 border border-slate-200/60 rounded-2xl p-5 md:p-6 text-sm">
                <div class="space-y-1">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Autores</span>
                    <strong class="text-slate-800 text-base font-extrabold leading-normal block">
                        {{ $production->authors }}
                    </strong>
                </div>

                <div class="space-y-1">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Tutor Académico</span>
                    <strong class="text-slate-800 text-base font-extrabold leading-normal block">
                        {{ $production->tutor }}
                    </strong>
                </div>

                <div class="space-y-1 border-t border-slate-200/60 pt-3 md:border-t-0 md:pt-0">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Programa de Grado</span>
                    <strong class="text-slate-700 font-semibold block">
                        {{ $production->academicProgram->name ?? 'N/A' }}
                    </strong>
                </div>

                <div class="space-y-1 border-t border-slate-200/60 pt-3 md:border-t-0 md:pt-0">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Línea de Investigación</span>
                    <strong class="text-slate-700 font-semibold block">
                        {{ $production->researchLine->name ?? 'N/A' }}
                    </strong>
                </div>
            </section>

            <!-- Abstract Section -->
            <section class="space-y-3">
                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-2">
                    Resumen Académico
                </h3>
                <p class="text-sm md:text-base text-slate-650 leading-relaxed text-justify whitespace-pre-line font-medium">
                    {{ $production->abstract }}
                </p>
            </section>

            <!-- Keywords Section -->
            @if($production->keywords->isNotEmpty())
                <section class="space-y-2 border-t border-slate-150 pt-6">
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                        Palabras Clave
                    </h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach($production->keywords as $keyword)
                            <a href="{{ route('catalog.index') }}?q={{ $keyword->name }}" class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-600 border border-slate-200/40 transition">
                                #{{ $keyword->name }}
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            <!-- PDF Download and PDF.js Viewer Block -->
            @if($production->hasMedia('documento'))
                <footer x-data="{ downloading: false }" class="flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-slate-100 pt-8 mt-6">
                    
                    <div class="flex items-center space-x-3 text-slate-500">
                        <div class="p-2.5 bg-rose-50 text-rose-600 rounded-xl">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9v-2h2v2zm0-4H9V7h2v5zm4 4h-2v-5h2v5zm0-7h-2V7h2v2z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider block text-slate-400">Documento Adjunto</span>
                            <span class="text-xs font-semibold text-slate-600 block">PDF Completo indexado por Google Scholar</span>
                        </div>
                    </div>

                    <a href="{{ route('catalog.download-public-pdf', $production->uuid) }}" 
                       @click="downloading = true; setTimeout(() => downloading = false, 4000)"
                       class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3.5 bg-[#0d4d98] hover:bg-[#09356b] text-white text-sm font-bold uppercase tracking-wider rounded-xl transition shadow-md shadow-[#0d4d98]/10 hover:shadow-lg">
                        
                        <!-- Spinner loader for user experience -->
                        <svg x-show="downloading" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" x-cloak>
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        
                        <!-- Download SVG Icon -->
                        <svg x-show="!downloading" class="w-4 h-4 mr-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        
                        <span x-text="downloading ? 'Descargando...' : 'Descargar Tesis (PDF)'"></span>
                    </a>
                </footer>
            @endif
        </article>
    </div>
</x-public-layout>
