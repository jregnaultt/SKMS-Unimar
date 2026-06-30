<section class="space-y-6">
    <header class="mb-6">
        <h2 class="text-xl font-bold text-slate-800 tracking-tight">
            Actualizar Contraseña
        </h2>
        <p class="text-xs text-slate-500 mt-1">
            Garantiza la seguridad de tu cuenta utilizando una contraseña larga y aleatoria.
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        @method('put')

        <!-- Contraseña Actual -->
        <div>
            <label for="update_password_current_password" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Contraseña Actual</label>
            <input id="update_password_current_password" 
                   name="current_password" 
                   type="password" 
                   autocomplete="current-password" 
                   class="block w-full rounded-xl border-slate-200/80 bg-slate-50/50 text-slate-700 placeholder-slate-400 text-sm py-2.5 px-4 focus:border-unimar-blue focus:ring focus:ring-unimar-blue/10 transition-all duration-150" 
                   placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1.5 text-xs text-rose-600" />
        </div>

        <!-- Nueva Contraseña -->
        <div>
            <label for="update_password_password" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Nueva Contraseña</label>
            <input id="update_password_password" 
                   name="password" 
                   type="password" 
                   autocomplete="new-password" 
                   class="block w-full rounded-xl border-slate-200/80 bg-slate-50/50 text-slate-700 placeholder-slate-400 text-sm py-2.5 px-4 focus:border-unimar-blue focus:ring focus:ring-unimar-blue/10 transition-all duration-150" 
                   placeholder="Mínimo 8 caracteres" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1.5 text-xs text-rose-600" />
        </div>

        <!-- Confirmar Nueva Contraseña -->
        <div>
            <label for="update_password_password_confirmation" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Confirmar Nueva Contraseña</label>
            <input id="update_password_password_confirmation" 
                   name="password_confirmation" 
                   type="password" 
                   autocomplete="new-password" 
                   class="block w-full rounded-xl border-slate-200/80 bg-slate-50/50 text-slate-700 placeholder-slate-400 text-sm py-2.5 px-4 focus:border-unimar-blue focus:ring focus:ring-unimar-blue/10 transition-all duration-150" 
                   placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1.5 text-xs text-rose-600" />
        </div>

        <!-- Botón de Acción e Indicador -->
        <div class="flex items-center space-x-4 pt-4">
            <button type="submit" class="py-2.5 px-6 bg-unimar-blue hover:bg-unimar-blue/95 text-white font-bold rounded-xl transition-all duration-150 shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-unimar-blue text-sm">
                Actualizar Contraseña
            </button>

            @if (session('status') === 'password-updated')
                <div x-data="{ show: true }"
                     x-show="show"
                     x-transition:leave="transition ease-in duration-500"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     x-init="setTimeout(() => show = false, 3000)"
                     class="flex items-center space-x-1.5 text-emerald-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-xs font-bold">Contraseña actualizada.</span>
                </div>
            @endif
        </div>
    </form>
</section>
