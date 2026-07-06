<x-dashboard-layout :roles="$roles" :activeRole="$activeRole">
    <div class="space-y-6">
        <!-- Header Principal -->
        <div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-[0_10px_30px_rgba(13,77,152,0.03)] flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Trabajos Asignados</h1>
                <p class="text-xs text-slate-500 font-semibold mt-1 uppercase tracking-wider">
                    {{ $isDirectivo ? 'Supervisión y control global de la carga académica del periodo' : 'Bandeja personal de tutorías y evaluaciones' }}
                </p>
            </div>
            
            @if(!$isDirectivo)
                <!-- Indicadores Rápidos para Docentes -->
                <div class="flex items-center space-x-3 shrink-0">
                    <span class="inline-flex items-center px-3 py-1.5 rounded-xl bg-[#0d4d98]/5 text-[#0d4d98] text-xs font-bold border border-[#0d4d98]/10">
                        {{ $tutorProductions->count() }} Tutorías
                    </span>
                    <span class="inline-flex items-center px-3 py-1.5 rounded-xl bg-amber-50 text-amber-700 text-xs font-bold border border-amber-100">
                        {{ $juryProductions->count() }} Evaluaciones
                    </span>
                </div>
            @endif
        </div>

        @if($isDirectivo)
            <!-- VISTA DIRECTIVA (COORDINADOR / DECANO) -->
            <div x-data="{ activeTab: 'tutores', search: '' }" class="space-y-5">
                <!-- Selectores de Pestañas e Input de Búsqueda -->
                <div class="flex flex-col md:flex-row gap-4 justify-between items-center bg-white border border-slate-100 rounded-2xl p-4 shadow-[0_10px_30px_rgba(13,77,152,0.02)]">
                    <div class="flex space-x-2 w-full md:w-auto">
                        <button @click="activeTab = 'tutores'" 
                                :class="activeTab === 'tutores' ? 'bg-[#0d4d98] text-white' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
                                class="flex-1 md:flex-none px-5 py-2.5 rounded-xl text-xs font-bold transition duration-200 cursor-pointer">
                            Distribución de Tutores
                        </button>
                        <button @click="activeTab = 'jurados'" 
                                :class="activeTab === 'jurados' ? 'bg-[#0d4d98] text-white' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
                                class="flex-1 md:flex-none px-5 py-2.5 rounded-xl text-xs font-bold transition duration-200 cursor-pointer">
                            Distribución de Jurados
                        </button>
                    </div>

                    <!-- Buscador de Profesores -->
                    <div class="relative w-full md:w-80">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </span>
                        <input x-model="search" 
                               type="text" 
                               placeholder="Buscar profesor por nombre..." 
                               class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/20 focus:border-[#0d4d98] transition">
                    </div>
                </div>

                <!-- Contenedor Pestaña Tutores -->
                <div x-show="activeTab === 'tutores'" class="space-y-3" x-transition>
                    @if($tutors->isEmpty())
                        <div class="text-center py-12 bg-white border border-slate-100 rounded-2xl">
                            <p class="text-xs text-slate-500 font-medium">No hay tutores registrados en el sistema.</p>
                        </div>
                    @else
                        @foreach($tutors as $tutor)
                            @php
                                $initials = strtoupper(substr($tutor->name, 0, 2));
                            @endphp
                            <!-- Acordeón de Tutor -->
                            <div x-show="search === '' || '{{ strtolower($tutor->name) }}'.includes(search.toLowerCase())" 
                                 x-data="{ open: false }" 
                                 class="bg-white border border-slate-150 rounded-2xl overflow-hidden shadow-[0_10px_30px_rgba(13,77,152,0.02)] transition duration-200"
                                 :class="open ? 'ring-2 ring-[#0d4d98]/10 border-[#0d4d98]/30' : ''">
                                
                                <!-- Cabecera del Acordeón -->
                                <button @click="open = !open" class="w-full px-5 py-4 flex items-center justify-between hover:bg-slate-50/50 transition text-left cursor-pointer">
                                    <div class="flex items-center space-x-3.5">
                                        <div class="w-10 h-10 rounded-full bg-[#0d4d98]/10 text-[#0d4d98] flex items-center justify-center text-xs font-extrabold border border-[#0d4d98]/5 shrink-0">
                                            {{ $initials }}
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-bold text-slate-800">{{ $tutor->name }}</h4>
                                            <p class="text-[11px] text-slate-400 font-medium mt-0.5">{{ $tutor->email }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-lg {{ $tutor->productions->count() > 0 ? 'bg-[#0d4d98]/5 text-[#0d4d98]' : 'bg-slate-50 text-slate-400' }}">
                                            {{ $tutor->productions->count() }} {{ $tutor->productions->count() === 1 ? 'Trabajo' : 'Trabajos' }}
                                        </span>
                                        <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" 
                                             :class="open ? 'rotate-180 text-[#0d4d98]' : ''" 
                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </div>
                                </button>

                                <!-- Detalle del Acordeón -->
                                <div x-show="open" x-collapse style="display: none;">
                                    <div class="px-5 pb-5 pt-1 border-t border-slate-100">
                                        @if($tutor->productions->isEmpty())
                                            <p class="text-xs text-slate-500 py-3 text-center">Este profesor no tiene trabajos asignados como tutor.</p>
                                        @else
                                            <div class="overflow-x-auto border border-slate-100 rounded-xl mt-3">
                                                <table class="min-w-full divide-y divide-slate-100">
                                                    <thead class="bg-slate-50/50">
                                                        <tr>
                                                            <th class="px-4 py-2.5 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Título de la Obra</th>
                                                            <th class="px-4 py-2.5 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Estudiante</th>
                                                            <th class="px-4 py-2.5 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Estado</th>
                                                            <th class="px-4 py-2.5 text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">Acciones</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100 bg-white">
                                                        @foreach($tutor->productions as $prod)
                                                            @php
                                                                $studentName = $prod->authors ?? 'No registrado';
                                                                foreach ($prod->users as $u) {
                                                                    if ($u->pivot->role === 'author') {
                                                                        $studentName = $u->name;
                                                                        break;
                                                                    }
                                                                }
                                                                $stateLabels = [
                                                                    'draft' => 'Borrador',
                                                                    'under_review' => 'En Revisión',
                                                                    'needs_corrections' => 'Requiere Correcciones',
                                                                    'approved' => 'Aprobado',
                                                                    'published' => 'Publicado',
                                                                    'rejected' => 'Rechazado',
                                                                ];
                                                            @endphp
                                                            <tr class="hover:bg-slate-50/30 transition duration-150">
                                                                <td class="px-4 py-3 text-xs font-bold text-slate-800 max-w-xs truncate" title="{{ $prod->title }}">
                                                                    {{ $prod->title }}
                                                                </td>
                                                                <td class="px-4 py-3 text-xs font-semibold text-slate-650 whitespace-nowrap">
                                                                    {{ $studentName }}
                                                                </td>
                                                                <td class="px-4 py-3 whitespace-nowrap">
                                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border 
                                                                        {{ $prod->workflow_state === 'approved' || $prod->workflow_state === 'published' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : (
                                                                        $prod->workflow_state === 'under_review' || $prod->workflow_state === 'needs_corrections' ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-slate-50 text-slate-600 border-slate-200') }}">
                                                                        {{ $stateLabels[$prod->workflow_state] ?? $prod->workflow_state }}
                                                                    </span>
                                                                </td>
                                                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                                                    <a href="{{ $prod->show_url }}" class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-[#0d4d98] hover:bg-[#0b3d78] text-white rounded-lg text-[11px] font-bold transition shadow-sm">
                                                                        <span>Ver Tesis</span>
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <!-- Contenedor Pestaña Jurados -->
                <div x-show="activeTab === 'jurados'" class="space-y-3" x-transition style="display: none;">
                    @if($jurados->isEmpty())
                        <div class="text-center py-12 bg-white border border-slate-100 rounded-2xl">
                            <p class="text-xs text-slate-500 font-medium">No hay jurados registrados en el sistema.</p>
                        </div>
                    @else
                        @foreach($jurados as $jurado)
                            @php
                                $initials = strtoupper(substr($jurado->name, 0, 2));
                            @endphp
                            <!-- Acordeón de Jurado -->
                            <div x-show="search === '' || '{{ strtolower($jurado->name) }}'.includes(search.toLowerCase())" 
                                 x-data="{ open: false }" 
                                 class="bg-white border border-slate-150 rounded-2xl overflow-hidden shadow-[0_10px_30px_rgba(13,77,152,0.02)] transition duration-200"
                                 :class="open ? 'ring-2 ring-[#0d4d98]/10 border-[#0d4d98]/30' : ''">
                                
                                <!-- Cabecera del Acordeón -->
                                <button @click="open = !open" class="w-full px-5 py-4 flex items-center justify-between hover:bg-slate-50/50 transition text-left cursor-pointer">
                                    <div class="flex items-center space-x-3.5">
                                        <div class="w-10 h-10 rounded-full bg-[#0d4d98]/10 text-[#0d4d98] flex items-center justify-center text-xs font-extrabold border border-[#0d4d98]/5 shrink-0">
                                            {{ $initials }}
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-bold text-slate-800">{{ $jurado->name }}</h4>
                                            <p class="text-[11px] text-slate-400 font-medium mt-0.5">{{ $jurado->email }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-lg {{ $jurado->productions->count() > 0 ? 'bg-amber-50 text-amber-700' : 'bg-slate-50 text-slate-400' }}">
                                            {{ $jurado->productions->count() }} {{ $jurado->productions->count() === 1 ? 'Trabajo' : 'Trabajos' }}
                                        </span>
                                        <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" 
                                             :class="open ? 'rotate-180 text-amber-600' : ''" 
                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </div>
                                </button>

                                <!-- Detalle del Acordeón -->
                                <div x-show="open" x-collapse style="display: none;">
                                    <div class="px-5 pb-5 pt-1 border-t border-slate-100">
                                        @if($jurado->productions->isEmpty())
                                            <p class="text-xs text-slate-500 py-3 text-center">Este profesor no tiene trabajos asignados como jurado.</p>
                                        @else
                                            <div class="overflow-x-auto border border-slate-100 rounded-xl mt-3">
                                                <table class="min-w-full divide-y divide-slate-100">
                                                    <thead class="bg-slate-50/50">
                                                        <tr>
                                                            <th class="px-4 py-2.5 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Título de la Obra</th>
                                                            <th class="px-4 py-2.5 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Estudiante</th>
                                                            <th class="px-4 py-2.5 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Estado</th>
                                                            <th class="px-4 py-2.5 text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">Acciones</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100 bg-white">
                                                        @foreach($jurado->productions as $prod)
                                                            @php
                                                                $studentName = $prod->authors ?? 'No registrado';
                                                                foreach ($prod->users as $u) {
                                                                    if ($u->pivot->role === 'author') {
                                                                        $studentName = $u->name;
                                                                        break;
                                                                    }
                                                                }
                                                                $stateLabels = [
                                                                    'draft' => 'Borrador',
                                                                    'under_review' => 'En Revisión',
                                                                    'needs_corrections' => 'Requiere Correcciones',
                                                                    'approved' => 'Aprobado',
                                                                    'published' => 'Publicado',
                                                                    'rejected' => 'Rechazado',
                                                                ];
                                                            @endphp
                                                            <tr class="hover:bg-slate-50/30 transition duration-150">
                                                                <td class="px-4 py-3 text-xs font-bold text-slate-800 max-w-xs truncate" title="{{ $prod->title }}">
                                                                    {{ $prod->title }}
                                                                </td>
                                                                <td class="px-4 py-3 text-xs font-semibold text-slate-650 whitespace-nowrap">
                                                                    {{ $studentName }}
                                                                </td>
                                                                <td class="px-4 py-3 whitespace-nowrap">
                                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border 
                                                                        {{ $prod->workflow_state === 'approved' || $prod->workflow_state === 'published' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : (
                                                                        $prod->workflow_state === 'under_review' || $prod->workflow_state === 'needs_corrections' ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-slate-50 text-slate-600 border-slate-200') }}">
                                                                        {{ $stateLabels[$prod->workflow_state] ?? $prod->workflow_state }}
                                                                    </span>
                                                                </td>
                                                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                                                    <a href="{{ $prod->show_url }}" class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-[#0d4d98] hover:bg-[#0b3d78] text-white rounded-lg text-[11px] font-bold transition shadow-sm">
                                                                        <span>Ver Tesis</span>
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

        @else
            <!-- VISTA DOCENTE (TUTOR / JURADO) -->
            <div x-data="{ activeTab: 'tutorias' }" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Contenido Principal: Bandejas (2/3 de ancho) -->
                <div class="lg:col-span-2 space-y-5">
                    <!-- Selector de Pestañas -->
                    <div class="flex space-x-2 bg-white border border-slate-100 rounded-2xl p-2 shadow-[0_10px_30px_rgba(13,77,152,0.02)]">
                        <button @click="activeTab = 'tutorias'" 
                                :class="activeTab === 'tutorias' ? 'bg-[#0d4d98] text-white font-bold shadow-md shadow-[#0d4d98]/10' : 'text-slate-650 hover:bg-slate-50'"
                                class="flex-1 px-4 py-3 rounded-xl text-xs font-bold transition duration-150 cursor-pointer">
                            Mis Tutorías Activas ({{ $tutorProductions->count() }})
                        </button>
                        <button @click="activeTab = 'evaluaciones'" 
                                :class="activeTab === 'evaluaciones' ? 'bg-[#0d4d98] text-white font-bold shadow-md shadow-[#0d4d98]/10' : 'text-slate-650 hover:bg-slate-50'"
                                class="flex-1 px-4 py-3 rounded-xl text-xs font-bold transition duration-150 cursor-pointer">
                            Mis Evaluaciones como Jurado ({{ $juryProductions->count() }})
                        </button>
                    </div>

                    <!-- Tabla de Tutorías -->
                    <div x-show="activeTab === 'tutorias'" class="bg-white border border-slate-100 rounded-2xl p-5 md:p-6 shadow-[0_10px_30px_rgba(13,77,152,0.03)]" x-transition>
                        <div class="mb-5">
                            <h3 class="text-base font-extrabold text-slate-800">Trabajos bajo Tutoría</h3>
                            <p class="text-[11px] text-slate-400 font-medium">Lista de tesis y proyectos científicos que estás asesorando actualmente</p>
                        </div>

                        @if ($tutorProductions->isEmpty())
                            <div class="text-center py-12 border-2 border-dashed border-slate-100 rounded-2xl max-w-md mx-auto my-6 p-6 bg-slate-50/20">
                                <p class="text-xs text-slate-505 font-bold text-slate-600">No tienes tutorías asignadas</p>
                                <p class="mt-1 text-[11px] text-slate-450 text-slate-500">Actualmente no figuras como tutor en ningún trabajo científico activo.</p>
                            </div>
                        @else
                            <div class="overflow-x-auto border border-slate-100 rounded-xl">
                                <table class="min-w-full divide-y divide-slate-100">
                                    <thead class="bg-slate-50/50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Título de la Obra</th>
                                            <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Estudiante</th>
                                            <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Estado</th>
                                            <th class="px-4 py-3 text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        @foreach($tutorProductions as $prod)
                                            @php
                                                $studentName = $prod->authors ?? 'No registrado';
                                                foreach ($prod->users as $u) {
                                                    if ($u->pivot->role === 'author') {
                                                        $studentName = $u->name;
                                                        break;
                                                    }
                                                }
                                                $stateColors = [
                                                    'draft' => 'bg-slate-50 text-slate-700 border-slate-200',
                                                    'under_review' => 'bg-amber-50 text-amber-800 border-amber-200/60',
                                                    'needs_corrections' => 'bg-orange-50 text-orange-850 border-orange-200/60',
                                                    'approved' => 'bg-emerald-50 text-emerald-800 border-emerald-200/60',
                                                    'published' => 'bg-blue-50 text-blue-800 border-blue-200/60',
                                                    'rejected' => 'bg-rose-50 text-rose-800 border-rose-200/60',
                                                ];
                                                $stateLabels = [
                                                    'draft' => 'Borrador',
                                                    'under_review' => 'En Revisión',
                                                    'needs_corrections' => 'Requiere Correcciones',
                                                    'approved' => 'Aprobado',
                                                    'published' => 'Publicado',
                                                    'rejected' => 'Rechazado',
                                                ];
                                            @endphp
                                            <tr class="hover:bg-slate-50/30 transition duration-150">
                                                <td class="px-4 py-3.5">
                                                    <div class="text-xs font-bold text-slate-800 max-w-xs truncate" title="{{ $prod->title }}">
                                                        {{ $prod->title }}
                                                    </div>
                                                    <div class="text-[10px] text-slate-400 mt-1">
                                                        {{ $prod->academicProgram->name ?? 'Programa' }} • {{ $prod->academicPeriod->name ?? '' }}
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3.5 text-xs font-semibold text-slate-650 whitespace-nowrap">
                                                    {{ $studentName }}
                                                </td>
                                                <td class="px-4 py-3.5 whitespace-nowrap">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $stateColors[$prod->workflow_state] ?? 'bg-slate-50 text-slate-700' }}">
                                                        {{ $stateLabels[$prod->workflow_state] ?? $prod->workflow_state }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                                    <a href="{{ $prod->show_url }}" class="inline-flex items-center space-x-1 px-3 py-1.5 bg-[#0d4d98] hover:bg-[#0b3d78] text-white rounded-lg text-[11px] font-bold shadow-sm transition">
                                                        <span>Evaluar</span>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <!-- Tabla de Evaluaciones -->
                    <div x-show="activeTab === 'evaluaciones'" class="bg-white border border-slate-100 rounded-2xl p-5 md:p-6 shadow-[0_10px_30px_rgba(13,77,152,0.03)]" x-transition style="display: none;">
                        <div class="mb-5">
                            <h3 class="text-base font-extrabold text-slate-800">Trabajos para Evaluación (Jurado)</h3>
                            <p class="text-[11px] text-slate-400 font-medium">Lista de tesis y proyectos científicos asignados para tu veredicto final como jurado</p>
                        </div>

                        @if ($juryProductions->isEmpty())
                            <div class="text-center py-12 border-2 border-dashed border-slate-100 rounded-2xl max-w-md mx-auto my-6 p-6 bg-slate-50/20">
                                <p class="text-xs text-slate-505 font-bold text-slate-600">No tienes evaluaciones asignadas</p>
                                <p class="mt-1 text-[11px] text-slate-450 text-slate-500">Actualmente no figuras como jurado en ningún trabajo científico activo.</p>
                            </div>
                        @else
                            <div class="overflow-x-auto border border-slate-100 rounded-xl">
                                <table class="min-w-full divide-y divide-slate-100">
                                    <thead class="bg-slate-50/50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Título de la Obra</th>
                                            <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Estudiante</th>
                                            <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Estado</th>
                                            <th class="px-4 py-3 text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        @foreach($juryProductions as $prod)
                                            @php
                                                $studentName = $prod->authors ?? 'No registrado';
                                                foreach ($prod->users as $u) {
                                                    if ($u->pivot->role === 'author') {
                                                        $studentName = $u->name;
                                                        break;
                                                    }
                                                }
                                                $stateColors = [
                                                    'draft' => 'bg-slate-50 text-slate-700 border-slate-200',
                                                    'under_review' => 'bg-amber-50 text-amber-800 border-amber-200/60',
                                                    'needs_corrections' => 'bg-orange-50 text-orange-850 border-orange-200/60',
                                                    'approved' => 'bg-emerald-50 text-emerald-800 border-emerald-200/60',
                                                    'published' => 'bg-blue-50 text-blue-800 border-blue-200/60',
                                                    'rejected' => 'bg-rose-50 text-rose-800 border-rose-200/60',
                                                ];
                                                $stateLabels = [
                                                    'draft' => 'Borrador',
                                                    'under_review' => 'En Revisión',
                                                    'needs_corrections' => 'Requiere Correcciones',
                                                    'approved' => 'Aprobado',
                                                    'published' => 'Publicado',
                                                    'rejected' => 'Rechazado',
                                                ];
                                            @endphp
                                            <tr class="hover:bg-slate-50/30 transition duration-150">
                                                <td class="px-4 py-3.5">
                                                    <div class="text-xs font-bold text-slate-800 max-w-xs truncate" title="{{ $prod->title }}">
                                                        {{ $prod->title }}
                                                    </div>
                                                    <div class="text-[10px] text-slate-400 mt-1">
                                                        {{ $prod->academicProgram->name ?? 'Programa' }} • {{ $prod->academicPeriod->name ?? '' }}
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3.5 text-xs font-semibold text-slate-650 whitespace-nowrap">
                                                    {{ $studentName }}
                                                </td>
                                                <td class="px-4 py-3.5 whitespace-nowrap">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $stateColors[$prod->workflow_state] ?? 'bg-slate-50 text-slate-700' }}">
                                                        {{ $stateLabels[$prod->workflow_state] ?? $prod->workflow_state }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                                    <a href="{{ $prod->show_url }}" class="inline-flex items-center space-x-1 px-3 py-1.5 bg-[#0d4d98] hover:bg-[#0b3d78] text-white rounded-lg text-[11px] font-bold shadow-sm transition">
                                                        <span>Evaluar</span>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Calendario de Defensas (1/3 de ancho) -->
                <div class="space-y-4"
                     x-data="{
                         currentDate: new Date(),
                         days: [],
                         defensas: @js($defensas->map(fn($d) => [
                             'title' => $d->title,
                             'date' => $d->scheduled_date->format('Y-m-d'),
                             'time' => $d->scheduled_date->format('h:i A'),
                             'student' => $d->production->authors ?? 'Estudiante',
                             'production_title' => $d->production->title
                         ])),
                         selectedDefenses: [],
                         init() {
                             this.generateCalendar();
                         },
                         generateCalendar() {
                             let year = this.currentDate.getFullYear();
                             let month = this.currentDate.getMonth();
                             
                             let firstDayIndex = new Date(year, month, 1).getDay();
                             let lastDay = new Date(year, month + 1, 0).getDate();
                             
                             let tempDays = [];
                             
                             for (let i = 0; i < firstDayIndex; i++) {
                                 tempDays.push({ day: '', fullDate: '', hasDefense: false, defensesList: [] });
                             }
                             
                             for (let d = 1; d <= lastDay; d++) {
                                 let fullDateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                                 let dayDefenses = this.defensas.filter(def => def.date === fullDateStr);
                                 
                                 tempDays.push({
                                     day: d,
                                     fullDate: fullDateStr,
                                     hasDefense: dayDefenses.length > 0,
                                     defensesList: dayDefenses
                                 });
                             }
                             
                             this.days = tempDays;
                         },
                         prevMonth() {
                             this.currentDate.setMonth(this.currentDate.getMonth() - 1);
                             this.generateCalendar();
                         },
                         nextMonth() {
                             this.currentDate.setMonth(this.currentDate.getMonth() + 1);
                             this.generateCalendar();
                         },
                         getMonthName() {
                             return this.currentDate.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });
                         },
                         selectDay(d) {
                             if (d.hasDefense) {
                                 this.selectedDefenses = d.defensesList;
                             } else {
                                 this.selectedDefenses = [];
                             }
                         }
                     }">
                     
                    <!-- Calendario -->
                    <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-[0_10px_30px_rgba(13,77,152,0.03)] space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div>
                                <h4 class="text-xs font-extrabold text-slate-800">Calendario de Defensas</h4>
                                <p class="text-[10px] text-slate-400 font-semibold tracking-wide">Cronograma de sustentaciones</p>
                            </div>
                            <!-- Navegación -->
                            <div class="flex items-center space-x-1">
                                <button @click="prevMonth()" class="p-1.5 hover:bg-slate-100 border border-slate-200 rounded-lg text-slate-500 hover:text-slate-805 transition cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                </button>
                                <button @click="nextMonth()" class="p-1.5 hover:bg-slate-100 border border-slate-200 rounded-lg text-slate-500 hover:text-slate-805 transition cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="text-center font-bold text-slate-700 capitalize text-xs bg-slate-50 py-1 rounded-lg">
                            <span x-text="getMonthName()"></span>
                        </div>

                        <div class="grid grid-cols-7 gap-1 text-center text-[11px]">
                            <!-- Días Semana -->
                            <template x-for="day in ['D', 'L', 'M', 'M', 'J', 'V', 'S']">
                                <div class="font-extrabold text-slate-400 py-0.5" x-text="day"></div>
                            </template>

                            <!-- Días Mes -->
                            <template x-for="d in days">
                                <div class="relative py-0.5 flex items-center justify-center">
                                    <button @click="selectDay(d)"
                                            :disabled="!d.day"
                                            :class="{
                                                'w-8 h-8 rounded-lg text-[11px] font-bold flex items-center justify-center transition': true,
                                                'hover:bg-slate-100 text-slate-700 cursor-pointer': d.day && !d.hasDefense,
                                                'bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100 cursor-pointer': d.hasDefense,
                                                'text-slate-300 pointer-events-none': !d.day
                                            }"
                                            x-text="d.day"></button>
                                    <template x-if="d.hasDefense">
                                        <span class="absolute bottom-0.5 w-1 h-1 rounded-full bg-amber-500"></span>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Detalle de defensa seleccionada -->
                    <div x-show="selectedDefenses.length > 0" class="bg-white border border-slate-100 rounded-2xl p-4 shadow-[0_10px_30px_rgba(13,77,152,0.03)] space-y-3" x-transition style="display: none;">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                            <span class="text-xs font-extrabold text-slate-800">Detalles de la Defensa</span>
                            <button @click="selectedDefenses = []" class="text-slate-400 hover:text-slate-600 rounded-lg p-1 hover:bg-slate-50 transition cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="space-y-2">
                            <template x-for="def in selectedDefenses">
                                <div class="p-3 bg-slate-50 border border-slate-100 rounded-xl space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <span class="px-2 py-0.5 text-[9px] font-bold rounded bg-amber-50 text-amber-700 border border-amber-200 uppercase" x-text="def.title"></span>
                                        <span class="text-[10px] text-slate-600 font-bold" x-text="def.time"></span>
                                    </div>
                                    <h6 class="text-xs font-bold text-slate-800 leading-normal" x-text="def.production_title"></h6>
                                    <p class="text-[10px] text-slate-500 border-t border-slate-200/55 pt-1.5">
                                        Estudiante: <strong class="text-slate-700 font-bold" x-text="def.student"></strong>
                                    </p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-dashboard-layout>
