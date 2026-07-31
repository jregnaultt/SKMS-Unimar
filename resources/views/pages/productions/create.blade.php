@php
    $user = auth()->user();
    $userRoles = $user->roles->pluck('name')->toArray();
    $activeRole = session('active_dashboard_role') ?? ($userRoles[0] ?? 'Estudiante');
@endphp

<x-dashboard-layout :roles="$userRoles" :activeRole="$activeRole">
    <!-- Load Google Picker and Google Identity Services scripts -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script src="https://apis.google.com/js/api.js" onload="gapi.load('auth', () => {}); gapi.load('picker', () => {})" async defer></script>

    <div x-data="Object.assign(documentUpload({{ $researchLines->toJson() }}), {
        sourceType: 'google',
        showUploadTipModal: true,
        googleDriveFileId: '',
        googleDocumentTitle: '',
        googleAccessToken: '',
        developerKey: '{{ config('services.google.api_key') }}',
        clientId: '{{ config('services.google.client_id') }}',
        scope: ['https://www.googleapis.com/auth/drive'],
        subjectId: '{{ $enrollment ? $enrollment->subject_id : '' }}',

        handleGoogleAuth() {
            const tokenClient = google.accounts.oauth2.initTokenClient({
                client_id: this.clientId,
                scope: this.scope.join(' '),
                callback: (tokenResponse) => {
                    if (tokenResponse.error !== undefined) {
                        alert('Error de autenticación con Google.');
                        return;
                    }
                    this.googleAccessToken = tokenResponse.access_token;
                    this.openGooglePicker();
                },
            });
            tokenClient.requestAccessToken({ prompt: 'consent' });
        },

        openGooglePicker() {
            if (!this.googleAccessToken) return;

            const view = new google.picker.View(google.picker.ViewId.DOCUMENTS);
            view.setMimeTypes('application/vnd.google-apps.document');

            const picker = new google.picker.PickerBuilder()
                .addView(view)
                .setOAuthToken(this.googleAccessToken)
                .setDeveloperKey(this.developerKey)
                .setCallback((data) => {
                    if (data.action === google.picker.Action.PICKED) {
                        const doc = data.docs[0];
                        this.googleDriveFileId = doc.id;
                        this.googleDocumentTitle = doc.name;

                        // Trigger async extraction using IA
                        this.extractGoogleDoc(doc.id, this.googleAccessToken);
                    }
                })
                .build();
            picker.setVisible(true);
        },

        extractGoogleDoc(fileId, token) {
            this.isUploading = true;
            this.statusMessage = 'Vinculando y procesando Google Doc con IA...';
            this.uploadProgress = 0;

            axios.post('{{ route('productions.extract-google') }}', {
                google_drive_file_id: fileId,
                google_access_token: token
            })
            .then(response => {
                this.statusMessage = 'Procesando el documento con Inteligencia Artificial...';
                if (response.data && response.data.file_id) {
                    this.fileId = response.data.file_id;
                }
            })
            .catch(error => {
                this.isUploading = false;
                this.statusMessage = 'Error al procesar Google Doc.';
                console.error(error);
            });
        },

        // Override original submitForm validation
        submitForm(event) {
            if (this.sourceType === 'local' && !this.fileId) {
                event.preventDefault();
                alert('Por favor, sube un documento PDF o Word primero.');
                return false;
            }
            if (this.sourceType === 'google' && !this.googleDriveFileId) {
                event.preventDefault();
                alert('Por favor, vincula tu documento de Google Docs primero.');
                return false;
            }
            return true;
        }
    })" 
    x-init="
        @if(isset($enrollment))
            academicPeriodId = '{{ $enrollment->academic_period_id }}';
            subjectId = '{{ $enrollment->subject_id }}';
            metadata.tutor_id = '{{ $enrollment->tutor_id }}';
            metadata.authors = '{{ $user->name }}';
        @endif

        @if(isset($previousProduction))
            metadata.title = {!! json_encode($previousProduction->title) !!};
            metadata.abstract = {!! json_encode($previousProduction->abstract) !!};
            metadata.tutor_id = '{{ $previousProduction->users()->wherePivot('role', 'tutor')->first()?->id ?? ($enrollment->tutor_id ?? '') }}';
            academicProgramId = '{{ $previousProduction->academic_program_id }}';
            productionTypeId = '{{ $previousProduction->production_type_id }}';
            researchLineId = '{{ $previousProduction->research_line_id }}';
            
            // Trigger filtering and selection of research line
            filterResearchLines();
            
            @if($previousProduction->keywords->count() > 0)
                keywordList = {!! json_encode($previousProduction->keywords->pluck('name')->toArray()) !!};
            @endif
        @endif
    "
    class="space-y-8 max-w-5xl mx-auto pb-12">

        <!-- Encabezado de la Página -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
            <div>
                <h1 class="text-3xl font-bold text-slate-800 tracking-tight font-sans">Subir Producción Científica</h1>
                <p class="text-sm text-slate-550 mt-1 font-semibold uppercase tracking-wider">Registra tu proyecto de investigación utilizando el estándar internacional Dublin Core</p>
            </div>
            
            <!-- Estatus dinámico de carga -->
            <div x-show="isUploading" 
                 x-transition
                 class="inline-flex items-center space-x-2 bg-unimar-blue/10 border border-unimar-blue/20 px-4 py-2.5 rounded-xl text-unimar-blue animate-pulse h-11" 
                 style="display: none;">
                <span class="w-2.5 h-2.5 rounded-full bg-unimar-blue"></span>
                <span class="text-sm font-bold uppercase tracking-wider">Procesando con IA</span>
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

        @if ($errors->any())
            <div class="p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-xl shadow-sm text-rose-800 transition duration-300">
                <div class="font-bold text-sm mb-2 uppercase tracking-wider">Errores de validación:</div>
                <ul class="list-disc list-inside text-sm space-y-1 font-medium">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(isset($enrollment) && $enrollment->subject->code === 'TRI1206441')
            <div class="p-4 bg-amber-50 border-l-4 border-amber-500 rounded-r-xl shadow-sm text-amber-800 transition duration-300">
                <div class="flex items-start">
                    <svg class="w-5 h-5 mr-3 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div>
                        <span class="font-bold text-sm">Advertencia de Continuidad:</span>
                        <p class="text-sm mt-1 font-medium">Estás registrando tu proyecto para <strong>Trabajo de Investigación II</strong>. Por norma académica de la institución, debes mantener el mismo tutor y tema que fueron aprobados en Trabajo de Investigación I, salvo excepciones muy justificadas autorizadas por la coordinación.</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Formulario General de Registro -->
        <form id="production-upload-form" method="POST" action="{{ route('productions.store') }}" @submit="submitForm($event)" class="space-y-8">
            @csrf
            
            <!-- Parámetros Ocultos -->
            <input type="hidden" name="file_id" :value="fileId">
            <input type="hidden" name="google_drive_file_id" :value="googleDriveFileId">
            <input type="hidden" name="google_document_title" :value="googleDocumentTitle">
            <input type="hidden" name="google_access_token" :value="googleAccessToken">
            <input type="hidden" name="action" :value="action">

            <!-- PANEL 1: ORIGEN DEL DOCUMENTO -->
            <div class="bg-white border border-slate-200/80 shadow-sm rounded-2xl p-4 md:p-5 sm:p-8 space-y-6">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">1. Origen del Documento</h3>
                    <p class="text-sm text-slate-500 mt-0.5">Sube tu manuscrito en PDF/Word o impórtalo directamente desde tu Google Drive académico</p>
                </div>

                <!-- Selector de Tipo de Origen -->
                <div class="flex space-x-2 border-b border-slate-200 bg-slate-50/50 p-1 rounded-xl w-fit">
                    <button id="tab-local-file" type="button" 
                            @click="sourceType = 'local'" 
                            :class="sourceType === 'local' ? 'bg-white text-unimar-blue font-bold shadow-sm' : 'text-slate-600 hover:text-slate-800'" 
                            class="py-2.5 px-4 rounded-lg text-sm font-bold transition duration-150 uppercase tracking-wider">
                         Archivo Local (PDF / DOCX)
                    </button>
                    <button id="tab-google-drive" type="button" 
                            @click="sourceType = 'google'" 
                            :class="sourceType === 'google' ? 'bg-white text-unimar-blue font-bold shadow-sm' : 'text-slate-600 hover:text-slate-800'" 
                            class="py-2.5 px-4 rounded-lg text-sm font-bold transition duration-150 uppercase tracking-wider flex items-center space-x-2">
                        <span>Google Drive (Docs)</span>
                        <span class="px-2 py-0.5 text-[9px] font-extrabold tracking-normal text-white bg-green-500 uppercase rounded-md shadow-sm">Recomendado</span>
                    </button>
                </div>

                <!-- Carga de Archivo Local -->
                <div x-show="sourceType === 'local'" x-transition class="space-y-4">
                    <label class="flex flex-col items-center justify-center w-full h-36 px-4 transition-all bg-slate-50/50 border-2 border-dashed border-slate-300 hover:border-unimar-blue rounded-xl appearance-none cursor-pointer focus:outline-none"
                           :class="{'border-unimar-blue bg-unimar-blue/5': isUploading}">
                        <div class="flex flex-col items-center justify-center space-y-2">
                            <div class="p-3 bg-white rounded-full shadow-sm text-unimar-blue border border-slate-200/80">
                                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                            </div>
                            <span class="text-sm font-bold text-slate-700" x-text="file ? file.name : 'Arrastra tu tesis o haz clic para seleccionar'"></span>
                            <span class="text-sm text-slate-550 font-bold">Soporta formatos PDF y Word (DOCX) hasta 10MB</span>
                        </div>
                        <input type="file" name="file_upload" class="hidden" accept="application/pdf, application/vnd.openxmlformats-officedocument.wordprocessingml.document" @change="handleFileSelect">
                    </label>

                    <!-- Barra de progreso e indicador de extracción -->
                    <div x-show="isUploading || statusMessage" x-transition class="mt-4 p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2" style="display: none;">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-bold text-unimar-blue flex items-center space-x-2">
                                <svg class="animate-spin h-3.5 w-3.5 text-unimar-blue" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="statusMessage"></span>
                            </span>
                            <span class="text-sm font-bold text-unimar-blue" x-show="isUploading" x-text="uploadProgress + '%'"></span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden" x-show="isUploading">
                            <div class="bg-unimar-blue h-2 rounded-full transition-all duration-300" :style="'width: ' + uploadProgress + '%'"></div>
                        </div>
                    </div>
                </div>

                <!-- Google Docs (Drive) -->
                <div x-show="sourceType === 'google'" x-transition class="p-8 bg-slate-50/50 border border-slate-200 rounded-xl text-center space-y-4" style="display: none;">
                    <div x-show="!googleDriveFileId" class="space-y-4">
                        <div class="p-3 bg-white rounded-full shadow-sm text-slate-450 border border-slate-200/80 w-fit mx-auto">
                            <svg aria-hidden="true" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h7a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <button id="btn-google-drive-search" type="button" @click="handleGoogleAuth" class="inline-flex items-center justify-center py-3 px-6 bg-unimar-blue hover:bg-unimar-blue/95 text-white font-bold rounded-xl text-sm uppercase tracking-wider transition shadow-sm hover:shadow-md focus:outline-none h-11">
                            <svg aria-hidden="true" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                            </svg>
                            Buscar en Google Drive
                        </button>
                        <p class="text-sm text-slate-550 font-bold max-w-md mx-auto">
                            Conéctate de forma segura a tu cuenta institucional de Google para elegir tu tesis de investigación directamente desde tus carpetas compartidas.
                        </p>
                    </div>
                    <div x-show="googleDriveFileId" class="space-y-4" style="display: none;">
                        <div class="inline-flex items-center px-4 py-2.5 bg-emerald-50 text-emerald-800 rounded-xl border border-emerald-200 h-11">
                            <svg aria-hidden="true" class="w-4 h-4 mr-2 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-sm font-bold">Documento Vinculado: <span class="underline" x-text="googleDocumentTitle"></span></span>
                        </div>
                        <div>
                            <button type="button" @click="handleGoogleAuth" class="text-sm text-unimar-blue hover:underline font-bold transition">
                                Cambiar documento seleccionado
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PANEL 2: METADATOS DUBLIN CORE -->
            <div class="bg-white border border-slate-200/80 shadow-sm rounded-2xl p-4 md:p-5 sm:p-8 space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-2 sm:space-y-0">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">2. Metadatos de la Tesis (Dublin Core)</h3>
                        <p class="text-sm text-slate-550 mt-0.5 font-bold uppercase tracking-wider">Completa o verifica la catalogación del manuscrito extraído mediante Inteligencia Artificial</p>
                    </div>

                    <!-- Indicador de Carga Shimmer -->
                    <span x-show="isUploading" 
                          x-transition
                          class="inline-flex items-center space-x-1 bg-unimar-blue/10 border border-unimar-blue/25 text-unimar-blue px-2.5 py-1 rounded-lg text-sm font-bold uppercase tracking-wider animate-pulse" 
                          style="display: none;">
                        <span>Esperando Extracción</span>
                    </span>
                </div>

                <div class="space-y-5">
                    <!-- Título -->
                    <div>
                        <label for="title" class="block text-sm font-bold text-slate-600 uppercase tracking-wider mb-1.5">Título del Trabajo de Grado</label>
                        <input type="text" 
                               name="title" 
                               id="title" 
                               x-model="metadata.title" 
                               required 
                               :class="isUploading ? 'animate-pulse bg-slate-100 border-slate-200 text-transparent pointer-events-none' : 'bg-slate-50/50 text-slate-700 placeholder-slate-400 focus:border-unimar-blue focus:ring-unimar-blue/10 border-slate-200/80'"
                               class="block w-full rounded-xl text-base py-3 px-4 focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition-all duration-150 h-11" 
                               placeholder="EJ. PROPUESTA DE UN REPOSITORIO DE CONOCIMIENTO CIENTÍFICO..." />
                    </div>

                    @if(isset($enrollment) && $enrollment->subject->code === 'TRI1206441')
                        <!-- Resumen -->
                        <div>
                            <label for="abstract" class="block text-sm font-bold text-slate-600 uppercase tracking-wider mb-1.5">Resumen Académico (Abstract)</label>
                            <textarea name="abstract" 
                                      id="abstract" 
                                      x-model="metadata.abstract" 
                                      rows="6" 
                                      :class="isUploading ? 'animate-pulse bg-slate-100 border-slate-200 text-transparent pointer-events-none' : 'bg-slate-50/50 text-slate-700 placeholder-slate-400 focus:border-unimar-blue focus:ring-unimar-blue/10 border-slate-200/80'"
                                      class="block w-full rounded-xl text-base py-3 px-4 focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition-all duration-150"
                                      placeholder="Escribe o verifica el resumen de la investigación..."></textarea>
                        </div>

                        <!-- Palabras Clave (Chips reactivos) -->
                        <div>
                            <label class="block text-sm font-bold text-slate-600 uppercase tracking-wider mb-1.5">Palabras Clave</label>
                            <div class="flex flex-wrap gap-2 p-3 border border-slate-200/80 rounded-xl bg-slate-50/50 min-h-[46px] focus-within:border-unimar-blue focus-within:ring focus-within:ring-unimar-blue/10 transition duration-150">
                                <!-- Listado de Chips -->
                                <template x-for="(tag, index) in keywordList" :key="index">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-sm font-bold bg-unimar-blue/10 text-unimar-blue border border-unimar-blue/20 transition-all duration-150">
                                        <span x-text="tag"></span>
                                        <button type="button" @click="removeKeyword(index)" class="ml-1.5 text-unimar-blue/60 hover:text-unimar-blue focus:outline-none">
                                            <svg aria-hidden="true" class="h-3 w-3" stroke="currentColor" fill="none" viewBox="0 0 8 8">
                                                <path stroke-linecap="round" stroke-width="1.5" d="M1 1l6 6m0-6L1 7" />
                                            </svg>
                                        </button>
                                    </span>
                                </template>
                                <!-- Input para nuevos chips -->
                                <input type="text" 
                                       id="keyword_input"
                                       aria-label="Añadir palabra clave"
                                       placeholder="Añadir palabra y presionar Enter..." 
                                       x-model="newTag" 
                                       @keydown.enter.prevent="addKeyword" 
                                       @keydown.comma.prevent="addKeyword" 
                                       @blur="addKeyword" 
                                       class="flex-1 border-0 p-0 text-base focus:ring-0 text-slate-700 bg-transparent placeholder-slate-400 min-w-[180px]" />
                            </div>
                            <input type="hidden" name="keywords" :value="keywordList.join(',')">
                            <p class="text-sm text-slate-555 mt-1.5 font-bold uppercase tracking-wider">Presiona Enter o escribe una Coma para separar las palabras clave.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- PANEL 3: AFILIACIÓN Y TUTORÍA -->
            <div class="bg-white border border-slate-200/80 shadow-sm rounded-2xl p-4 md:p-5 sm:p-8 space-y-6">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">3. Datos Académicos y Afiliación</h3>
                    <p class="text-sm text-slate-550 mt-0.5">Asigna el tutor, programa y línea de investigación del manuscrito</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- Autor(es) -->
                    <div>
                        <label for="authors" class="block text-sm font-bold text-slate-600 uppercase tracking-wider mb-1.5">Autor(es) / Investigador(es)</label>
                        <input type="text" 
                               name="authors" 
                               id="authors" 
                               x-model="metadata.authors" 
                               required 
                               :class="isUploading ? 'animate-pulse bg-slate-100 border-slate-200 text-transparent pointer-events-none' : 'bg-slate-50/50 text-slate-700 placeholder-slate-400 focus:border-unimar-blue focus:ring-unimar-blue/10 border-slate-200/80'"
                               class="block w-full rounded-xl text-base py-3 px-4 focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition-all duration-150 h-11" 
                               placeholder="Nombre del estudiante" />
                    </div>

                    <!-- Unidad Curricular (Materia) -->
                    <div>
                        <label for="subject_id" class="block text-sm font-bold text-slate-600 uppercase tracking-wider mb-1.5">Unidad Curricular (Materia)</label>
                        @if(isset($enrollment))
                            <select id="subject_id_display" disabled
                                    class="block w-full rounded-xl border-slate-200/80 bg-slate-100 text-slate-500 text-base py-2.5 px-4 focus:outline-none cursor-not-allowed font-medium h-11">
                                <option value="{{ $enrollment->subject_id }}">{{ $enrollment->subject->name }}</option>
                            </select>
                            <input type="hidden" name="subject_id" value="{{ $enrollment->subject_id }}">
                        @else
                            <select name="subject_id" id="subject_id" x-model="subjectId" required
                                    class="block w-full rounded-xl border-slate-200/80 bg-slate-50/50 text-slate-700 text-base py-2.5 px-4 focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition duration-150 cursor-pointer font-medium h-11">
                                <option value="">Selecciona materia...</option>
                                @foreach(\App\Models\Subject::orderBy('name')->get() as $subj)
                                    <option value="{{ $subj->id }}">{{ $subj->name }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    <!-- Tutor -->
                    <div>
                        <label for="tutor_id" class="block text-sm font-bold text-slate-600 uppercase tracking-wider mb-1.5">Tutor Académico</label>
                        @if(isset($enrollment) && $enrollment->tutor)
                            <select id="tutor_id_display" disabled
                                    class="block w-full rounded-xl border-slate-200/80 bg-slate-100 text-slate-500 text-base py-2.5 px-4 focus:outline-none cursor-not-allowed font-medium h-11">
                                <option value="{{ $enrollment->tutor_id }}">{{ $enrollment->tutor->name }}</option>
                            </select>
                            <input type="hidden" name="tutor_id" value="{{ $enrollment->tutor_id }}">
                        @else
                            <select name="tutor_id" 
                                    id="tutor_id" 
                                    x-model="metadata.tutor_id" 
                                    required 
                                    :class="isUploading ? 'animate-pulse bg-slate-100 border-slate-200 text-transparent pointer-events-none' : 'bg-slate-50/50 text-slate-700 focus:border-unimar-blue focus:ring-unimar-blue/10 border-slate-200/80'"
                                    class="block w-full rounded-xl text-base py-2.5 px-4 focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition-all duration-150 h-11 cursor-pointer font-medium">
                                <option value="">-- Selecciona tu Tutor Académico --</option>
                                @foreach ($tutors as $tutor)
                                    <option value="{{ $tutor->id }}">{{ $tutor->name }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    <!-- Programa Académico -->
                    <div>
                        <label for="academic_program_id" class="block text-sm font-bold text-slate-600 uppercase tracking-wider mb-1.5">Programa Académico</label>
                        <select name="academic_program_id" 
                                id="academic_program_id" 
                                x-model="academicProgramId" 
                                @change="filterResearchLines" 
                                required 
                                class="block w-full rounded-xl border-slate-200/80 bg-slate-50/50 text-slate-700 text-base py-3 px-4 focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition duration-150 cursor-pointer font-medium h-11">
                            <option value="">Selecciona un programa...</option>
                            @foreach($academicPrograms as $prog)
                                <option value="{{ $prog->id }}">{{ $prog->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Línea de Investigación -->
                    <div>
                        <label for="research_line_id" class="block text-sm font-bold text-slate-600 uppercase tracking-wider mb-1.5">Línea de Investigación</label>
                        <select name="research_line_id" 
                                id="research_line_id" 
                                x-model="researchLineId" 
                                required 
                                class="block w-full rounded-xl border-slate-200/80 bg-slate-50/50 text-slate-700 text-base py-3 px-4 focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition duration-150 cursor-pointer font-medium h-11">
                            <option value="">Selecciona una línea de investigación...</option>
                            <template x-for="line in filteredResearchLines" :key="line.id">
                                <option :value="line.id" x-text="line.name" :selected="line.id == researchLineId"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Tipo de Producción -->
                    <div>
                        <label for="production_type_id" class="block text-sm font-bold text-slate-600 uppercase tracking-wider mb-1.5">Tipo de Producción</label>
                        <select name="production_type_id" 
                                id="production_type_id" 
                                x-model="productionTypeId" 
                                required 
                                class="block w-full rounded-xl border-slate-200/80 bg-slate-50/50 text-slate-700 text-base py-3 px-4 focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition duration-150 cursor-pointer font-medium h-11">
                            <option value="">Selecciona un tipo...</option>
                            @foreach($productionTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Período Académico -->
                    <div>
                        <label for="academic_period_id" class="block text-sm font-bold text-slate-600 uppercase tracking-wider mb-1.5">Período Académico</label>
                        @if(isset($enrollment))
                            <select id="academic_period_id_display" disabled
                                    class="block w-full rounded-xl border-slate-200/80 bg-slate-100 text-slate-500 text-base py-2.5 px-4 focus:outline-none cursor-not-allowed font-medium h-11">
                                <option value="{{ $enrollment->academic_period_id }}">{{ $enrollment->academicPeriod->name }}</option>
                            </select>
                            <input type="hidden" name="academic_period_id" value="{{ $enrollment->academic_period_id }}">
                        @else
                            <select name="academic_period_id" 
                                    id="academic_period_id" 
                                    x-model="academicPeriodId" 
                                    required 
                                    class="block w-full rounded-xl border-slate-200/80 bg-slate-50/50 text-slate-700 text-base py-3 px-4 focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition duration-150 cursor-pointer font-medium h-11">
                                <option value="">Selecciona un período...</option>
                                @foreach($academicPeriods as $period)
                                    <option value="{{ $period->id }}">{{ $period->name }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Botones de Acción Finales -->
            <div class="flex items-center justify-end space-x-3 pt-6 border-t border-slate-200">
                <!-- Guardar Borrador -->
                <button type="submit" 
                        @click="action = 'draft'" 
                        class="py-3 px-6 bg-unimar-blue hover:bg-unimar-blue/95 text-white font-bold rounded-xl transition-all duration-150 shadow-sm hover:shadow-md text-base focus:outline-none h-11 flex items-center justify-center cursor-pointer">
                    Guardar como Borrador
                </button>
            </div>
        </form>

        @if(auth()->user()->hasRole('Estudiante'))
            <!-- Info Modal for Students -->
            <div x-show="showUploadTipModal" 
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 style="display: none;">
                <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 transform transition-all flex flex-col items-center text-center space-y-4"
                     @click.outside="showUploadTipModal = false"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95">
                    
                    <!-- Google/Material Info Icon -->
                    <div class="w-14 h-14 rounded-full bg-blue-50 text-[#0d4d98] flex items-center justify-center border border-blue-100/50 shadow-sm">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>

                    <div class="space-y-3">
                        <h3 class="text-lg font-bold text-slate-800">Información de Registro</h3>
                        <p class="text-sm text-slate-550 leading-relaxed font-medium">
                            Al cargar tu archivo, la Inteligencia Artificial del sistema intentará extraer automáticamente el título, resumen y tutor del documento.
                        </p>
                        <p class="text-sm text-slate-650 leading-relaxed font-medium">
                            <strong>No te preocupes si el resumen, palabras clave o algún otro campo no se extraen completos inmediatamente</strong>. Podrás editarlos, corregirlos y agregar cualquier detalle faltante más adelante desde tu panel de control antes de enviar tu trabajo a revisión formal.
                        </p>
                    </div>

                    <button type="button" 
                            @click="showUploadTipModal = false" 
                            class="w-full py-2.5 bg-[#0d4d98] hover:bg-[#09356b] text-white font-bold rounded-xl text-sm uppercase tracking-wider transition shadow-sm hover:shadow-md h-11 flex items-center justify-center">
                        Entendido, continuar
                    </button>
                </div>
            </div>
        @endif

    </div>
</x-dashboard-layout>
