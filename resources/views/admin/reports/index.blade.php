@php
    $user = auth()->user();
    $userRoles = $user->roles->pluck('name')->toArray();
    $activeRole = session('active_dashboard_role') ?? ($userRoles[0] ?? 'Estudiante');
@endphp

<x-dashboard-layout :roles="$userRoles" :activeRole="$activeRole">
    <div class="space-y-8 max-w-5xl mx-auto pb-12" 
         x-data="{
             generating: false,
             reportUrl: null,
             successMessage: null,
             errorMessage: null,
             programId: '',
             periodId: '',
             state: '',
             type: 'pdf',
             async requestReport() {
                 this.generating = true;
                 this.reportUrl = null;
                 this.successMessage = null;
                 this.errorMessage = null;
 
                 try {
                     let response = await axios.post('{{ route('admin.reports.generate') }}', {
                         type: this.type,
                         program_id: this.programId || null,
                         period_id: this.periodId || null,
                         state: this.state || null
                     });
 
                     if (response.data.status === 'queued') {
                         this.successMessage = 'El reporte ha sido encolado para procesamiento en segundo plano. El servidor lo está compilando de forma asíncrona. Te notificaremos aquí mismo apenas esté listo para descarga.';
                     }
                 } catch (error) {
                     this.generating = false;
                     this.errorMessage = error.response?.data?.message || 'Ocurrió un error al solicitar la generación del reporte.';
                 }
             }
         }"
         x-init="
             if (window.Echo) {
                 window.Echo.private('users.' + {{ auth()->user()->id }})
                     .listen('ReportGenerated', (e) => {
                         generating = false;
                         reportUrl = `/admin/reports/download/${e.filename}`;
                         successMessage = '¡Reporte generado con éxito! El archivo está listo para su descarga.';
                     });
             }
         ">
        
        <!-- Encabezado de la Página -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
                <h1 class="text-3xl font-bold text-slate-800 tracking-tight font-sans">Reportes Institucionales</h1>
                <p class="text-sm text-slate-500 mt-1 font-medium">Filtra la productividad científica del decanato y genera documentos oficiales (PDF y Excel) en tiempo real</p>
            </div>
        </div>

        <!-- Estado de Generación / Mensajes de Feedback -->
        <div class="space-y-4">
            <!-- Animación de Carga Activa -->
            <div x-show="generating && !reportUrl" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform -translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 class="p-6 bg-blue-50/50 border border-blue-100 rounded-2xl flex flex-col sm:flex-row items-center gap-4 shadow-sm"
                 style="display: none;">
                <div class="p-3 bg-unimar-blue/10 rounded-xl text-unimar-blue shrink-0">
                    <svg class="animate-spin h-6 w-6 text-unimar-blue" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <div class="text-center sm:text-left space-y-1">
                    <h4 class="text-sm font-bold text-slate-850">Compilando datos en segundo plano...</h4>
                    <p class="text-xs text-slate-500 font-medium">Procesando y aplicando la maquetación institucional del reporte. No cierres esta pestaña.</p>
                </div>
            </div>

            <!-- Alerta de Éxito / Descarga -->
            <div x-show="successMessage" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform -translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 class="p-6 bg-emerald-50 border border-emerald-200/50 rounded-2xl shadow-sm space-y-4"
                 style="display: none;">
                <div class="flex items-start">
                    <div class="p-2 bg-emerald-500/10 rounded-lg text-emerald-600 shrink-0 mr-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-emerald-900">Operación en curso</h4>
                        <p class="text-xs text-emerald-800/90 font-medium mt-0.5" x-text="successMessage"></p>
                    </div>
                </div>

                <!-- Botón de Descarga Real -->
                <div x-show="reportUrl" class="pt-2 border-t border-emerald-200/40">
                    <a :href="reportUrl" 
                       class="inline-flex items-center px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition shadow-sm hover:shadow-md">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Descargar Archivo Generado
                    </a>
                </div>
            </div>

            <!-- Alerta de Error -->
            <div x-show="errorMessage" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform -translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 class="p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-xl shadow-sm text-rose-800"
                 style="display: none;">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="font-bold text-xs" x-text="errorMessage"></span>
                </div>
            </div>
        </div>

        <!-- Tarjeta del Generador -->
        <div class="bg-white border border-slate-200/80 shadow-sm rounded-2xl overflow-hidden">
            <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                <h3 class="text-lg font-bold text-slate-800 font-sans">Parámetros de Filtrado</h3>
                <p class="text-xs text-slate-500 mt-0.5 font-medium">Especifica los filtros para segmentar la producción académica y generar el consolidado de datos</p>
            </div>

            <form @submit.prevent="requestReport()" class="p-8 space-y-8">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Programa Académico -->
                    <div>
                        <label for="program_id" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Programa Académico</label>
                        <select id="program_id" 
                                x-model="programId" 
                                class="block w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:border-unimar-blue focus:ring focus:ring-unimar-blue/10 transition duration-150 text-slate-700 font-medium">
                            <option value="">Todos los programas académicos</option>
                            @foreach($programs as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Período Académico -->
                    <div>
                        <label for="period_id" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Período Académico</label>
                        <select id="period_id" 
                                x-model="periodId" 
                                class="block w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:border-unimar-blue focus:ring focus:ring-unimar-blue/10 transition duration-150 text-slate-700 font-medium">
                            <option value="">Todos los períodos activos</option>
                            @foreach($periods as $pe)
                                <option value="{{ $pe->id }}">{{ $pe->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Estado del Flujo -->
                    <div>
                        <label for="workflow_state" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Estado del Flujo</label>
                        <select id="workflow_state" 
                                x-model="state" 
                                class="block w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:border-unimar-blue focus:ring focus:ring-unimar-blue/10 transition duration-150 text-slate-700 font-medium">
                            <option value="">Todos los estados</option>
                            <option value="draft">Borrador</option>
                            <option value="under_review">En Revisión</option>
                            <option value="requires_corrections">Requiere Correcciones</option>
                            <option value="approved">Aprobado</option>
                            <option value="published">Publicado</option>
                            <option value="rejected">Rechazado</option>
                        </select>
                    </div>

                    <!-- Formato de Exportación -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Formato de Exportación</label>
                        <div class="grid grid-cols-2 gap-4">
                            
                            <!-- PDF -->
                            <label class="flex items-center p-3 bg-white border border-slate-200 rounded-xl hover:bg-slate-50/50 cursor-pointer transition">
                                <input type="radio" 
                                       x-model="type" 
                                       value="pdf" 
                                       class="rounded-full border-slate-300 text-unimar-blue focus:ring-unimar-blue/10 w-4 h-4" />
                                <div class="ml-3 text-xs">
                                    <span class="block font-bold text-slate-700">PDF Oficial</span>
                                    <span class="block text-slate-400 font-medium mt-0.5">Diseño institucional de UNIMAR</span>
                                </div>
                            </label>

                            <!-- Excel -->
                            <label class="flex items-center p-3 bg-white border border-slate-200 rounded-xl hover:bg-slate-50/50 cursor-pointer transition">
                                <input type="radio" 
                                       x-model="type" 
                                       value="excel" 
                                       class="rounded-full border-slate-300 text-unimar-blue focus:ring-unimar-blue/10 w-4 h-4" />
                                <div class="ml-3 text-xs">
                                    <span class="block font-bold text-slate-700">Microsoft Excel</span>
                                    <span class="block text-slate-400 font-medium mt-0.5">Formato tabular de datos (.xlsx)</span>
                                </div>
                            </label>
                            
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="flex items-center justify-end space-x-3 pt-6 border-t border-slate-100">
                    <button type="submit" 
                            :disabled="generating" 
                            class="py-3 px-6 bg-unimar-blue hover:bg-unimar-blue/95 disabled:bg-slate-350 disabled:text-slate-500 disabled:cursor-not-allowed text-white font-bold rounded-xl text-xs uppercase tracking-wider transition shadow-sm hover:shadow-md flex items-center justify-center min-w-48">
                        <svg x-show="generating" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" style="display: none;">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="generating ? 'Procesando Reporte...' : 'Solicitar Generación'"></span>
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-dashboard-layout>
