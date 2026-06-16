<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Seguimiento de Progreso Científico') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{
        openModal: false,
        productionId: null,
        productionTitle: '',
        actionUrl: '',
        milestones: [],
        initModal(prod) {
            this.productionId = prod.id;
            this.productionTitle = prod.title;
            this.actionUrl = '/productions/' + prod.id + '/hitos';
            
            if (prod.milestones && prod.milestones.length > 0) {
                this.milestones = prod.milestones.map(m => ({
                    id: m.id,
                    type: m.type,
                    title: m.title,
                    scheduled_date: m.scheduled_date ? m.scheduled_date.substring(0, 10) : '',
                    status: m.status
                }));
            } else {
                this.milestones = [
                    { type: 'delivery', title: 'Entrega del Primer Borrador', scheduled_date: '', status: 'pending' },
                    { type: 'pre_defense', title: 'Pre-Defensa Académica', scheduled_date: '', status: 'pending' },
                    { type: 'defense', title: 'Defensa Final de Tesis', scheduled_date: '', status: 'pending' }
                ];
            }
            this.openModal = true;
        },
        addMilestone() {
            this.milestones.push({ type: 'delivery', title: 'Nuevo Hito', scheduled_date: '', status: 'pending' });
        },
        removeMilestone(index) {
            this.milestones.splice(index, 1);
        }
    }">
        <div class="max-w-7xl mx-auto sm:px-4 lg:px-8 space-y-6">

            <!-- Alerts -->
            @if (session('success'))
                <div class="p-4 bg-emerald-50 dark:bg-emerald-950/20 border-l-4 border-emerald-500 rounded-r-lg shadow-sm text-emerald-800 dark:text-emerald-300">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-medium text-sm">{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="p-4 bg-rose-50 dark:bg-rose-950/20 border-l-4 border-rose-500 rounded-r-lg shadow-sm text-rose-800 dark:text-rose-300">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-medium text-sm">{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            <!-- Filtros de Busqueda -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                <form method="GET" action="{{ route('progress.coordinator.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    
                    <!-- Search input -->
                    <div class="md:col-span-2 space-y-1">
                        <label for="search" class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Buscar Proyecto o Estudiante
                        </label>
                        <div class="relative">
                            <input type="text" id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Ingrese título de tesis o nombre del alumno..." class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 dark:bg-gray-900 dark:border-gray-700 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500 dark:text-white" />
                            <svg class="absolute left-3.5 top-3 w-4.5 h-4.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                    </div>

                    <!-- Program filter -->
                    <div class="space-y-1">
                        <label for="academic_program_id" class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Programa Académico
                        </label>
                        <select id="academic_program_id" name="academic_program_id" class="w-full py-2 bg-gray-50 border border-gray-200 dark:bg-gray-900 dark:border-gray-700 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500 dark:text-white">
                            <option value="">Todos los programas</option>
                            @foreach ($programs as $prog)
                                <option value="{{ $prog->id }}" {{ ($filters['academic_program_id'] ?? '') == $prog->id ? 'selected' : '' }}>
                                    {{ $prog->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- State filter -->
                    <div class="space-y-1">
                        <label for="workflow_state" class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Estado del Workflow
                        </label>
                        <select id="workflow_state" name="workflow_state" class="w-full py-2 bg-gray-50 border border-gray-200 dark:bg-gray-900 dark:border-gray-700 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500 dark:text-white">
                            <option value="">Todos los estados</option>
                            <option value="draft" {{ ($filters['workflow_state'] ?? '') === 'draft' ? 'selected' : '' }}>Borrador</option>
                            <option value="under_review" {{ ($filters['workflow_state'] ?? '') === 'under_review' ? 'selected' : '' }}>En Revisión</option>
                            <option value="needs_corrections" {{ ($filters['workflow_state'] ?? '') === 'needs_corrections' ? 'selected' : '' }}>Requiere Correcciones</option>
                            <option value="approved" {{ ($filters['workflow_state'] ?? '') === 'approved' ? 'selected' : '' }}>Aprobado</option>
                            <option value="published" {{ ($filters['workflow_state'] ?? '') === 'published' ? 'selected' : '' }}>Publicado</option>
                            <option value="rejected" {{ ($filters['workflow_state'] ?? '') === 'rejected' ? 'selected' : '' }}>Rechazado</option>
                        </select>
                    </div>

                    <!-- Line filter -->
                    <div class="space-y-1">
                        <label for="research_line_id" class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Línea de Investigación
                        </label>
                        <select id="research_line_id" name="research_line_id" class="w-full py-2 bg-gray-50 border border-gray-200 dark:bg-gray-900 dark:border-gray-700 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500 dark:text-white">
                            <option value="">Todas las líneas</option>
                            @foreach ($lines as $line)
                                <option value="{{ $line->id }}" {{ ($filters['research_line_id'] ?? '') == $line->id ? 'selected' : '' }}>
                                    {{ $line->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Tutor filter -->
                    <div class="space-y-1">
                        <label for="tutor_id" class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Tutor Asignado
                        </label>
                        <select id="tutor_id" name="tutor_id" class="w-full py-2 bg-gray-50 border border-gray-200 dark:bg-gray-900 dark:border-gray-700 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500 dark:text-white">
                            <option value="">Todos los tutores</option>
                            @foreach ($tutors as $tutor)
                                <option value="{{ $tutor->id }}" {{ ($filters['tutor_id'] ?? '') == $tutor->id ? 'selected' : '' }}>
                                    {{ $tutor->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Submit & Clear Buttons -->
                    <div class="md:col-span-2 flex space-x-2">
                        <button type="submit" class="flex-1 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold transition shadow-sm">
                            Aplicar Filtros
                        </button>
                        <a href="{{ route('progress.coordinator.index') }}" class="py-2 px-4 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-xl text-sm font-semibold transition text-center">
                            Limpiar
                        </a>
                    </div>
                </form>
            </div>

            <!-- Listado de Estudiantes y Tesis -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/75 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                <th class="p-4 pl-6">Estudiante y Título</th>
                                <th class="p-4">Programa / Línea</th>
                                <th class="p-4">Progreso General</th>
                                <th class="p-4 text-center">Estado Workflow</th>
                                <th class="p-4 pr-6 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm text-gray-700 dark:text-gray-300">
                            @if ($productions->isEmpty())
                                <tr>
                                    <td colspan="5" class="p-8 text-center text-gray-400 dark:text-gray-500">
                                        No se encontraron producciones científicas registradas con los filtros provistos.
                                    </td>
                                </tr>
                            @else
                                @foreach ($productions as $prod)
                                    @php
                                        $studentUser = $prod->users->where('pivot.role', 'author')->first();
                                        
                                        $stateColors = [
                                            'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
                                            'under_review' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/20 dark:text-blue-400',
                                            'needs_corrections' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400',
                                            'approved' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400',
                                            'published' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/20 dark:text-indigo-400',
                                            'rejected' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/20 dark:text-rose-400',
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
                                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/10 transition">
                                        
                                        <!-- Estudiante y Titulo -->
                                        <td class="p-4 pl-6 space-y-1 max-w-sm">
                                            <span class="block font-bold text-gray-900 dark:text-white leading-tight">
                                                {{ $studentUser ? $studentUser->name : 'Autor no especificado' }}
                                            </span>
                                            <p class="text-xs text-gray-400 dark:text-gray-500 truncate" title="{{ $prod->title }}">
                                                {{ $prod->title }}
                                            </p>
                                        </td>

                                        <!-- Programa / Linea -->
                                        <td class="p-4 space-y-0.5">
                                            <span class="block font-semibold text-gray-800 dark:text-gray-200 text-xs">
                                                {{ $prod->academicProgram ? $prod->academicProgram->name : 'N/A' }}
                                            </span>
                                            <span class="block text-[11px] text-gray-400">
                                                {{ $prod->researchLine ? $prod->researchLine->name : 'N/A' }}
                                            </span>
                                        </td>

                                        <!-- Progreso General -->
                                        <td class="p-4 max-w-xs">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                    @php
                                                        $progressBarColor = 'bg-blue-600 dark:bg-blue-500';
                                                        if ($prod->progress_percentage < 40) {
                                                            $progressBarColor = 'bg-rose-500';
                                                        } elseif ($prod->progress_percentage < 80) {
                                                            $progressBarColor = 'bg-amber-500';
                                                        } else {
                                                            $progressBarColor = 'bg-emerald-500 dark:bg-emerald-400';
                                                        }
                                                    @endphp
                                                    <div class="{{ $progressBarColor }} h-2 rounded-full transition-all duration-300" style="width: {{ $prod->progress_percentage }}%"></div>
                                                </div>
                                                <span class="text-xs font-bold text-gray-900 dark:text-white shrink-0">
                                                    {{ $prod->progress_percentage }}%
                                                </span>
                                            </div>
                                            <span class="text-[10px] text-gray-400">
                                                {{ $prod->milestones->where('status', 'completed')->count() }} de {{ $prod->milestones->count() }} hitos cumplidos
                                            </span>
                                        </td>

                                        <!-- Estado Workflow -->
                                        <td class="p-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $stateClass }}">
                                                {{ $stateLabel }}
                                            </span>
                                        </td>

                                        <!-- Acciones -->
                                        <td class="p-4 pr-6 text-right space-x-2 shrink-0 whitespace-nowrap">
                                            <!-- Configurar Hitos -->
                                            <button type="button" @click="initModal({{ json_encode($prod) }})" class="inline-flex items-center px-3 py-1.5 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900 text-gray-600 dark:text-gray-300 rounded-lg text-xs font-semibold transition shadow-sm">
                                                Configurar Hitos
                                            </button>
                                            <!-- Ver Progreso -->
                                            <a href="{{ route('progress.student.show', $prod) }}" class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold transition shadow-sm">
                                                Ver Detalle
                                            </a>
                                        </td>

                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>

                <!-- Paginated links -->
                @if ($productions->hasPages())
                    <div class="bg-gray-50 dark:bg-gray-900/30 px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                        {{ $productions->links() }}
                    </div>
                @endif
            </div>

        </div>

        <!-- Alpine.js modal for milestones configuration -->
        <div x-show="openModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/55 backdrop-blur-sm" x-transition.opacity style="display: none;">
            <div class="bg-white dark:bg-gray-800 w-full max-w-2xl rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700" @click.outside="openModal = false" x-transition.scale>
                
                <!-- Header -->
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            Configurar Hitos Académicos
                        </h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 truncate max-w-md" x-text="productionTitle"></p>
                    </div>
                    <button type="button" @click="openModal = false" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <!-- Form -->
                <form :action="actionUrl" method="POST">
                    @csrf
                    
                    <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto pr-2">
                        
                        <!-- List of milestones dynamically -->
                        <template x-for="(milestone, index) in milestones" :key="index">
                            <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200/50 dark:border-gray-700/50 relative space-y-3">
                                
                                <!-- Remove Button -->
                                <button type="button" @click="removeMilestone(index)" class="absolute top-2 right-2 text-rose-500 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>

                                <!-- Hidden Inputs -->
                                <input type="hidden" :name="'milestones['+index+'][id]'" :value="milestone.id || ''">

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <!-- Milestone Title -->
                                    <div class="sm:col-span-2 space-y-1">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                                            Título del Hito
                                        </label>
                                        <input type="text" :name="'milestones['+index+'][title]'" x-model="milestone.title" required class="w-full px-3 py-1.5 bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-lg text-xs dark:text-white" />
                                    </div>
                                    <!-- Type -->
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                                            Tipo
                                        </label>
                                        <select :name="'milestones['+index+'][type]'" x-model="milestone.type" class="w-full py-1.5 bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-lg text-xs dark:text-white">
                                            <option value="delivery">Entrega</option>
                                            <option value="pre_defense">Pre-Defensa</option>
                                            <option value="defense">Defensa</option>
                                            <option value="system_defense">Defensa de Sistema</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <!-- Date -->
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                                            Fecha Límite
                                        </label>
                                        <input type="date" :name="'milestones['+index+'][scheduled_date]'" x-model="milestone.scheduled_date" required class="w-full px-3 py-1.5 bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-lg text-xs dark:text-white" />
                                    </div>
                                    <!-- Status -->
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                                            Estado
                                        </label>
                                        <select :name="'milestones['+index+'][status]'" x-model="milestone.status" class="w-full py-1.5 bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-lg text-xs dark:text-white">
                                            <option value="pending">Pendiente</option>
                                            <option value="completed">Completado</option>
                                            <option value="missed">Atrasado</option>
                                        </select>
                                    </div>
                                </div>

                            </div>
                        </template>

                        <!-- Add Button -->
                        <button type="button" @click="addMilestone()" class="w-full py-2 border-2 border-dashed border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 rounded-xl text-xs font-semibold inline-flex items-center justify-center transition">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                            Añadir Hito Académico
                        </button>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/30 border-t border-gray-100 dark:border-gray-700 flex justify-end space-x-2">
                        <button type="button" @click="openModal = false" class="py-2 px-4 bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 rounded-xl text-xs font-bold transition">
                            Cancelar
                        </button>
                        <button type="submit" class="py-2 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-sm">
                            Guardar Cambios
                        </button>
                    </div>
                </form>

            </div>
        </div>

    </div>
</x-app-layout>
