<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center space-x-3">
                <a href="{{ route('productions.show', $production) }}" class="inline-flex items-center text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        {{ __('Seguimiento de Progreso') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate max-w-lg md:max-w-xl">
                        {{ $production->title }}
                    </p>
                </div>
            </div>

            <div class="flex items-center space-x-2 shrink-0">
                @php
                    $stateColors = [
                        'draft' => 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
                        'under_review' => 'bg-blue-50 text-blue-700 border-blue-100 dark:bg-blue-950/20 dark:text-blue-400 dark:border-blue-900/50',
                        'needs_corrections' => 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900/50',
                        'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-900/50',
                        'published' => 'bg-indigo-50 text-indigo-700 border-indigo-100 dark:bg-indigo-950/20 dark:text-indigo-400 dark:border-indigo-900/50',
                        'rejected' => 'bg-rose-50 text-rose-700 border-rose-100 dark:bg-rose-950/20 dark:text-rose-400 dark:border-rose-900/50',
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
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border {{ $stateColor }}">
                    <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-current"></span>
                    {{ $stateLabel }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-4 lg:px-8 space-y-6">
            
            <!-- Grid Metricas Principales -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Tarjeta Porcentaje de Progreso -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Progreso de la Investigación
                        </h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                            Calculado según hitos académicos completados.
                        </p>
                    </div>

                    <div class="my-6 flex items-center justify-center">
                        <div class="relative w-36 h-36 flex items-center justify-center">
                            <!-- SVG Circular Progress Bar -->
                            <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                                <path class="text-gray-200 dark:text-gray-700" stroke-width="3" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                @php
                                    $strokeColor = 'text-blue-600 dark:text-blue-500';
                                    if ($progress_percentage < 40) {
                                        $strokeColor = 'text-rose-500';
                                    } elseif ($progress_percentage < 80) {
                                        $strokeColor = 'text-amber-500';
                                    } else {
                                        $strokeColor = 'text-emerald-500 dark:text-emerald-400';
                                    }
                                @endphp
                                <path class="{{ $strokeColor }} transition-all duration-500 ease-out" stroke-dasharray="{{ $progress_percentage }}, 100" stroke-width="3" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <div class="absolute text-center">
                                <span class="text-3xl font-extrabold text-gray-900 dark:text-white leading-none">
                                    {{ $progress_percentage }}%
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="text-center text-xs text-gray-500 dark:text-gray-400">
                        {{ $milestones->where('status', 'completed')->count() }} de {{ $milestones->count() }} hitos académicos cumplidos
                    </div>
                </div>

                <!-- Tarjeta Hitos y Entregas -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Resumen de Entregas y Defensas
                        </h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                            Estado de los entregables y evaluaciones.
                        </p>
                    </div>

                    <div class="grid grid-cols-3 gap-4 my-auto py-4">
                        <div class="bg-blue-50/50 dark:bg-blue-950/10 rounded-xl p-3 text-center border border-blue-500/10">
                            <span class="block text-2xl font-bold text-blue-600 dark:text-blue-400">
                                {{ $milestones->where('status', 'pending')->count() }}
                            </span>
                            <span class="text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">
                                Pendientes
                            </span>
                        </div>
                        <div class="bg-emerald-50/50 dark:bg-emerald-950/10 rounded-xl p-3 text-center border border-emerald-500/10">
                            <span class="block text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                                {{ $milestones->where('status', 'completed')->count() }}
                            </span>
                            <span class="text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">
                                Cumplidos
                            </span>
                        </div>
                        <div class="bg-rose-50/50 dark:bg-rose-950/10 rounded-xl p-3 text-center border border-rose-500/10">
                            <span class="block text-2xl font-bold text-rose-600 dark:text-rose-400">
                                {{ $milestones->where('status', 'missed')->count() }}
                            </span>
                            <span class="text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">
                                Atrasados
                            </span>
                        </div>
                    </div>

                    @php
                        $nextMilestone = $milestones->where('status', 'pending')->first();
                    @endphp
                    <div class="border-t border-gray-100 dark:border-gray-700 pt-3 flex items-center justify-between text-xs">
                        <span class="text-gray-400">Próxima entrega:</span>
                        <span class="font-semibold text-gray-700 dark:text-gray-300">
                            {{ $nextMilestone ? $nextMilestone->scheduled_date->format('d/m/Y') : 'Ninguna pendiente' }}
                        </span>
                    </div>
                </div>

                <!-- Tarjeta Resumen Observaciones -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Estado de Observaciones
                        </h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                            Retroalimentación realizada por tutores y jurados.
                        </p>
                    </div>

                    <div class="space-y-3 my-4">
                        <!-- Pendientes -->
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center space-x-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                                <span class="text-gray-600 dark:text-gray-400">Pendientes</span>
                            </div>
                            <span class="font-bold text-gray-900 dark:text-white">{{ $comments_summary['pending'] }}</span>
                        </div>
                        <!-- En progreso -->
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center space-x-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                <span class="text-gray-600 dark:text-gray-400">En progreso</span>
                            </div>
                            <span class="font-bold text-gray-900 dark:text-white">{{ $comments_summary['in_progress'] }}</span>
                        </div>
                        <!-- Atendidas -->
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center space-x-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                <span class="text-gray-600 dark:text-gray-400">Atendidas</span>
                            </div>
                            <span class="font-bold text-gray-900 dark:text-white">{{ $comments_summary['addressed'] }}</span>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 dark:border-gray-700 pt-3 text-center">
                        <a href="{{ route('productions.show', $production) }}#feedback-comments" class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-semibold inline-flex items-center">
                            Ver comentarios en visor PDF
                            <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                    </div>
                </div>

            </div>

            <!-- Seccion de Hitos/Timeline y Detalle -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Columna Timeline de Hitos (2 cols) -->
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 space-y-6">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        Hitos Académicos Programados
                    </h3>

                    @if ($milestones->isEmpty())
                        <div class="py-8 text-center text-gray-400">
                            No hay hitos programados para esta producción por el coordinador.
                        </div>
                    @else
                        <!-- Listado de Hitos -->
                        <div class="relative border-l-2 border-gray-200 dark:border-gray-700 ml-4 space-y-6">
                            @foreach ($milestones as $milestone)
                                @php
                                    $iconBg = 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400';
                                    $borderColor = 'border-gray-200 dark:border-gray-700';
                                    $labelText = 'Pendiente';

                                    if ($milestone->status === 'completed') {
                                        $iconBg = 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-400';
                                        $borderColor = 'border-emerald-500';
                                        $labelText = 'Cumplido';
                                    } elseif ($milestone->status === 'missed') {
                                        $iconBg = 'bg-rose-100 text-rose-600 dark:bg-rose-950/30 dark:text-rose-400';
                                        $borderColor = 'border-rose-500';
                                        $labelText = 'Atrasado';
                                    }
                                @endphp
                                <div class="relative pl-6">
                                    <!-- Bullet/Icon -->
                                    <span class="absolute -left-3.5 flex items-center justify-center w-7 h-7 rounded-full border-2 {{ $borderColor }} {{ $iconBg }} shadow-sm">
                                        @if ($milestone->status === 'completed')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                        @elseif ($milestone->status === 'missed')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                        @else
                                            <span class="w-2 h-2 rounded-full bg-current"></span>
                                        @endif
                                    </span>

                                    <div class="bg-gray-50/50 dark:bg-gray-900/30 rounded-xl p-4 border border-gray-100/50 dark:border-gray-700/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div>
                                            <h4 class="font-bold text-gray-900 dark:text-white text-sm">
                                                {{ $milestone->title }}
                                            </h4>
                                            <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">
                                                Tipo: {{ $milestone->type === 'delivery' ? 'Entrega' : ($milestone->type === 'defense' ? 'Defensa' : ($milestone->type === 'pre_defense' ? 'Pre-Defensa' : 'Evaluación')) }}
                                            </span>
                                        </div>
                                        <div class="flex flex-col sm:items-end gap-1">
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                Límite: <span class="font-semibold">{{ $milestone->scheduled_date->format('d/m/Y') }}</span>
                                            </div>
                                            @if ($milestone->completed_date)
                                                <div class="text-[11px] text-emerald-600 dark:text-emerald-400">
                                                    Completado: <span class="font-medium">{{ $milestone->completed_date->format('d/m/Y') }}</span>
                                                </div>
                                            @endif
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $milestone->status === 'completed' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400' : ($milestone->status === 'missed' ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/20 dark:text-rose-400' : 'bg-blue-50 text-blue-700 dark:bg-blue-950/20 dark:text-blue-400') }}">
                                                {{ $labelText }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Columna Timeline Workflow e Historial Versiones (1 col) -->
                <div class="space-y-6">
                    
                    <!-- Historial Versiones -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 space-y-4">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center justify-between">
                            <span>Versiones del PDF</span>
                            <span class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded text-gray-500 dark:text-gray-400">
                                {{ $version_history->count() }} subidas
                            </span>
                        </h3>

                        @if ($version_history->isEmpty())
                            <div class="py-4 text-center text-xs text-gray-400">
                                Ninguna versión registrada.
                            </div>
                        @else
                            <div class="space-y-3 max-h-60 overflow-y-auto pr-1">
                                @foreach ($version_history as $version)
                                    <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-700/50 text-xs">
                                        <div class="flex items-center justify-between mb-1.5">
                                            <span class="font-bold text-gray-900 dark:text-white">
                                                Versión {{ $version->version_number }}
                                            </span>
                                            <span class="text-gray-400 text-[10px]">
                                                {{ $version->created_at->format('d/m/Y h:i A') }}
                                            </span>
                                        </div>
                                        <p class="text-gray-600 dark:text-gray-400 mb-2 italic">
                                            "{{ $version->changelog ?? 'Sin descripción de cambios' }}"
                                        </p>
                                        <div class="flex items-center justify-between pt-1 border-t border-gray-100 dark:border-gray-700">
                                            <span class="text-gray-400 text-[10px]">
                                                Subido por: {{ $version->user->name }}
                                            </span>
                                            <a href="{{ route('versions.document', $version) }}" class="text-blue-600 dark:text-blue-400 hover:underline font-semibold text-[10px]">
                                                Descargar PDF
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Bitacora de Workflow -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 space-y-4">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            Bitácora de Estados
                        </h3>

                        @if ($timeline->isEmpty())
                            <div class="py-4 text-center text-xs text-gray-400">
                                Sin cambios de estado registrados.
                            </div>
                        @else
                            <div class="relative border-l border-gray-200 dark:border-gray-700 ml-2 space-y-4 max-h-80 overflow-y-auto pr-1">
                                @foreach ($timeline as $revision)
                                    <div class="relative pl-4">
                                        <span class="absolute -left-1.5 w-3.5 h-3.5 rounded-full bg-blue-500 border-2 border-white dark:border-gray-800 shadow-sm"></span>
                                        <div class="text-xs">
                                            <div class="flex items-center justify-between">
                                                <span class="font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider text-[10px]">
                                                    {{ $stateLabels[$revision->new_state] ?? $revision->new_state }}
                                                </span>
                                                <span class="text-[10px] text-gray-400">
                                                    {{ $revision->created_at->format('d/m/Y') }}
                                                </span>
                                            </div>
                                            <p class="text-gray-500 dark:text-gray-400 mt-1">
                                                {{ $revision->comment ?? 'Cambio de estado en el workflow.' }}
                                            </p>
                                            <span class="text-[9px] font-semibold text-gray-400 uppercase tracking-wider block mt-1">
                                                Responsable: {{ $revision->user->name }}
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
    </div>
</x-app-layout>
