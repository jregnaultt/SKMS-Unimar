@php
    $myProductions = $data['myProductions'] ?? collect();
    $suggestedProductions = $data['suggestedProductions'] ?? collect();
    $activeProduction = $data['activeProduction'] ?? null;
    $progressData = $data['progressData'] ?? [];
    $progressPercentage = $progressData['progress_percentage'] ?? 0;
    $milestones = $progressData['milestones'] ?? collect();
    $commentsSummary = $progressData['comments_summary'] ?? ['pending' => 0, 'in_progress' => 0, 'addressed' => 0];
    $versionHistory = $progressData['version_history'] ?? collect();
    $timeline = $progressData['timeline'] ?? collect();
    $comments = $progressData['comments'] ?? collect();
@endphp

<div class="space-y-6">
    <!-- Suggested Productions (Sugerencias de vinculación) -->
    @if ($suggestedProductions->isNotEmpty())
        <div class="bg-gradient-to-r from-blue-500/10 via-indigo-500/5 to-transparent border border-blue-100 rounded-2xl p-6 shadow-sm">
            <div class="flex items-center space-x-2.5 mb-4">
                <span class="flex h-2.5 w-2.5 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-blue-500"></span>
                </span>
                <h3 class="text-base font-bold text-slate-800">Trabajos científicos sugeridos</h3>
            </div>
            <p class="text-xs text-slate-500 mb-4">
                Hemos encontrado tesis históricas que podrían ser tuyas. Reclama tu vinculación oficial para que aparezcan en tu panel de control.
            </p>
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($suggestedProductions as $prod)
                    <div class="bg-white border border-slate-100 rounded-xl p-5 shadow-[0_4px_20px_rgba(13,77,152,0.02)] flex flex-col justify-between hover:-translate-y-0.5 hover:shadow-md transition-all duration-200">
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full bg-slate-100 text-slate-600 uppercase tracking-wider">
                                    {{ $prod->productionType->name ?? 'Tesis' }}
                                </span>
                                <span class="text-[10px] text-slate-400 font-semibold">
                                    {{ $prod->academicPeriod->name ?? '' }}
                                </span>
                            </div>
                            <h4 class="text-xs font-bold text-slate-800 line-clamp-2 mb-3">{{ $prod->title }}</h4>
                            <div class="space-y-1 text-[11px] text-slate-500">
                                <p>Autores: <strong class="text-slate-700">{{ $prod->authors }}</strong></p>
                                <p>Tutor: <strong class="text-slate-700">{{ $prod->tutor }}</strong></p>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                            <form action="{{ route('claims.store') }}" method="POST" onsubmit="return confirm('¿Reclamar autoría como estudiante de este trabajo?')">
                                @csrf
                                <input type="hidden" name="production_id" value="{{ $prod->id }}">
                                <input type="hidden" name="role" value="author">
                                <button type="submit" class="px-3 py-1.5 bg-[#0d4d98] hover:bg-[#0b3d78] text-white rounded-lg text-[10px] font-bold transition">
                                    Reclamar Autoría
                                </button>
                            </form>
                            <form action="{{ route('claims.store') }}" method="POST" onsubmit="return confirm('¿Reclamar rol de tutor de este trabajo?')">
                                @csrf
                                <input type="hidden" name="production_id" value="{{ $prod->id }}">
                                <input type="hidden" name="role" value="tutor">
                                <button type="submit" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[10px] font-bold transition">
                                    Reclamar Tutoría
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Active Production Details -->
    @if ($activeProduction)
        <!-- Title and General Info -->
        <div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-[0_10px_30px_rgba(13,77,152,0.03)] flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="flex flex-wrap gap-2 items-center">
                    <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full bg-[#0d4d98]/10 text-[#0d4d98] uppercase tracking-wider">
                        {{ $activeProduction->productionType->name ?? 'Tesis' }}
                    </span>
                    <span class="text-xs text-slate-400 font-semibold">•</span>
                    <span class="text-xs text-slate-500 font-medium">
                        {{ $activeProduction->academicProgram->name ?? '' }}
                    </span>
                </div>
                <h3 class="text-lg font-extrabold text-slate-800 leading-tight">
                    {{ $activeProduction->title }}
                </h3>
                <p class="text-xs text-slate-500">
                    Tutor asignado: <strong class="text-slate-700">{{ $activeProduction->tutor ?? 'No especificado' }}</strong>
                </p>
            </div>

            <!-- Workflow State Badge -->
            <div class="shrink-0 flex flex-col items-start md:items-end space-y-1.5">
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Estado actual</p>
                @php
                    $statusColors = [
                        'draft' => 'bg-slate-100 text-slate-800 border-slate-200',
                        'under_review' => 'bg-amber-50 text-amber-800 border-amber-200/60',
                        'needs_corrections' => 'bg-orange-50 text-orange-800 border-orange-200/60',
                        'approved' => 'bg-emerald-50 text-emerald-800 border-emerald-200/60',
                        'published' => 'bg-blue-50 text-blue-800 border-blue-200/60',
                        'rejected' => 'bg-rose-50 text-rose-800 border-rose-200/60',
                    ];
                    $statusLabels = [
                        'draft' => 'Borrador',
                        'under_review' => 'En Revisión',
                        'needs_corrections' => 'Requiere Correcciones',
                        'approved' => 'Aprobado',
                        'published' => 'Publicado',
                        'rejected' => 'Rechazado',
                    ];
                    $colorClass = $statusColors[$activeProduction->workflow_state] ?? 'bg-slate-100 text-slate-800';
                    $label = $statusLabels[$activeProduction->workflow_state] ?? $activeProduction->workflow_state;
                @endphp
                <span class="px-3.5 py-1.5 inline-flex text-xs leading-5 font-extrabold rounded-full border {{ $colorClass }}">
                    {{ $label }}
                </span>
            </div>
        </div>

        <!-- Línea de Tiempo del Flujo (Workflow Timeline) -->
        <div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-[0_10px_30px_rgba(13,77,152,0.03)]">
            <h4 class="text-xs font-extrabold text-slate-500 uppercase tracking-wider mb-6">Línea de Tiempo del Proceso</h4>
            
            <div class="relative flex flex-col md:flex-row items-center justify-between gap-8 md:gap-0">
                <!-- Línea conectora de fondo -->
                <div class="absolute left-1/2 md:left-0 md:right-0 top-0 bottom-0 md:bottom-auto md:top-1/2 -translate-x-1/2 md:translate-x-0 h-full md:h-1 bg-slate-100 w-1 md:w-full -z-0"></div>

                @php
                    $states = [
                        ['state' => 'draft', 'label' => 'Borrador', 'step' => 1],
                        ['state' => 'under_review', 'label' => 'En Revisión', 'step' => 2],
                        ['state' => 'needs_corrections', 'label' => 'Correcciones', 'step' => 3],
                        ['state' => 'approved', 'label' => 'Aprobado', 'step' => 4],
                        ['state' => 'published', 'label' => 'Publicado', 'step' => 5]
                    ];
                    $currentStateIndex = 0;
                    foreach ($states as $index => $s) {
                        if ($activeProduction->workflow_state === $s['state']) {
                            $currentStateIndex = $index;
                        }
                    }
                    if ($activeProduction->workflow_state === 'rejected') {
                        $currentStateIndex = -1; // Special rejected state
                    }
                @endphp

                @foreach ($states as $index => $s)
                    @php
                        $isCurrent = $activeProduction->workflow_state === $s['state'];
                        $isCompleted = $currentStateIndex > $index;
                        
                        // Icon & circle color classes
                        if ($isCurrent) {
                            $circleClass = 'bg-[#F5B800] text-slate-900 ring-4 ring-[#F5B800]/20 font-bold';
                        } elseif ($isCompleted) {
                            $circleClass = 'bg-emerald-500 text-white';
                        } else {
                            $circleClass = 'bg-slate-100 text-slate-400';
                        }
                    @endphp
                    <div class="flex flex-col items-center z-10 w-full md:w-1/5 text-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-300 {{ $circleClass }}">
                            @if ($isCompleted)
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                </svg>
                            @else
                                {{ $s['step'] }}
                            @endif
                        </div>
                        <span class="text-xs font-bold mt-2.5 {{ $isCurrent ? 'text-slate-800' : 'text-slate-400' }}">
                            {{ $s['label'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Main Dashboard Split Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left Column: Buzón de Observaciones (2/3 width) -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-[0_10px_30px_rgba(13,77,152,0.03)]">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h4 class="text-sm font-extrabold text-slate-800">Buzón de Observaciones</h4>
                            <p class="text-[10px] text-slate-400 font-semibold mt-0.5 uppercase tracking-wider">Revisiones y correcciones metodológicas</p>
                        </div>
                        <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-orange-50 text-orange-700">
                            {{ $commentsSummary['pending'] + $commentsSummary['in_progress'] }} Pendientes
                        </span>
                    </div>

                    @if ($comments->isEmpty())
                        <div class="text-center py-12 border-2 border-dashed border-slate-100 rounded-xl">
                            <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                            <h5 class="mt-2 text-xs font-bold text-slate-700">Sin observaciones registradas</h5>
                            <p class="mt-1 text-[11px] text-slate-400">Tu tutor o jurado aún no han registrado observaciones sobre esta versión.</p>
                        </div>
                    @else
                        <div class="space-y-6">
                            @foreach ($comments as $comment)
                                <div class="p-5 border border-slate-100 rounded-xl bg-slate-50/50 space-y-4 hover:-translate-y-0.5 hover:shadow-sm transition-all duration-200">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-[10px] bg-slate-200 text-slate-800 px-2 py-0.5 rounded font-bold font-mono">
                                                {{ $comment->reference_section ?? 'General' }}
                                            </span>
                                            <span class="text-xs text-slate-400">•</span>
                                            <span class="text-xs font-bold text-slate-700">{{ $comment->user->name }}</span>
                                        </div>
                                        
                                        <!-- Interactive Status Selector Form -->
                                        <div class="flex items-center space-x-2">
                                            <span class="text-[10px] text-slate-400 font-semibold">Avance:</span>
                                            <form action="{{ route('comments.update-status', $comment) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <select name="status" onchange="this.form.submit()" class="text-[11px] font-bold rounded-lg border-slate-200 py-0.5 px-2 pr-7 text-slate-700 focus:ring-[#0d4d98] focus:border-[#0d4d98] cursor-pointer bg-white">
                                                    <option value="pending" {{ $comment->status === 'pending' ? 'selected' : '' }}>Pendiente</option>
                                                    <option value="in_progress" {{ $comment->status === 'in_progress' ? 'selected' : '' }}>En Progreso</option>
                                                    <option value="addressed" {{ $comment->status === 'addressed' ? 'selected' : '' }}>Subsanado</option>
                                                </select>
                                            </form>
                                        </div>
                                    </div>

                                    <p class="text-xs text-slate-600 leading-relaxed">
                                        {{ $comment->content }}
                                    </p>

                                    <!-- Replies List -->
                                    @if ($comment->replies->isNotEmpty())
                                        <div class="pl-4 border-l-2 border-slate-250 space-y-3 pt-2">
                                            @foreach ($comment->replies as $reply)
                                                <div class="text-xs">
                                                    <p class="font-bold text-slate-700">{{ $reply->user->name }}:</p>
                                                    <p class="text-slate-500 leading-normal mt-0.5">{{ $reply->content }}</p>
                                                    <p class="text-[9px] text-slate-400 font-semibold mt-0.5">{{ $reply->created_at->diffForHumans() }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    <!-- Quick Reply Form -->
                                    <form action="{{ route('comments.reply', $comment) }}" method="POST" class="flex gap-2 pt-2">
                                        @csrf
                                        <input type="text" name="content" placeholder="Responder a esta observación..." required class="flex-1 text-xs rounded-xl border-slate-200 px-3.5 py-2 focus:ring-[#0d4d98] focus:border-[#0d4d98]">
                                        <button type="submit" class="px-3 py-2 bg-slate-100 hover:bg-[#0d4d98] hover:text-white text-slate-600 rounded-xl text-xs font-bold transition">
                                            Responder
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Complete Version History Card -->
                <div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-[0_10px_30px_rgba(13,77,152,0.03)]">
                    <h4 class="text-sm font-extrabold text-slate-800 mb-6">Historial de Versiones</h4>
                    <div class="space-y-4">
                        @foreach ($versionHistory as $version)
                            <div class="flex items-start justify-between p-4 border border-slate-100 rounded-xl hover:bg-slate-50/30 transition">
                                <div class="flex items-start space-x-3">
                                    <div class="p-2 bg-slate-100 rounded-lg text-slate-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="flex items-center space-x-2">
                                            <h5 class="text-xs font-bold text-slate-800">Versión {{ $version->version_number }}</h5>
                                            <span class="text-[9px] text-slate-400 font-semibold">• {{ $version->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-1">
                                            {{ $version->changelog ?? 'Sin descripción de cambios.' }}
                                        </p>
                                    </div>
                                </div>
                                
                                <a href="{{ route('versions.document', $version) }}" target="_blank" class="px-2.5 py-1.5 bg-slate-50 border border-slate-200/60 hover:bg-[#0d4d98] hover:text-white rounded-lg text-[10px] font-bold transition text-slate-700">
                                    Ver Documento
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Right Column: Estado de Entrega y Carga de PDF (1/3 width) -->
            <div class="space-y-6">
                <!-- Correction Progress Chart -->
                <div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-[0_10px_30px_rgba(13,77,152,0.03)] text-center space-y-4">
                    <h4 class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Avance del Proyecto</h4>
                    
                    <!-- Circular progress bar via inline CSS/SVG -->
                    <div class="relative w-36 h-36 mx-auto flex items-center justify-center">
                        <svg class="w-full h-full transform -rotate-95" viewBox="0 0 36 36">
                            <path class="text-slate-100" stroke-width="3" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <path class="text-[#0d4d98] transition-all duration-500 ease-out" stroke-dasharray="{{ $progressPercentage }}, 100" stroke-width="3" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        </svg>
                        <div class="absolute flex flex-col items-center">
                            <span class="text-2xl font-extrabold text-slate-800">{{ $progressPercentage }}%</span>
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">Completado</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2 pt-2 border-t border-slate-150 text-center">
                        <div>
                            <span class="block text-sm font-bold text-slate-800">{{ $commentsSummary['pending'] }}</span>
                            <span class="text-[9px] text-slate-400 font-semibold uppercase">Pendientes</span>
                        </div>
                        <div>
                            <span class="block text-sm font-bold text-[#F5B800]">{{ $commentsSummary['in_progress'] }}</span>
                            <span class="text-[9px] text-slate-400 font-semibold uppercase">En Proceso</span>
                        </div>
                        <div>
                            <span class="block text-sm font-bold text-emerald-500">{{ $commentsSummary['addressed'] }}</span>
                            <span class="text-[9px] text-slate-400 font-semibold uppercase">Listas</span>
                        </div>
                    </div>
                </div>

                <!-- Carga de Nueva Versión (PDF) Card -->
                <div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-[0_10px_30px_rgba(13,77,152,0.03)] space-y-4" x-data="{ open: false }">
                    <h4 class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Entregas y Documentos</h4>
                    
                    <div class="p-4 bg-amber-50 border border-amber-200/50 rounded-xl flex items-start space-x-3">
                        <div class="p-1 bg-[#F5B800]/20 text-[#F5B800] rounded-lg mt-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div>
                            <h5 class="text-xs font-bold text-slate-800">Fecha límite cercana</h5>
                            <p class="text-[10px] text-slate-500 mt-0.5 leading-normal">
                                Tu tutor espera la siguiente revisión. Sube la versión con las correcciones subsanadas.
                            </p>
                        </div>
                    </div>

                    @if ($activeProduction->workflow_state === 'needs_corrections' || $activeProduction->workflow_state === 'draft')
                        <button @click="open = true" class="w-full flex items-center justify-center space-x-2 px-4 py-3 bg-[#0d4d98] hover:bg-[#0b3d78] text-white rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition-all duration-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                            </svg>
                            <span>Subir Nueva Versión (PDF)</span>
                        </button>
                    @else
                        <button disabled class="w-full flex items-center justify-center space-x-2 px-4 py-3 bg-slate-100 text-slate-400 rounded-xl text-xs font-bold border border-slate-200 cursor-not-allowed">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            <span>En revisión / Aprobado</span>
                        </button>
                    @endif

                    <!-- Modal de Carga de Nueva Versión (Alpine.js) -->
                    <div x-show="open" class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm flex items-center justify-center z-50 p-4" style="display: none;" x-transition>
                        <div @click.outside="open = false" class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl border border-slate-150 space-y-6"
                             x-data="{
                                 file: null,
                                 isUploading: false,
                                 uploadProgress: 0,
                                 statusMessage: '',
                                 fileId: '',
                                 changelog: '',
                                 handleFileSelect(event) {
                                     this.file = event.target.files[0];
                                     if (this.file) {
                                         this.upload();
                                     }
                                 },
                                 async upload() {
                                     this.isUploading = true;
                                     this.statusMessage = 'Subiendo archivo y extrayendo metadatos...';
                                     this.uploadProgress = 0;
                                     
                                     let formData = new FormData();
                                     formData.append('documento', this.file);
                                     
                                     try {
                                         let response = await axios.post('{{ route('productions.extract') }}', formData, {
                                             headers: { 'Content-Type': 'multipart/form-data' },
                                             onUploadProgress: (progressEvent) => {
                                                 this.uploadProgress = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                                             }
                                         });
                                         this.statusMessage = '¡Archivo subido correctamente!';
                                         this.fileId = response.data.file_id;
                                         this.isUploading = false;
                                     } catch (error) {
                                         this.isUploading = false;
                                         this.statusMessage = 'Error al procesar el archivo.';
                                         console.error(error);
                                     }
                                 },
                                 submitForm(e) {
                                     if (!this.fileId) {
                                         e.preventDefault();
                                         alert('Por favor, selecciona y sube un archivo PDF o Word primero.');
                                         return false;
                                     }
                                     if (!this.changelog.trim()) {
                                         e.preventDefault();
                                         alert('Por favor, describe las correcciones aplicadas.');
                                         return false;
                                     }
                                     return true;
                                 }
                             }">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                                <h5 class="text-sm font-extrabold text-slate-800">Cargar Corrección Científica</h5>
                                <button @click="open = false" class="text-slate-400 hover:text-slate-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>

                            <form action="{{ route('productions.transition', $activeProduction) }}" method="POST" @submit="submitForm($event)" class="space-y-4">
                                @csrf
                                <input type="hidden" name="target_state" value="under_review">
                                <input type="hidden" name="file_id" :value="fileId">

                                <!-- Subidor de Archivo -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-slate-500 uppercase">Documento corregido (PDF / DOCX)</label>
                                    <label class="flex flex-col items-center justify-center w-full h-32 px-4 transition bg-slate-50 border-2 border-slate-250 border-dashed rounded-xl cursor-pointer hover:border-[#0d4d98] hover:bg-slate-100/55"
                                           :class="{'border-[#0d4d98] bg-slate-100': isUploading}">
                                        <div class="flex flex-col items-center justify-center space-y-1.5 text-center">
                                            <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                            </svg>
                                            <span class="text-xs font-semibold text-slate-500" x-text="file ? file.name : 'Arrastra o selecciona el documento corregido'"></span>
                                        </div>
                                        <input type="file" class="hidden" accept="application/pdf, application/vnd.openxmlformats-officedocument.wordprocessingml.document" @change="handleFileSelect">
                                    </label>
                                </div>

                                <!-- Barra de Progreso -->
                                <div x-show="isUploading || statusMessage" class="space-y-1">
                                    <div class="flex justify-between text-[10px] font-bold text-slate-600">
                                        <span x-text="statusMessage"></span>
                                        <span x-show="isUploading" x-text="uploadProgress + '%'"></span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-1.5" x-show="isUploading">
                                        <div class="bg-[#0d4d98] h-1.5 rounded-full transition-all duration-250" :style="'width: ' + uploadProgress + '%'"></div>
                                    </div>
                                </div>

                                <!-- Registro de Cambios (Changelog) -->
                                <div class="space-y-1.5">
                                    <label for="changelog" class="block text-xs font-bold text-slate-500 uppercase">Cambios realizados (Changelog)</label>
                                    <textarea id="changelog" name="changelog" x-model="changelog" rows="3" placeholder="Ej. Se corrigió el capítulo III y se actualizaron las referencias bibliográficas según las observaciones de tutor..." required class="w-full text-xs rounded-xl border-slate-200 focus:ring-[#0d4d98] focus:border-[#0d4d98]"></textarea>
                                </div>

                                <!-- Botones Acción -->
                                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                                    <button type="button" @click="open = false" class="px-4 py-2 text-xs font-bold text-slate-500 hover:bg-slate-50 rounded-xl transition">
                                        Cancelar
                                    </button>
                                    <button type="submit" :disabled="isUploading || !fileId" class="px-4 py-2 bg-[#0d4d98] hover:bg-[#0b3d78] disabled:bg-slate-300 disabled:cursor-not-allowed text-white rounded-xl text-xs font-bold shadow transition">
                                        Enviar a Revisión
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    @else
        <!-- No productions yet -->
        <div class="bg-white border border-slate-100 rounded-2xl p-10 shadow-[0_10px_30px_rgba(13,77,152,0.03)] text-center max-w-xl mx-auto space-y-4">
            <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
            </div>
            <h4 class="text-sm font-extrabold text-slate-850">No tienes producciones científicas registradas</h4>
            <p class="text-xs text-slate-500 leading-relaxed">
                Comienza subiendo tu primer trabajo de grado o de investigación al sistema para que tu tutor pueda iniciar el proceso de revisión y corrección metodológica.
            </p>
            <a href="{{ route('productions.create') }}" class="inline-flex items-center space-x-2 px-4 py-2.5 bg-[#0d4d98] hover:bg-[#0b3d78] text-white rounded-xl text-xs font-bold shadow transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Subir Trabajo Científico</span>
            </a>
        </div>
    @endif
</div>
