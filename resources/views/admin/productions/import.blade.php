<x-dashboard-layout :roles="auth()->user()->roles->pluck('name')->toArray()" :activeRole="session('active_dashboard_role') ?? 'Coordinador'">
    <div x-data="bulkImport({
        periods: {{ $academicPeriods->toJson() }},
        programs: {{ $academicPrograms->toJson() }},
        lines: {{ $researchLines->toJson() }},
        types: {{ $productionTypes->toJson() }}
    })" class="space-y-8 max-w-7xl mx-auto pb-12 px-4 sm:px-6">

        <!-- Encabezado -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight font-sans bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-800 bg-clip-text text-transparent" x-text="activeTab === 'bulk' ? 'Importación Masiva de Producciones Históricas' : 'Carga Individual de Producciones Históricas'">
                    Importación Masiva de Producciones Históricas
                </h1>
                <p class="text-sm text-slate-550 mt-1 font-semibold uppercase tracking-wider" x-text="activeTab === 'bulk' ? 'Sube lotes de tesis históricas, extrae sus metadatos con IA y publícalas masivamente' : 'Sube una tesis histórica, extrae sus metadatos con IA y publícala individualmente'">
                    Sube lotes de tesis históricas, extrae sus metadatos con IA y publícalas masivamente
                </p>
            </div>
            
            <a href="{{ route('dashboard') }}" class="inline-flex items-center space-x-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-bold transition shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span>Volver al Panel</span>
            </a>
        </div>

        <!-- Alertas Flash -->
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

        <!-- Pestañas para cambiar entre Importación Masiva e Individual -->
        <div class="flex space-x-2 border-b border-slate-200 bg-slate-100/50 p-1 rounded-xl w-fit">
            <button type="button" 
                    @click="activeTab = 'bulk'" 
                    :class="activeTab === 'bulk' ? 'bg-white text-blue-900 font-bold shadow-sm' : 'text-slate-600 hover:text-slate-800 font-semibold'" 
                    class="py-2 px-4 rounded-lg text-sm transition duration-150 uppercase tracking-wider cursor-pointer">
                Importación Masiva (Lote)
            </button>
            <button type="button" 
                    @click="activeTab = 'single'" 
                    :class="activeTab === 'single' ? 'bg-white text-blue-900 font-bold shadow-sm' : 'text-slate-600 hover:text-slate-800 font-semibold'" 
                    class="py-2 px-4 rounded-lg text-sm transition duration-150 uppercase tracking-wider cursor-pointer">
                Carga Individual (1 a 1)
            </button>
        </div>

        <div x-show="activeTab === 'bulk'" class="space-y-8">
            <form action="{{ route('admin.productions.import.store') }}" method="POST" @submit="submitBatchForm($event)" class="space-y-8">
                @csrf

            <!-- SECCIÓN 1: VALORES POR DEFECTO DEL LOTE -->
            <div id="import-defaults-card" class="bg-white border border-slate-200/80 shadow-[0_10px_30px_rgba(13,77,152,0.02)] rounded-2xl p-6 md:p-8 space-y-6">
                <div class="flex items-center space-x-3 border-b border-slate-100 pb-4">
                    <div class="p-2.5 bg-blue-50 text-[#0d4d98] rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">1. Valores Predeterminados para el Lote</h3>
                        <p class="text-sm text-slate-500 mt-0.5">Define los valores globales que se aplicarán automáticamente a todas las tesis cargadas</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
                    <!-- Período Académico -->
                    <div>
                        <label class="block text-sm font-extrabold text-slate-600 uppercase tracking-wider mb-2">Período Académico</label>
                        <select x-model="defaults.academic_period_id" @change="applyDefaultsToAll('academic_period_id')" class="block w-full rounded-xl border-slate-200 bg-slate-50/50 text-slate-700 text-sm py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition duration-150 cursor-pointer font-medium h-11">
                            <option value="">Seleccionar período...</option>
                            <template x-for="p in catalog.periods" :key="p.id">
                                <option :value="p.id" x-text="p.name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Programa Académico -->
                    <div>
                        <label class="block text-sm font-extrabold text-slate-600 uppercase tracking-wider mb-2">Programa Académico</label>
                        <select x-model="defaults.academic_program_id" @change="onDefaultProgramChange()" class="block w-full rounded-xl border-slate-200 bg-slate-50/50 text-slate-700 text-sm py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition duration-150 cursor-pointer font-medium h-11">
                            <option value="">Seleccionar programa...</option>
                            <template x-for="p in catalog.programs" :key="p.id">
                                <option :value="p.id" x-text="p.name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Línea de Investigación -->
                    <div>
                        <label class="block text-sm font-extrabold text-slate-600 uppercase tracking-wider mb-2">Línea de Investigación</label>
                        <select x-model="defaults.research_line_id" @change="applyDefaultsToAll('research_line_id')" :disabled="!defaults.academic_program_id" class="block w-full rounded-xl border-slate-200 bg-slate-50/50 text-slate-700 text-sm py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition duration-150 cursor-pointer font-medium h-11 disabled:bg-slate-100 disabled:cursor-not-allowed">
                            <option value="">Seleccionar línea...</option>
                            <template x-for="l in defaultFilteredLines" :key="l.id">
                                <option :value="l.id" x-text="l.name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Tipo de Producción -->
                    <div>
                        <label class="block text-sm font-extrabold text-slate-600 uppercase tracking-wider mb-2">Tipo de Producción</label>
                        <select x-model="defaults.production_type_id" @change="applyDefaultsToAll('production_type_id')" class="block w-full rounded-xl border-slate-200 bg-slate-50/50 text-slate-700 text-sm py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition duration-150 cursor-pointer font-medium h-11">
                            <option value="">Seleccionar tipo...</option>
                            <template x-for="t in catalog.types" :key="t.id">
                                <option :value="t.id" x-text="t.name"></option>
                            </template>
                        </select>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 2: ZONA DE ARRASTRE Y SOLTADO (DRAG & DROP) -->
            <div class="bg-white border border-slate-200/80 shadow-[0_10px_30px_rgba(13,77,152,0.02)] rounded-2xl p-6 md:p-8 space-y-6">
                <div class="flex items-center space-x-3 border-b border-slate-100 pb-4">
                    <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">2. Cargar Manuscritos en PDF o DOCX</h3>
                        <p class="text-sm text-slate-550 mt-0.5">Sube hasta 50 archivos. El sistema comenzará la extracción automática por lotes inmediatamente.</p>
                    </div>
                </div>

                <div id="import-dropzone" @dragover.prevent="dragOver = true" 
                     @dragleave.prevent="dragOver = false" 
                     @drop.prevent="dragOver = false; handleFileDrop($event)"
                     :class="dragOver ? 'border-[#0d4d98] bg-[#0d4d98]/5 ring-2 ring-[#0d4d98]/10' : 'border-slate-300 bg-slate-50/30'"
                     class="flex flex-col items-center justify-center w-full h-48 px-4 transition-all border-2 border-dashed rounded-2xl cursor-pointer hover:border-[#0d4d98] hover:bg-slate-50/70 focus:outline-none">
                    
                    <label class="flex flex-col items-center justify-center space-y-3 text-center cursor-pointer w-full h-full">
                        <div class="p-4 bg-white rounded-full shadow-md text-[#0d4d98] border border-slate-150">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h7a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div class="space-y-1">
                            <p class="text-base font-extrabold text-slate-700">Arrastra tus tesis o haz clic para seleccionar</p>
                            <p class="text-sm text-slate-555 font-bold">PDF o Word (DOCX) hasta 20 MB por archivo. Límite máximo de 50 archivos por lote.</p>
                        </div>
                        <input type="file" multiple class="hidden" accept="application/pdf, application/vnd.openxmlformats-officedocument.wordprocessingml.document" @change="handleFileSelect">
                    </label>
                </div>
            </div>

            <!-- SECCIÓN 3: TABLA DE ELEMENTOS CARGADOS -->
            <div x-show="items.length > 0" x-transition class="bg-white border border-slate-200/80 shadow-[0_10px_30px_rgba(13,77,152,0.02)] rounded-2xl overflow-hidden" style="display: none;">
                <div class="p-6 md:p-8 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">3. Trabajos en el Lote de Importación</h3>
                        <p class="text-sm text-slate-550 mt-0.5">Revisa, edita los detalles de cada manuscrito y confirma los campos extraídos</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button type="button" @click="clearBatch()" class="px-4 py-2 text-sm font-bold text-rose-600 hover:bg-rose-50 rounded-xl transition cursor-pointer">
                            Limpiar Lote
                        </button>
                        <span class="px-3.5 py-1.5 bg-[#0d4d98]/10 text-[#0d4d98] rounded-xl text-sm font-bold">
                            <span x-text="items.length"></span> Archivos en lote
                        </span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-150 text-slate-600 font-extrabold text-sm uppercase tracking-wider">
                                <th class="py-4 px-6 min-w-[200px]">Archivo / Estado</th>
                                <th class="py-4 px-6 min-w-[320px]">Título del Trabajo de Grado</th>
                                <th class="py-4 px-6 min-w-[180px]">Autores (Texto Plano)</th>
                                <th class="py-4 px-6 min-w-[180px]">Tutor (Texto Plano)</th>
                                <th class="py-4 px-6 text-center w-[120px]">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(item, index) in items" :key="item.file_id">
                                <tr class="hover:bg-slate-50/40 transition duration-150">
                                    
                                    <!-- Archivo y Estado -->
                                    <td class="py-4 px-6">
                                        <div class="space-y-1.5">
                                            <p class="text-sm font-extrabold text-slate-700 line-clamp-1" x-text="item.filename"></p>
                                            
                                            <!-- Barra de progreso de subida -->
                                            <div x-show="item.status === 'uploading'" class="space-y-1 w-44">
                                                <div class="flex justify-between text-sm font-bold text-slate-500 uppercase">
                                                    <span>Subiendo...</span>
                                                    <span x-text="item.progress + '%'"></span>
                                                </div>
                                                <div class="w-full bg-slate-150 rounded-full h-1 overflow-hidden">
                                                    <div class="bg-[#0d4d98] h-1 rounded-full transition-all duration-200" :style="'width: ' + item.progress + '%'"></div>
                                                </div>
                                            </div>

                                            <!-- Badges de Estado -->
                                            <span x-show="item.status === 'processing'" class="inline-flex items-center px-2 py-0.5 rounded text-sm font-bold bg-amber-50 text-amber-700 border border-amber-200/50 animate-pulse">
                                                <svg class="animate-spin -ml-0.5 mr-1 h-3 w-3 text-amber-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Analizando con IA...
                                            </span>

                                            <span x-show="item.status === 'completed'" class="inline-flex items-center px-2 py-0.5 rounded text-sm font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                                                Listo para importar
                                            </span>

                                            <span x-show="item.status === 'error'" class="inline-flex items-center px-2 py-0.5 rounded text-sm font-bold bg-rose-50 text-rose-700 border border-rose-200/60" :title="item.error_message">
                                                Error en carga
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Input Título -->
                                    <td class="py-4 px-6">
                                        <textarea x-model="item.title" required rows="2" 
                                                  :disabled="item.status !== 'completed'"
                                                  placeholder="EJ. PROPUESTA DE UN SISTEMA DE INFORMACIÓN..."
                                                  class="w-full text-sm rounded-xl border-slate-200 bg-slate-50/50 focus:border-[#0d4d98] focus:ring-[#0d4d98]/10 disabled:bg-slate-100/50 disabled:text-slate-400 py-1.5 px-3 resize-none focus:outline-none"></textarea>
                                    </td>

                                    <!-- Input Autores -->
                                    <td class="py-4 px-6">
                                        <input type="text" x-model="item.authors" required 
                                               :disabled="item.status !== 'completed'"
                                               placeholder="EJ. PEDRO PÉREZ, JUAN GÓMEZ"
                                               class="w-full text-sm rounded-xl border-slate-200 bg-slate-50/50 focus:border-[#0d4d98] focus:ring-[#0d4d98]/10 disabled:bg-slate-100/50 disabled:text-slate-400 py-1.5 px-3 focus:outline-none h-10">
                                    </td>

                                    <!-- Input Tutor -->
                                    <td class="py-4 px-6">
                                        <input type="text" x-model="item.tutor" required 
                                               :disabled="item.status !== 'completed'"
                                               placeholder="EJ. DRA. MARÍA RODRÍGUEZ"
                                               class="w-full text-sm rounded-xl border-slate-200 bg-slate-50/50 focus:border-[#0d4d98] focus:ring-[#0d4d98]/10 disabled:bg-slate-100/50 disabled:text-slate-400 py-1.5 px-3 focus:outline-none h-10">
                                    </td>

                                    <!-- Botones de Acción de Fila -->
                                    <td class="py-4 px-6 text-center">
                                        <div class="flex items-center justify-center space-x-1">
                                            <!-- Editar Detalles (Modal) -->
                                            <button type="button" @click="openEditModal(index)" 
                                                    :disabled="item.status !== 'completed'"
                                                    title="Editar metadatos detallados (Resumen, Línea, etc.)"
                                                    class="p-2 text-[#0d4d98] hover:bg-blue-50 rounded-lg disabled:text-slate-300 disabled:hover:bg-transparent transition cursor-pointer">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </button>

                                            <!-- Eliminar de la fila -->
                                            <button type="button" @click="removeItem(index)" 
                                                    title="Eliminar del lote"
                                                    class="p-2 text-rose-600 hover:bg-rose-50 rounded-lg transition cursor-pointer">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>

                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Inputes ocultos para enviar el lote mediante formulario POST -->
                <div class="hidden">
                    <template x-for="(item, idx) in items" :key="item.file_id">
                        <div>
                            <input type="hidden" :name="'productions['+idx+'][file_id]'" :value="item.file_id">
                            <input type="hidden" :name="'productions['+idx+'][title]'" :value="item.title">
                            <input type="hidden" :name="'productions['+idx+'][abstract]'" :value="item.abstract">
                            <input type="hidden" :name="'productions['+idx+'][authors]'" :value="item.authors">
                            <input type="hidden" :name="'productions['+idx+'][tutor]'" :value="item.tutor">
                            <input type="hidden" :name="'productions['+idx+'][academic_program_id]'" :value="item.academic_program_id">
                            <input type="hidden" :name="'productions['+idx+'][research_line_id]'" :value="item.research_line_id">
                            <input type="hidden" :name="'productions['+idx+'][production_type_id]'" :value="item.production_type_id">
                            <input type="hidden" :name="'productions['+idx+'][academic_period_id]'" :value="item.academic_period_id">
                            <input type="hidden" :name="'productions['+idx+'][keywords]'" :value="item.keywords">
                        </div>
                    </template>
                </div>
            </div>

            </form>
        </div>

        <!-- SECCIÓN DE CARGA INDIVIDUAL -->
        <div x-show="activeTab === 'single'" x-cloak class="space-y-8">
            <form action="{{ route('admin.productions.import.store-single') }}" method="POST" @submit="submitSingleForm($event)" class="space-y-8">
                @csrf
                <input type="hidden" name="file_id" :value="singleItem.file_id">

                <!-- 1. Cargar Manuscrito -->
                <div class="bg-white border border-slate-200/80 shadow-[0_10px_30px_rgba(13,77,152,0.02)] rounded-2xl p-6 md:p-8 space-y-6">
                    <div class="flex items-center space-x-3 border-b border-slate-100 pb-4">
                        <div class="p-2.5 bg-indigo-50 text-[#0d4d98] rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-800">1. Cargar Manuscrito Histórico (PDF o DOCX)</h3>
                            <p class="text-sm text-slate-500 mt-0.5">Sube el documento de la tesis para extraer sus metadatos automáticamente usando Inteligencia Artificial</p>
                        </div>
                    </div>

                    <!-- Drag & Drop Zone -->
                    <div x-show="singleItem.status === 'idle' || singleItem.status === 'error'"
                         @dragover.prevent="dragOver = true" 
                         @dragleave.prevent="dragOver = false" 
                         @drop.prevent="dragOver = false; handleSingleFileDrop($event)"
                         :class="dragOver ? 'border-[#0d4d98] bg-[#0d4d98]/5 ring-2 ring-[#0d4d98]/10' : 'border-slate-300 bg-slate-50/30'"
                         class="flex flex-col items-center justify-center w-full h-44 px-4 transition-all border-2 border-dashed rounded-2xl cursor-pointer hover:border-[#0d4d98] hover:bg-slate-50/70 focus:outline-none">
                        
                        <label class="flex flex-col items-center justify-center space-y-3 text-center cursor-pointer w-full h-full">
                            <div class="p-4 bg-white rounded-full shadow-md text-[#0d4d98] border border-slate-150">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h7a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <div class="space-y-1">
                                <p class="text-base font-extrabold text-slate-700">Arrastra la tesis o haz clic para seleccionar</p>
                                <p class="text-sm text-slate-555 font-bold">PDF o Word (DOCX) hasta 5 MB.</p>
                            </div>
                            <input type="file" class="hidden" accept="application/pdf, application/vnd.openxmlformats-officedocument.wordprocessingml.document" @change="handleSingleFileSelect">
                        </label>
                    </div>

                    <!-- Progress and Loading -->
                    <div x-show="singleItem.status === 'uploading' || singleItem.status === 'processing'" class="p-6 bg-slate-50 border border-slate-200 rounded-2xl space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-bold text-[#0d4d98] flex items-center space-x-2">
                                <svg class="animate-spin h-4 w-4 text-[#0d4d98]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="singleItem.status === 'uploading' ? 'Subiendo manuscrito...' : 'Analizando manuscrito con IA...'"></span>
                            </span>
                            <span class="text-sm font-bold text-[#0d4d98]" x-show="singleItem.status === 'uploading'" x-text="singleItem.progress + '%'"></span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden" x-show="singleItem.status === 'uploading'">
                            <div class="bg-[#0d4d98] h-2 rounded-full transition-all duration-300" :style="'width: ' + singleItem.progress + '%'"></div>
                        </div>
                    </div>

                    <!-- Upload Completed Card -->
                    <div x-show="singleItem.status === 'completed'" class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-emerald-500 text-white rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-extrabold text-slate-700" x-text="singleItem.filename"></p>
                                <p class="text-xs text-slate-550 font-semibold">¡Metadatos extraídos con éxito! Revisa y ajusta los detalles en el formulario de abajo.</p>
                            </div>
                        </div>
                        <button type="button" @click="resetSingleFile()" class="px-4 py-2 text-sm font-bold text-rose-600 hover:bg-rose-50 rounded-xl transition cursor-pointer">
                            Quitar Archivo
                        </button>
                    </div>

                    <!-- Error Card -->
                    <div x-show="singleItem.status === 'error'" class="p-4 bg-rose-50 border border-rose-200 rounded-2xl flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-rose-500 text-white rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-extrabold text-slate-700">Error al procesar el archivo</p>
                                <p class="text-xs text-rose-600 font-semibold" x-text="singleItem.error_message"></p>
                            </div>
                        </div>
                        <button type="button" @click="resetSingleFile()" class="px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100 rounded-xl transition cursor-pointer">
                            Reintentar
                        </button>
                    </div>
                </div>

                <!-- 2. Información Metadatos -->
                <div class="bg-white border border-slate-200/80 shadow-[0_10px_30px_rgba(13,77,152,0.02)] rounded-2xl p-6 md:p-8 space-y-6">
                    <div class="flex items-center space-x-3 border-b border-slate-100 pb-4">
                        <div class="p-2.5 bg-blue-50 text-[#0d4d98] rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-800">2. Información del Trabajo de Grado (Dublin Core)</h3>
                            <p class="text-sm text-slate-550 mt-0.5">Valores extraídos con IA. Rellena los campos obligatorios para catalogar la publicación.</p>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <!-- Título -->
                        <div>
                            <label class="block text-sm font-extrabold text-slate-600 uppercase tracking-wider mb-2">Título de la Tesis <span class="text-rose-500">*</span></label>
                            <input type="text" name="title" x-model="singleItem.title" required
                                   placeholder="Ej. DISEÑO DE UN REPOSITORIO CIENTÍFICO INTELIGENTE..."
                                   class="block w-full rounded-xl border-slate-200 bg-slate-50/50 text-slate-700 text-sm py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition h-11 font-medium">
                        </div>

                        <!-- Resumen -->
                        <div>
                            <label class="block text-sm font-extrabold text-slate-600 uppercase tracking-wider mb-2">Resumen Académico (Abstract) <span class="text-rose-500">*</span></label>
                            <textarea name="abstract" x-model="singleItem.abstract" required rows="5"
                                      placeholder="Escribe el resumen del trabajo de grado..."
                                      class="w-full text-sm rounded-xl border-slate-200 bg-slate-50/50 text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 py-2 px-3 resize-none font-medium"></textarea>
                        </div>

                        <!-- Autores y Tutor -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-extrabold text-slate-600 uppercase tracking-wider mb-2">Autor(es) <span class="text-rose-500">*</span></label>
                                <input type="text" name="authors" x-model="singleItem.authors" required
                                       placeholder="Ej. PEDRO PÉREZ, JUAN GÓMEZ"
                                       class="block w-full rounded-xl border-slate-200 bg-slate-50/50 text-slate-700 text-sm py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition h-11 font-medium">
                            </div>
                            <div>
                                <label class="block text-sm font-extrabold text-slate-600 uppercase tracking-wider mb-2">Tutor Académico <span class="text-rose-500">*</span></label>
                                <input type="text" name="tutor" x-model="singleItem.tutor" required
                                       placeholder="Ej. DRA. MARÍA RODRÍGUEZ"
                                       class="block w-full rounded-xl border-slate-200 bg-slate-50/50 text-slate-700 text-sm py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition h-11 font-medium">
                            </div>
                        </div>

                        <!-- Catálogos y Clasificación -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
                            <!-- Período Académico -->
                            <div>
                                <label class="block text-sm font-extrabold text-slate-600 uppercase tracking-wider mb-2">Período Académico <span class="text-rose-500">*</span></label>
                                <select name="academic_period_id" x-model="singleItem.academic_period_id" required
                                        class="block w-full rounded-xl border-slate-200 bg-slate-50/50 text-slate-700 text-sm py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition cursor-pointer h-11 font-medium">
                                    <option value="">Seleccionar período...</option>
                                    <template x-for="p in catalog.periods" :key="p.id">
                                        <option :value="p.id" x-text="p.name" :selected="p.id == singleItem.academic_period_id"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- Programa Académico -->
                            <div>
                                <label class="block text-sm font-extrabold text-slate-600 uppercase tracking-wider mb-2">Programa Académico <span class="text-rose-500">*</span></label>
                                <select name="academic_program_id" x-model="singleItem.academic_program_id" @change="onSingleProgramChange()" required
                                        class="block w-full rounded-xl border-slate-200 bg-slate-50/50 text-slate-700 text-sm py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition cursor-pointer h-11 font-medium">
                                    <option value="">Seleccionar programa...</option>
                                    <template x-for="p in catalog.programs" :key="p.id">
                                        <option :value="p.id" x-text="p.name" :selected="p.id == singleItem.academic_program_id"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- Línea de Investigación -->
                            <div>
                                <label class="block text-sm font-extrabold text-slate-600 uppercase tracking-wider mb-2">Línea de Investigación <span class="text-rose-500">*</span></label>
                                <select name="research_line_id" x-model="singleItem.research_line_id" :disabled="!singleItem.academic_program_id" required
                                        class="block w-full rounded-xl border-slate-200 bg-slate-50/50 text-slate-700 text-sm py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition cursor-pointer h-11 font-medium disabled:bg-slate-100 disabled:cursor-not-allowed">
                                    <option value="">Seleccionar línea...</option>
                                    <template x-for="l in singleFilteredLines" :key="l.id">
                                        <option :value="l.id" x-text="l.name" :selected="l.id == singleItem.research_line_id"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- Tipo de Producción -->
                            <div>
                                <label class="block text-sm font-extrabold text-slate-600 uppercase tracking-wider mb-2">Tipo de Producción <span class="text-rose-500">*</span></label>
                                <select name="production_type_id" x-model="singleItem.production_type_id" required
                                        class="block w-full rounded-xl border-slate-200 bg-slate-50/50 text-slate-700 text-sm py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition cursor-pointer h-11 font-medium">
                                    <option value="">Seleccionar tipo...</option>
                                    <template x-for="t in catalog.types" :key="t.id">
                                        <option :value="t.id" x-text="t.name" :selected="t.id == singleItem.production_type_id"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <!-- Palabras Clave (Chips) -->
                        <div>
                            <label class="block text-sm font-extrabold text-slate-600 uppercase tracking-wider mb-2">Palabras Clave (Separadas por Comas)</label>
                            <div class="flex flex-wrap gap-2 p-3 border border-slate-200/80 rounded-xl bg-slate-50/50 min-h-[46px] focus-within:border-[#0d4d98] focus-within:ring focus-within:ring-[#0d4d98]/10 transition duration-150">
                                <!-- Tags loop -->
                                <template x-for="(tag, index) in singleKeywordList" :key="index">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-sm font-bold bg-[#0d4d98]/10 text-[#0d4d98] border border-[#0d4d98]/20">
                                        <span x-text="tag"></span>
                                        <button type="button" @click="removeSingleKeyword(index)" class="ml-1.5 text-[#0d4d98]/60 hover:text-[#0d4d98] focus:outline-none">
                                            <svg class="h-3 w-3" stroke="currentColor" fill="none" viewBox="0 0 8 8">
                                                <path stroke-linecap="round" stroke-width="1.5" d="M1 1l6 6m0-6L1 7" />
                                            </svg>
                                        </button>
                                    </span>
                                </template>
                                <input type="text" placeholder="Añadir palabra y presionar Enter..."
                                       x-model="singleNewTag"
                                       @keydown.enter.prevent="addSingleKeyword"
                                       @keydown.comma.prevent="addSingleKeyword"
                                       @blur="addSingleKeyword"
                                       class="flex-1 border-0 p-0 text-base focus:ring-0 text-slate-700 bg-transparent placeholder-slate-400 min-w-[180px]" />
                            </div>
                            <input type="hidden" name="keywords" :value="singleKeywordList.join(',')">
                            <p class="text-sm text-slate-555 mt-1.5 font-bold uppercase tracking-wider">Presiona Enter o escribe una Coma para separar las palabras clave.</p>
                        </div>
                    </div>
                </div>

                <!-- BOTONES ACCIÓN INDIVIDUAL -->
                <div class="flex items-center justify-end space-x-3 pt-6 border-t border-slate-200">
                    <button type="submit" :disabled="!isReadyToImportSingle()"
                            class="py-3 px-6 bg-[#0d4d98] hover:bg-[#0b3d78] disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-bold rounded-xl transition shadow-sm hover:shadow-md text-base h-11 flex items-center justify-center cursor-pointer">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Importar y Publicar Trabajo</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- MODAL DETALLADO DE METADATOS DUBLIN CORE -->
        <div x-show="modalOpen" class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm flex items-center justify-center z-50 p-4" style="display: none;" x-transition>
            <div @click.outside="modalOpen = false" class="bg-white rounded-2xl w-full max-w-3xl shadow-2xl border border-slate-150 overflow-hidden flex flex-col max-h-[90vh]">
                
                <!-- Cabecera Modal -->
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between shrink-0 bg-slate-50/50">
                    <div>
                        <h4 class="text-base font-extrabold text-slate-800">Metadatos de Tesis Histórica</h4>
                        <p class="text-sm text-slate-555 mt-0.5" x-text="activeItem ? activeItem.filename : ''"></p>
                    </div>
                    <button type="button" @click="modalOpen = false" class="p-2 -m-2 text-slate-555 hover:text-slate-700 rounded-xl cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Formulario Metadatos -->
                <div class="p-6 overflow-y-auto space-y-5 flex-1" x-if="activeItem">
                    <template x-if="activeItem">
                        <div class="space-y-5">
                            <!-- Resumen / Abstract -->
                            <div>
                                <label class="block text-sm font-extrabold text-slate-600 uppercase tracking-wider mb-2">Resumen Académico (Abstract)</label>
                                <textarea x-model="activeItem.abstract" rows="5" placeholder="Escribe o revisa el resumen del trabajo..."
                                          class="w-full text-sm rounded-xl border-slate-200 bg-slate-50/50 focus:border-[#0d4d98] focus:ring-[#0d4d98]/10 py-2 px-3 focus:outline-none"></textarea>
                            </div>

                            <!-- Palabras Clave (Comma separated) -->
                            <div>
                                <label class="block text-sm font-extrabold text-slate-600 uppercase tracking-wider mb-2">Palabras Clave (Separadas por Comas)</label>
                                <input type="text" x-model="activeItem.keywords" placeholder="Ej: Repositorio, IA, Dublin Core, PHP"
                                       class="w-full text-sm rounded-xl border-slate-200 bg-slate-50/50 focus:border-[#0d4d98] focus:ring-[#0d4d98]/10 py-2.5 px-3 focus:outline-none h-11">
                            </div>

                            <!-- Catálogos Específicos -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Período -->
                                <div>
                                    <label class="block text-sm font-extrabold text-slate-600 uppercase tracking-wider mb-2">Período Académico</label>
                                    <select x-model="activeItem.academic_period_id" class="block w-full rounded-xl border-slate-200 bg-slate-50/50 text-slate-700 text-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition cursor-pointer font-medium h-11">
                                        <option value="">Seleccionar período...</option>
                                        <template x-for="p in catalog.periods" :key="p.id">
                                            <option :value="p.id" x-text="p.name" :selected="p.id == activeItem.academic_period_id"></option>
                                        </template>
                                    </select>
                                </div>

                                <!-- Programa -->
                                <div>
                                    <label class="block text-sm font-extrabold text-slate-600 uppercase tracking-wider mb-2">Programa Académico</label>
                                    <select x-model="activeItem.academic_program_id" @change="onItemProgramChange()" class="block w-full rounded-xl border-slate-200 bg-slate-50/50 text-slate-700 text-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition cursor-pointer font-medium h-11">
                                        <option value="">Seleccionar programa...</option>
                                        <template x-for="p in catalog.programs" :key="p.id">
                                            <option :value="p.id" x-text="p.name" :selected="p.id == activeItem.academic_program_id"></option>
                                        </template>
                                    </select>
                                </div>

                                <!-- Línea -->
                                <div>
                                    <label class="block text-sm font-extrabold text-slate-600 uppercase tracking-wider mb-2">Línea de Investigación</label>
                                    <select x-model="activeItem.research_line_id" :disabled="!activeItem.academic_program_id" class="block w-full rounded-xl border-slate-200 bg-slate-50/50 text-slate-700 text-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition cursor-pointer font-medium h-11 disabled:bg-slate-100 disabled:cursor-not-allowed">
                                        <option value="">Seleccionar línea...</option>
                                        <template x-for="l in activeItemFilteredLines" :key="l.id">
                                            <option :value="l.id" x-text="l.name" :selected="l.id == activeItem.research_line_id"></option>
                                        </template>
                                    </select>
                                </div>

                                <!-- Tipo -->
                                <div>
                                    <label class="block text-sm font-extrabold text-slate-600 uppercase tracking-wider mb-2">Tipo de Producción</label>
                                    <select x-model="activeItem.production_type_id" class="block w-full rounded-xl border-slate-200 bg-slate-50/50 text-slate-700 text-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition cursor-pointer font-medium h-11">
                                        <option value="">Seleccionar tipo...</option>
                                        <template x-for="t in catalog.types" :key="t.id">
                                            <option :value="t.id" x-text="t.name" :selected="t.id == activeItem.production_type_id"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Footer Modal -->
                <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end space-x-2 shrink-0 bg-slate-50/50">
                    <button type="button" @click="modalOpen = false" class="px-4 py-2.5 bg-[#0d4d98] hover:bg-[#0b3d78] text-white rounded-xl text-sm font-bold shadow transition cursor-pointer">
                        Listo
                    </button>
                </div>
            </div>
        </div>

        <script>
            function bulkImport(catalogs) {
            return {
                activeTab: 'bulk',
                dragOver: false,
                items: [],
                defaults: {
                    academic_period_id: '',
                    academic_program_id: '',
                    research_line_id: '',
                    production_type_id: ''
                },
                catalog: catalogs,
                defaultFilteredLines: [],
                activeItemFilteredLines: [],
                modalOpen: false,
                activeItemIndex: null,
                activeItem: null,
                pollingInterval: null,

                // Single import states
                singleItem: {
                    file_id: '',
                    filename: '',
                    status: 'idle', // idle, uploading, processing, completed, error
                    progress: 0,
                    title: '',
                    abstract: '',
                    authors: '',
                    tutor: '',
                    keywords: '',
                    academic_period_id: '',
                    academic_program_id: '',
                    research_line_id: '',
                    production_type_id: '',
                    error_message: ''
                },
                singleFile: null,
                singleKeywordList: [],
                singleFilteredLines: [],
                singleNewTag: '',

                init() {
                    // Load state from localStorage on init
                    const saved = localStorage.getItem('skms_bulk_import_batch');
                    if (saved) {
                        try {
                            this.items = JSON.parse(saved);
                            
                            // Re-start polling if there are any processing items
                            if (this.items.some(item => item.status === 'processing' || item.status === 'uploading')) {
                                this.startPolling();
                            }
                        } catch (e) {
                            console.error('Failed to parse saved bulk import batch', e);
                        }
                    }

                    // Listen to Broadcast event if configured
                    const userId = document.head.querySelector('meta[name="user-id"]')?.content;
                    if (userId && window.Echo) {
                        window.Echo.private(`user.${userId}`)
                            .listen('MetadataExtracted', (e) => {
                                const idx = this.items.findIndex(item => item.file_id === e.fileId);
                                if (idx !== -1 && this.items[idx].status === 'processing') {
                                    if (e.metadata && e.metadata.status === 'error') {
                                        this.items[idx].status = 'error';
                                        this.items[idx].error_message = e.metadata.error_message || 'Error en la extracción.';
                                        this.saveState();
                                    } else {
                                        this.applyExtractedMetadata(idx, e.metadata.metadata || e.metadata);
                                    }
                                }

                                if (this.singleItem.file_id === e.fileId && this.singleItem.status === 'processing') {
                                    if (e.metadata && e.metadata.status === 'error') {
                                        this.singleItem.status = 'error';
                                        this.singleItem.error_message = e.metadata.error_message || 'Error en la extracción.';
                                    } else {
                                        this.applySingleExtractedMetadata(e.metadata.metadata || e.metadata);
                                    }
                                }
                            });
                    }
                },

                saveState() {
                    localStorage.setItem('skms_bulk_import_batch', JSON.stringify(this.items));
                },

                onDefaultProgramChange() {
                    this.defaults.research_line_id = '';
                    if (!this.defaults.academic_program_id) {
                        this.defaultFilteredLines = [];
                    } else {
                        this.defaultFilteredLines = this.catalog.lines.filter(
                            l => l.academic_program_id == this.defaults.academic_program_id
                        );
                    }
                    this.applyDefaultsToAll('academic_program_id');
                    this.applyDefaultsToAll('research_line_id');
                },

                onItemProgramChange() {
                    if (this.activeItem) {
                        this.activeItem.research_line_id = '';
                        if (!this.activeItem.academic_program_id) {
                            this.activeItemFilteredLines = [];
                        } else {
                            this.activeItemFilteredLines = this.catalog.lines.filter(
                                l => l.academic_program_id == this.activeItem.academic_program_id
                            );
                        }
                    }
                },

                applyDefaultsToAll(field) {
                    this.items.forEach(item => {
                        // Only auto-apply defaults if the field is empty or was not manually customized
                        if (this.defaults[field]) {
                            item[field] = this.defaults[field];
                        }
                    });
                    this.saveState();
                },

                handleFileSelect(event) {
                    const files = Array.from(event.target.files);
                    this.processFiles(files);
                },

                handleFileDrop(event) {
                    const files = Array.from(event.dataTransfer.files);
                    this.processFiles(files);
                },

                processFiles(files) {
                    if (this.items.length + files.length > 50) {
                        alert('No puedes cargar más de 50 archivos por lote.');
                        return;
                    }

                    const validFiles = [];
                    files.forEach(file => {
                        if (file.size > 5 * 1024 * 1024) {
                            alert(`El archivo "${file.name}" supera el tamaño máximo permitido de 5MB y no será procesado.`);
                            return;
                        }
                        validFiles.push(file);
                    });

                    validFiles.forEach(file => {
                        const fileId = 'pending-' + Math.random().toString(36).substring(2, 9);
                        const newItem = {
                            file_id: fileId,
                            filename: file.name,
                            status: 'uploading',
                            progress: 0,
                            title: file.name.replace(/\.[^/.]+$/, ""), // title without extension
                            abstract: '',
                            authors: '',
                            tutor: '',
                            keywords: '',
                            academic_period_id: this.defaults.academic_period_id,
                            academic_program_id: this.defaults.academic_program_id,
                            research_line_id: this.defaults.research_line_id,
                            production_type_id: this.defaults.production_type_id,
                            error_message: ''
                        };

                        this.items.push(newItem);
                        this.saveState();

                        this.uploadFile(file, newItem);
                    });
                },

                uploadFile(file, itemRef) {
                    let formData = new FormData();
                    formData.append('file', file);

                    axios.post('{{ route('admin.productions.import.upload') }}', formData, {
                        headers: { 'Content-Type': 'multipart/form-data' },
                        onUploadProgress: (progressEvent) => {
                            const percent = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                            const idx = this.items.findIndex(i => i.file_id === itemRef.file_id);
                            if (idx !== -1) {
                                this.items[idx].progress = percent;
                                if (percent >= 100) {
                                    this.items[idx].status = 'processing';
                                }
                                this.saveState();
                            }
                        }
                    })
                    .then(response => {
                        const idx = this.items.findIndex(i => i.file_id === itemRef.file_id);
                        if (idx !== -1) {
                            // Update the temporary ID with the official server UUID
                            const oldId = this.items[idx].file_id;
                            this.items[idx].file_id = response.data.file_id;
                            this.items[idx].status = 'processing';
                            this.saveState();

                            // Start checking status via polling or wait for Echo
                            this.startPolling();
                        }
                    })
                    .catch(err => {
                        console.error('Upload failed for file ' + itemRef.filename, err);
                        const idx = this.items.findIndex(i => i.file_id === itemRef.file_id);
                        if (idx !== -1) {
                            this.items[idx].status = 'error';
                            this.items[idx].error_message = err.response?.data?.message || 'Error de conexión.';
                            this.saveState();
                        }
                    });
                },

                startPolling() {
                    if (this.pollingInterval) return;

                    this.pollingInterval = setInterval(() => {
                        const processingIds = this.items
                            .filter(i => i.status === 'processing' && !i.file_id.startsWith('pending-'))
                            .map(i => i.file_id);

                        if (processingIds.length === 0) {
                            clearInterval(this.pollingInterval);
                            this.pollingInterval = null;
                            return;
                        }

                        axios.get('{{ route('admin.productions.import.status') }}', {
                            params: { file_ids: processingIds }
                        })
                        .then(response => {
                            let updatedAny = false;
                            Object.keys(response.data).forEach(fileId => {
                                const info = response.data[fileId];
                                if (info.status === 'completed') {
                                    const idx = this.items.findIndex(i => i.file_id === fileId);
                                    if (idx !== -1) {
                                        this.applyExtractedMetadata(idx, info.metadata);
                                        updatedAny = true;
                                    }
                                } else if (info.status === 'error') {
                                    const idx = this.items.findIndex(i => i.file_id === fileId);
                                    if (idx !== -1) {
                                        this.items[idx].status = 'error';
                                        this.items[idx].error_message = info.error_message || 'Falla en la extracción.';
                                        updatedAny = true;
                                    }
                                }
                            });

                            if (updatedAny) {
                                this.saveState();
                            }
                        })
                        .catch(err => {
                            console.error('Status polling error', err);
                        });
                    }, 3000); // Poll every 3 seconds
                },

                applyExtractedMetadata(index, metadata) {
                    const item = this.items[index];
                    item.status = 'completed';
                    item.title = metadata.title || item.title;
                    item.abstract = metadata.abstract || '';
                    item.authors = metadata.authors || '';
                    item.tutor = metadata.tutor || '';
                    item.keywords = metadata.keywords || '';
                    
                    // Trigger program change to refresh research lines for the modal
                    this.saveState();
                },

                removeItem(index) {
                    this.items.splice(index, 1);
                    this.saveState();
                },

                clearBatch() {
                    if (confirm('¿Estás seguro de que deseas vaciar el lote actual de importación?')) {
                        this.items = [];
                        this.saveState();
                    }
                },

                openEditModal(index) {
                    this.activeItemIndex = index;
                    this.activeItem = this.items[index];
                    
                    // Initialize filtered lines for the active item
                    if (this.activeItem.academic_program_id) {
                        this.activeItemFilteredLines = this.catalog.lines.filter(
                            l => l.academic_program_id == this.activeItem.academic_program_id
                        );
                    } else {
                        this.activeItemFilteredLines = [];
                    }

                    this.modalOpen = true;
                },

                isReadyToImport() {
                    if (this.items.length === 0) return false;
                    
                    // All items must be completed (upload + metadata extraction) and have required fields
                    return this.items.every(i => {
                        return i.status === 'completed' &&
                               i.title.trim().length > 0 &&
                               i.authors.trim().length > 0 &&
                               i.tutor.trim().length > 0 &&
                               i.academic_period_id &&
                               i.academic_program_id &&
                               i.research_line_id &&
                               i.production_type_id;
                    });
                },

                submitBatchForm(event) {
                    if (!this.isReadyToImport()) {
                        event.preventDefault();
                        alert('Por favor, asegúrate de que todos los archivos terminen de procesarse y tengan los metadatos obligatorios completos (Título, Autores, Tutor, Período, Programa, Línea, Tipo).');
                        return false;
                    }

                    // Clear local storage on successful submission
                    localStorage.removeItem('skms_bulk_import_batch');
                    return true;
                },

                // Single Import Methods
                handleSingleFileSelect(event) {
                    this.singleFile = event.target.files[0];
                    if (this.singleFile) {
                        if (this.singleFile.size > 5 * 1024 * 1024) {
                            alert('El archivo supera el tamaño máximo permitido de 5MB. Por favor, comprímelo o reduce su tamaño antes de subir.');
                            event.target.value = '';
                            this.singleFile = null;
                            return;
                        }
                        this.uploadSingleAndExtract();
                    }
                },

                handleSingleFileDrop(event) {
                    this.singleFile = event.dataTransfer.files[0];
                    if (this.singleFile) {
                        if (this.singleFile.size > 5 * 1024 * 1024) {
                            alert('El archivo supera el tamaño máximo permitido de 5MB. Por favor, comprímelo o reduce su tamaño antes de subir.');
                            this.singleFile = null;
                            return;
                        }
                        this.uploadSingleAndExtract();
                    }
                },

                uploadSingleAndExtract() {
                    if (!this.singleFile) return;

                    this.singleItem.status = 'uploading';
                    this.singleItem.progress = 0;
                    this.singleItem.filename = this.singleFile.name;
                    this.singleItem.title = this.singleFile.name.replace(/\.[^/.]+$/, "");

                    let formData = new FormData();
                    formData.append('file', this.singleFile);

                    axios.post('{{ route('admin.productions.import.upload') }}', formData, {
                        headers: { 'Content-Type': 'multipart/form-data' },
                        onUploadProgress: (progressEvent) => {
                            const percent = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                            this.singleItem.progress = percent;
                            if (percent >= 100) {
                                this.singleItem.status = 'processing';
                            }
                        }
                    })
                    .then(response => {
                        this.singleItem.file_id = response.data.file_id;
                        this.singleItem.status = 'processing';
                        this.startSinglePolling();
                    })
                    .catch(err => {
                        console.error('Single upload failed', err);
                        this.singleItem.status = 'error';
                        this.singleItem.error_message = err.response?.data?.message || 'Error de conexión.';
                    });
                },

                startSinglePolling() {
                    const poll = setInterval(() => {
                        if (this.singleItem.status !== 'processing' || !this.singleItem.file_id) {
                            clearInterval(poll);
                            return;
                        }

                        axios.get('{{ route('admin.productions.import.status') }}', {
                            params: { file_ids: [this.singleItem.file_id] }
                        })
                        .then(response => {
                            const info = response.data[this.singleItem.file_id];
                            if (info && info.status === 'completed') {
                                clearInterval(poll);
                                this.applySingleExtractedMetadata(info.metadata);
                            } else if (info && info.status === 'error') {
                                clearInterval(poll);
                                this.singleItem.status = 'error';
                                this.singleItem.error_message = info.error_message || 'Falla en la extracción.';
                            }
                        })
                        .catch(err => {
                            console.error('Single status polling error', err);
                        });
                    }, 3000);
                },

                applySingleExtractedMetadata(metadata) {
                    this.singleItem.status = 'completed';
                    this.singleItem.title = metadata.title || this.singleItem.title;
                    this.singleItem.abstract = metadata.abstract || '';
                    this.singleItem.authors = metadata.authors || '';
                    this.singleItem.tutor = metadata.tutor || '';
                    this.singleItem.keywords = metadata.keywords || '';

                    if (metadata.keywords) {
                        this.singleKeywordList = metadata.keywords
                            .split(',')
                            .map(k => k.trim())
                            .filter(k => k.length > 0);
                    }
                },

                resetSingleFile() {
                    this.singleFile = null;
                    this.singleItem.file_id = '';
                    this.singleItem.filename = '';
                    this.singleItem.status = 'idle';
                    this.singleItem.progress = 0;
                    this.singleItem.title = '';
                    this.singleItem.abstract = '';
                    this.singleItem.authors = '';
                    this.singleItem.tutor = '';
                    this.singleItem.keywords = '';
                    this.singleKeywordList = [];
                },

                onSingleProgramChange() {
                    this.singleItem.research_line_id = '';
                    if (!this.singleItem.academic_program_id) {
                        this.singleFilteredLines = [];
                    } else {
                        this.singleFilteredLines = this.catalog.lines.filter(
                            l => l.academic_program_id == this.singleItem.academic_program_id
                        );
                    }
                },

                addSingleKeyword() {
                    let tag = this.singleNewTag.trim();
                    tag = tag.replace(/,+$/, '').trim();
                    if (tag && !this.singleKeywordList.includes(tag)) {
                        this.singleKeywordList.push(tag);
                    }
                    this.singleNewTag = '';
                },

                removeSingleKeyword(index) {
                    this.singleKeywordList.splice(index, 1);
                },

                isReadyToImportSingle() {
                    return this.singleItem.status === 'completed' &&
                           this.singleItem.title.trim().length > 0 &&
                           this.singleItem.authors.trim().length > 0 &&
                           this.singleItem.tutor.trim().length > 0 &&
                           this.singleItem.abstract.trim().length > 0 &&
                           this.singleItem.academic_period_id &&
                           this.singleItem.academic_program_id &&
                           this.singleItem.research_line_id &&
                           this.singleItem.production_type_id;
                },

                submitSingleForm(event) {
                    if (!this.isReadyToImportSingle()) {
                        event.preventDefault();
                        alert('Por favor, asegúrate de que el archivo termine de procesarse y todos los campos obligatorios estén completos.');
                        return false;
                    }
                    return true;
                }
            };
        }
    </script>
</x-dashboard-layout>
