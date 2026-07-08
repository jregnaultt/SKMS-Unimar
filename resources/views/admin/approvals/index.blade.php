@php
    $user = auth()->user();
    $userRoles = $user->roles->pluck('name')->toArray();
    $activeRole = session('active_dashboard_role') ?? ($userRoles[0] ?? 'Estudiante');
@endphp

<x-dashboard-layout :roles="$userRoles" :activeRole="$activeRole">
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Header Section -->
            <div class="md:flex md:items-center md:justify-between mb-8">
                <div class="flex-1 min-w-0">
                    <h2 class="text-2xl font-bold leading-7 text-slate-900 sm:text-3xl sm:truncate">
                        Aprobaciones de Coordinación
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Bandeja de trabajos y tesis recomendados por los tutores que requieren tu visto bueno final.
                    </p>
                </div>
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-sm flex items-center">
                    <svg class="w-5 h-5 mr-2.5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- Filter Card -->
            <div class="bg-white rounded-2xl shadow-[0_10px_30px_rgba(13,77,152,0.03)] border border-slate-200 p-5 mb-8">
                <form method="GET" action="{{ route('admin.approvals.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <!-- Search Query -->
                    <div>
                        <label for="search" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Buscar</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}"
                               placeholder="Título, autor o tutor..."
                               class="w-full text-xs rounded-xl border-slate-200 focus:ring-[#0d4d98] focus:border-[#0d4d98] px-3.5 py-2">
                    </div>

                    <!-- Academic Period -->
                    <div>
                        <label for="academic_period_id" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Periodo Académico</label>
                        <select name="academic_period_id" id="academic_period_id" 
                                class="w-full text-xs rounded-xl border-slate-200 focus:ring-[#0d4d98] focus:border-[#0d4d98] px-3.5 py-2">
                            <option value="">-- Todos los Periodos --</option>
                            @foreach ($periods as $p)
                                <option value="{{ $p->id }}" {{ request('academic_period_id') == $p->id ? 'selected' : '' }}>
                                    {{ $p->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-end space-x-2">
                        <button type="submit" class="flex-1 py-2 bg-[#0d4d98] hover:bg-[#0b3d78] text-white text-xs font-bold uppercase rounded-xl tracking-wider transition shadow cursor-pointer">
                            Filtrar
                        </button>
                        <a href="{{ route('admin.approvals.index') }}" class="py-2 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold uppercase rounded-xl tracking-wider text-center transition cursor-pointer">
                            Limpiar
                        </a>
                    </div>
                </form>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-2xl shadow-[0_10px_30px_rgba(13,77,152,0.03)] border border-slate-200 overflow-hidden">
                @if ($productions->isEmpty())
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-1">Sin Pendientes</h3>
                        <p class="text-xs text-slate-400">
                            No hay trabajos de investigación pendientes por aprobación de coordinación en este momento.
                        </p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="bg-slate-50/50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider">Obra Académica</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider">Estudiante / Autor</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider">Asignación Académica</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-400 uppercase tracking-wider">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach ($productions as $prod)
                                    @php
                                        $assignedTutorUser = $prod->users()->wherePivot('role', 'tutor')->first();
                                        $authorUser = $prod->users()->wherePivot('role', 'author')->first();
                                    @endphp
                                    <tr class="hover:bg-slate-50/30 transition duration-150">
                                        
                                        <!-- Production Details -->
                                        <td class="px-6 py-5 whitespace-nowrap">
                                            <div class="text-sm font-semibold text-slate-800 truncate max-w-md">
                                                {{ $prod->title }}
                                            </div>
                                            <div class="flex items-center space-x-2 mt-1">
                                                <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-150 uppercase">
                                                    {{ $prod->subject?->name ?: 'S/A' }}
                                                </span>
                                                <span class="text-[11px] text-slate-400 font-semibold uppercase">
                                                    Periodo: {{ $prod->academicPeriod?->name ?: 'N/A' }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Student details -->
                                        <td class="px-6 py-5 whitespace-nowrap">
                                            @if ($authorUser)
                                                <div class="text-sm font-semibold text-slate-800">{{ $authorUser->name }}</div>
                                                <div class="text-xs text-slate-400 font-semibold mt-0.5">{{ $authorUser->email }}</div>
                                            @else
                                                <div class="text-sm text-slate-800">{{ $prod->authors }}</div>
                                            @endif
                                        </td>

                                        <!-- Tutor Details -->
                                        <td class="px-6 py-5 whitespace-nowrap">
                                            @if ($assignedTutorUser)
                                                <div class="text-sm font-semibold text-slate-800">Tutor: {{ $assignedTutorUser->name }}</div>
                                                <div class="text-xs text-slate-400 font-semibold mt-0.5">{{ $assignedTutorUser->email }}</div>
                                            @else
                                                <span class="text-slate-400 italic text-sm font-medium">Sin tutor asignado</span>
                                            @endif
                                        </td>

                                        <!-- Action -->
                                        <td class="px-6 py-5 text-right whitespace-nowrap">
                                            <a href="{{ $prod->show_url }}" class="inline-flex items-center space-x-1.5 px-4 py-2 bg-[#0d4d98] hover:bg-[#0b3d78] text-white rounded-xl text-xs font-bold transition duration-150 shadow-sm cursor-pointer uppercase tracking-wider">
                                                <span>Evaluar y Aprobar</span>
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination Footer -->
                    @if ($productions->hasPages())
                        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/30">
                            {{ $productions->links() }}
                        </div>
                    @endif
                @endif
            </div>

        </div>
    </div>
</x-dashboard-layout>
