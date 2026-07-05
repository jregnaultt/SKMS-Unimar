@php
    $user = auth()->user();
    $userRoles = $user->roles->pluck('name')->toArray();
    $activeRole = session('active_dashboard_role') ?? ($userRoles[0] ?? 'Estudiante');
@endphp

<x-dashboard-layout :roles="$userRoles" :activeRole="$activeRole">
    <div class="space-y-8 max-w-7xl mx-auto pb-12" x-data="{
        openModal: false,
        productionId: null,
        productionTitle: '',
        actionUrl: '',
        milestones: [],
        initModal(prod) {
            this.productionId = prod.id;
            this.productionTitle = prod.title;
            this.actionUrl = '/productions/' + prod.uuid + '/hitos';
            
            if (prod.milestones && prod.milestones.length > 0) {
                this.milestones = prod.milestones.map(m => ({
                    id: m.id,
                    type: m.type,
                    title: m.title,
                    scheduled_date: m.scheduled_date ? m.scheduled_date.substring(0, 10) : '',
                    status: m.status
                }));
            } else {
                this.loadStandardMilestones();
            }
            this.openModal = true;
        },
        loadStandardMilestones() {
            this.milestones = [
                { type: 'delivery', title: 'Entrega del Primer Borrador', scheduled_date: '', status: 'pending' },
                { type: 'pre_defense', title: 'Pre-Defensa Académica', scheduled_date: '', status: 'pending' },
                { type: 'defense', title: 'Defensa Final de Tesis', scheduled_date: '', status: 'pending' }
            ];
        },
        addMilestone() {
            this.milestones.push({ type: 'delivery', title: 'Nuevo Hito', scheduled_date: '', status: 'pending' });
        },
        removeMilestone(index) {
            this.milestones.splice(index, 1);
        }
    }">
        
        <!-- Encabezado de la Página -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
                <h1 class="text-3xl font-bold text-slate-800 tracking-tight font-sans">Seguimiento de Progreso Científico</h1>
                <p class="text-base text-slate-500 mt-1 font-medium">Monitoreo y asignación de hitos académicos de la cohorte activa, Decanato de Ingeniería y Afines</p>
            </div>
            
            <!-- Periodo Académico Activo Badge -->
            <div class="flex items-center shrink-0">
                <span class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-bold border border-unimar-gold/30 bg-unimar-gold/10 text-slate-700 shadow-sm">
                    <svg class="w-4 h-4 text-unimar-gold mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Período Activo: 2026-I
                </span>
            </div>
        </div>

        <!-- Alertas Flash del Sistema -->
        @if (session('success'))
            <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl shadow-sm text-emerald-800 transition duration-300">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="font-semibold text-sm">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-xl shadow-sm text-rose-800 transition duration-300">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="font-semibold text-sm">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        <!-- Tarjeta de Filtros de Búsqueda -->
        <div class="bg-white border border-slate-200/80 shadow-sm rounded-2xl p-6">
            <form method="GET" action="{{ route('progress.coordinator.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-5 items-end">
                
                <!-- Búsqueda por texto -->
                <div class="md:col-span-2 space-y-1.5">
                    <label for="search" class="block text-sm font-bold text-slate-600 uppercase tracking-wider">Buscar Proyecto o Estudiante</label>
                    <div class="relative">
                        <input type="text" 
                               id="search" 
                               name="search" 
                               value="{{ $filters['search'] ?? '' }}" 
                               placeholder="Ingresa título de tesis o nombre del alumno..." 
                               class="block w-full pl-10 pr-4 py-3 bg-slate-50/50 border border-slate-200/80 rounded-xl text-base focus:border-unimar-blue focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition duration-150 text-slate-700 placeholder-slate-400 font-medium h-11" />
                        <svg aria-hidden="true" class="absolute left-3.5 top-3.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Programa Académico -->
                <div class="space-y-1.5">
                    <label for="academic_program_id" class="block text-sm font-bold text-slate-600 uppercase tracking-wider">Programa Académico</label>
                    <select id="academic_program_id" 
                            name="academic_program_id" 
                            class="block w-full py-3 px-4 bg-slate-50/50 border border-slate-200/80 rounded-xl text-base focus:border-unimar-blue focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition duration-150 text-slate-700 cursor-pointer font-medium h-11">
                        <option value="">Todos los programas</option>
                        @foreach ($programs as $prog)
                            <option value="{{ $prog->id }}" {{ ($filters['academic_program_id'] ?? '') == $prog->id ? 'selected' : '' }}>
                                {{ $prog->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Estado del Workflow -->
                <div class="space-y-1.5">
                    <label for="workflow_state" class="block text-sm font-bold text-slate-600 uppercase tracking-wider">Estado del Workflow</label>
                    <select id="workflow_state" 
                            name="workflow_state" 
                            class="block w-full py-3 px-4 bg-slate-50/50 border border-slate-200/80 rounded-xl text-base focus:border-unimar-blue focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition duration-150 text-slate-700 cursor-pointer font-medium h-11">
                        <option value="">Todos los estados</option>
                        <option value="draft" {{ ($filters['workflow_state'] ?? '') === 'draft' ? 'selected' : '' }}>Borrador</option>
                        <option value="under_review" {{ ($filters['workflow_state'] ?? '') === 'under_review' ? 'selected' : '' }}>En Revisión</option>
                        <option value="needs_corrections" {{ ($filters['workflow_state'] ?? '') === 'needs_corrections' ? 'selected' : '' }}>Requiere Correcciones</option>
                        <option value="approved" {{ ($filters['workflow_state'] ?? '') === 'approved' ? 'selected' : '' }}>Aprobado</option>
                        <option value="published" {{ ($filters['workflow_state'] ?? '') === 'published' ? 'selected' : '' }}>Publicado</option>
                        <option value="rejected" {{ ($filters['workflow_state'] ?? '') === 'rejected' ? 'selected' : '' }}>Rechazado</option>
                    </select>
                </div>

                <!-- Línea de Investigación -->
                <div class="space-y-1.5">
                    <label for="research_line_id" class="block text-sm font-bold text-slate-600 uppercase tracking-wider">Línea de Investigación</label>
                    <select id="research_line_id" 
                            name="research_line_id" 
                            class="block w-full py-3 px-4 bg-slate-50/50 border border-slate-200/80 rounded-xl text-base focus:border-unimar-blue focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition duration-150 text-slate-700 cursor-pointer font-medium h-11">
                        <option value="">Todas las líneas</option>
                        @foreach ($lines as $line)
                            <option value="{{ $line->id }}" {{ ($filters['research_line_id'] ?? '') == $line->id ? 'selected' : '' }}>
                                {{ $line->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Tutor Asignado -->
                <div class="space-y-1.5">
                    <label for="tutor_id" class="block text-sm font-bold text-slate-600 uppercase tracking-wider">Tutor Asignado</label>
                    <select id="tutor_id" 
                            name="tutor_id" 
                            class="block w-full py-3 px-4 bg-slate-50/50 border border-slate-200/80 rounded-xl text-base focus:border-unimar-blue focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition duration-150 text-slate-700 cursor-pointer font-medium h-11">
                        <option value="">Todos los tutores</option>
                        @foreach ($tutors as $tutor)
                            <option value="{{ $tutor->id }}" {{ ($filters['tutor_id'] ?? '') == $tutor->id ? 'selected' : '' }}>
                                {{ $tutor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Botones de Acción -->
                <div class="md:col-span-2 flex space-x-3 pt-2 sm:pt-0">
                    <button type="submit" class="flex-1 py-3 px-6 bg-unimar-blue hover:bg-unimar-blue/95 text-white font-bold rounded-xl text-base transition shadow-sm hover:shadow-md focus:outline-none uppercase tracking-wider h-11 flex items-center justify-center cursor-pointer">
                        Aplicar Filtros
                    </button>
                    <a href="{{ route('progress.coordinator.index') }}" class="py-3 px-6 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-xl text-base border border-slate-200 transition text-center uppercase tracking-wider h-11 flex items-center justify-center cursor-pointer">
                        Limpiar
                    </a>
                </div>
            </form>
        </div>

        <!-- Listado de Estudiantes y Tesis -->
        <div class="bg-white border border-slate-200/80 shadow-sm rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[800px]">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-sm font-bold uppercase tracking-wider text-slate-650">
                            <th class="p-4 pl-6">Estudiante y Título</th>
                            <th class="p-4">Programa / Línea</th>
                            <th class="p-4">Progreso General</th>
                            <th class="p-4 text-center">Estado Workflow</th>
                            <th class="p-4 pr-6 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-base text-slate-700">
                        @if ($productions->isEmpty())
                            <tr>
                                <td colspan="5" class="p-12 text-center text-slate-550 font-semibold">
                                    No se encontraron producciones científicas registradas con los filtros provistos.
                                </td>
                            </tr>
                        @else
                            @foreach ($productions as $prod)
                                @php
                                    $studentUser = $prod->users->where('pivot.role', 'author')->first();
                                    
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

                                    $stateClass = $stateColors[$prod->workflow_state] ?? $stateColors['draft'];
                                    $stateLabel = $stateLabels[$prod->workflow_state] ?? 'Borrador';
                                @endphp
                                <tr class="hover:bg-slate-50/50 transition duration-150">
                                    
                                    <!-- Estudiante y Titulo -->
                                    <td class="p-4 pl-6 space-y-1 max-w-sm">
                                        <span class="block font-bold text-slate-800 leading-tight">
                                            {{ $studentUser ? $studentUser->name : 'Autor no especificado' }}
                                        </span>
                                        <p class="text-sm text-slate-550 truncate font-bold" title="{{ $prod->title }}">
                                            {{ $prod->title }}
                                        </p>
                                    </td>

                                    <!-- Programa / Linea -->
                                    <td class="p-4 space-y-0.5">
                                        <span class="block font-semibold text-slate-700 text-sm">
                                            {{ $prod->academicProgram ? $prod->academicProgram->name : 'N/A' }}
                                        </span>
                                        <span class="block text-sm text-slate-550 font-bold">
                                            {{ $prod->researchLine ? $prod->researchLine->name : 'N/A' }}
                                        </span>
                                    </td>

                                    <!-- Progreso General -->
                                    <td class="p-4 max-w-xs">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden border border-slate-200/50">
                                                @php
                                                    $progressBarColor = 'bg-unimar-blue';
                                                    if ($prod->progress_percentage < 40) {
                                                        $progressBarColor = 'bg-rose-500';
                                                    } elseif ($prod->progress_percentage < 80) {
                                                        $progressBarColor = 'bg-unimar-gold';
                                                    } else {
                                                        $progressBarColor = 'bg-emerald-500';
                                                    }
                                                @endphp
                                                <div class="{{ $progressBarColor }} h-2 rounded-full transition-all duration-300" style="width: {{ $prod->progress_percentage }}%"></div>
                                            </div>
                                            <span class="text-sm font-bold text-slate-800 shrink-0 font-sans">
                                                {{ $prod->progress_percentage }}%
                                            </span>
                                        </div>
                                        <span class="text-sm text-slate-555 font-bold">
                                            {{ $prod->milestones->where('status', 'completed')->count() }} de {{ $prod->milestones->count() }} hitos cumplidos
                                        </span>
                                    </td>

                                    <!-- Estado Workflow -->
                                    <td class="p-4 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-bold border {{ $stateClass }}">
                                            {{ $stateLabel }}
                                        </span>
                                    </td>

                                    <!-- Acciones -->
                                    <td class="p-4 pr-6 text-right space-x-2 shrink-0 whitespace-nowrap">
                                        <!-- Configurar Hitos -->
                                        <button type="button" 
                                                @click="initModal({{ json_encode($prod) }})" 
                                                class="inline-flex items-center px-3.5 py-2.5 border border-slate-200 hover:bg-slate-50 hover:border-slate-300 text-slate-700 rounded-lg text-sm font-bold transition shadow-sm focus:outline-none h-11 cursor-pointer">
                                            Configurar Hitos
                                        </button>
                                        <!-- Ver Progreso -->
                                        <a href="{{ route('progress.student.show', $prod) }}" class="inline-flex items-center px-3.5 py-2.5 bg-unimar-blue hover:bg-unimar-blue/95 text-white rounded-lg text-sm font-bold transition shadow-sm hover:shadow-md h-11 cursor-pointer">
                                            Ver Detalle
                                        </a>
                                    </td>

                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- Enlaces de paginación -->
            @if ($productions->hasPages())
                <div class="bg-slate-50 px-6 py-4 border-t border-slate-200">
                    {{ $productions->links() }}
                </div>
            @endif
        </div>

        <!-- Modal de Configuración de Hitos de Alpine.js -->
        <div x-show="openModal" 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" 
             x-transition.opacity 
             style="display: none;">
            
            <div class="bg-white w-full max-w-2xl rounded-2xl shadow-xl overflow-hidden border border-slate-200" 
                 @click.outside="openModal = false" 
                 x-transition.scale>
                
                <!-- Encabezado del Modal -->
                <div class="px-6 py-5 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800 font-sans">
                            Configurar Hitos Académicos
                        </h3>
                        <p class="text-sm text-slate-550 mt-0.5 truncate max-w-md font-bold uppercase tracking-wider" x-text="productionTitle"></p>
                    </div>
                    <button type="button" @click="openModal = false" aria-label="Cerrar modal" class="p-2 -m-2 text-slate-550 hover:text-slate-700 rounded-xl cursor-pointer">
                        <svg aria-hidden="true" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Formulario de Hitos -->
                <form :action="actionUrl" method="POST">
                    @csrf
                    
                    <div class="p-6 space-y-4 max-h-[55vh] overflow-y-auto pr-2">
                        
                        <!-- Botón Cargar Fechas Estándar -->
                        <button type="button" 
                                @click="loadStandardMilestones()" 
                                class="w-full mb-2 py-3 px-4 bg-unimar-blue/10 hover:bg-unimar-blue/15 text-unimar-blue font-bold rounded-xl text-sm uppercase tracking-wider transition border border-unimar-blue/20 inline-flex items-center justify-center space-x-2 h-11 cursor-pointer">
                            <svg aria-hidden="true" class="w-4 h-4 text-unimar-blue shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                            </svg>
                            <span>Cargar Fechas Estándar (UNIMAR)</span>
                        </button>

                        <!-- Listado dinámico de hitos -->
                        <template x-for="(milestone, index) in milestones" :key="index">
                            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl relative space-y-3 shadow-sm">
                                
                                <!-- Botón Eliminar Hito -->
                                <button type="button" 
                                        @click="removeMilestone(index)" 
                                        aria-label="Eliminar hito"
                                        class="absolute top-2.5 right-2.5 p-2 -m-2 text-rose-500 hover:text-rose-700 rounded-xl cursor-pointer">
                                    <svg aria-hidden="true" class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>

                                <input type="hidden" :name="'milestones['+index+'][id]'" :value="milestone.id || ''">

                                <!-- Fila 1: Título y Tipo -->
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div class="sm:col-span-2 space-y-1">
                                        <label class="block text-sm font-bold text-slate-550 uppercase tracking-wider">Título del Hito</label>
                                        <input type="text" 
                                               :name="'milestones['+index+'][title]'" 
                                               x-model="milestone.title" 
                                               required 
                                               class="block w-full px-3 py-2 h-10 bg-white border border-slate-200 rounded-lg text-sm font-medium text-slate-700 focus:border-unimar-blue focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition duration-150" />
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-sm font-bold text-slate-555 uppercase tracking-wider">Tipo</label>
                                        <select :name="'milestones['+index+'][type]'" 
                                                x-model="milestone.type" 
                                                class="block w-full py-2 px-3 h-10 bg-white border border-slate-200 rounded-lg text-sm font-medium text-slate-700 focus:border-unimar-blue focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition duration-150 cursor-pointer">
                                            <option value="delivery">Entrega</option>
                                            <option value="pre_defense">Pre-Defensa</option>
                                            <option value="defense">Defensa</option>
                                            <option value="system_defense">Defensa de Sistema</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Fila 2: Fecha y Estado -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-bold text-slate-555 uppercase tracking-wider">Fecha Límite</label>
                                        <input type="date" 
                                               :name="'milestones['+index+'][scheduled_date]'" 
                                               x-model="milestone.scheduled_date" 
                                               required 
                                               class="block w-full px-3 py-2 h-10 bg-white border border-slate-200 rounded-lg text-sm font-medium text-slate-700 focus:border-unimar-blue focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition duration-150" />
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-sm font-bold text-slate-555 uppercase tracking-wider">Estado</label>
                                        <select :name="'milestones['+index+'][status]'" 
                                                x-model="milestone.status" 
                                                class="block w-full py-2 px-3 h-10 bg-white border border-slate-200 rounded-lg text-sm font-medium text-slate-700 focus:border-unimar-blue focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition duration-150 cursor-pointer">
                                            <option value="pending">Pendiente</option>
                                            <option value="completed">Completado</option>
                                            <option value="missed">Atrasado</option>
                                        </select>
                                    </div>
                                </div>

                            </div>
                        </template>

                        <!-- Botón Añadir Hito Libre -->
                        <button type="button" 
                                @click="addMilestone()" 
                                class="w-full py-3 border-2 border-dashed border-slate-300 hover:border-unimar-blue text-slate-555 hover:text-unimar-blue rounded-xl text-sm font-bold inline-flex items-center justify-center transition focus:outline-none h-11 cursor-pointer">
                            <svg aria-hidden="true" class="w-4.5 h-4.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <span>Añadir Hito Académico Personalizado</span>
                        </button>
                    </div>

                    <!-- Pie del Modal -->
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end space-x-3">
                        <button type="button" 
                                @click="openModal = false" 
                                class="py-3 px-4 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 font-bold rounded-xl text-sm uppercase tracking-wider transition focus:outline-none h-11 flex items-center justify-center cursor-pointer">
                            Cancelar
                        </button>
                        <button type="submit" 
                                class="py-3 px-5 bg-unimar-blue hover:bg-unimar-blue/95 text-white font-bold rounded-xl text-sm uppercase tracking-wider transition shadow-sm hover:shadow-md focus:outline-none h-11 flex items-center justify-center cursor-pointer">
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-dashboard-layout>
