@php
    $user = auth()->user();
    $userRoles = $user->roles->pluck('name')->toArray();
    $activeRole = session('active_dashboard_role') ?? ($userRoles[0] ?? 'Estudiante');
@endphp

<x-dashboard-layout :roles="$userRoles" :activeRole="$activeRole">
    <div class="space-y-6 max-w-8xl mx-auto pb-12">
        
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
                <h1 class="text-3xl font-bold text-slate-800 tracking-tight font-sans">Asignación de Jurados</h1>
                <p class="text-base text-slate-500 mt-1 font-medium">Asignación y gestión de jurados evaluadores para Trabajos de Investigación II (TRI1206441)</p>
            </div>
            
            <!-- Quick search form -->
            <div class="w-full md:w-96 shrink-0">
                <form action="{{ route('admin.juries.index') }}" method="GET" class="flex gap-2">
                    <input type="hidden" name="academic_period_id" value="{{ request('academic_period_id') }}">
                    <input type="hidden" name="status" value="{{ request('status') }}">
                    <div class="relative w-full">
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="Buscar por tesis o estudiante..."
                               class="block w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-base focus:border-unimar-blue focus:ring focus:ring-unimar-blue/10 transition duration-150 text-slate-700 placeholder-slate-400 font-medium" />
                        <button type="submit" class="absolute left-3 top-3.5 text-slate-400 hover:text-unimar-blue transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Feedback Alerts -->
        @if (session('success'))
            <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl shadow-sm text-emerald-800 transition duration-300">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="font-semibold text-base">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        <!-- Filter Row -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-sm flex flex-wrap gap-4 items-center justify-between">
            <form action="{{ route('admin.juries.index') }}" method="GET" class="flex flex-wrap gap-4 items-center w-full md:w-auto">
                <input type="hidden" name="search" value="{{ request('search') }}">
                
                <!-- Academic Period Filter -->
                <div class="flex items-center space-x-2">
                    <label for="academic_period_id" class="text-sm font-bold text-slate-500 uppercase tracking-wider">Período:</label>
                    <select id="academic_period_id" name="academic_period_id" onchange="this.form.submit()" class="h-10 text-sm rounded-xl border-slate-200 focus:ring-unimar-blue focus:border-unimar-blue">
                        <option value="">Todos los períodos</option>
                        @foreach ($periods as $p)
                            <option value="{{ $p->id }}" {{ request('academic_period_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="flex items-center space-x-2">
                    <label for="status" class="text-sm font-bold text-slate-500 uppercase tracking-wider">Estado:</label>
                    <select id="status" name="status" onchange="this.form.submit()" class="h-10 text-sm rounded-xl border-slate-200 focus:ring-unimar-blue focus:border-unimar-blue">
                        <option value="">Todos los estados</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pendientes de Jurado</option>
                        <option value="assigned" {{ request('status') === 'assigned' ? 'selected' : '' }}>Con Jurado Asignado</option>
                    </select>
                </div>

                @if(request('academic_period_id') || request('status') || request('search'))
                    <a href="{{ route('admin.juries.index') }}" class="text-sm text-rose-600 hover:text-rose-800 font-bold uppercase tracking-wider">Limpiar Filtros</a>
                @endif
            </form>
            
            <div class="text-sm text-slate-400 font-semibold uppercase tracking-wider">
                Total: {{ $productions->total() }} Trabajos
            </div>
        </div>

        <!-- Main Content Table Card -->
        <div id="admin-juries-table-card" class="bg-white border border-slate-200/80 shadow-sm rounded-2xl overflow-hidden">
            <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                <h3 class="text-lg font-bold text-slate-800 font-sans">Listado de Trabajos de Investigación II</h3>
                <p class="text-sm text-slate-500 mt-0.5 font-medium">Asigna el jurado evaluador correspondiente para el proceso formal de revisión y pre-defensa</p>
            </div>

            @if ($productions->isEmpty())
                <div class="py-16 text-center text-slate-400">
                    <svg class="mx-auto h-16 w-16 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h4 class="text-lg font-bold text-slate-700">No se encontraron trabajos</h4>
                    <p class="text-base text-slate-500 mt-1">No hay registros de Trabajo de Investigación II que coincidan con la búsqueda.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[900px]">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-sm font-bold uppercase tracking-wider text-slate-500">
                                <th class="px-6 py-4">
                                    <a href="{{ route('admin.juries.index', array_merge(request()->query(), ['sort_by' => 'title', 'sort_direction' => request('sort_direction') === 'asc' && request('sort_by') === 'title' ? 'desc' : 'asc'])) }}" class="flex items-center gap-1 hover:text-slate-800">
                                        Obra / Tesis
                                        @if(request('sort_by') === 'title')
                                            <span class="text-xs">{!! request('sort_direction') === 'asc' ? '&#9650;' : '&#9660;' !!}</span>
                                        @else
                                            <span class="text-slate-300">&#8597;</span>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-6 py-4">
                                    <a href="{{ route('admin.juries.index', array_merge(request()->query(), ['sort_by' => 'authors', 'sort_direction' => request('sort_direction') === 'asc' && request('sort_by') === 'authors' ? 'desc' : 'asc'])) }}" class="flex items-center gap-1 hover:text-slate-800">
                                        Autor(es)
                                        @if(request('sort_by') === 'authors')
                                            <span class="text-xs">{!! request('sort_direction') === 'asc' ? '&#9650;' : '&#9660;' !!}</span>
                                        @else
                                            <span class="text-slate-300">&#8597;</span>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-6 py-4">Tutor Asignado</th>
                                <th class="px-6 py-4">Asignación de Jurado</th>
                                <th class="px-6 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white text-base">
                            @php $currentSubjectId = null; @endphp
                            @foreach ($productions as $prod)
                                @php
                                    $assignedTutorUser = $prod->users()->wherePivot('role', 'tutor')->first();
                                    $assignedJuryUser = $prod->users()->wherePivot('role', 'jury')->first();
                                    $studentName = $prod->authors ?: 'No especificado';
                                @endphp
                                
                                {{-- Group header row if we are grouped by subject --}}
                                @if (request('sort_by', 'subject_id') === 'subject_id' && $prod->subject_id !== $currentSubjectId)
                                    @php $currentSubjectId = $prod->subject_id; @endphp
                                    <tr class="bg-slate-100/80 text-xs font-bold uppercase tracking-wider text-slate-700">
                                        <td colspan="5" class="px-6 py-3 border-y border-slate-200">
                                            Materia: {{ $prod->subject->name ?? 'Sin Asignatura' }} ({{ $prod->subject->code ?? '' }})
                                        </td>
                                    </tr>
                                @endif

                                <tr class="hover:bg-slate-50/40 transition duration-150">
                                    <!-- Title & Subject Info -->
                                    <td class="px-6 py-5 max-w-md min-w-[300px]">
                                        <div class="font-bold text-slate-800 leading-snug line-clamp-2" title="{{ $prod->title }}">
                                            {{ $prod->title }}
                                        </div>
                                        <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                            @if (request('sort_by') !== 'subject_id')
                                                <span class="px-2 py-0.5 text-[10px] font-extrabold rounded-md bg-slate-100 text-slate-600 border border-slate-200 uppercase tracking-wider whitespace-nowrap shrink-0">
                                                    {{ $prod->subject->name ?? 'Sin Asignatura' }}
                                                </span>
                                            @endif
                                            <span class="px-2 py-0.5 text-[11px] font-extrabold rounded-md bg-indigo-50 text-indigo-700 border border-indigo-100/50 uppercase tracking-wider whitespace-nowrap shrink-0">
                                                {{ $prod->academicPeriod->name }}
                                            </span>
                                            <span class="px-2 py-0.5 text-[11px] font-extrabold rounded-md border uppercase tracking-wider whitespace-nowrap shrink-0 {{ $prod->getStatusColorClass() }}">
                                                {{ $prod->getStatusLabel() }}
                                            </span>
                                        </div>
                                    </td>
                                    
                                    <!-- Student Name -->
                                    <td class="px-6 py-5 font-semibold text-slate-700 whitespace-nowrap">
                                        {{ $studentName }}
                                    </td>
 
                                    <!-- Tutor -->
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        @if ($assignedTutorUser)
                                            <div class="font-bold text-slate-800">{{ $assignedTutorUser->name }}</div>
                                            <div class="text-xs text-slate-400 font-semibold mt-0.5">{{ $assignedTutorUser->email }}</div>
                                        @else
                                            <span class="text-slate-400 italic text-sm font-medium">Sin tutor asignado</span>
                                        @endif
                                    </td>
 
                                    <!-- Jury Assignment Dropdown -->
                                    <td class="px-6 py-5 whitespace-nowrap min-w-[420px]">
                                        @php
                                            $assignedJuries = $prod->users()->wherePivot('role', 'jury')->get();
                                            $jury1 = $assignedJuries->get(0);
                                            $jury2 = $assignedJuries->get(1);
                                        @endphp
                                        <form action="{{ route('admin.juries.assign', $prod) }}" method="POST" class="flex flex-col gap-2">
                                            @csrf
                                            <div class="flex items-center gap-3">
                                                <div class="flex flex-col sm:flex-row gap-2">
                                                    <select name="jury_1_id" class="w-40 text-xs rounded-xl border-slate-200 focus:ring-unimar-blue focus:border-unimar-blue">
                                                        <option value="">-- Jurado 1 --</option>
                                                        @foreach ($juries as $j)
                                                            @if (! $assignedTutorUser || $assignedTutorUser->id !== $j->id)
                                                                <option value="{{ $j->id }}" {{ $jury1 && $jury1->id === $j->id ? 'selected' : '' }}>
                                                                    {{ $j->name }}
                                                                </option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                    <select name="jury_2_id" class="w-40 text-xs rounded-xl border-slate-200 focus:ring-unimar-blue focus:border-unimar-blue">
                                                        <option value="">-- Jurado 2 --</option>
                                                        @foreach ($juries as $j)
                                                            @if (! $assignedTutorUser || $assignedTutorUser->id !== $j->id)
                                                                <option value="{{ $j->id }}" {{ $jury2 && $jury2->id === $j->id ? 'selected' : '' }}>
                                                                    {{ $j->name }}
                                                                </option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <button type="submit" class="px-3 py-2 bg-[#0d4d98] hover:bg-[#0b3d78] text-white font-bold text-xs rounded-xl shadow transition duration-150 shrink-0 uppercase tracking-wider cursor-pointer">
                                                    Asignar
                                                </button>
                                            </div>
                                            @if($errors->any() && old('jury_1_id') && request()->route('production') && request()->route('production')->id == $prod->id)
                                                <div class="text-[10px] font-bold text-rose-600">
                                                    {{ $errors->first() }}
                                                </div>
                                            @endif
                                        </form>
                                    </td>
 
                                    <!-- View Details Action -->
                                    <td class="px-6 py-5 text-right whitespace-nowrap">
                                        <a href="{{ $prod->show_url }}" class="inline-flex items-center space-x-1.5 px-4 py-2 bg-slate-100 hover:bg-[#0d4d98] hover:text-white text-slate-700 rounded-xl text-xs font-bold transition duration-150">
                                            <span>Detalles de Obra</span>
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
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/30">
                    {{ $productions->links() }}
                </div>
            @endif
        </div>
    </div>
</x-dashboard-layout>
