@php
    $user = auth()->user();
    $userRoles = $user->roles->pluck('name')->toArray();
    $activeRole = session('active_dashboard_role') ?? ($userRoles[0] ?? 'Estudiante');
@endphp

<x-dashboard-layout :roles="$userRoles" :activeRole="$activeRole">
    <div class="space-y-6">
        <!-- Encabezado de la página -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white border border-slate-100 rounded-2xl p-6 shadow-[0_10px_30px_rgba(13,77,152,0.02)]">
            <div class="space-y-1">
                <h2 class="text-xl font-extrabold text-slate-800 leading-tight">Mis Producciones Científicas</h2>
                <p class="text-sm text-slate-500 font-medium">Historial completo y estado de tus trabajos de grado e investigación</p>
            </div>
            <div>
                <a id="btn-upload-new-production" href="{{ route('productions.create') }}" class="inline-flex items-center space-x-2 px-4 py-2.5 bg-[#0d4d98] hover:bg-[#0b3d78] text-white rounded-xl text-sm font-bold shadow transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>Subir Nueva Producción</span>
                </a>
            </div>
        </div>

        <!-- Alertas de Sesión -->
        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-250 text-emerald-800 p-4 rounded-xl text-sm font-semibold flex items-center space-x-2">
                <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Listado de Producciones -->
        @if ($productions->isEmpty())
            <div class="bg-white border border-slate-100 rounded-2xl p-12 text-center max-w-xl mx-auto space-y-4 shadow-[0_10px_30px_rgba(13,77,152,0.02)]">
                <div class="w-16 h-16 bg-slate-50 text-slate-400 rounded-full flex items-center justify-center mx-auto border border-slate-100">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <h4 class="text-base font-extrabold text-slate-850">No tienes producciones científicas registradas</h4>
                <p class="text-sm text-slate-500 leading-relaxed">
                    Comienza subiendo tu primer trabajo de grado o de investigación al sistema para iniciar el proceso de revisión y corrección metodológica.
                </p>
                <a id="btn-upload-new-production" href="{{ route('productions.create') }}" class="inline-flex items-center space-x-2 px-5 py-2.5 bg-[#0d4d98] hover:bg-[#0b3d78] text-white rounded-xl text-sm font-bold shadow transition">
                    <span>Subir Trabajo Científico</span>
                </a>
            </div>
        @else
            <div id="productions-list-container" class="grid grid-cols-1 gap-4">
                @foreach ($productions as $production)
                    @php
                        $colorClass = $production->getStatusColorClass();
                        $label = $production->getStatusLabel();
                    @endphp

                    <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-[0_4px_25px_rgba(13,77,152,0.02)] flex flex-col md:flex-row md:items-center justify-between gap-4 hover:-translate-y-0.5 hover:shadow-md transition-all duration-200">
                        <div class="space-y-2.5 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-[#0d4d98]/10 text-[#0d4d98] uppercase tracking-wider">
                                    {{ $production->productionType->name ?? 'Trabajo' }}
                                </span>
                                <span class="text-slate-300">•</span>
                                <span class="text-xs font-bold text-slate-500 uppercase font-mono">
                                    {{ $production->subject->code ?? '' }} - {{ $production->subject->name ?? 'Sin Asignatura' }}
                                </span>
                                <span class="text-slate-300">•</span>
                                <span class="text-xs text-slate-550 font-bold uppercase tracking-wider">
                                    {{ $production->academicPeriod->name ?? '' }}
                                </span>
                            </div>
                            
                            <h3 class="text-base font-extrabold text-slate-850 hover:text-unimar-blue transition duration-150 leading-snug">
                                <a href="{{ route('productions.show', $production) }}">
                                    {{ $production->title }}
                                </a>
                            </h3>

                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-650 font-medium">
                                <p>Tutor: <strong class="text-slate-750 font-semibold">{{ $production->tutor ?? 'No especificado' }}</strong></p>
                                <p>Línea: <strong class="text-slate-750 font-semibold">{{ $production->researchLine->name ?? 'No especificada' }}</strong></p>
                            </div>
                        </div>

                        <!-- Estado y Botón de Acceso -->
                        <div class="shrink-0 flex items-center md:flex-col md:items-end justify-between md:justify-center gap-3">
                            <span class="px-3.5 py-1.5 inline-flex text-xs leading-5 font-extrabold rounded-full border {{ $colorClass }}">
                                {{ $label }}
                            </span>
                            <a href="{{ route('productions.show', $production) }}" class="inline-flex items-center justify-center px-4 py-2 bg-slate-50 hover:bg-[#0d4d98] hover:text-white text-[#0d4d98] border border-[#0d4d98]/20 rounded-xl text-xs font-bold uppercase transition tracking-wider">
                                <span>Ver Detalles</span>
                                <svg class="w-3.5 h-3.5 ml-1.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-dashboard-layout>
