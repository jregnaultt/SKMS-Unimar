@php
    $user = auth()->user();
    $userRoles = $user->roles->pluck('name')->toArray();
    $activeRole = session('active_dashboard_role') ?? ($userRoles[0] ?? 'Estudiante');
@endphp

<x-dashboard-layout :roles="$userRoles" :activeRole="$activeRole">
    <div class="space-y-8 max-w-7xl mx-auto pb-12">
        
        <!-- Encabezado de la Página -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
                <h1 class="text-3xl font-bold text-slate-800 tracking-tight font-sans">Mis Hitos Académicos</h1>
                <p class="text-base text-slate-500 mt-1 font-medium">
                    Cronograma general de actividades programadas para tu materia inscrita.
                </p>
            </div>
        </div>

        <!-- Warning / Banner de alerta -->
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 md:p-6 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div class="flex items-start space-x-4">
                <div class="p-3 bg-amber-100 text-amber-800 rounded-xl">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-amber-800">Registro de Trabajo Requerido</h3>
                    <p class="text-sm text-amber-700/90 font-medium mt-1 leading-relaxed">
                        Aún no has registrado tu propuesta o trabajo de investigación en el sistema. Registra tu trabajo para poder asociar estas actividades, realizar entregas formales a tu tutor y habilitar la sincronización con Google Calendar.
                    </p>
                </div>
            </div>
            <a href="{{ route('productions.create') }}" class="w-full md:w-auto px-5 py-3 bg-[#0d4d98] hover:bg-[#0d4d98]/95 text-white font-bold rounded-xl text-base uppercase tracking-wider transition text-center shadow-md shrink-0">
                Registrar Trabajo
            </a>
        </div>

        <!-- Tabla / Cuadro de Hitos Globales -->
        <div class="bg-white border border-slate-200/80 shadow-sm rounded-2xl p-5 md:p-6">
            <div class="mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold text-slate-800">Actividades y Entregas Programadas</h2>
                    <p class="text-sm text-slate-500 font-semibold uppercase tracking-wider mt-0.5">
                        Asignatura: {{ $enrollment?->subject?->name ?? 'N/A' }} | Período: {{ $enrollment?->academicPeriod?->name ?? 'N/A' }}
                    </p>
                </div>
            </div>

            @if ($periodMilestones->isEmpty())
                <div class="text-center py-16 max-w-md mx-auto">
                    <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center mx-auto text-slate-400 mb-4 border border-slate-100">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-700">No hay hitos programados</h3>
                    <p class="text-sm text-slate-500 mt-1 leading-relaxed">
                        No se han encontrado hitos o actividades planificadas de manera global para esta asignatura en el periodo académico activo.
                    </p>
                </div>
            @else
                <div class="overflow-x-auto border border-slate-200/60 rounded-xl">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-6 py-4.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Hito / Actividad</th>
                                <th scope="col" class="px-6 py-4.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Tipo</th>
                                <th scope="col" class="px-6 py-4.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Fecha Límite</th>
                                <th scope="col" class="px-6 py-4.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Notificaciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            @foreach ($periodMilestones as $pm)
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="px-6 py-5.5">
                                        <div class="font-bold text-slate-800 text-base leading-tight">{{ $pm->title }}</div>
                                    </td>
                                    <td class="px-6 py-5.5 whitespace-nowrap">
                                        @php
                                            $types = [
                                                'delivery' => ['bg-blue-50 text-blue-700 border-blue-100', 'Entrega'],
                                                'defense' => ['bg-emerald-50 text-emerald-700 border-emerald-100', 'Defensa'],
                                                'pre_defense' => ['bg-purple-50 text-purple-700 border-purple-100', 'Pre-defensa'],
                                                'system_defense' => ['bg-indigo-50 text-indigo-700 border-indigo-100', 'Defensa de Sistema'],
                                            ];
                                            $typeInfo = $types[$pm->type] ?? ['bg-slate-50 text-slate-700 border-slate-100', $pm->type];
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-sm font-bold border {{ $typeInfo[0] }}">
                                            {{ $typeInfo[1] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-5.5 whitespace-nowrap">
                                        <div class="text-slate-800 font-bold text-base">
                                            {{ $pm->scheduled_date->format('d/m/Y') }}
                                        </div>
                                        <div class="text-sm text-slate-500 font-medium mt-0.5">
                                            {{ $pm->scheduled_date->format('h:i A') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-5.5 whitespace-nowrap">
                                        <div class="flex flex-col space-y-1 text-sm font-semibold text-slate-500">
                                            @if($pm->notify_tutor)
                                                <span class="inline-flex items-center text-slate-600">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-2"></span>
                                                    Avisar al Tutor
                                                </span>
                                            @endif
                                            @if($pm->notify_jury)
                                                <span class="inline-flex items-center text-slate-600">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-2"></span>
                                                    Avisar a Jurados
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-dashboard-layout>
