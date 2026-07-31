@php
    $activeRole = session('active_dashboard_role', auth()->user()->getRoleNames()->first() ?? 'Estudiante');
    $roles = auth()->user()->getRoleNames()->toArray();
@endphp

<x-dashboard-layout :roles="$roles" :activeRole="$activeRole">
    <div class="py-6 px-4 md:px-8 space-y-6 max-w-7xl mx-auto">
        <!-- Top header bar -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200/60 pb-5">
            <div>
                <h1 class="text-xl md:text-2xl font-extrabold text-slate-800 tracking-tight">Propuestas de Rechazo</h1>
                <p class="text-sm text-slate-500 font-semibold mt-1 uppercase tracking-wide">Resolución de solicitudes de rechazo académico por parte de Tutores o Jurados</p>
            </div>
            <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold uppercase tracking-wider transition">
                Volver al Panel
            </a>
        </div>

        @if ($productions->isEmpty())
            <div class="bg-white border border-slate-150 rounded-2xl p-12 text-center shadow-sm">
                <div class="w-16 h-16 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-emerald-100">
                    <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-850">Sin propuestas pendientes</h3>
                <p class="text-sm text-slate-500 max-w-md mx-auto mt-2 leading-relaxed">
                    ¡Excelente! Actualmente no hay propuestas de rechazo de trabajos académicos enviadas para revisión de la Coordinación.
                </p>
            </div>
        @else
            <div id="rejections-list-container" class="grid grid-cols-1 gap-6">
                @foreach ($productions as $p)
                    @php
                        $rejectProposal = $p->revisions()->where('new_state', 'rejection_proposed')->latest()->first();
                        $proposer = $rejectProposal->user ?? null;
                        $author = $p->users()->wherePivot('role', 'author')->first();
                    @endphp
                    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-[0_10px_30px_rgba(13,77,152,0.02)] flex flex-col md:flex-row gap-6 hover:shadow-md transition duration-200">
                        <div class="flex-1 space-y-4">
                            <!-- Header details -->
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <span class="px-2.5 py-1 font-bold rounded-lg bg-pink-50 text-pink-800 border border-pink-100/60 uppercase">
                                    {{ $p->getStatusLabel() }}
                                </span>
                                <span class="text-slate-400 font-semibold">•</span>
                                <span class="text-slate-550 font-bold uppercase tracking-wider">{{ $p->academicProgram->name ?? 'Sin Programa' }}</span>
                                <span class="text-slate-400 font-semibold">•</span>
                                <span class="text-slate-550 font-medium">{{ $p->academicPeriod->name ?? '' }}</span>
                            </div>

                            <!-- Title -->
                            <div class="space-y-1">
                                <h3 class="text-base md:text-lg font-extrabold text-slate-800 leading-snug">
                                    {{ $p->title }}
                                </h3>
                                <p class="text-sm text-slate-550">
                                    Autor: <strong class="text-slate-700 font-bold">{{ $author->name ?? $p->authors }}</strong>
                                </p>
                            </div>

                            <!-- Rejection details -->
                            <div class="bg-rose-50/50 border border-rose-100/80 p-4 rounded-xl space-y-2">
                                <div class="flex items-center space-x-2 text-xs text-rose-800 font-bold uppercase">
                                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                    <span>Razón, Motivo y Circunstancias del Rechazo</span>
                                </div>
                                <p class="text-sm text-slate-650 italic leading-relaxed">
                                    "{{ $rejectProposal->comment ?? 'Sin comentarios explicativos.' }}"
                                </p>
                                <div class="pt-2 border-t border-rose-100 flex items-center justify-between text-xs text-slate-550 font-semibold">
                                    <span>Propuesto por: <strong class="text-slate-755">{{ $proposer->name ?? 'Evaluador' }}</strong> ({{ $rejectProposal->rol ?? 'Tutor/Jurado' }})</span>
                                    <span class="text-slate-400 font-semibold">{{ $rejectProposal ? $rejectProposal->created_at->diffForHumans() : '' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions block -->
                        <div class="flex flex-col justify-between items-stretch md:items-end w-full md:w-60 border-t md:border-t-0 md:border-l border-slate-150 pt-4 md:pt-0 md:pl-6 shrink-0 gap-4">
                            <div class="text-xs text-slate-400 font-medium self-start md:self-end">
                                ID de referencia: {{ substr($p->uuid, 0, 8) }}
                            </div>
                            
                            <div class="flex flex-col gap-2.5 w-full">
                                <a href="{{ route('productions.show', $p) }}" class="w-full flex items-center justify-center space-x-1.5 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold uppercase tracking-wider transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    <span>Ver Detalles</span>
                                </a>

                                <!-- Confirm Rejection Form -->
                                <form action="{{ route('productions.transition', $p) }}" method="POST" class="w-full" onsubmit="return confirm('¿Estás seguro de que deseas confirmar el rechazo definitivo de esta producción?')">
                                    @csrf
                                    <input type="hidden" name="target_state" value="rejected">
                                    <input type="hidden" name="comment" value="Rechazo confirmado por Coordinación.">
                                    <button type="submit" class="w-full flex items-center justify-center space-x-1.5 px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider transition shadow-sm hover:shadow">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        <span>Confirmar Rechazo</span>
                                    </button>
                                </form>

                                <!-- Dismiss Rejection Form -->
                                <form action="{{ route('productions.transition', $p) }}" method="POST" class="w-full" onsubmit="return confirm('¿Deseas desestimar el rechazo y regresar la tesis a revisión?')">
                                    @csrf
                                    <input type="hidden" name="target_state" value="{{ $p->subject?->code === 'SMI1004341' || $p->subject?->code === 'TRI1106341' ? 'under_tutor_review' : 'under_jury_review' }}">
                                    <input type="hidden" name="comment" value="Propuesta de rechazo desestimada por Coordinación. Regresa a revisión.">
                                    <button type="submit" class="w-full flex items-center justify-center space-x-1.5 px-4 py-2.5 bg-slate-700 hover:bg-slate-800 text-white rounded-xl text-xs font-bold uppercase tracking-wider transition shadow-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                        </svg>
                                        <span>Desestimar y Devolver</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <div class="pt-4">
                {{ $productions->links() }}
            </div>
        @endif
    </div>
</x-dashboard-layout>
