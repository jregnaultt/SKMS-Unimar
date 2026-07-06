@php
    $user = auth()->user();
    $userRoles = $user->roles->pluck('name')->toArray();
    $activeRole = session('active_dashboard_role') ?? ($userRoles[0] ?? 'Estudiante');
@endphp

<x-dashboard-layout :roles="$userRoles" :activeRole="$activeRole">
    <!-- Load Google Identity Services script for sync -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>

    <div x-data="{
        googleDriveFileId: '{{ $production->google_drive_file_id }}',
        googleDocumentTitle: '{{ $production->google_document_title }}',
        isSyncing: false,
        statusMessage: '',
        clientId: '{{ config('services.google.client_id') }}',
        scope: ['https://www.googleapis.com/auth/drive'],

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
                            alert('¡Sincronización exitosa! Los cambios han sido importados correctamente al sistema.');
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
        }
    }" class="py-6 space-y-6 flex flex-col h-[calc(100vh-6rem)]">

        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200/60 pb-5 shrink-0">
            <div class="flex items-center space-x-3">
                <a href="{{ route('dashboard') }}" class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition duration-150" title="Regresar al Dashboard">
                    <svg aria-hidden="true" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h2 class="font-extrabold text-2xl text-slate-800 leading-tight">
                        Editor de Documento SKMS
                    </h2>
                    <p class="text-sm text-slate-550 font-bold uppercase tracking-wider mt-0.5">
                        Edición en vivo del trabajo de grado vinculado en Google Docs
                    </p>
                </div>
            </div>

            <!-- Sync Controls -->
            <div class="flex items-center space-x-3">
                <button type="button" @click="syncGoogleDocs()" :disabled="isSyncing" class="inline-flex items-center px-4 py-2.5 bg-[#0d4d98] hover:bg-[#0b3d78] text-white rounded-xl text-sm font-bold uppercase tracking-wider transition shadow-sm hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed h-11">
                    <svg aria-hidden="true" class="w-4 h-4 mr-2 shrink-0" :class="isSyncing ? 'animate-spin' : ''" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89H18" />
                    </svg>
                    <span x-text="isSyncing ? 'Sincronizando...' : 'Sincronizar Cambios'"></span>
                </button>
            </div>
        </div>

        <!-- Sync status notification banner -->
        <div x-show="statusMessage" x-transition class="p-4 bg-blue-50 border border-blue-200 text-[#0d4d98] rounded-xl flex items-center space-x-3 shrink-0" style="display: none;">
            <svg class="animate-spin h-5 w-5 text-[#0d4d98]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm font-bold" x-text="statusMessage"></span>
        </div>

        <!-- Document Details Information Banner -->
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between shrink-0">
            <div class="flex items-center space-x-3 text-sm text-slate-700">
                <svg aria-hidden="true" class="w-5 h-5 text-blue-650 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                </svg>
                <span>Documento: <strong class="text-slate-900" x-text="googleDocumentTitle"></strong></span>
            </div>
            <button type="button" @click="openGoogleDocsEditor()" class="text-sm text-unimar-blue hover:text-[#0b3d78] font-bold uppercase transition">
                Abrir en Google Docs original ↗
            </button>
        </div>

        <!-- Embed Editor Frame -->
        <div class="flex-1 bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm relative min-h-[450px]">
            <iframe :src="'https://docs.google.com/document/d/' + googleDriveFileId + '/edit?embedded=true'" class="absolute inset-0 w-full h-full border-none" allow="fullscreen"></iframe>
        </div>

    </div>
</x-dashboard-layout>
