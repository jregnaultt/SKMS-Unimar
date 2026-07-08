@php
    $user = auth()->user();
    $showCalendarTutorial = false;

    if ($user && $user->hasAnyRole(['Estudiante', 'Tutor', 'Jurado']) && !$user->google_refresh_token) {
        if (!session()->has('has_seen_calendar_tutorial_this_session')) {
            $showCalendarTutorial = true;
            session()->put('has_seen_calendar_tutorial_this_session', true);
        }
    }
@endphp

@if($showCalendarTutorial)
    <div x-data="{ 
            showModal: !localStorage.getItem('skms_hide_cal_tut_' + @js($user->id)),
            currentStep: 1,
            dismissTutorial() {
                localStorage.setItem('skms_hide_cal_tut_' + @js($user->id), 'true');
                this.showModal = false;
            }
         }" 
         x-show="showModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
         style="display: none;">
         
        <!-- Tarjeta del Modal -->
        <div @click.outside="showModal = false"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-4"
             class="bg-white rounded-2xl shadow-xl border border-slate-100 max-w-lg w-full overflow-hidden flex flex-col relative">
             
            <!-- Botón de Cerrar (Equis) -->
            <button @click="showModal = false" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 transition focus:outline-none">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <!-- Encabezado con Indicador de Pasos -->
            <div class="px-6 pt-6 pb-2 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="p-1.5 bg-unimar-blue/10 rounded-lg text-unimar-blue shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </span>
                    <h3 class="text-lg font-bold text-slate-800">Conectar Cuenta de Google</h3>
                </div>
                
                <!-- Indicador visual (Pasos) -->
                <div class="flex items-center space-x-1">
                    <template x-for="i in 3" :key="i">
                        <span :class="currentStep >= i ? 'bg-unimar-blue' : 'bg-slate-200'" class="h-1.5 rounded-full transition-all duration-300" :style="currentStep == i ? 'width: 1.5rem;' : 'width: 0.375rem;'"></span>
                    </template>
                </div>
            </div>

            <!-- Contenido del Paso 1: Introducción -->
            <div x-show="currentStep === 1" class="p-6 flex-1 flex flex-col items-center text-center space-y-4">
                <!-- SVG animado de Conexión -->
                <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center relative shadow-inner">
                    <div class="absolute inset-0 bg-unimar-blue/5 rounded-full animate-ping"></div>
                    <!-- Icono SKMS y Google Services -->
                    <svg class="w-14 h-14 text-unimar-blue" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-.778.099-1.533.284-2.253"/>
                    </svg>
                </div>
                <h4 class="text-base font-bold text-slate-800">¿Por qué conectar tu cuenta?</h4>
                <p class="text-sm text-slate-500 leading-relaxed max-w-sm">
                    Al conectar tu cuenta de Google, agendaremos tus entregas y defensas en tu **Google Calendar** e invitaremos a tus tutores. También activaremos la **sincronización automática de comentarios y observaciones** directamente desde tus documentos vinculados de **Google Docs**.
                </p>
                <div class="w-full bg-slate-50 p-3 rounded-xl border border-slate-100 flex items-center space-x-2 text-left">
                    <span class="text-emerald-500 shrink-0">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </span>
                    <span class="text-xs text-slate-600 font-semibold">¡Todo automático! Olvídate de copiar observaciones o agendar citas a mano.</span>
                </div>
            </div>

            <!-- Contenido del Paso 2: Consentimiento Google Mockup -->
            <div x-show="currentStep === 2" class="p-6 flex-1 flex flex-col items-center text-center space-y-4">
                <h4 class="text-base font-bold text-slate-800">Paso 2: Conceder los permisos de Google</h4>
                <p class="text-sm text-slate-500 leading-relaxed max-w-sm">
                    Durante la vinculación de la cuenta, Google te solicitará permiso para gestionar tu calendario y leer tus documentos.
                </p>
                
                <!-- Mockup CSS/SVG de la pantalla de Google -->
                <div class="w-full max-w-xs border border-slate-200 rounded-xl overflow-hidden shadow-sm bg-white text-left font-sans text-xs">
                    <div class="bg-slate-50 p-2.5 border-b border-slate-200 flex items-center justify-between">
                        <div class="flex items-center space-x-1.5">
                            <span class="w-2 h-2 rounded-full bg-red-400"></span>
                            <span class="w-2 h-2 rounded-full bg-yellow-400"></span>
                            <span class="w-2 h-2 rounded-full bg-green-400"></span>
                        </div>
                        <span class="text-xs text-slate-400">accounts.google.com</span>
                    </div>
                    <div class="p-4 space-y-3">
                        <div class="flex items-center space-x-2">
                            <!-- Logo Google ficticio -->
                            <span class="w-5 h-5 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold text-xs">G</span>
                            <span class="font-bold text-slate-700">Acceder con Google</span>
                        </div>
                        <p class="text-xs text-slate-500">SKMS Unimar quiere acceder a tu Cuenta de Google:</p>
                        
                        <!-- Permiso Caja 1 -->
                        <div class="p-2 bg-blue-50/50 border border-blue-100 rounded-lg flex items-start space-x-2">
                            <input type="checkbox" checked disabled class="mt-0.5 rounded text-blue-600 focus:ring-0">
                            <div class="space-y-0.5">
                                <p class="font-bold text-slate-800 text-xs">Ver y editar eventos de tu calendario</p>
                                <p class="text-[9px] text-slate-400 leading-tight">Agendar las entregas automáticamente.</p>
                            </div>
                        </div>

                        <!-- Permiso Caja 2 -->
                        <div class="p-2 bg-blue-50/50 border border-blue-100 rounded-lg flex items-start space-x-2">
                            <input type="checkbox" checked disabled class="mt-0.5 rounded text-blue-600 focus:ring-0">
                            <div class="space-y-0.5">
                                <p class="font-bold text-slate-800 text-xs">Ver tus archivos de Google Drive</p>
                                <p class="text-[9px] text-slate-400 leading-tight">Extraer tus tesis y comentarios de Google Docs.</p>
                            </div>
                        </div>

                        <!-- Botones de Google Mockup -->
                        <div class="flex justify-end space-x-2 pt-2">
                            <span class="px-2.5 py-1 text-xs font-bold text-slate-450 border border-slate-250 rounded cursor-default">Cancelar</span>
                            <span class="px-2.5 py-1 text-xs font-bold text-white bg-blue-600 rounded shadow-sm relative cursor-default">
                                Continuar
                                <!-- Resaltado / Indicador animado -->
                                <span class="absolute -bottom-1 -right-1 flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenido del Paso 3: Conectar ahora -->
            <div x-show="currentStep === 3" class="p-6 flex-1 flex flex-col items-center text-center space-y-4">
                <div class="w-20 h-20 bg-emerald-50 rounded-full flex items-center justify-center text-emerald-500 shadow-inner">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h4 class="text-base font-bold text-slate-800">¡Todo listo para conectar!</h4>
                <p class="text-sm text-slate-500 leading-relaxed max-w-sm">
                    Al presionar el botón de abajo, serás redirigido de forma segura a Google para iniciar la sincronización. ¡No te tomará más de un minuto!
                </p>
                <div class="w-full pt-2">
                    <a href="{{ route('google.redirect') }}" 
                       class="w-full flex items-center justify-center px-4 py-3 bg-unimar-blue hover:bg-unimar-blue/95 text-white font-bold rounded-xl shadow-md hover:shadow transition-all space-x-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12.24 10.285V14.4h6.887c-.648 2.41-2.519 4.114-6.887 4.114-4.68 0-8.472-3.84-8.472-8.5s3.792-8.5 8.472-8.5c2.17 0 4.015.772 5.485 2.146l3.007-3.007C18.66.772 15.658 0 12.24 0 5.58 0 0 5.37 0 12s5.58 12 12.24 12c6.96 0 11.57-4.89 11.57-11.79 0-.795-.085-1.57-.24-2.285H12.24z"/>
                        </svg>
                        <span>Vincular mi Cuenta de Google</span>
                    </a>
                </div>
            </div>

            <!-- Footer con Botones de Navegación -->
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                <div>
                    <!-- Botón No volver a mostrar -->
                    <button @click="dismissTutorial()" class="text-sm text-slate-450 hover:text-slate-650 font-bold underline transition focus:outline-none">
                        No volver a mostrar
                    </button>
                </div>
                
                <div class="flex items-center space-x-2">
                    <!-- Botón Anterior -->
                    <button x-show="currentStep > 1" 
                            @click="currentStep--" 
                            class="px-4 py-2 border border-slate-200 text-slate-600 hover:bg-slate-100 text-sm font-semibold rounded-lg transition focus:outline-none">
                        Atrás
                    </button>
                    
                    <!-- Botón Siguiente -->
                    <button x-show="currentStep < 3" 
                            @click="currentStep++" 
                            class="px-4 py-2 bg-slate-800 hover:bg-slate-750 text-white text-sm font-semibold rounded-lg transition focus:outline-none">
                        Siguiente
                    </button>
                </div>
            </div>

        </div>
    </div>
@endif
