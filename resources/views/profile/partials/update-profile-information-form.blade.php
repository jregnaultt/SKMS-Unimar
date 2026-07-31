    <header class="mb-5">
        <h2 class="text-xl font-bold text-slate-800 tracking-tight">
            Información del Perfil
        </h2>
        <p class="text-base text-slate-550 mt-1.5 font-medium">
            Actualiza los datos personales y de contacto asociados a tu cuenta de investigador.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-6">
        @csrf
        @method('patch')

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
             <!-- Columna Izquierda: Tarjeta de Perfil Rápido -->
            <div class="lg:col-span-4 bg-slate-50 border border-slate-200/70 rounded-2xl p-7 flex flex-col items-center text-center shadow-sm">
                <div class="relative mb-5">
                    <!-- Iniciales Avatar -->
                    <div class="w-24 h-24 rounded-full bg-gradient-to-tr from-[#0d4d98] to-[#1a64bd] text-white font-extrabold flex items-center justify-center text-4xl shadow-md border-4 border-white">
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    </div>
                    <!-- Estatus Badge -->
                    <span class="absolute bottom-1 right-1 w-6 h-6 rounded-full bg-emerald-500 border-2 border-white flex items-center justify-center shadow" title="Investigador Activo">
                        <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                    </span>
                </div>

                <h3 class="text-base font-bold text-slate-800 leading-tight truncate w-full px-2" title="{{ $user->name }}">
                    {{ $user->name }}
                </h3>
                <span class="inline-flex mt-1.5 px-3 py-1 text-sm font-extrabold bg-[#0d4d98]/10 text-[#0d4d98] border border-[#0d4d98]/20 rounded-full uppercase tracking-wider">
                    {{ $activeRole }}
                </span>

                <!-- Detalle de Registro Rápido -->
                <div class="w-full mt-6 pt-5 border-t border-slate-200/85 text-left space-y-3.5">
                    <div class="flex items-center justify-between text-base">
                        <span class="text-slate-500 font-semibold">Cédula:</span>
                        <span class="text-slate-700 font-bold">{{ $user->cedula ?? 'No registrada' }}</span>
                    </div>
                    <div class="flex items-center justify-between text-base">
                        <span class="text-slate-500 font-semibold">Registro:</span>
                        <span class="text-slate-700 font-semibold">{{ $user->created_at->translatedFormat('F Y') }}</span>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Campos del Formulario -->
            <div class="lg:col-span-8 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Nombre Completo -->
                    <div class="md:col-span-2">
                        <label for="name" class="block text-base font-bold text-slate-700 uppercase tracking-wider mb-2">Nombre Completo</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <input id="name" 
                                   name="name" 
                                   type="text" 
                                   value="{{ old('name', $user->name) }}" 
                                   required 
                                   autofocus 
                                   autocomplete="name" 
                                   class="block w-full rounded-xl border-slate-200 bg-slate-50/50 text-slate-700 placeholder-slate-400 text-base py-3 pl-12 pr-4 focus:border-unimar-blue focus:ring focus:ring-unimar-blue/10 transition-all duration-150 h-12" />
                        </div>
                        <x-input-error class="mt-1.5 text-sm text-rose-600" :messages="$errors->get('name')" />
                    </div>

                    <!-- Correo Institucional -->
                    <div>
                        <label for="email_display" class="block text-base font-bold text-slate-700 uppercase tracking-wider mb-2">Correo Institucional (No modificable)</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <input id="email_display" 
                                   type="email" 
                                   value="{{ $user->email }}" 
                                   disabled 
                                   class="block w-full rounded-xl border-slate-200/50 bg-slate-100 text-slate-500 text-base py-3 pl-12 pr-12 cursor-not-allowed font-semibold h-12" />
                            <span class="absolute right-4 top-3.5 text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </span>
                        </div>
                    </div>

                    <!-- Teléfono de Contacto -->
                    <div>
                        <label for="telefono" class="block text-base font-bold text-slate-700 uppercase tracking-wider mb-2">Teléfono de Contacto</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            </div>
                            <input id="telefono" 
                                   name="telefono" 
                                   type="tel" 
                                   value="{{ old('telefono', $user->telefono) }}" 
                                   required 
                                   placeholder="+584141234567" 
                                   class="block w-full rounded-xl border-slate-200 bg-slate-50/50 text-slate-700 placeholder-slate-400 text-base py-3 pl-12 pr-4 focus:border-unimar-blue focus:ring focus:ring-unimar-blue/10 transition-all duration-150 h-12" />
                        </div>
                        <x-input-error class="mt-1.5 text-sm text-rose-600" :messages="$errors->get('telefono')" />
                    </div>

                    <!-- Cédula de Identidad (Solo Lectura / Auditoría) -->
                    <div class="md:col-span-2">
                        <label for="cedula_display" class="block text-base font-bold text-slate-700 uppercase tracking-wider mb-2">Cédula de Identidad (No modificable)</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.378 0 2.472.5 3 1.5M12 14v.01" />
                                </svg>
                            </div>
                            <input id="cedula_display" 
                                   type="text" 
                                   value="{{ $user->cedula }}" 
                                   disabled 
                                   class="block w-full rounded-xl border-slate-200/50 bg-slate-100 text-slate-500 text-base py-3 pl-12 pr-12 cursor-not-allowed font-semibold h-12" />
                            <span class="absolute right-4 top-3.5 text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </span>
                        </div>
                        <p class="text-sm text-slate-400 mt-2 font-semibold flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Por motivos de auditoría académica, la cédula solo puede ser modificada por la administración del Decanato.
                        </p>
                    </div>

                </div>

                <!-- Botón de Guardado e Indicador -->
                <div class="flex items-center space-x-4 pt-6 border-t border-slate-100">
                    <button type="submit" class="py-3 px-8 bg-unimar-blue hover:bg-[#0a3c76] text-white font-bold rounded-xl transition-all duration-150 shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-unimar-blue text-base flex items-center space-x-2.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Guardar Cambios</span>
                    </button>

                    @if (session('status') === 'profile-updated')
                        <div x-data="{ show: true }"
                             x-show="show"
                             x-transition:leave="transition ease-in duration-500"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             x-init="setTimeout(() => show = false, 3000)"
                             class="flex items-center space-x-1.5 text-emerald-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-base font-bold">Cambios guardados con éxito.</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </form>
</section>
