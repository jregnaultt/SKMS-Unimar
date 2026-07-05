<section class="space-y-6">
    <header class="mb-5">
        <h2 class="text-xl font-bold text-slate-800 tracking-tight">
            Actualizar Contraseña
        </h2>
        <p class="text-base text-slate-550 mt-1.5 font-medium">
            Garantiza la seguridad de tu cuenta utilizando una contraseña larga y aleatoria.
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="space-y-6">
        @csrf
        @method('put')

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <!-- Columna Izquierda: Tarjeta de Tips de Seguridad -->
            <div class="lg:col-span-4 bg-slate-50 border border-slate-200/70 rounded-2xl p-7 shadow-sm space-y-4">
                <div class="flex items-center space-x-2.5 text-unimar-blue">
                    <div class="p-2 bg-unimar-blue/5 rounded-xl border border-unimar-blue/10">
                        <svg class="w-5 h-5 text-unimar-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-extrabold uppercase tracking-wider text-slate-700">Seguridad</h3>
                </div>
                
                <p class="text-base text-slate-500 leading-relaxed font-medium">
                    Una contraseña segura te ayuda a proteger tus trabajos de investigación y datos de autoría frente a accesos no autorizados.
                </p>

                <ul class="space-y-2.5 text-sm text-slate-500 font-semibold uppercase tracking-wide">
                    <li class="flex items-center space-x-2.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#F5B800] shrink-0"></span>
                        <span>Mínimo 8 caracteres</span>
                    </li>
                    <li class="flex items-center space-x-2.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#F5B800] shrink-0"></span>
                        <span>Usar letras y números</span>
                    </li>
                    <li class="flex items-center space-x-2.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#F5B800] shrink-0"></span>
                        <span>Evitar datos comunes</span>
                    </li>
                </ul>
            </div>

            <!-- Columna Derecha: Campos de Contraseña -->
            <div class="lg:col-span-8 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Contraseña Actual -->
                    <div class="md:col-span-2">
                        <label for="update_password_current_password" class="block text-base font-bold text-slate-655 uppercase tracking-wider mb-2">Contraseña Actual</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input id="update_password_current_password" 
                                   name="current_password" 
                                   type="password" 
                                   autocomplete="current-password" 
                                   class="block w-full rounded-xl border-slate-200 bg-slate-50/50 text-slate-700 placeholder-slate-400 text-base py-3 pl-12 pr-4 focus:border-unimar-blue focus:ring focus:ring-unimar-blue/10 transition-all duration-150 h-12" 
                                   placeholder="••••••••" />
                        </div>
                        <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1.5 text-sm text-rose-600" />
                    </div>

                    <!-- Nueva Contraseña -->
                    <div>
                        <label for="update_password_password" class="block text-base font-bold text-slate-655 uppercase tracking-wider mb-2">Nueva Contraseña</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input id="update_password_password" 
                                   name="password" 
                                   type="password" 
                                   autocomplete="new-password" 
                                   class="block w-full rounded-xl border-slate-200 bg-slate-50/50 text-slate-700 placeholder-slate-400 text-base py-3 pl-12 pr-4 focus:border-unimar-blue focus:ring focus:ring-unimar-blue/10 transition-all duration-150 h-12" 
                                   placeholder="Mínimo 8 caracteres" />
                        </div>
                        <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1.5 text-sm text-rose-600" />
                    </div>

                    <!-- Confirmar Nueva Contraseña -->
                    <div>
                        <label for="update_password_password_confirmation" class="block text-base font-bold text-slate-655 uppercase tracking-wider mb-2">Confirmar Nueva Contraseña</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input id="update_password_password_confirmation" 
                                   name="password_confirmation" 
                                   type="password" 
                                   autocomplete="new-password" 
                                   class="block w-full rounded-xl border-slate-200 bg-slate-50/50 text-slate-700 placeholder-slate-400 text-base py-3 pl-12 pr-4 focus:border-unimar-blue focus:ring focus:ring-unimar-blue/10 transition-all duration-150 h-12" 
                                   placeholder="••••••••" />
                        </div>
                        <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1.5 text-sm text-rose-600" />
                    </div>
                </div>

                <!-- Botón de Acción e Indicador -->
                <div class="flex items-center space-x-4 pt-6 border-t border-slate-100">
                    <button type="submit" class="py-3 px-8 bg-unimar-blue hover:bg-[#0a3c76] text-white font-bold rounded-xl transition-all duration-150 shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-unimar-blue text-base flex items-center space-x-2.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <span>Actualizar Contraseña</span>
                    </button>

                    @if (session('status') === 'password-updated')
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
                            <span class="text-base font-bold">Contraseña actualizada.</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </form>
</section>
