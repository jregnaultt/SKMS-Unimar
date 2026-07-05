@php
    $metrics = $data['metrics'] ?? ['total' => 0, 'draft' => 0, 'under_review' => 0, 'needs_corrections' => 0, 'approved' => 0, 'published' => 0, 'rejected' => 0];
    $validationQueue = $data['validationQueue'] ?? collect();
    $paginatedProductions = $data['paginatedProductions'] ?? collect();
    $programs = $data['programs'] ?? collect();
    $lines = $data['lines'] ?? collect();
    $tutors = $data['tutors'] ?? collect();
    $filters = $data['filters'] ?? [];
@endphp

<div class="space-y-4">
    <!-- Métricas de Resumen (KPI Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total -->
        <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-[0_10px_30px_rgba(13,77,152,0.03)] flex items-center space-x-4">
            <div class="p-3 bg-slate-100 text-slate-650 rounded-xl">
                <svg aria-hidden="true" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
            </div>
            <div>
                <span class="block text-3xl font-extrabold text-slate-800">{{ $metrics['total'] }}</span>
                <span class="text-sm text-slate-600 font-bold uppercase tracking-wider">Total Obras</span>
            </div>
        </div>

        <!-- En Revisión -->
        <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-[0_10px_30px_rgba(13,77,152,0.03)] flex items-center space-x-4">
            <div class="p-3 bg-amber-50 text-[#F5B800] rounded-xl">
                <svg aria-hidden="true" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <span class="block text-3xl font-extrabold text-slate-800">{{ $metrics['under_review'] }}</span>
                <span class="text-sm text-slate-600 font-bold uppercase tracking-wider">En Revisión</span>
            </div>
        </div>

        <!-- Por Publicar (Dorado UNIMAR Highlight!) -->
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 shadow-md flex items-center space-x-4">
            <div class="p-3 bg-[#F5B800] text-slate-900 rounded-xl">
                <svg aria-hidden="true" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                </svg>
            </div>
            <div>
                <span class="block text-2xl font-extrabold text-[#0d4d98]">{{ $metrics['approved'] }}</span>
                <span class="text-sm text-amber-900 font-bold uppercase tracking-wider">Listos para Publicar</span>
            </div>
        </div>

        <!-- Publicadas -->
        <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-[0_10px_30px_rgba(13,77,152,0.03)] flex items-center space-x-4">
            <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                <svg aria-hidden="true" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
            </div>
            <div>
                <span class="block text-3xl font-extrabold text-slate-800">{{ $metrics['published'] }}</span>
                <span class="text-sm text-slate-600 font-bold uppercase tracking-wider">Publicadas</span>
            </div>
        </div>
    </div>iv>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        
        <!-- Left: Cola de Validación Dublin Core (2/3 width) -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white border border-slate-100 rounded-2xl p-4 md:p-5 shadow-[0_10px_30px_rgba(13,77,152,0.03)]">
                <div class="mb-3">
                    <h3 class="text-base font-extrabold text-slate-800">Cola de Validación Dublin Core</h3>
                    <p class="text-sm text-slate-550 font-semibold mt-0.5 uppercase tracking-wider">Investigaciones aprobadas por tutor/jurado pendientes de indexación y publicación</p>
                </div>

                @if ($validationQueue->isEmpty())
                    <div class="text-center py-10 border-2 border-dashed border-slate-100 rounded-xl">
                        <svg aria-hidden="true" class="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                        </svg>
                        <h4 class="mt-2 text-sm font-bold text-slate-700">Sin investigaciones pendientes de validación</h4>
                        <p class="mt-1 text-sm text-slate-550">Todos los trabajos aprobados han sido publicados oficialmente en el catálogo científico.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 min-w-[700px]">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th class="px-4 py-3 text-left text-sm font-bold text-slate-650 uppercase tracking-wider">Título de la Obra</th>
                                    <th class="px-4 py-3 text-left text-sm font-bold text-slate-650 uppercase tracking-wider">Estudiante / Autor</th>
                                    <th class="px-4 py-3 text-right text-sm font-bold text-slate-650 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-100">
                                @foreach ($validationQueue as $prod)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-4 py-3">
                                            <div class="text-sm font-bold text-slate-800 max-w-xs truncate" title="{{ $prod->title }}">
                                                {{ $prod->title }}
                                            </div>
                                            <div class="text-sm text-slate-550 mt-1">
                                                {{ $prod->academicProgram->name ?? 'Programa' }} • {{ $prod->academicPeriod->name ?? '' }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-650 font-medium">
                                            {{ $prod->authors }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex items-center justify-end space-x-2">
                                                <!-- Link to show page to validate full Dublin Core -->
                                                <a href="{{ route('productions.show', $prod) }}" class="px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-bold transition">
                                                    Validar Metadatos
                                                </a>
                                                <!-- Quick publish action -->
                                                <form action="{{ route('productions.transition', $prod) }}" method="POST" onsubmit="return confirm('¿Aprobar metadatos e indexar publicación de forma definitiva en el catálogo y endpoint OAI-PMH?')">
                                                    @csrf
                                                    <input type="hidden" name="target_state" value="published">
                                                    <button type="submit" class="px-3.5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg font-bold transition shadow-sm">
                                                        Publicar
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- Monitoreo Consolidado de Estudiantes -->
            <div class="bg-white border border-slate-100 rounded-2xl p-4 md:p-5 shadow-[0_10px_30px_rgba(13,77,152,0.03)]">
                <div class="mb-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-800">Monitoreo de Estudiantes</h3>
                        <p class="text-sm text-slate-550 font-semibold mt-0.5 uppercase tracking-wider">Progreso de tesis del periodo académico activo</p>
                    </div>

                    <!-- Search Filter Form -->
                    <form action="{{ route('dashboard') }}" method="GET" class="flex items-center space-x-2 max-w-xs w-full">
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Buscar por título o alumno..." aria-label="Buscar estudiante o tesis" class="w-full text-sm rounded-xl border-slate-200 py-2.5 px-3 focus:ring-[#0d4d98] focus:border-[#0d4d98]">
                        <button type="submit" aria-label="Ejecutar búsqueda" class="p-2.5 bg-[#0d4d98] hover:bg-[#0b3d78] text-white rounded-xl transition">
                            <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </button>
                    </form>
                </div>

                @if ($paginatedProductions->isEmpty())
                    <div class="text-center py-12 border border-slate-100 rounded-xl">
                        <p class="text-sm text-slate-550">No se encontraron estudiantes con los criterios de búsqueda actuales.</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach ($paginatedProductions as $p)
                            @php
                                $statusMap = [
                                    'draft' => 'Borrador',
                                    'under_review' => 'En Revisión',
                                    'needs_corrections' => 'Correcciones',
                                    'approved' => 'Aprobado',
                                    'published' => 'Publicado',
                                    'rejected' => 'Rechazado',
                                ];
                                $colorMap = [
                                    'draft' => 'bg-slate-100 text-slate-650',
                                    'under_review' => 'bg-amber-50 text-amber-800',
                                    'needs_corrections' => 'bg-orange-50 text-orange-800',
                                    'approved' => 'bg-emerald-50 text-emerald-800',
                                    'published' => 'bg-blue-50 text-blue-800',
                                    'rejected' => 'bg-rose-50 text-rose-800',
                                ];
                            @endphp
                            <div class="p-4 border border-slate-100 rounded-xl hover:bg-slate-50/30 transition flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div class="space-y-1.5 flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="px-2 py-0.5 text-sm font-bold rounded-md {{ $colorMap[$p->workflow_state] ?? 'bg-slate-100 text-slate-650' }}">
                                            {{ $statusMap[$p->workflow_state] ?? $p->workflow_state }}
                                        </span>
                                        <span class="text-sm text-slate-550 truncate max-w-xs">{{ $p->academicProgram->name ?? 'Sin programa' }}</span>
                                    </div>
                                    <h5 class="text-sm font-bold text-slate-800 truncate" title="{{ $p->title }}">{{ $p->title }}</h5>
                                    <p class="text-sm text-slate-600 font-semibold">Autor: <strong class="text-slate-700">{{ $p->authors }}</strong></p>
                                </div>
                                
                                <div class="flex items-center space-x-6 shrink-0">
                                    <!-- Progress bar -->
                                    <div class="w-24 space-y-1">
                                        <div class="flex justify-between text-sm font-bold text-slate-600">
                                            <span>Avance</span>
                                            <span>{{ $p->progress_percentage }}%</span>
                                        </div>
                                        <div class="w-full bg-slate-100 rounded-full h-1.5">
                                            <div class="bg-[#0d4d98] h-1.5 rounded-full transition-all duration-300" style="width: {{ $p->progress_percentage }}%"></div>
                                        </div>
                                    </div>

                                    <a href="{{ route('progress.student.show', $p) }}" class="px-3.5 py-2.5 bg-slate-50 border border-slate-200 hover:bg-[#0d4d98] hover:text-white rounded-lg text-sm font-bold transition text-slate-700">
                                        Ver Progreso
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $paginatedProductions->links() }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Right: Filtros y Configuración (1/3 width) -->
        <div class="space-y-4">
            <!-- Panel de Filtros -->
            <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-[0_10px_30px_rgba(13,77,152,0.03)] space-y-4">
                <h4 class="text-base font-bold text-slate-600 uppercase tracking-wider">Filtros de Búsqueda</h4>
                
                <form action="{{ route('dashboard') }}" method="GET" class="space-y-4">
                    <!-- Programa Académico -->
                    <div class="space-y-1">
                        <label for="academic_program_id" class="text-sm font-bold text-slate-600 uppercase tracking-wider">Programa Académico</label>
                        <select id="academic_program_id" name="academic_program_id" class="w-full h-11 text-sm rounded-xl border-slate-200 focus:ring-[#0d4d98] focus:border-[#0d4d98]">
                            <option value="">Todos los programas</option>
                            @foreach ($programs as $prog)
                                <option value="{{ $prog->id }}" {{ ($filters['academic_program_id'] ?? '') == $prog->id ? 'selected' : '' }}>{{ $prog->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Línea de Investigación -->
                    <div class="space-y-1">
                        <label for="research_line_id" class="text-sm font-bold text-slate-600 uppercase tracking-wider">Línea de Investigación</label>
                        <select id="research_line_id" name="research_line_id" class="w-full h-11 text-sm rounded-xl border-slate-200 focus:ring-[#0d4d98] focus:border-[#0d4d98]">
                            <option value="">Todas las líneas</option>
                            @foreach ($lines as $l)
                                <option value="{{ $l->id }}" {{ ($filters['research_line_id'] ?? '') == $l->id ? 'selected' : '' }}>{{ $l->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Estado del Flujo -->
                    <div class="space-y-1">
                        <label for="workflow_state" class="text-sm font-bold text-slate-600 uppercase tracking-wider">Estado del Flujo</label>
                        <select id="workflow_state" name="workflow_state" class="w-full h-11 text-sm rounded-xl border-slate-200 focus:ring-[#0d4d98] focus:border-[#0d4d98]">
                            <option value="">Todos los estados</option>
                            <option value="draft" {{ ($filters['workflow_state'] ?? '') == 'draft' ? 'selected' : '' }}>Borrador</option>
                            <option value="under_review" {{ ($filters['workflow_state'] ?? '') == 'under_review' ? 'selected' : '' }}>En Revisión</option>
                            <option value="needs_corrections" {{ ($filters['workflow_state'] ?? '') == 'needs_corrections' ? 'selected' : '' }}>Requiere Correcciones</option>
                            <option value="approved" {{ ($filters['workflow_state'] ?? '') == 'approved' ? 'selected' : '' }}>Aprobado</option>
                            <option value="published" {{ ($filters['workflow_state'] ?? '') == 'published' ? 'selected' : '' }}>Publicado</option>
                            <option value="rejected" {{ ($filters['workflow_state'] ?? '') == 'rejected' ? 'selected' : '' }}>Rechazado</option>
                        </select>
                    </div>

                    <!-- Tutor Asignado -->
                    <div class="space-y-1">
                        <label for="tutor_id" class="text-sm font-bold text-slate-600 uppercase tracking-wider">Tutor Evaluador</label>
                        <select id="tutor_id" name="tutor_id" class="w-full h-11 text-sm rounded-xl border-slate-200 focus:ring-[#0d4d98] focus:border-[#0d4d98]">
                            <option value="">Todos los tutores</option>
                            @foreach ($tutors as $t)
                                <option value="{{ $t->id }}" {{ ($filters['tutor_id'] ?? '') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex gap-2 pt-2">
                        <a href="{{ route('dashboard') }}" class="w-1/2 h-11 flex items-center justify-center bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-bold transition">
                            Limpiar
                        </a>
                        <button type="submit" class="w-1/2 h-11 flex items-center justify-center bg-[#0d4d98] hover:bg-[#0b3d78] text-white rounded-xl text-sm font-bold transition shadow">
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>

            <!-- Accesos Rápidos de Reportes (Módulo 8) -->
            <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-[0_10px_30px_rgba(13,77,152,0.03)] space-y-4">
                <h4 class="text-base font-bold text-slate-600 uppercase tracking-wider">Reportes y Estadísticas</h4>
                <p class="text-sm text-slate-600 font-semibold leading-normal">
                    Genera reportes de productividad del periodo académico en formatos Excel y PDF.
                </p>
                
                <div class="space-y-2">
                    <a href="{{ route('admin.reports.index') }}" class="w-full flex items-center justify-between p-3.5 bg-slate-50 hover:bg-[#0d4d98] hover:text-white rounded-xl text-sm font-bold text-slate-700 border border-slate-200/40 transition">
                        <span>Ir a Consola de Reportes</span>
                        <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Importación Masiva (Históricos) -->
            <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-[0_10px_30px_rgba(13,77,152,0.03)] space-y-4">
                <h4 class="text-base font-bold text-slate-600 uppercase tracking-wider">Carga Histórica</h4>
                <p class="text-sm text-slate-600 font-semibold leading-normal">
                    Importa múltiples tesis de años anteriores mediante el extractor de metadatos con IA.
                </p>
                
                <div class="space-y-2">
                    <a href="{{ route('admin.productions.import') }}" class="w-full flex items-center justify-between p-3.5 bg-slate-50 hover:bg-[#0d4d98] hover:text-white rounded-xl text-sm font-bold text-slate-700 border border-slate-200/40 transition">
                        <span>Importar Históricos</span>
                        <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
