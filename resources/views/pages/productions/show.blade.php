<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center text-xs font-semibold text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400 mb-2 transition duration-150">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Volver al Dashboard
                </a>
                <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100 leading-tight">
                    Detalles de la Producción Científica
                </h2>
            </div>
            
            @php
                $statusColors = [
                    'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-700/60 dark:text-gray-300 border border-gray-200 dark:border-gray-600',
                    'under_review' => 'bg-yellow-50 text-yellow-800 dark:bg-yellow-950/20 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-900/50',
                    'needs_corrections' => 'bg-orange-50 text-orange-800 dark:bg-orange-950/20 dark:text-orange-300 border border-orange-200 dark:border-orange-900/50',
                    'approved' => 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/20 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-900/50',
                    'published' => 'bg-blue-50 text-blue-800 dark:bg-blue-950/20 dark:text-blue-300 border border-blue-200 dark:border-blue-900/50',
                    'rejected' => 'bg-rose-50 text-rose-800 dark:bg-rose-950/20 dark:text-rose-300 border border-rose-200 dark:border-rose-900/50',
                ];
                $statusLabels = [
                    'draft' => 'Borrador',
                    'under_review' => 'En Revisión',
                    'needs_corrections' => 'Requiere Correcciones',
                    'approved' => 'Aprobado',
                    'published' => 'Publicado',
                    'rejected' => 'Rechazado',
                ];
                $colorClass = $statusColors[$production->workflow_state] ?? 'bg-gray-100 text-gray-800';
                $label = $statusLabels[$production->workflow_state] ?? $production->workflow_state;
            @endphp
            
            <span class="px-4 py-1.5 inline-flex text-sm leading-5 font-bold rounded-full {{ $colorClass }}">
                {{ $label }}
            </span>
        </div>
    </x-slot>

    <div class="py-12" x-data="{ 
        activePdfUrl: '{{ route('productions.document', $production) }}',
        activeVersionNumber: 'Actual',
        showCorrectionModal: false,
        showRejectModal: false,
        actionComment: '',
        isUploading: false,
        uploadProgress: 0,
        statusMessage: '',
        fileId: '',
        changelog: '',

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
                    this.statusMessage = '¡Documento listo para reenviar!';
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
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

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

            @if ($errors->any())
                <div class="p-4 bg-rose-50 dark:bg-rose-950/20 border-l-4 border-rose-500 rounded-r-lg shadow-sm text-rose-800 dark:text-rose-300">
                    <div class="font-semibold text-sm mb-2">Por favor, corrige los siguientes errores:</div>
                    <ul class="list-disc list-inside text-xs space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Left Column: Visor PDF (takes 2/3 space) -->
                <div class="lg:col-span-2 flex flex-col space-y-4">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col h-[75vh]">
                        <!-- PDF Header (Version Selector) -->
                        <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50/50 dark:bg-gray-800/50 rounded-t-2xl">
                            <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 flex items-center">
                                <svg class="w-4 h-4 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                Documento PDF: <span class="ml-1 text-blue-600 dark:text-blue-400 font-semibold" x-text="activeVersionNumber"></span>
                            </h3>

                            @if ($versions->isNotEmpty())
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Ver versión:</span>
                                    <div class="inline-flex rounded-lg shadow-sm">
                                        <button type="button" 
                                                class="px-2.5 py-1 text-xs font-semibold rounded-l-lg border transition duration-150"
                                                :class="activeVersionNumber === 'Actual' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600'"
                                                @click="activePdfUrl = '{{ route('productions.document', $production) }}'; activeVersionNumber = 'Actual'">
                                            Actual
                                        </button>
                                        @foreach ($versions as $ver)
                                            <button type="button" 
                                                    class="px-2.5 py-1 text-xs font-semibold border-t border-b border-r transition duration-150 last:rounded-r-lg"
                                                    :class="activeVersionNumber == 'v{{ $ver->version_number }}' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600'"
                                                    @click="activePdfUrl = '{{ route('versions.document', $ver) }}'; activeVersionNumber = 'v{{ $ver->version_number }}'">
                                                v{{ $ver->version_number }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Embedded PDF Frame -->
                        <div class="flex-1 bg-gray-100 dark:bg-gray-900 rounded-b-2xl relative overflow-hidden">
                            <iframe :src="activePdfUrl" class="w-full h-full border-none" allow="fullscreen"></iframe>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Dublin Core, Timeline, Action Panels (takes 1/3 space) -->
                <div class="space-y-6">

                    <!-- Metadata Details Card (Dublin Core) -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                        <h3 class="text-md font-bold text-gray-900 dark:text-gray-100 border-b border-gray-100 dark:border-gray-700 pb-3 mb-4 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Metadatos Dublin Core
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Título</h4>
                                <p class="text-sm text-gray-900 dark:text-gray-100 font-semibold mt-0.5">{{ $production->title }}</p>
                            </div>

                            <div>
                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Resumen</h4>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 line-clamp-4 hover:line-clamp-none transition duration-150 cursor-pointer" title="Haz clic para expandir">{{ $production->abstract }}</p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Autores</h4>
                                    <p class="text-xs text-gray-900 dark:text-gray-100 font-semibold mt-0.5">{{ $production->authors }}</p>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Tutor</h4>
                                    <p class="text-xs text-gray-900 dark:text-gray-100 font-semibold mt-0.5">{{ $production->tutor }}</p>
                                </div>
                            </div>

                            <div>
                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Programa Académico</h4>
                                <p class="text-xs text-gray-900 dark:text-gray-200 mt-0.5">{{ $production->academicProgram->name ?? 'No especificado' }}</p>
                            </div>

                            <div>
                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Línea de Investigación</h4>
                                <p class="text-xs text-gray-900 dark:text-gray-200 mt-0.5">{{ $production->researchLine->name ?? 'No especificado' }}</p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Tipo</h4>
                                    <p class="text-xs text-gray-900 dark:text-gray-200 mt-0.5">{{ $production->productionType->name ?? 'No especificado' }}</p>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Período</h4>
                                    <p class="text-xs text-gray-900 dark:text-gray-200 mt-0.5">{{ $production->academicPeriod->name ?? 'No especificado' }}</p>
                                </div>
                            </div>

                            <div>
                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Palabras Clave</h4>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @foreach ($production->keywords as $kw)
                                        <span class="px-2 py-0.5 text-[10px] font-semibold rounded bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300 border border-blue-100 dark:border-blue-900/30">
                                            {{ $kw->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Panel Card (Decisiones) -->
                    @php
                        $user = auth()->user();
                        $isAuthor = $production->users()->where('user_id', $user->id)->wherePivot('role', 'author')->exists();
                        $isTutor = $production->users()->where('user_id', $user->id)->wherePivot('role', 'tutor')->exists();
                        $isJury = $production->users()->where('user_id', $user->id)->wherePivot('role', 'jury')->exists();
                        $isCoordinator = $user->hasRole(['Coordinador', 'Super Admin']);
                    @endphp

                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                        <h3 class="text-md font-bold text-gray-900 dark:text-gray-100 border-b border-gray-100 dark:border-gray-700 pb-3 mb-4 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            Panel de Decisiones
                        </h3>

                        <!-- Action: Student submit draft -->
                        @if ($production->workflow_state === 'draft' && ($isAuthor || $isCoordinator))
                            <form action="{{ route('productions.transition', $production) }}" method="POST">
                                @csrf
                                <input type="hidden" name="target_state" value="under_review">
                                <button type="submit" class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow transition duration-150">
                                    Enviar a Revisión Oficial
                                </button>
                            </form>

                        <!-- Action: Tutor / Jury reviews under_review production -->
                        @elseif ($production->workflow_state === 'under_review' && ($isTutor || $isJury || $isCoordinator))
                            <div class="space-y-3">
                                <!-- Approve -->
                                <form action="{{ route('productions.transition', $production) }}" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas APROBAR esta producción científica?')">
                                    @csrf
                                    <input type="hidden" name="target_state" value="approved">
                                    <button type="submit" class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow transition duration-150 flex items-center justify-center">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        Aprobar Documento
                                    </button>
                                </form>

                                <!-- Needs Corrections Trigger -->
                                <button type="button" @click="showCorrectionModal = true" class="w-full py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg text-xs font-semibold shadow transition duration-150 flex items-center justify-center">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    Solicitar Correcciones
                                </button>

                                <!-- Reject Trigger -->
                                <button type="button" @click="showRejectModal = true" class="w-full py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-semibold shadow transition duration-150 flex items-center justify-center">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    Rechazar Documento
                                </button>
                            </div>

                        <!-- Action: Student resubmits after needs_corrections -->
                        @elseif ($production->workflow_state === 'needs_corrections' && ($isAuthor || $isCoordinator))
                            <form action="{{ route('productions.transition', $production) }}" method="POST" class="space-y-4">
                                @csrf
                                <input type="hidden" name="target_state" value="under_review">
                                <input type="hidden" name="file_id" :value="fileId">

                                <div class="bg-gray-50 dark:bg-gray-800/40 p-3 rounded-lg border border-dashed border-gray-300 dark:border-gray-600">
                                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2">Subir Nueva Versión (PDF)</label>
                                    <input type="file" @change="handleFileSelect" class="hidden" id="new-pdf-resubmit" accept="application/pdf">
                                    <label for="new-pdf-resubmit" class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded text-xs font-medium cursor-pointer hover:bg-gray-200 transition duration-150 inline-block">
                                        Seleccionar Archivo
                                    </label>
                                    <p class="text-[10px] text-gray-400 mt-2" x-text="statusMessage || 'Ningún archivo cargado todavía'"></p>
                                    
                                    <div x-show="isUploading" class="w-full bg-gray-200 h-1.5 rounded-full mt-2">
                                        <div class="bg-blue-600 h-1.5 rounded-full transition-all" :style="'width: ' + uploadProgress + '%'"></div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Cambios realizados</label>
                                    <textarea name="changelog" required rows="3" class="w-full rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-xs focus:ring-blue-500" placeholder="Describe brevemente las correcciones aplicadas..."></textarea>
                                </div>

                                <button type="submit" class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow transition duration-150" :disabled="!fileId">
                                    Enviar Correcciones a Revisión
                                </button>
                            </form>

                        <!-- Action: Coordinator publishes approved production -->
                        @elseif ($production->workflow_state === 'approved' && $isCoordinator)
                            <form action="{{ route('productions.transition', $production) }}" method="POST" onsubmit="return confirm('¿Estás seguro de PUBLICAR oficialmente este trabajo? Pasará a ser público en el catálogo e indexado por el OAI-PMH.')">
                                @csrf
                                <input type="hidden" name="target_state" value="published">
                                <button type="submit" class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow transition duration-150 flex items-center justify-center">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path></svg>
                                    Publicar en el Repositorio
                                </button>
                            </form>
                        @else
                            <p class="text-xs text-gray-500 dark:text-gray-400 text-center py-2 italic">No hay acciones disponibles para tu rol en este estado.</p>
                        @endif
                    </div>

                    <!-- Revision History (Timeline) -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                        <h3 class="text-md font-bold text-gray-900 dark:text-gray-100 border-b border-gray-100 dark:border-gray-700 pb-3 mb-4 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Historial de Revisiones
                        </h3>

                        @if ($revisions->isEmpty())
                            <p class="text-xs text-gray-400 dark:text-gray-500 italic text-center">No se registran revisiones para este documento.</p>
                        @else
                            <div class="relative pl-6 border-l border-gray-200 dark:border-gray-700 space-y-6">
                                @foreach ($revisions as $rev)
                                    <div class="relative">
                                        <!-- Timeline dot -->
                                        <span class="absolute -left-[31px] top-1 flex h-4 w-4 rounded-full border-2 border-white dark:border-gray-800 bg-blue-500"></span>
                                        
                                        <div class="space-y-1">
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs font-bold text-gray-900 dark:text-gray-100">
                                                    {{ $statusLabels[$rev->new_state] ?? $rev->new_state }}
                                                </span>
                                                <span class="text-[10px] text-gray-400" title="{{ $rev->created_at }}">
                                                    {{ $rev->created_at->diffForHumans() }}
                                                </span>
                                            </div>
                                            <p class="text-[11px] text-gray-500">
                                                Por: <strong>{{ $rev->user->name ?? 'Usuario de Sistema' }}</strong>
                                            </p>
                                            @if ($rev->comment)
                                                <p class="text-xs bg-gray-50 dark:bg-gray-900/40 p-2 rounded-lg border border-gray-100 dark:border-gray-800 text-gray-600 dark:text-gray-400 mt-1.5 italic">
                                                    "{{ $rev->comment }}"
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
        </div>

        <!-- Needs Corrections Modal -->
        <div x-show="showCorrectionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" style="display: none;" x-transition>
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full p-6 shadow-xl border border-gray-100 dark:border-gray-700" @click.outside="showCorrectionModal = false">
                <h3 class="text-md font-bold text-gray-900 dark:text-gray-100 mb-2">Solicitar Correcciones</h3>
                <p class="text-xs text-gray-500 mb-4">Ingresa las observaciones o cambios solicitados al estudiante de forma clara. Este comentario será enviado como notificación.</p>
                
                <form action="{{ route('productions.transition', $production) }}" method="POST">
                    @csrf
                    <input type="hidden" name="target_state" value="needs_corrections">
                    
                    <textarea name="comment" required rows="4" class="w-full rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-xs focus:ring-blue-500 mb-4" placeholder="Detalla las correcciones obligatorias aquí..."></textarea>
                    
                    <div class="flex justify-end space-x-2">
                        <button type="button" @click="showCorrectionModal = false" class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded text-xs font-semibold">
                            Cancelar
                        </button>
                        <button type="submit" class="px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white rounded text-xs font-semibold shadow">
                            Enviar Observaciones
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Reject Modal -->
        <div x-show="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" style="display: none;" x-transition>
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full p-6 shadow-xl border border-gray-100 dark:border-gray-700" @click.outside="showRejectModal = false">
                <h3 class="text-md font-bold text-gray-900 dark:text-gray-100 mb-2 text-rose-600">Rechazar Documento</h3>
                <p class="text-xs text-gray-500 mb-4">Ingresa el motivo del rechazo del trabajo académico. Esta acción finalizará el ciclo de vida actual del documento.</p>
                
                <form action="{{ route('productions.transition', $production) }}" method="POST">
                    @csrf
                    <input type="hidden" name="target_state" value="rejected">
                    
                    <textarea name="comment" required rows="4" class="w-full rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-xs focus:ring-blue-500 mb-4" placeholder="Escribe la justificación del rechazo..."></textarea>
                    
                    <div class="flex justify-end space-x-2">
                        <button type="button" @click="showRejectModal = false" class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded text-xs font-semibold">
                            Cancelar
                        </button>
                        <button type="submit" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded text-xs font-semibold shadow">
                            Rechazar Documento
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
