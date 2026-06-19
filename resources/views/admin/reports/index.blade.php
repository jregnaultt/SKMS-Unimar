<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Reportes Institucionales') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{
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
                    // Show processing message
                    this.successMessage = 'El reporte ha sido encolado para procesamiento en segundo plano. Te avisaremos por este medio cuando esté listo.';
                }
            } catch (error) {
                this.generating = false;
                this.errorMessage = error.response?.data?.message || 'Ocurrió un error al solicitar el reporte.';
            }
        }
    }"
    x-init="
        if (window.Echo) {
            window.Echo.private('users.' + {{ auth()->user()->id }})
                .listen('ReportGenerated', (e) => {
                    generating = false;
                    reportUrl = `/admin/reports/download/${e.filename}`;
                    successMessage = '¡Reporte generado con éxito! Ya puedes descargarlo haciendo clic en el botón de abajo.';
                });
        }
    ">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row gap-6">
                <!-- Sidebar -->
                @include('admin.shared.sidebar')

                <!-- Main Content -->
                <div class="flex-1 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 dark:border-gray-700">
                    <div class="p-6">
                        <div class="mb-6">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                Generador de Reportes Académicos
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Filtra la productividad científica del decanato y genera exportables formales en segundo plano.
                            </p>
                        </div>

                        <!-- Feedback messages -->
                        <div x-show="successMessage" class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm" style="display: none;">
                            <span x-text="successMessage"></span>
                            <div x-show="reportUrl" class="mt-3">
                                <a :href="reportUrl" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-bold rounded-lg transition duration-150">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                    Descargar Reporte Generado
                                </a>
                            </div>
                        </div>

                        <div x-show="errorMessage" class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm" style="display: none;">
                            <span x-text="errorMessage"></span>
                        </div>

                        <!-- Form -->
                        <form @submit.prevent="requestReport()" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Program -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase mb-2">Programa Académico</label>
                                    <select x-model="programId" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600">
                                        <option value="">Todos los programas</option>
                                        @foreach($programs as $p)
                                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Academic Period -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase mb-2">Período Académico</label>
                                    <select x-model="periodId" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600">
                                        <option value="">Todos los períodos</option>
                                        @foreach($periods as $pe)
                                            <option value="{{ $pe->id }}">{{ $pe->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Workflow State -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase mb-2">Estado del Flujo</label>
                                    <select x-model="state" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600">
                                        <option value="">Todos los estados</option>
                                        <option value="draft">Borrador</option>
                                        <option value="under_review">En Revisión</option>
                                        <option value="requires_corrections">Requiere Correcciones</option>
                                        <option value="approved">Aprobado</option>
                                        <option value="published">Publicado</option>
                                        <option value="rejected">Rechazado</option>
                                    </select>
                                </div>

                                <!-- Format -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase mb-2">Formato de Exportación</label>
                                    <div class="flex items-center space-x-6 mt-2">
                                        <label class="inline-flex items-center text-xs text-gray-700 dark:text-gray-300">
                                            <input type="radio" x-model="type" value="pdf" class="text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-700 dark:bg-gray-900" />
                                            <span class="ml-2">PDF Oficial (UNIMAR)</span>
                                        </label>
                                        <label class="inline-flex items-center text-xs text-gray-700 dark:text-gray-300">
                                            <input type="radio" x-model="type" value="excel" class="text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-700 dark:bg-gray-900" />
                                            <span class="ml-2">Microsoft Excel (.xlsx)</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex justify-end pt-4 border-t border-gray-100 dark:border-gray-700">
                                <button type="submit" :disabled="generating" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-bold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 disabled:opacity-50">
                                    <svg x-show="generating" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" style="display: none;">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span x-text="generating ? 'Generando...' : 'Solicitar Reporte'"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
