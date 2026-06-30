@php
    $user = auth()->user();
    $userRoles = $user->roles->pluck('name')->toArray();
    $activeRole = session('active_dashboard_role') ?? ($userRoles[0] ?? 'Estudiante');
@endphp

<x-dashboard-layout :roles="$userRoles" :activeRole="$activeRole">
    <div class="space-y-8 max-w-7xl mx-auto pb-12">
        
        <!-- Encabezado de la Página -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-5">
            <div class="flex items-center space-x-3">
                <a href="{{ route('productions.show', $production) }}" class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-slate-200 bg-white text-slate-500 hover:text-slate-700 hover:border-slate-300 shadow-sm transition duration-150">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 tracking-tight font-sans">Seguimiento de Progreso</h1>
                    <p class="text-sm text-slate-500 mt-1 font-medium max-w-2xl truncate" title="{{ $production->title }}">
                        {{ $production->title }}
                    </p>
                </div>
            </div>

            <!-- Estatus de Workflow -->
            <div class="flex items-center shrink-0">
                @php
                    $stateColors = [
                        'draft' => 'bg-slate-100 text-slate-700 border-slate-200',
                        'under_review' => 'bg-blue-50 text-unimar-blue border-blue-100',
                        'needs_corrections' => 'bg-amber-50 text-amber-700 border-amber-200',
                        'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'published' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                        'rejected' => 'bg-rose-50 text-rose-700 border-rose-200',
                    ];

                    $stateLabels = [
                        'draft' => 'Borrador',
                        'under_review' => 'En Revisión',
                        'needs_corrections' => 'Requiere Correcciones',
                        'approved' => 'Aprobado',
                        'published' => 'Publicado',
                        'rejected' => 'Rechazado',
                    ];

                    $stateColor = $stateColors[$production->workflow_state] ?? $stateColors['draft'];
                    $stateLabel = $stateLabels[$production->workflow_state] ?? 'Desconocido';
                @endphp
                <span class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold border {{ $stateColor }} shadow-sm">
                    <span class="w-2 h-2 mr-2 rounded-full bg-current animate-pulse"></span>
                    {{ $stateLabel }}
                </span>
            </div>
        </div>

        <!-- Grid Principal de Contenido -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Columna Izquierda (2/3 de ancho) - Progreso e Hitos -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Tarjeta de Porcentaje de Progreso -->
                <div class="bg-white border border-slate-200/80 shadow-sm rounded-2xl p-6 flex flex-col sm:flex-row items-center gap-6">
                    <div class="relative w-36 h-36 flex items-center justify-center shrink-0">
                        <!-- SVG Circular Progress Bar -->
                        <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                            <path class="text-slate-100" stroke-width="3.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            @php
                                $strokeColor = 'text-unimar-blue';
                                if ($progress_percentage < 40) {
                                    $strokeColor = 'text-rose-500';
                                } elseif ($progress_percentage < 80) {
                                    $strokeColor = 'text-unimar-gold';
                                } else {
                                    $strokeColor = 'text-emerald-500';
                                }
                            @endphp
                            <path class="{{ $strokeColor }} transition-all duration-500 ease-out" 
                                  stroke-dasharray="{{ $progress_percentage }}, 100" 
                                  stroke-width="3.5" 
                                  stroke-linecap="round" 
                                  stroke="currentColor" 
                                  fill="none" 
                                  d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        </svg>
                        <div class="absolute text-center">
                            <span class="text-3xl font-extrabold text-slate-800 leading-none font-sans">
                                {{ $progress_percentage }}%
                            </span>
                        </div>
                    </div>
                    
                    <div class="text-center sm:text-left space-y-2">
                        <h3 class="text-lg font-bold text-slate-800">Progreso de la Investigación</h3>
                        <p class="text-xs text-slate-500 font-medium">
                            Este porcentaje se calcula automáticamente según los hitos académicos programados por el Decanato de Ingeniería y Afines que has cumplido satisfactoriamente.
                        </p>
                        <div class="inline-flex items-center px-3 py-1 bg-slate-50 rounded-lg border border-slate-200 text-xs font-bold text-slate-600 mt-1">
                            {{ $milestones->where('status', 'completed')->count() }} de {{ $milestones->count() }} hitos cumplidos
                        </div>
                    </div>
                </div>

                <!-- Tarjeta Hitos Académicos (Stepper Vertical) -->
                <div class="bg-white border border-slate-200/80 shadow-sm rounded-2xl p-6 sm:p-8 space-y-6" x-data="{ activeMilestone: 0 }">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">Línea de Tiempo de Hitos Académicos</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Haz clic sobre cualquier hito para expandir los detalles de entrega, comentarios de revisión e historial</p>
                    </div>

                    @if ($milestones->isEmpty())
                        <div class="py-12 text-center text-slate-400 border border-dashed border-slate-200 rounded-xl">
                            <svg class="w-12 h-12 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span class="text-sm font-medium">No hay hitos académicos programados para esta producción.</span>
                        </div>
                    @else
                        <!-- Stepper Timeline -->
                        <div class="relative border-l-2 border-slate-200 ml-4 space-y-6">
                            @foreach ($milestones as $milestone)
                                @php
                                    $isCompleted = $milestone->status === 'completed';
                                    $isMissed = $milestone->status === 'missed';
                                    
                                    if ($isCompleted) {
                                        $nodeBg = 'bg-emerald-500 text-white';
                                        $nodeBorder = 'border-emerald-500';
                                        $badgeClass = 'bg-emerald-50 text-emerald-800 border-emerald-200';
                                        $badgeLabel = 'Cumplido';
                                    } elseif ($isMissed) {
                                        $nodeBg = 'bg-rose-500 text-white';
                                        $nodeBorder = 'border-rose-500';
                                        $badgeClass = 'bg-rose-50 text-rose-800 border-rose-200';
                                        $badgeLabel = 'Atrasado';
                                    } else {
                                        $nodeBg = 'bg-white text-unimar-blue';
                                        $nodeBorder = 'border-unimar-blue ring-4 ring-unimar-gold/20';
                                        $badgeClass = 'bg-blue-50 text-unimar-blue border-blue-200';
                                        $badgeLabel = 'Pendiente';
                                    }
                                @endphp

                                <div class="relative pl-8 group">
                                    <!-- Icono del Nodo -->
                                    <button type="button" 
                                            @click="activeMilestone = (activeMilestone === {{ $loop->index }} ? null : {{ $loop->index }})"
                                            class="absolute -left-3.5 top-0.5 flex items-center justify-center w-7 h-7 rounded-full border-2 {{ $nodeBorder }} {{ $nodeBg }} shadow-sm transition-all duration-200 focus:outline-none shrink-0 z-10">
                                        @if ($isCompleted)
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        @elseif ($isMissed)
                                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                            </svg>
                                        @else
                                            <span class="w-2 h-2 rounded-full bg-unimar-blue"></span>
                                        @endif
                                    </button>

                                    <!-- Cabecera del Hito -->
                                    <div class="cursor-pointer" @click="activeMilestone = (activeMilestone === {{ $loop->index }} ? null : {{ $loop->index }})">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                            <div>
                                                <h4 class="font-bold text-slate-800 text-sm group-hover:text-unimar-blue transition duration-150">
                                                    {{ $milestone->title }}
                                                </h4>
                                                <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">
                                                    Tipo: {{ $milestone->type === 'delivery' ? 'Entrega' : ($milestone->type === 'defense' ? 'Defensa de Trabajo' : ($milestone->type === 'pre_defense' ? 'Pre-Defensa Académica' : 'Defensa de Sistema')) }}
                                                </span>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border {{ $badgeClass }}">
                                                    {{ $badgeLabel }}
                                                </span>
                                                <!-- Icono indicador de expansión -->
                                                <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="{'rotate-180': activeMilestone === {{ $loop->index }}}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Panel Expandible de Detalles -->
                                    <div x-show="activeMilestone === {{ $loop->index }}"
                                         x-collapse
                                         style="display: none;"
                                         class="mt-3">
                                        <div class="p-4 bg-slate-50 border border-slate-200/80 rounded-xl space-y-3 text-xs">
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                <div>
                                                    <span class="block text-slate-400 font-bold uppercase tracking-wider text-[10px]">Fecha Límite Establecida</span>
                                                    <span class="font-bold text-slate-700 text-sm">{{ $milestone->scheduled_date ? $milestone->scheduled_date->format('d/m/Y') : 'Sin fecha límite' }}</span>
                                                </div>
                                                @if ($milestone->completed_date)
                                                    <div>
                                                        <span class="block text-slate-400 font-bold uppercase tracking-wider text-[10px]">Fecha de Cumplimiento</span>
                                                        <span class="font-bold text-emerald-600 text-sm">{{ $milestone->completed_date->format('d/m/Y') }}</span>
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- Visualización de comentarios de tutoría vinculados al hito si aplica -->
                                            @if (!$isCompleted && $milestone->type === 'delivery' && $production->workflow_state === 'needs_corrections')
                                                <div class="border-t border-slate-200 pt-3 space-y-2">
                                                    <span class="block text-slate-400 font-bold uppercase tracking-wider text-[10px]">Observaciones Pendientes de Corrección</span>
                                                    <p class="text-slate-600 leading-relaxed font-medium">
                                                        Tienes observaciones asignadas en el visor de documentos. Por favor revisa los comentarios del tutor y sube una nueva versión corregida.
                                                    </p>
                                                    <div class="pt-1">
                                                        <a href="{{ route('productions.show', $production) }}" class="inline-flex items-center py-1.5 px-3 bg-unimar-blue hover:bg-unimar-blue/95 text-white font-bold rounded-lg text-[10px] uppercase tracking-wider transition shadow-sm">
                                                            Abrir Visor PDF para Corregir
                                                        </a>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            </div>

            <!-- Columna Derecha (1/3 de ancho) - Versiones y Bitácora -->
            <div class="space-y-8">
                
                <!-- Tarjeta de Versiones del Manuscrito -->
                <div class="bg-white border border-slate-200/80 shadow-sm rounded-2xl p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="text-base font-bold text-slate-800">Versiones del Manuscrito</h3>
                        <span class="text-xs bg-slate-100 px-2.5 py-0.5 rounded-full text-slate-600 font-bold">
                            {{ $version_history->count() }}
                        </span>
                    </div>

                    @if ($version_history->isEmpty())
                        <div class="py-6 text-center text-xs text-slate-400">
                            Ninguna versión registrada en el servidor.
                        </div>
                    @else
                        <div class="space-y-3 max-h-[280px] overflow-y-auto pr-1">
                            @foreach ($version_history as $version)
                                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-slate-800">
                                            Versión {{ $version->version_number }}
                                        </span>
                                        <span class="text-slate-400 text-[10px] font-medium">
                                            {{ $version->created_at->format('d/m/Y h:i A') }}
                                        </span>
                                    </div>
                                    <p class="text-slate-600 italic font-medium leading-relaxed">
                                        "{{ $version->changelog ?? 'Sin descripción de cambios' }}"
                                    </p>
                                    <div class="flex items-center justify-between pt-2 border-t border-slate-200/60">
                                        <span class="text-slate-400 text-[10px] font-medium">
                                            Por: {{ $version->user->name }}
                                        </span>
                                        <a href="{{ route('versions.document', $version) }}" class="text-unimar-blue hover:text-unimar-blue/80 font-bold text-[10px] inline-flex items-center space-x-1 transition">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                            </svg>
                                            <span>Descargar PDF</span>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Tarjeta de Bitácora de Estados -->
                <div class="bg-white border border-slate-200/80 shadow-sm rounded-2xl p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="text-base font-bold text-slate-800">Bitácora de Estados</h3>
                    </div>

                    @if ($timeline->isEmpty())
                        <div class="py-6 text-center text-xs text-slate-400">
                            Sin cambios de estado registrados en el sistema.
                        </div>
                    @else
                        <div class="relative border-l border-slate-200 ml-2 space-y-4 max-h-[340px] overflow-y-auto pr-1">
                            @foreach ($timeline as $revision)
                                <div class="relative pl-5">
                                    <!-- Bullet/Node -->
                                    <span class="absolute -left-[4.5px] top-1 w-2.5 h-2.5 rounded-full bg-unimar-blue border border-white shadow-sm shrink-0"></span>
                                    
                                    <div class="text-xs space-y-1">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-bold text-slate-800 uppercase tracking-wider text-[9px]">
                                                {{ $stateLabels[$revision->new_state] ?? $revision->new_state }}
                                            </span>
                                            <span class="text-[9px] text-slate-400 font-medium">
                                                {{ $revision->created_at->format('d/m/Y') }}
                                            </span>
                                        </div>
                                        <p class="text-slate-500 leading-relaxed font-medium">
                                            {{ $revision->comment ?? 'Cambio de estado en el workflow.' }}
                                        </p>
                                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">
                                            Resp: {{ $revision->user->name }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            </div>

        </div>

    </div>
</x-dashboard-layout>
