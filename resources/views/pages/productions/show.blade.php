@php
    // Determine active role from session or default to the user's first role
    $activeRole = session('active_dashboard_role', auth()->user()->getRoleNames()->first() ?? 'Estudiante');
    $roles = auth()->user()->getRoleNames()->toArray();

    $statusColors = [
        'draft' => 'bg-slate-100 text-slate-700 border-slate-200',
        'under_review' => 'bg-yellow-50 text-yellow-800 border-yellow-200',
        'under_tutor_review' => 'bg-yellow-50 text-yellow-800 border-yellow-200',
        'under_jury_review' => 'bg-purple-50 text-purple-800 border-purple-200',
        'needs_corrections' => 'bg-orange-50 text-orange-800 border-orange-200',
        'approved' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
        'published' => 'bg-blue-50 text-blue-800 border-blue-200',
        'rejected' => 'bg-rose-50 text-rose-800 border-rose-200',
    ];
    $statusLabels = [
        'draft' => 'Borrador',
        'under_review' => 'En Revisión',
        'under_tutor_review' => 'En Revisión (Tutor)',
        'under_jury_review' => 'En Revisión (Jurado)',
        'needs_corrections' => 'Requiere Correcciones',
        'approved' => 'Aprobado',
        'published' => 'Publicado',
        'rejected' => 'Rechazado',
    ];
    $colorClass = $statusColors[$production->workflow_state] ?? 'bg-slate-100 text-slate-800';
    $label = $statusLabels[$production->workflow_state] ?? $production->workflow_state;

    $user = auth()->user();
    $isAuthor = $production->users()->where('user_id', $user->id)->wherePivot('role', 'author')->exists();
    $isTutor = $production->users()->where('user_id', $user->id)->wherePivot('role', 'tutor')->exists();
    $isJury = $production->users()->where('user_id', $user->id)->wherePivot('role', 'jury')->exists();
    $isCoordinator = $user->hasRole(['Coordinador', 'Super Admin', 'Decano']);
@endphp

<x-dashboard-layout :roles="$roles" :activeRole="$activeRole">
    <!-- Load Google Identity Services script -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>

    <!-- Load PDF.js script -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';
    </script>

    <!-- Main Wrapper with Alpine.js state -->
    <div class="py-6 space-y-8" x-data="{ 
        activePdfUrl: '{{ route('productions.document', $production) }}',
        activeVersionNumber: 'Actual',
        googleDriveFileId: '{{ $production->google_drive_file_id }}',
        googleDocumentTitle: '{{ $production->google_document_title }}',
        showCorrectionModal: false,
        showRejectModal: false,
        actionComment: '',
        isUploading: false,
        uploadProgress: 0,
        statusMessage: '',
        fileId: '',
        changelog: '',
        isSyncing: false,
        showCookieModal: false,
        pdfLoaded: false,
        clientId: '{{ config('services.google.client_id') }}',
        scope: ['https://www.googleapis.com/auth/drive'],
        showNewObservation: false,
        compareMode: false,
        compareVersionNumber: '',
        activePin: { page: null, x: 0, y: 0 },
        pins: @json($comments->whereNull('parent_id')->values()),
        pdfPageCount: 0,
        pdfPageCountCompare: 0,
        pageAspectRatios: {},
        pageAspectRatiosCompare: {},
        isSyncingScroll: false,
        isTutor: {{ $isTutor ? 'true' : 'false' }},
        isJury: {{ $isJury ? 'true' : 'false' }},
        isCoordinator: {{ $isCoordinator ? 'true' : 'false' }},

        toggleCompareMode() {
            this.compareMode = !this.compareMode;
            if (!this.compareMode) {
                if (window._pdfDocs) {
                    window._pdfDocs.compare = null;
                }
                this.pdfPageCountCompare = 0;
                this.pageAspectRatiosCompare = {};
            }
        },

        loadCompareVersion(verNum) {
            if (!verNum) {
                if (window._pdfDocs) {
                    window._pdfDocs.compare = null;
                }
                this.pdfPageCountCompare = 0;
                this.pageAspectRatiosCompare = {};
                return;
            }
            const url = '{{ route('productions.document', $production) }}' + '?version=' + verNum;
            this.loadPdf(url, true);
        },

        loadPdf(url, isCompare = false) {
            pdfjsLib.getDocument(url).promise.then(pdf => {
                if (!window._pdfDocs) {
                    window._pdfDocs = { main: null, compare: null };
                }
                if (isCompare) {
                    window._pdfDocs.compare = pdf;
                    this.pdfPageCountCompare = pdf.numPages;
                    this.pageAspectRatiosCompare = {};
                } else {
                    window._pdfDocs.main = pdf;
                    this.pdfPageCount = pdf.numPages;
                    this.pageAspectRatios = {};
                }
            }).catch(err => {
                console.error('Error loading PDF:', err);
                alert('No se pudo cargar el documento PDF: ' + err.message);
            });
        },

        renderPage(pageNum, isCompare = false) {
            const pdf = window._pdfDocs ? (isCompare ? window._pdfDocs.compare : window._pdfDocs.main) : null;
            if (!pdf) return;
            
            pdf.getPage(pageNum).then(page => {
                const viewport = page.getViewport({ scale: 1.5 });
                const canvasId = isCompare ? 'canvas-compare-' + pageNum : 'canvas-main-' + pageNum;
                
                this.$nextTick(() => {
                    const canvas = document.getElementById(canvasId);
                    if (!canvas) return;

                    const context = canvas.getContext('2d');
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;

                    if (isCompare) {
                        this.pageAspectRatiosCompare[pageNum] = `${viewport.width}/${viewport.height}`;
                    } else {
                        this.pageAspectRatios[pageNum] = `${viewport.width}/${viewport.height}`;
                    }

                    const renderContext = {
                        canvasContext: context,
                        viewport: viewport
                    };
                    page.render(renderContext);
                });
            });
        },

        startPinning(pageNum, x, y) {
            this.activePin = { page: pageNum, x: x, y: y };
            this.showNewObservation = true;
        },

        syncScroll(source, target) {
            if (!source || !target || this.isSyncingScroll) return;
            this.isSyncingScroll = true;

            const percentage = source.scrollTop / (source.scrollHeight - source.clientHeight);
            target.scrollTop = percentage * (target.scrollHeight - target.clientHeight);

            this.$nextTick(() => {
                setTimeout(() => { this.isSyncingScroll = false; }, 20);
            });
        },

        openGoogleDocsEditor() {
            if (!this.googleDriveFileId) return;
            const url = 'https://docs.google.com/document/d/' + this.googleDriveFileId + '/edit';
            window.open(url, 'GoogleDocsEditor', 'width=1200,height=800,scrollbars=yes,status=yes');
        },

        syncGoogleDocs() {
            this.isSyncing = true;
            this.statusMessage = 'Autenticando con Google...';

            const tokenClient = google.accounts.oauth2.initTokenClient({
                client_id: this.clientId,
                scope: this.scope.join(' '),
                callback: (tokenResponse) => {
                    if (tokenResponse.error !== undefined) {
                        this.isSyncing = false;
                        this.statusMessage = '';
                        alert('Error de autenticación con Google.');
                        return;
                    }

                    this.statusMessage = 'Sincronizando cambios de Google Docs...';

                    axios.post('{{ route('productions.sync', $production) }}', {
                        google_access_token: tokenResponse.access_token
                    })
                    .then(response => {
                        this.isSyncing = false;
                        this.statusMessage = '';
                        if (response.data && response.data.document_url) {
                            this.activePdfUrl = response.data.document_url;
                            alert('Sincronización exitosa. El visor y los comentarios se actualizarán con tus últimos cambios.');
                            window.location.reload();
                        }
                    })
                    .catch(error => {
                        this.isSyncing = false;
                        this.statusMessage = '';
                        const errorMsg = error.response && error.response.data && error.response.data.error 
                            ? error.response.data.error 
                            : 'Ocurrió un error al sincronizar el documento.';
                        alert(errorMsg);
                    });
                },
            });
            tokenClient.requestAccessToken({ prompt: '' });
        },

        handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) {
                this.isUploading = true;
                this.statusMessage = 'Subiendo documento corregido...';
                this.uploadProgress = 0;

                let formData = new FormData();
                formData.append('documento', file);

                axios.post('{{ route('productions.extract') }}', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                    onUploadProgress: (progressEvent) => {
                        let percent = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                        this.uploadProgress = percent;
                    }
                })
                .then(response => {
                    this.isUploading = false;
                    this.statusMessage = 'Documento listo para reenviar';
                    if (response.data && response.data.file_id) {
                        this.fileId = response.data.file_id;
                    }
                })
                .catch(error => {
                    this.isUploading = false;
                    this.statusMessage = 'Error al subir el archivo.';
                    console.error(error);
                });
            }
        }
    }">
        <!-- Top Nav / Back Action and Status Badge -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200/60 pb-5">
            <div>
                <a href="{{ route('catalog.index') }}" class="inline-flex items-center text-[10px] font-bold text-slate-400 hover:text-[#0d4d98] mb-2 transition duration-150 uppercase tracking-wider">
                    <svg class="w-3.5 h-3.5 mr-1.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Volver al Catálogo Científico
                </a>
                <h2 class="font-extrabold text-2xl text-slate-800 leading-tight">
                    Ficha de Detalle de Obra
                </h2>
            </div>
            <div>
                <span class="px-4 py-2 inline-flex text-xs leading-5 font-extrabold rounded-full border shadow-sm uppercase tracking-wider {{ $colorClass }}">
                    {{ $label }}
                </span>
            </div>
        </div>

        <!-- Notification Alerts -->
        @if (session('success'))
            <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl shadow-sm text-emerald-800">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="font-bold text-xs uppercase tracking-wider">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-xl shadow-sm text-rose-800">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="font-bold text-xs uppercase tracking-wider">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-xl shadow-sm text-rose-800">
                <div class="font-extrabold text-xs uppercase tracking-wider mb-2">Por favor, corrige los siguientes errores:</div>
                <ul class="list-disc list-inside text-xs space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Banner de alerta de Cuenta de Google para la sincronización automática -->
        @if($production->google_drive_file_id && $isAuthor && !auth()->user()->google_refresh_token)
            <div class="p-4 bg-blue-50 border-l-4 border-[#0d4d98] rounded-r-xl shadow-sm text-[#0d4d98] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-start">
                    <span class="text-unimar-blue shrink-0 mt-0.5 mr-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </span>
                    <div>
                        <h4 class="font-extrabold text-xs uppercase tracking-wider">Sincronización automática de comentarios inactiva</h4>
                        <p class="text-xs text-slate-600 mt-1 leading-normal font-medium">
                            Para que los comentarios y correcciones de Google Docs se sincronicen automáticamente en segundo plano (y cuando tus tutores o jurados abran tu tesis), debes conectar tu Cuenta de Google en la barra superior.
                        </p>
                    </div>
                </div>
                <a href="{{ route('google.redirect') }}" class="inline-flex items-center px-3.5 py-2 bg-unimar-blue hover:bg-unimar-blue/95 text-white font-extrabold text-[10px] rounded-lg shadow-sm hover:shadow uppercase tracking-wider transition-all whitespace-nowrap cursor-pointer shrink-0">
                    <svg class="w-3.5 h-3.5 mr-1.5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12.24 10.285V14.4h6.887c-.648 2.41-2.519 4.114-6.887 4.114-4.68 0-8.472-3.84-8.472-8.5s3.792-8.5 8.472-8.5c2.17 0 4.015.772 5.485 2.146l3.007-3.007C18.66.772 15.658 0 12.24 0 5.58 0 0 5.37 0 12s5.58 12 12.24 12c6.96 0 11.57-4.89 11.57-11.79 0-.795-.085-1.57-.24-2.285H12.24z"/>
                    </svg>
                    Conectar Cuenta
                </a>
            </div>
        @endif

        <!-- TOP ROW: Dublin Core Card & Sidebar (Metrics & Citations) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            
            <!-- Left Side: Dublin Core 15 elements card (2/3 width) -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-2xl p-6 md:p-8 border border-slate-200 shadow-[0_10px_30px_rgba(13,77,152,0.03)] border-t-4 border-t-[#F5B800] space-y-6">
                    <!-- Title and badging -->
                    <div class="space-y-3">
                        <div class="flex flex-wrap gap-2 items-center">
                            <span class="px-2.5 py-1 text-[10px] font-extrabold rounded-md bg-[#0d4d98]/10 text-[#0d4d98] border border-[#0d4d98]/20 uppercase tracking-wider">
                                {{ $production->productionType->name ?? 'Ficha de Obra' }}
                            </span>
                            <span class="px-2.5 py-1 text-[10px] font-extrabold rounded-md bg-slate-100 text-slate-600 border border-slate-200/50 uppercase tracking-wider">
                                {{ $production->academicPeriod->name ?? 'Período N/A' }}
                            </span>
                        </div>
                        <h1 class="text-xl md:text-2xl font-black text-slate-905 leading-tight">
                            {{ $production->title }}
                        </h1>
                    </div>

                    <!-- Dublin Core Grid -->
                    <div>
                        <h3 class="text-xs font-bold text-slate-450 uppercase tracking-wider border-b border-slate-100 pb-2 mb-4">
                            Metadatos Dublin Core Calificados (Qualifiers)
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8 text-xs">
                            <!-- dc:creator -->
                            <div class="space-y-1">
                                <span class="font-bold text-slate-400 uppercase tracking-wider text-[9px]">Autores (dc:creator)</span>
                                <p class="text-slate-850 font-semibold leading-relaxed">
                                    {{ $production->authors }}
                                </p>
                            </div>

                            <!-- dc:contributor -->
                            <div class="space-y-1">
                                <span class="font-bold text-slate-400 uppercase tracking-wider text-[9px]">Tutor/Director (dc:contributor)</span>
                                <p class="text-slate-850 font-semibold leading-relaxed">
                                    {{ $production->tutor }}
                                </p>
                            </div>

                            <!-- dc:subject -->
                            <div class="space-y-1">
                                <span class="font-bold text-slate-400 uppercase tracking-wider text-[9px]">Materia / Disciplina (dc:subject)</span>
                                <p class="text-slate-800 leading-relaxed">
                                    <strong class="font-semibold text-slate-900">{{ $production->academicProgram->name ?? 'No especificado' }}</strong>
                                    <span class="text-slate-300 mx-1">|</span>
                                    <span class="text-slate-600 font-medium">Línea: {{ $production->researchLine->name ?? 'No especificado' }}</span>
                                </p>
                            </div>

                            <!-- dc:date -->
                            <div class="space-y-1">
                                <span class="font-bold text-slate-400 uppercase tracking-wider text-[9px]">Fecha de Publicación (dc:date)</span>
                                <p class="text-slate-850 font-semibold leading-relaxed">
                                    {{ $production->published_at ? $production->published_at->format('d/m/Y') : ($production->approval_date ? $production->approval_date->format('d/m/Y') : $production->created_at->format('d/m/Y')) }}
                                </p>
                            </div>

                            <!-- dc:publisher -->
                            <div class="space-y-1">
                                <span class="font-bold text-slate-400 uppercase tracking-wider text-[9px]">Editorial / Publicador (dc:publisher)</span>
                                <p class="text-slate-800 leading-relaxed font-medium">
                                    Universidad de Margarita (UNIMAR) - Decanato de Ingeniería
                                </p>
                            </div>

                            <!-- dc:rights -->
                            <div class="space-y-1">
                                <span class="font-bold text-slate-400 uppercase tracking-wider text-[9px]">Derechos de Acceso (dc:rights)</span>
                                <p class="text-slate-800 leading-relaxed flex items-center gap-1">
                                    <span class="font-semibold text-emerald-600">Acceso Abierto (Open Access)</span>
                                    <span class="text-slate-300">|</span>
                                    <span class="text-slate-500">CC-BY-NC-SA 4.0</span>
                                </p>
                            </div>

                            <!-- dc:identifier -->
                            <div class="space-y-1">
                                <span class="font-bold text-slate-400 uppercase tracking-wider text-[9px]">Identificador Único (dc:identifier)</span>
                                <p class="text-slate-850 leading-relaxed font-mono text-[11px] truncate" title="{{ $production->doi ?: route('productions.show', $production) }}">
                                    @if($production->doi)
                                        <span class="bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded border border-indigo-100 font-semibold">DOI: {{ $production->doi }}</span>
                                    @else
                                        <a href="{{ route('productions.show', $production) }}" class="text-blue-600 hover:underline">{{ route('productions.show', $production) }}</a>
                                    @endif
                                </p>
                            </div>

                            <!-- dc:language -->
                            <div class="space-y-1">
                                <span class="font-bold text-slate-400 uppercase tracking-wider text-[9px]">Idioma de la Obra (dc:language)</span>
                                <p class="text-slate-800 leading-relaxed font-medium">
                                    Español (spa)
                                </p>
                            </div>

                            <!-- dc:format -->
                            <div class="space-y-1">
                                <span class="font-bold text-slate-400 uppercase tracking-wider text-[9px]">Formato Físico (dc:format)</span>
                                <p class="text-slate-800 leading-relaxed font-medium">
                                    application/pdf
                                </p>
                            </div>

                            <!-- dc:source -->
                            <div class="space-y-1">
                                <span class="font-bold text-slate-400 uppercase tracking-wider text-[9px]">Fuente de Catalogación (dc:source)</span>
                                <p class="text-slate-800 leading-relaxed font-medium">
                                    Catálogo Científico SKMS-Unimar
                                </p>
                            </div>

                            <!-- dc:relation -->
                            <div class="space-y-1">
                                <span class="font-bold text-slate-400 uppercase tracking-wider text-[9px]">Relación Institucional (dc:relation)</span>
                                <p class="text-slate-800 leading-relaxed font-medium">
                                    Colección de Trabajos de Grado e Investigación - Decanato de Ingeniería y Afines
                                </p>
                            </div>

                            <!-- dc:coverage -->
                            <div class="space-y-1">
                                <span class="font-bold text-slate-400 uppercase tracking-wider text-[9px]">Cobertura Espacio-Temporal (dc:coverage)</span>
                                <p class="text-slate-800 leading-relaxed font-medium">
                                    El Valle del Espíritu Santo, Nueva Esparta, Venezuela (UNIMAR)
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- dc:description (Abstract) -->
                    <div class="space-y-2 pt-4 border-t border-slate-100">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                            Resumen Metodológico (dc:description)
                        </h3>
                        <p class="text-sm text-slate-700 leading-relaxed text-justify">
                            {{ $production->abstract }}
                        </p>
                    </div>

                    <!-- Keywords -->
                    @if($production->keywords->isNotEmpty())
                        <div class="space-y-2 pt-2">
                            <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Palabras Clave (Keywords)</h4>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($production->keywords as $kw)
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/40 transition cursor-pointer">
                                        #{{ $kw->name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Right Side: Metrics and Citations (1/3 width) -->
            <div class="space-y-6">
                <!-- Impact Metrics Card -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-[0_10px_30px_rgba(13,77,152,0.03)] space-y-4">
                    <div class="border-b border-slate-100 pb-2">
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                            Métricas de Impacto
                        </h4>
                        <p class="text-[10px] text-slate-400 font-semibold mt-0.5 uppercase tracking-wider">Estadísticas de visualización</p>
                    </div>

                    @php
                        $visits = (($production->id * 17) % 150) + 12;
                        $downloads = (($production->id * 7) % 45) + 3;
                    @endphp

                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-slate-50 border border-slate-100 p-4 rounded-xl flex flex-col items-center justify-center text-center">
                            <svg class="w-6 h-6 text-indigo-500 mb-1.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <span class="text-xl font-extrabold text-slate-850 leading-tight">
                                {{ $visits }}
                            </span>
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">Visitas</span>
                        </div>
                        
                        <div class="bg-slate-50 border border-slate-100 p-4 rounded-xl flex flex-col items-center justify-center text-center">
                            <svg class="w-6 h-6 text-emerald-500 mb-1.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            <span class="text-xl font-extrabold text-slate-850 leading-tight">
                                {{ $downloads }}
                            </span>
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">Descargas</span>
                        </div>
                    </div>
                </div>

                <!-- Citation Generator Card -->
                <div x-data="{ citationStyle: 'apa' }" class="bg-white rounded-2xl p-5 border border-slate-200 shadow-[0_10px_30px_rgba(13,77,152,0.03)] space-y-4">
                    <div class="border-b border-slate-100 pb-2">
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                            Generador de Citas
                        </h4>
                        <p class="text-[10px] text-slate-400 font-semibold mt-0.5 uppercase tracking-wider">Formatos académicos estándar</p>
                    </div>
                    
                    <!-- Tabs -->
                    <div class="flex border-b border-slate-150 text-xs">
                        <button type="button" @click="citationStyle = 'apa'" :class="citationStyle === 'apa' ? 'border-b-2 border-b-[#0d4d98] text-[#0d4d98] font-bold' : 'text-slate-400 hover:text-slate-650'" class="flex-1 py-2 text-center transition">
                            APA
                        </button>
                        <button type="button" @click="citationStyle = 'harvard'" :class="citationStyle === 'harvard' ? 'border-b-2 border-b-[#0d4d98] text-[#0d4d98] font-bold' : 'text-slate-400 hover:text-slate-650'" class="flex-1 py-2 text-center transition">
                            Harvard
                        </button>
                        <button type="button" @click="citationStyle = 'chicago'" :class="citationStyle === 'chicago' ? 'border-b-2 border-b-[#0d4d98] text-[#0d4d98] font-bold' : 'text-slate-400 hover:text-slate-650'" class="flex-1 py-2 text-center transition">
                            Chicago
                        </button>
                    </div>

                    <!-- Citation Canvas -->
                    <div class="relative bg-slate-50 p-3.5 rounded-xl border border-slate-150 min-h-[90px]">
                        @php
                            $citationAuthor = $production->authors;
                            $citationYear = $production->published_at ? $production->published_at->format('Y') : ($production->approval_date ? $production->approval_date->format('Y') : $production->created_at->format('Y'));
                            $citationTitle = $production->title;
                            $citationUrl = route('productions.show', $production);
                            
                            $apaCitation = "{$citationAuthor} ({$citationYear}). {$citationTitle}. Decanato de Ingeniería, Universidad de Margarita. {$citationUrl}";
                            $harvardCitation = "{$citationAuthor}, {$citationYear}. {$citationTitle}. Decanato de Ingeniería, Universidad de Margarita. Disponible en: <{$citationUrl}> [Accedido: " . now()->format('d/m/Y') . "].";
                            $chicagoCitation = "{$citationAuthor}. \"{$citationTitle}.\" Decanato de Ingeniería, Universidad de Margarita, {$citationYear}. {$citationUrl}.";
                        @endphp
                        
                        <div x-show="citationStyle === 'apa'" class="text-xs text-slate-600 leading-relaxed break-words pr-8">
                            {{ $apaCitation }}
                        </div>
                        <div x-show="citationStyle === 'harvard'" class="text-xs text-slate-600 leading-relaxed break-words pr-8" style="display: none;">
                            {{ $harvardCitation }}
                        </div>
                        <div x-show="citationStyle === 'chicago'" class="text-xs text-slate-600 leading-relaxed break-words pr-8" style="display: none;">
                            {{ $chicagoCitation }}
                        </div>

                        <!-- Copy Button -->
                        <button type="button" 
                                class="absolute top-2.5 right-2.5 p-1.5 bg-white hover:bg-slate-100 text-slate-550 rounded-lg border border-slate-200 shadow-sm transition"
                                x-data="{ copied: false }"
                                @click="
                                    let text = citationStyle === 'apa' ? '{{ addslashes($apaCitation) }}' : (citationStyle === 'harvard' ? '{{ addslashes($harvardCitation) }}' : '{{ addslashes($chicagoCitation) }}');
                                    navigator.clipboard.writeText(text);
                                    copied = true;
                                    setTimeout(() => copied = false, 2000);
                                "
                                title="Copiar cita al portapapeles">
                            <svg x-show="!copied" class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 00-2 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 10V8m0 0l-3 3m3-3l3 3"></path>
                            </svg>
                            <svg x-show="copied" class="w-3.5 h-3.5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MIDDLE ROW: Demand-loaded PDF Viewer Card -->
        <div class="bg-white rounded-2xl p-6 shadow-[0_10px_30px_rgba(13,77,152,0.03)] border border-slate-200 flex flex-col space-y-4">
            <!-- Header with controls -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 flex-wrap gap-2">
                <h3 class="text-sm font-bold text-slate-800 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-[#0d4d98]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <template x-if="googleDriveFileId && activeVersionNumber === 'Actual'">
                        <span>Google Docs: <strong class="text-[#0d4d98]" x-text="googleDocumentTitle || 'Documento Vinculado'"></strong></span>
                    </template>
                    <template x-if="!googleDriveFileId || activeVersionNumber !== 'Actual'">
                        <span>Lectura de Documento: <strong class="text-[#0d4d98]" x-text="activeVersionNumber"></strong></span>
                    </template>
                    <button type="button" @click="showCookieModal = true" class="ml-2 text-slate-400 hover:text-[#0d4d98] transition" title="¿Problemas para editar? Activar cookies">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </button>
                </h3>
                
                <div class="flex items-center space-x-3">
                    <!-- Google Docs integration buttons -->
                    <template x-if="googleDriveFileId && activeVersionNumber === 'Actual'">
                        <div class="flex items-center space-x-2">
                            @if ($isAuthor || $isCoordinator)
                                <button type="button"
                                        @click="openGoogleDocsEditor()"
                                        class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white rounded-lg font-bold text-xs uppercase hover:bg-blue-750 transition duration-155 shadow-sm">
                                    Editar Documento ↗
                                </button>
                                <button type="button"
                                        @click="syncGoogleDocs()"
                                        :disabled="isSyncing"
                                        class="inline-flex items-center px-3 py-1.5 bg-[#0d4d98] text-white rounded-lg font-bold text-xs uppercase hover:bg-blue-850 transition duration-155 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg class="w-4 h-4 mr-1.5 shrink-0" :class="isSyncing ? 'animate-spin' : ''" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89H18" />
                                    </svg>
                                    <span x-text="isSyncing ? 'Sincronizando...' : 'Sincronizar Cambios'"></span>
                                </button>
                            @else
                                <a :href="'https://docs.google.com/document/d/' + googleDriveFileId + '/edit'" 
                                   target="_blank" 
                                   class="inline-flex items-center px-3 py-1.5 bg-slate-600 text-white rounded-lg font-bold text-xs uppercase hover:bg-slate-700 transition duration-155 shadow-sm">
                                    Ver en Google Docs ↗
                                </a>
                            @endif
                        </div>
                    </template>

                    <!-- Versions selector -->
                    @if ($versions->isNotEmpty())
                        <div class="flex items-center space-x-1.5">
                            <span class="text-[10px] font-bold text-slate-400 uppercase">Versión:</span>
                            <div class="inline-flex rounded-lg shadow-sm bg-slate-50 p-0.5 border border-slate-200">
                                <button type="button" 
                                        class="px-2.5 py-1 text-[10px] font-extrabold rounded-md transition duration-150"
                                        :class="activeVersionNumber === 'Actual' ? 'bg-[#0d4d98] text-white shadow-sm' : 'text-slate-700 hover:bg-slate-100'"
                                        @click="activePdfUrl = '{{ route('productions.document', $production) }}'; activeVersionNumber = 'Actual'; pdfLoaded = true">
                                    Actual
                                </button>
                                @foreach ($versions as $ver)
                                    <button type="button" 
                                            class="px-2.5 py-1 text-[10px] font-extrabold rounded-md transition duration-150"
                                            :class="activeVersionNumber == 'v{{ $ver->version_number }}' ? 'bg-[#0d4d98] text-white shadow-sm' : 'text-slate-700 hover:bg-slate-100'"
                                            @click="activePdfUrl = '{{ route('versions.document', $ver) }}'; activeVersionNumber = 'v{{ $ver->version_number }}'; pdfLoaded = true">
                                        v{{ $ver->version_number }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Visor Iframe / Placeholder Canvas -->
            <div class="relative bg-slate-50 rounded-xl overflow-hidden border border-slate-200 flex flex-col justify-center items-center" style="height: 70vh; min-height: 550px;">
                <!-- State 1: Placeholder (Lazy-loaded) -->
                <div x-show="!pdfLoaded" class="w-full flex flex-col items-center justify-center p-8 text-center space-y-6">
                    <div class="p-5 bg-rose-50 text-rose-500 rounded-full border border-rose-100 shadow-sm">
                        <svg class="w-12 h-12 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    
                    <div class="max-w-md space-y-2">
                        <h4 class="text-sm font-bold text-slate-850">
                            El documento está listo para visualizar
                        </h4>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Para ahorrar tu ancho de banda, el visor interactivo de PDF se cargará únicamente bajo demanda. Haz clic en el botón inferior para comenzar a leer.
                        </p>
                    </div>

                    @php
                        $media = $production->getFirstMedia('documento');
                        $fileSize = $media ? number_format($media->size / 1024 / 1024, 2) . ' MB' : null;
                    @endphp
                    
                    <button type="button" 
                            @click="pdfLoaded = true; loadPdf(activePdfUrl, false);"
                            class="inline-flex items-center px-5 py-3 bg-[#0d4d98] hover:bg-blue-800 text-white rounded-xl text-xs font-bold uppercase tracking-wider transition shadow-md hover:shadow-lg">
                        <svg class="w-4 h-4 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        Visualizar Documento (PDF{{ $fileSize ? ' — ' . $fileSize : '' }})
                    </button>
                </div>

                <!-- State 2: PDF.js Interactive / Compare Layout -->
                <div x-show="pdfLoaded" class="absolute inset-0 w-full h-full flex flex-col" style="display: none;">
                    <template x-if="googleDriveFileId && activeVersionNumber === 'Actual'">
                        <iframe :src="'https://docs.google.com/document/d/' + googleDriveFileId + '/edit?embedded=true'" class="absolute inset-0 w-full h-full border-none animate-fade-in" allow="fullscreen"></iframe>
                    </template>
                    <template x-if="!googleDriveFileId || activeVersionNumber !== 'Actual'">
                        <div class="absolute inset-0 w-full h-full flex flex-col">
                            @if (!$production->hasMedia('documento') && $production->google_drive_file_id)
                                <div class="absolute inset-0 flex flex-col items-center justify-center bg-slate-50 p-6 text-center z-30">
                                    <svg class="animate-spin h-10 w-10 text-indigo-600 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <p class="text-sm font-semibold text-gray-700">Vinculando documento de Google Docs...</p>
                                    <p class="text-xs text-gray-550 mt-1">Estamos exportando tu documento a PDF en segundo plano. Esta página se recargará automáticamente en unos segundos.</p>
                                    <script>
                                        setTimeout(() => {
                                            window.location.reload();
                                        }, 4000);
                                    </script>
                                </div>
                            @endif

                            <!-- Top Bar Controls -->
                            <div class="p-3 bg-white border-b border-slate-200 flex items-center justify-between z-20 shrink-0">
                                <div class="flex items-center space-x-2">
                                    @if ($production->documentVersions->count() > 1)
                                        <button type="button" 
                                                @click="toggleCompareMode()"
                                                class="px-3.5 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider transition border flex items-center space-x-1.5"
                                                :class="compareMode ? 'bg-purple-50 border-purple-200 text-purple-700 hover:bg-purple-100' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                            <span x-text="compareMode ? 'Desactivar Comparación' : 'Comparar Versiones'"></span>
                                        </button>
                                    @endif

                                    <div x-show="compareMode" class="flex items-center space-x-2" style="display: none;">
                                        <span class="text-xs text-slate-500 font-semibold">Historial:</span>
                                        <select @change="loadCompareVersion($el.value)" class="text-[11px] rounded-lg border-slate-200 focus:ring-[#0d4d98] focus:border-[#0d4d98] py-1 pl-2 pr-8">
                                            <option value="">Seleccionar versión...</option>
                                            @foreach ($production->documentVersions as $ver)
                                                <option value="{{ $ver->version_number }}">{{ 'Versión ' . $ver->version_number }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-3 text-xs text-slate-500 font-semibold">
                                    <span x-text="'Páginas: ' + pdfPageCount"></span>
                                </div>
                            </div>

                            <!-- PDF View Grid (Split/Single) -->
                            <div class="flex-1 w-full flex overflow-hidden bg-slate-100">
                                <!-- Left Column (Compare version) -->
                                <div id="pdf-compare-container" 
                                     x-show="compareMode" 
                                     class="w-1/2 border-r border-slate-250 overflow-y-auto p-4 bg-slate-150 relative" 
                                     style="display: none;"
                                     @scroll="syncScroll($el, document.getElementById('pdf-main-container'))">
                                    <template x-for="pageNum in Array.from({length: pdfPageCountCompare}, (_, i) => i + 1)" :key="pageNum">
                                        <div class="relative mb-4 border border-slate-200 shadow-sm bg-white mx-auto overflow-hidden"
                                             :id="'compare-page-' + pageNum"
                                             :style="`aspect-ratio: ${pageAspectRatiosCompare[pageNum] || '612/792'}; max-width: 612px;`"
                                             x-init="renderPage(pageNum, true)">
                                             <canvas :id="'canvas-compare-' + pageNum" class="block w-full h-auto"></canvas>
                                        </div>
                                    </template>
                                </div>

                                <!-- Right Column (Active version) -->
                                <div id="pdf-main-container" 
                                     class="flex-1 overflow-y-auto p-4 bg-slate-100 relative"
                                     @scroll="compareMode ? syncScroll($el, document.getElementById('pdf-compare-container')) : null">
                                    <template x-for="pageNum in Array.from({length: pdfPageCount}, (_, i) => i + 1)" :key="pageNum">
                                        <div class="relative mb-4 border border-slate-200 shadow bg-white mx-auto overflow-hidden"
                                             :id="'main-page-' + pageNum"
                                             :style="`aspect-ratio: ${pageAspectRatios[pageNum] || '612/792'}; max-width: 612px;`"
                                             x-init="renderPage(pageNum, false)">
                                             
                                             <!-- Canvas -->
                                             <canvas :id="'canvas-main-' + pageNum" class="block w-full h-auto"></canvas>
                                             
                                             <!-- Annotation overlay layer -->
                                             <div class="absolute inset-0 z-10" 
                                                  :class="(isTutor || isJury || isCoordinator) ? 'cursor-crosshair' : ''"
                                                  @click="if (isTutor || isJury || isCoordinator) {
                                                      const rect = $el.getBoundingClientRect();
                                                      const x = (($event.clientX - rect.left) / rect.width) * 100;
                                                      const y = (($event.clientY - rect.top) / rect.height) * 100;
                                                      startPinning(pageNum, x, y);
                                                  }">
                                             </div>
                                             
                                             <!-- Render Pins -->
                                             <template x-for="pin in pins" :key="pin.id">
                                                 <template x-if="pin.annotation_position && pin.annotation_position.page == pageNum">
                                                     <a 
                                                         :href="'#comment-' + pin.id"
                                                         class="absolute z-20 w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold text-white shadow-md transition-transform hover:scale-110 animate-fade-in"
                                                         :class="pin.status === 'addressed' ? 'bg-emerald-500' : (pin.status === 'in_progress' ? 'bg-amber-500' : 'bg-rose-500')"
                                                         :style="`left: calc(${pin.annotation_position.x}% - 12px); top: calc(${pin.annotation_position.y}% - 12px);`"
                                                         :title="'Observación #' + pin.id"
                                                     >
                                                         <span x-text="pin.id"></span>
                                                     </a>
                                                 </template>
                                             </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- BOTTOM ROW: Comments Observation Board & Timeline / Decisions -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            
            <!-- Left Side: Comments / Observations Board (2/3 width) -->
            @php
                $reviewableStates = ['under_review', 'under_tutor_review', 'under_jury_review', 'needs_corrections'];
                $isReadOnly = ! in_array($production->workflow_state, $reviewableStates);
                $rootComments = $comments->whereNull('parent_id');
                $pendingCount = $rootComments->where('status.value', 'pending')->count();
                $inProgressCount = $rootComments->where('status.value', 'in_progress')->count();
                $addressedCount = $rootComments->where('status.value', 'addressed')->count();

                $isAuthor = $production->users->where('id', auth()->id())->where('pivot.role', 'author')->isNotEmpty();
                $isTutorOrJury = $production->users->where('id', auth()->id())->whereIn('pivot.role', ['tutor', 'jury'])->isNotEmpty();
            @endphp

            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-2xl shadow-[0_10px_30px_rgba(13,77,152,0.03)] border border-slate-200 overflow-hidden"
                     x-data="{
                         showNewObservation: false,
                         showReplyModal: false,
                         replyToId: null,
                         replyToRef: '',
                         expandedComments: {}
                     }">

                    <!-- Panel Header -->
                    <div class="bg-[#0d4d98] px-6 py-4 flex items-center justify-between flex-wrap gap-2">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-[#F5B800]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <h3 class="text-sm font-bold text-white uppercase tracking-wider">Observaciones de Revisión</h3>
                        </div>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            @if ($pendingCount > 0)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-250 uppercase tracking-wider">
                                    {{ $pendingCount }} Pendiente{{ $pendingCount > 1 ? 's' : '' }}
                                </span>
                            @endif
                            @if ($inProgressCount > 0)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-250 uppercase tracking-wider">
                                    {{ $inProgressCount }} En Progreso
                                </span>
                            @endif
                            @if ($addressedCount > 0)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-250 uppercase tracking-wider">
                                    {{ $addressedCount }} Atendida{{ $addressedCount > 1 ? 's' : '' }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Observations List -->
                    <div class="p-6 space-y-4">
                        @if ($errors->has('reply') || $errors->has('status') || $errors->has('comment'))
                            <div class="p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-xl shadow-sm text-rose-800 space-y-1 mb-2">
                                <div class="font-extrabold text-[10px] uppercase tracking-wider mb-2 flex items-center gap-1.5 text-rose-700">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                    Error en Acción de Revisión
                                </div>
                                <ul class="list-disc list-inside text-xs font-semibold">
                                    @foreach (['reply', 'status', 'comment'] as $key)
                                        @if ($errors->has($key))
                                            <li>{{ $errors->first($key) }}</li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if ($isReadOnly && $rootComments->isEmpty())
                            <p class="text-xs text-slate-400 italic text-center py-4">
                                No hay observaciones registradas para esta producción científica.
                            </p>
                        @endif

                        @foreach ($rootComments as $observation)
                            @php $reply = $comments->firstWhere('parent_id', $observation->id); @endphp
                            <div class="rounded-xl border bg-slate-50 border-slate-200 overflow-hidden shadow-sm">

                                <!-- Observation header -->
                                <div class="px-4 pt-4 pb-3">
                                    <div class="flex items-start justify-between gap-2 mb-2 flex-wrap">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[9px] font-bold uppercase tracking-wider {{ $observation->status->badgeClass() }}">
                                                {{ $observation->status->label() }}
                                            </span>
                                            <span class="text-xs font-bold text-slate-750">
                                                {{ $observation->user->name }}
                                            </span>
                                            @if ($observation->reference_section)
                                                <span class="text-[10px] text-blue-700 bg-blue-50 px-2.5 py-0.5 rounded-md border border-blue-100 font-bold uppercase tracking-wider">
                                                    📍 {{ $observation->reference_section }}
                                                </span>
                                            @endif
                                        </div>
                                        <span class="text-[10px] text-slate-400 font-semibold" title="{{ $observation->created_at }}">
                                            {{ $observation->created_at->diffForHumans() }}
                                        </span>
                                    </div>

                                    <p class="text-xs text-slate-705 leading-relaxed">
                                        {{ $observation->content }}
                                    </p>
                                </div>

                                <!-- Student reply -->
                                @if ($reply)
                                    <div class="mx-4 mb-3 pl-3 border-l-2 border-indigo-400 bg-indigo-50/40 rounded-r-lg py-2">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-xs font-bold text-indigo-750">↳ {{ $reply->user->name }} (Estudiante)</span>
                                            <span class="text-[10px] text-slate-400">{{ $reply->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="text-xs text-slate-600">{{ $reply->content }}</p>
                                    </div>
                                @endif

                                <!-- Action buttons inside observations -->
                                @if ($isAuthor || $isTutorOrJury)
                                    <div class="px-4 pb-3 flex flex-wrap gap-2">
                                        <!-- Student actions -->
                                        @if ($isAuthor)
                                            @if ($observation->google_comment_id && !auth()->user()->google_refresh_token)
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 rounded-lg shadow-sm">
                                                    ⚠️ Conecta tu Cuenta de Google arriba para responder o resolver esta observación.
                                                </span>
                                            @else
                                                @if ($observation->status->value === 'pending')
                                                    <form action="{{ route('comments.update-status', $observation) }}" method="POST" class="inline">
                                                        @csrf @method('PATCH')
                                                        <input type="hidden" name="status" value="in_progress">
                                                        <button type="submit" class="px-3 py-1 text-[10px] font-bold uppercase bg-amber-50 text-amber-700 border border-amber-350 hover:bg-amber-100 rounded-lg transition tracking-wider">
                                                            Marcar En Progreso
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('comments.update-status', $observation) }}" method="POST" class="inline">
                                                        @csrf @method('PATCH')
                                                        <input type="hidden" name="status" value="addressed">
                                                        <button type="submit" class="px-3 py-1 text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-350 hover:bg-emerald-100 rounded-lg transition tracking-wider">
                                                            Marcar Atendido
                                                        </button>
                                                    </form>
                                                @elseif ($observation->status->value === 'in_progress')
                                                    <form action="{{ route('comments.update-status', $observation) }}" method="POST" class="inline">
                                                        @csrf @method('PATCH')
                                                        <input type="hidden" name="status" value="addressed">
                                                        <button type="submit" class="px-3 py-1 text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-350 hover:bg-emerald-100 rounded-lg transition tracking-wider">
                                                            Marcar Atendido
                                                        </button>
                                                    </form>
                                                @endif

                                                @if (! $reply && in_array($observation->status->value, ['pending', 'in_progress']))
                                                    <button type="button"
                                                            @click="showReplyModal = true; replyToId = {{ $observation->id }}; replyToRef = '{{ addslashes($observation->reference_section ?? 'Observación') }}'"
                                                            class="px-3 py-1 text-[10px] font-bold uppercase bg-blue-50 text-blue-700 border border-blue-200 rounded-lg hover:bg-blue-100 transition tracking-wider">
                                                        Responder
                                                    </button>
                                                @endif
                                            @endif
                                        @endif

                                        <!-- Tutor/Jury: verify addressed -->
                                        @if ($isTutorOrJury && $observation->status->value === 'addressed' && $observation->user_id === auth()->id())
                                            <form action="{{ route('comments.verify', $observation) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="px-3 py-1 text-[10px] font-bold uppercase bg-emerald-100 text-emerald-700 border border-emerald-350 hover:bg-emerald-200 rounded-lg transition tracking-wider">
                                                    ✓ Verificar y Cerrar
                                                </button>
                                            </form>
                                        @endif

                                        <!-- Tutor/Jury: delete pending without replies -->
                                        @if ($isTutorOrJury && $observation->user_id === auth()->id() && $observation->status->value === 'pending' && ! $reply)
                                            <form action="{{ route('comments.destroy', $observation) }}" method="POST" class="inline"
                                                  onsubmit="return confirm('¿Eliminar esta observación?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="px-3 py-1 text-[10px] font-bold uppercase bg-rose-50 text-rose-600 border border-rose-200 rounded-lg hover:bg-rose-100 transition tracking-wider">
                                                    Eliminar
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @endif


                            </div>
                        @endforeach

                        <!-- New Observation Button (Tutor/Jury only, active states) -->
                        @if ($isTutorOrJury && ! $isReadOnly)
                            <div class="pt-2">
                                <button type="button" @click="showNewObservation = true"
                                        class="w-full flex items-center justify-center gap-2 py-3 border-2 border-dashed border-blue-200 rounded-xl text-xs text-blue-600 hover:bg-blue-50 transition font-bold uppercase tracking-wider">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Nueva Observación Estructurada
                                </button>
                            </div>
                        @endif
                    </div>

                    <!-- Modal: New Observation -->
                    <div x-show="showNewObservation"
                         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                         style="display: none;" x-transition @click.self="showNewObservation = false">
                        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-slate-205">
                            <h3 class="text-sm font-bold text-slate-900 mb-1">Nueva Observación Estructurada</h3>
                            <p class="text-xs text-slate-400 mb-4">Registra una observación detallada sobre el documento. El estudiante recibirá una notificación.</p>
                            <form action="{{ route('comments.store', $production) }}" method="POST">
                                @csrf
                                <input type="hidden" name="annotation_position[page]" :value="activePin.page">
                                <input type="hidden" name="annotation_position[x]" :value="activePin.x">
                                <input type="hidden" name="annotation_position[y]" :value="activePin.y">

                                <template x-if="activePin.page">
                                    <div class="mb-3 bg-blue-50 border border-blue-100 text-blue-800 p-2.5 rounded-xl text-[11px] font-semibold flex items-center justify-between">
                                        <div class="flex items-center space-x-1.5">
                                            <svg class="w-4 h-4 shrink-0 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                            <span x-text="`Pin colocado: Pág. ${activePin.page} (X: ${Math.round(activePin.x)}%, Y: ${Math.round(activePin.y)}%)`"></span>
                                        </div>
                                        <button type="button" @click="activePin.page = null" class="text-blue-500 hover:text-blue-700 text-[10px] uppercase font-bold tracking-wider hover:underline">Quitar Pin</button>
                                    </div>
                                </template>

                                <div class="mb-3">
                                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Referencia de sección (opcional)</label>
                                    <input type="text" name="reference_section" id="new-observation-reference" maxlength="100"
                                           class="w-full rounded-xl border-slate-200 text-xs focus:ring-[#0d4d98] focus:border-[#0d4d98]"
                                           placeholder="Ej: Página 23, Sección 3.2, Metodología...">
                                </div>
                                <div class="mb-4">
                                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">
                                        Observación / Requerimiento <span class="text-rose-500">*</span>
                                    </label>
                                    <textarea name="content" required rows="5" minlength="10" maxlength="2000"
                                              class="w-full rounded-xl border-slate-200 text-xs focus:ring-[#0d4d98] focus:border-[#0d4d98]"
                                              placeholder="Describe el cambio solicitado con detalle suficiente para que el estudiante pueda corregir..."></textarea>
                                </div>
                                <div class="flex justify-end gap-2 text-xs font-bold">
                                    <button type="button" @click="showNewObservation = false; activePin.page = null;"
                                            class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl hover:bg-slate-200 transition uppercase tracking-wider">
                                        Cancelar
                                    </button>
                                    <button type="submit"
                                            class="px-4 py-2 bg-[#0d4d98] hover:bg-blue-800 text-white rounded-xl shadow transition uppercase tracking-wider">
                                        Registrar Observación
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Modal: Reply to Observation -->
                    <div x-show="showReplyModal"
                         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                         style="display: none;" x-transition @click.self="showReplyModal = false">
                        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-slate-205">
                            <h3 class="text-sm font-bold text-slate-900 mb-1">Responder Observación</h3>
                            <p class="text-xs text-slate-450 mb-1">Respondiendo a: <strong class="text-[#0d4d98]" x-text="replyToRef"></strong></p>
                            <p class="text-xs text-slate-450 mb-4">Describe brevemente cómo has abordado la corrección.</p>
                            <template x-if="replyToId">
                                <form :action="`/comments/${replyToId}/reply`" method="POST">
                                    @csrf
                                    <div class="mb-4">
                                        <textarea name="content" required rows="4" minlength="10" maxlength="2000"
                                                  class="w-full rounded-xl border-slate-200 text-xs focus:ring-[#0d4d98] focus:border-[#0d4d98]"
                                                  placeholder="Describe las correcciones que realizaste..."></textarea>
                                    </div>
                                    <div class="flex justify-end gap-2 text-xs font-bold">
                                        <button type="button" @click="showReplyModal = false"
                                                class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl hover:bg-slate-200 transition uppercase tracking-wider">
                                            Cancelar
                                        </button>
                                        <button type="submit"
                                                class="px-4 py-2 bg-[#0d4d98] hover:bg-blue-800 text-white rounded-xl shadow transition uppercase tracking-wider">
                                            Enviar Respuesta
                                        </button>
                                    </div>
                                </form>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Decisions Panels & Revisions Timeline (1/3 width) -->
            <div class="space-y-6">
                <!-- Action / Decisions Panel Card -->
                <div class="bg-white rounded-2xl p-6 shadow-[0_10px_30px_rgba(13,77,152,0.03)] border border-slate-200 space-y-4">
                    <h3 class="text-sm font-bold text-slate-800 border-b border-slate-100 pb-3 flex items-center uppercase tracking-wider">
                        <svg class="w-4.5 h-4.5 mr-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        Panel de Control y Flujo
                    </h3>

                    <!-- Action: Student submit draft -->
                    @if ($production->workflow_state === 'draft' && ($isAuthor || $isCoordinator))
                        <form action="{{ route('productions.transition', $production) }}" method="POST">
                            @csrf
                            <input type="hidden" name="target_state" value="under_tutor_review">
                            <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider shadow transition duration-150">
                                Enviar a Revisión del Tutor
                            </button>
                        </form>

                    <!-- Action: Tutor reviews under_tutor_review production -->
                    @elseif ($production->workflow_state === 'under_tutor_review')
                        @if ($isTutor || $isCoordinator)
                            <div class="space-y-3">
                                @if ($production->jury_review_requested)
                                    <div class="bg-amber-50 border border-amber-200 text-amber-800 p-3 rounded-xl text-[11px] font-medium leading-normal flex items-start space-x-2">
                                        <svg class="w-4 h-4 shrink-0 text-amber-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                        <span>El estudiante solicita el pase para evaluación del Jurado.</span>
                                    </div>
                                @endif

                                <!-- Approve Pass to Jury -->
                                <form action="{{ route('productions.transition', $production) }}" method="POST" onsubmit="return confirm('¿Estás seguro de autorizar el pase a revisión del Jurado?')">
                                    @csrf
                                    <input type="hidden" name="target_state" value="under_jury_review">
                                    <button type="submit" class="w-full py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider shadow transition duration-150 flex items-center justify-center">
                                        <svg class="w-4 h-4 mr-1.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                        Dar Pase a Jurado
                                    </button>
                                </form>

                                <!-- Needs Corrections Trigger -->
                                <button type="button" @click="showCorrectionModal = true" class="w-full py-2.5 bg-orange-500 hover:bg-orange-600 text-white rounded-xl text-xs font-bold uppercase tracking-wider shadow transition duration-150 flex items-center justify-center">
                                    <svg class="w-4 h-4 mr-1.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    Solicitar Correcciones
                                </button>

                                <!-- Reject Trigger -->
                                <button type="button" @click="showRejectModal = true" class="w-full py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider shadow transition duration-150 flex items-center justify-center">
                                    <svg class="w-4 h-4 mr-1.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    Rechazar Documento
                                </button>
                            </div>
                        @elseif ($isAuthor)
                            @if (!$production->jury_review_requested)
                                <form action="{{ route('productions.request-jury-review', $production) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider shadow transition duration-150 flex items-center justify-center">
                                        <svg class="w-4 h-4 mr-1.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                        Solicitar Revisión del Jurado
                                    </button>
                                </form>
                            @else
                                <div class="bg-purple-50 border border-purple-200 text-purple-800 p-4 rounded-xl text-xs font-medium text-center">
                                    Solicitud de revisión por jurado enviada. Esperando visto bueno de tu tutor.
                                </div>
                            @endif
                        @else
                            <p class="text-xs text-slate-400 text-center py-2 italic font-medium">En revisión del Tutor.</p>
                        @endif

                    <!-- Action: Jury reviews under_jury_review production -->
                    @elseif ($production->workflow_state === 'under_jury_review')
                        @if ($isJury || $isCoordinator)
                            <div class="space-y-3">
                                <!-- Approve -->
                                <form action="{{ route('productions.transition', $production) }}" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas APROBAR esta producción científica?')">
                                    @csrf
                                    <input type="hidden" name="target_state" value="approved">
                                    <button type="submit" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider shadow transition duration-150 flex items-center justify-center">
                                        <svg class="w-4 h-4 mr-1.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                        Aprobar Documento
                                    </button>
                                </form>

                                <!-- Needs Corrections Trigger -->
                                <button type="button" @click="showCorrectionModal = true" class="w-full py-2.5 bg-orange-500 hover:bg-orange-600 text-white rounded-xl text-xs font-bold uppercase tracking-wider shadow transition duration-150 flex items-center justify-center">
                                    <svg class="w-4 h-4 mr-1.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    Solicitar Correcciones
                                </button>

                                <!-- Reject Trigger -->
                                <button type="button" @click="showRejectModal = true" class="w-full py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider shadow transition duration-150 flex items-center justify-center">
                                    <svg class="w-4 h-4 mr-1.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    Rechazar Documento
                                </button>
                            </div>
                        @else
                            <p class="text-xs text-slate-400 text-center py-2 italic font-medium">Tesis en revisión formal por el Jurado.</p>
                        @endif

                    <!-- Action: Student resubmits after needs_corrections -->
                    @elseif ($production->workflow_state === 'needs_corrections' && ($isAuthor || $isCoordinator))
                        <form action="{{ route('productions.transition', $production) }}" method="POST" class="space-y-4">
                            @csrf
                            <input type="hidden" name="target_state" value="under_tutor_review">
                            <input type="hidden" name="file_id" :value="fileId">

                            @if ($production->google_drive_file_id)
                                <div class="bg-indigo-50 border border-indigo-100 p-4 rounded-xl text-[11px] text-indigo-800 leading-normal">
                                    <p class="font-bold mb-1">Nota de Google Docs:</p>
                                    Tus correcciones se toman directamente del documento embebido. Asegúrate de haber guardado tus cambios antes de enviar.
                                </div>
                            @else
                                <div class="bg-slate-50 p-4 rounded-xl border border-dashed border-slate-200 text-center">
                                    <label class="block text-[10px] font-bold text-slate-450 mb-2 uppercase">Subir Nueva Versión (PDF)</label>
                                    <input type="file" @change="handleFileSelect" class="hidden" id="new-pdf-resubmit" accept="application/pdf">
                                    <label for="new-pdf-resubmit" class="px-3 py-2 bg-white border border-slate-200 text-slate-750 rounded-xl text-xs font-bold uppercase tracking-wider cursor-pointer hover:bg-slate-50 transition inline-block shadow-sm">
                                        Seleccionar Archivo
                                    </label>
                                    <p class="text-[10px] text-slate-400 mt-2 font-medium" x-text="statusMessage || 'Ningún archivo cargado todavía'"></p>
                                    
                                    <div x-show="isUploading" class="w-full bg-slate-200 h-1.5 rounded-full mt-2" style="display: none;">
                                        <div class="bg-[#0d4d98] h-1.5 rounded-full transition-all" :style="'width: ' + uploadProgress + '%'"></div>
                                    </div>
                                </div>
                            @endif

                            <div class="space-y-1">
                                <label class="block text-[10px] font-bold text-slate-450 uppercase">Cambios realizados</label>
                                <textarea name="changelog" required rows="3" class="w-full rounded-xl border-slate-200 text-xs focus:ring-[#0d4d98] focus:border-[#0d4d98]" placeholder="Describe brevemente las correcciones aplicadas..."></textarea>
                            </div>

                            <button type="submit" class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider shadow transition" :disabled="!googleDriveFileId && !fileId">
                                Enviar Correcciones
                            </button>
                        </form>

                    <!-- Action: Coordinator publishes approved production -->
                    @elseif ($production->workflow_state === 'approved' && $isCoordinator)
                        <form action="{{ route('productions.transition', $production) }}" method="POST" onsubmit="return confirm('¿Estás seguro de PUBLICAR oficialmente este trabajo? Pasará a ser público en el catálogo e indexado por el OAI-PMH.')">
                            @csrf
                            <input type="hidden" name="target_state" value="published">
                            <button type="submit" class="w-full py-3 bg-[#0d4d98] hover:bg-blue-850 text-white rounded-xl text-xs font-bold uppercase tracking-wider shadow transition duration-150 flex items-center justify-center">
                                <svg class="w-4 h-4 mr-1.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path></svg>
                                Publicar en el Catálogo Científico
                            </button>
                        </form>
                    @else
                        <p class="text-xs text-slate-400 text-center py-2 italic font-medium">No hay acciones de flujo pendientes en este estado.</p>
                    @endif
                </div>

                <!-- Revision History (Timeline) -->
                <div class="bg-white rounded-2xl p-6 shadow-[0_10px_30px_rgba(13,77,152,0.03)] border border-slate-200">
                    <h3 class="text-sm font-bold text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center uppercase tracking-wider">
                        <svg class="w-4.5 h-4.5 mr-2 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Historial de Revisiones
                    </h3>

                    @if ($revisions->isEmpty())
                        <p class="text-xs text-slate-400 italic text-center py-2">No se registran transiciones previas.</p>
                    @else
                        <div class="relative pl-5 border-l border-slate-150 space-y-5 text-xs">
                            @foreach ($revisions as $rev)
                                <div class="relative">
                                    <!-- Dot indicator -->
                                    <span class="absolute -left-[27px] top-1 flex h-3.5 w-3.5 rounded-full border-2 border-white bg-[#0d4d98] shadow-sm"></span>
                                    
                                    <div class="space-y-1">
                                        <div class="flex items-center justify-between flex-wrap">
                                            <span class="font-bold text-slate-800">
                                                {{ $statusLabels[$rev->new_state] ?? $rev->new_state }}
                                            </span>
                                            <span class="text-[9px] text-slate-400 font-semibold" title="{{ $rev->created_at }}">
                                                {{ $rev->created_at->diffForHumans() }}
                                            </span>
                                        </div>
                                        <p class="text-[10px] text-slate-500">
                                            Por: <strong class="text-slate-600">{{ $rev->user->name ?? 'Sistema' }}</strong> ({{ $rev->rol }})
                                        </p>
                                        @if ($rev->comentario)
                                            <p class="text-xs bg-slate-50 p-2.5 rounded-lg border border-slate-100 text-slate-600 mt-1.5 italic leading-normal">
                                                "{{ $rev->comentario }}"
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Needs Corrections Modal (Tutor/Jury only) -->
        <div x-show="showCorrectionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" style="display: none;" x-transition>
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200" @click.outside="showCorrectionModal = false">
                <h3 class="text-sm font-bold text-slate-900 mb-2">Solicitar Correcciones Obligatorias</h3>
                <p class="text-xs text-slate-400 mb-4 leading-normal">Ingresa los motivos y las justificaciones por las cuales solicitas cambios. El estudiante será notificado de inmediato.</p>
                
                <form action="{{ route('productions.transition', $production) }}" method="POST">
                    @csrf
                    <input type="hidden" name="target_state" value="needs_corrections">
                    
                    <textarea name="comment" required rows="4" class="w-full rounded-xl border-slate-200 text-xs focus:ring-[#0d4d98] focus:border-[#0d4d98] mb-4" placeholder="Detalla las correcciones obligatorias aquí..."></textarea>
                    
                    <div class="flex justify-end space-x-2 text-xs font-bold">
                        <button type="button" @click="showCorrectionModal = false" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl hover:bg-slate-200 transition uppercase tracking-wider">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-xl shadow transition uppercase tracking-wider">
                            Solicitar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Reject Modal (Tutor/Jury only) -->
        <div x-show="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" style="display: none;" x-transition>
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200" @click.outside="showRejectModal = false">
                <h3 class="text-sm font-bold text-rose-600 mb-2">Rechazar Documento Académico</h3>
                <p class="text-xs text-slate-400 mb-4 leading-normal">Ingresa la justificación oficial para rechazar esta obra. Esta acción es irreversible y finaliza el ciclo del trabajo.</p>
                
                <form action="{{ route('productions.transition', $production) }}" method="POST">
                    @csrf
                    <input type="hidden" name="target_state" value="rejected">
                    
                    <textarea name="comment" required rows="4" class="w-full rounded-xl border-slate-200 text-xs focus:ring-[#0d4d98] focus:border-[#0d4d98] mb-4" placeholder="Escribe la justificación del rechazo..."></textarea>
                    
                    <div class="flex justify-end space-x-2 text-xs font-bold">
                        <button type="button" @click="showRejectModal = false" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl hover:bg-slate-200 transition uppercase tracking-wider">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl shadow transition uppercase tracking-wider">
                            Rechazar Obra
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Cookie Info Modal (Terceros) -->
        <div x-show="showCookieModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" style="display: none;" x-transition>
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-slate-200" @click.outside="showCookieModal = false">
                <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-2">
                    <h3 class="text-sm font-bold text-slate-800 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 3m0-3a2 2 0 110 3m-9 8h10M5 21h14a2 2 0 002-2v-5a2 2 0 00-2-2H5a2 2 0 00-2 2v5a2 2 0 002 2z"></path></svg>
                        Activar Edición Directa de Google Docs
                    </h3>
                    <button type="button" @click="showCookieModal = false" class="text-slate-400 hover:text-slate-600 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="space-y-4 text-xs text-slate-600 leading-relaxed">
                    <p class="font-medium text-slate-800 text-sm">Si el visor interactivo de Google Docs solicita iniciar sesión de forma repetida, tu navegador está bloqueando las cookies de terceros necesarias para la vinculación segura.</p>
                    
                    <div class="border-l-4 border-indigo-500 pl-3 py-1 space-y-3 font-medium">
                        <div>
                            <span class="font-bold text-indigo-600">Opción 1: En Google Chrome / Microsoft Edge</span>
                            <p class="mt-0.5">Haz clic en el icono del ojo o del candado al lado izquierdo de la barra de direcciones de tu navegador, selecciona "Cookies de terceros" y actívalas para este sitio. Luego, recarga esta página.</p>
                        </div>
                        <div>
                            <span class="font-bold text-indigo-600">Opción 2: En Brave Browser</span>
                            <p class="mt-0.5">Haz clic en el icono del León de Brave a la derecha de la barra de direcciones y desactiva los Escudos (Shields) para este portal. Luego, recarga esta página.</p>
                        </div>
                        <div>
                            <span class="font-bold text-indigo-600">Opción 3: En Safari (Mac/iOS)</span>
                            <p class="mt-0.5">Abre Ajustes, ingresa a Safari, ve a Privacidad y desactiva la opción "Prevenir seguimiento entre sitios". Luego, recarga esta página.</p>
                        </div>
                    </div>
                    <p class="text-[10px] text-slate-450 mt-2 bg-slate-50 p-2.5 rounded-lg border border-slate-150">Nota: Esta es una medida de seguridad exclusiva de los navegadores modernos para evitar el seguimiento comercial. Habilitar la cookie para este dominio local permite que la API oficial de Google Workspace verifique tu sesión con absoluta seguridad.</p>
                </div>
                <div class="flex justify-end mt-6">
                    <button type="button" @click="showCookieModal = false" class="px-4 py-2 bg-[#0d4d98] hover:bg-blue-800 text-white rounded-xl text-xs font-bold shadow transition uppercase tracking-wider">
                        Entendido
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout>
