<x-guest-layout>
    <!-- Encabezado de la Tarjeta -->
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-slate-800 tracking-tight font-sans">Iniciar Sesión</h2>
        <p class="text-xs text-slate-500 mt-1.5 font-medium">Ingresa tus credenciales académicas para acceder</p>
    </div>

    <!-- Estado de la Sesión -->
    <x-auth-session-status class="mb-6 p-3 bg-blue-50 border-l-4 border-unimar-blue rounded-r-lg text-xs font-semibold text-unimar-blue" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Correo Electrónico -->
        <div>
            <label for="email" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Correo Institucional</label>
            <input id="email" 
                   type="email" 
                   name="email" 
                   value="{{ old('email') }}" 
                   required 
                   autofocus 
                   autocomplete="username" 
                   class="block w-full rounded-xl border-slate-200/80 bg-slate-50/50 text-slate-700 placeholder-slate-400 text-sm py-2.5 px-4 focus:border-unimar-blue focus:ring focus:ring-unimar-blue/10 transition-all duration-150" 
                   placeholder="ejemplo@unimar.edu.ve" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-xs text-rose-600" />
        </div>

        <!-- Contraseña -->
        <div x-data="{ showPassword: false }">
            <label for="password" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Contraseña</label>
            <div class="relative">
                <input id="password" 
                       :type="showPassword ? 'text' : 'password'" 
                       name="password" 
                       required 
                       autocomplete="current-password" 
                       class="block w-full rounded-xl border-slate-200/80 bg-slate-50/50 text-slate-700 placeholder-slate-400 text-sm py-2.5 pl-4 pr-11 focus:border-unimar-blue focus:ring focus:ring-unimar-blue/10 transition-all duration-150" 
                       placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" />
                <button type="button" 
                        @click="showPassword = !showPassword" 
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-unimar-blue transition-colors focus:outline-none">
                    <!-- Icono Ojo (Ver contraseña) -->
                    <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <!-- Icono Ojo Tachado (Ocultar contraseña) -->
                    <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1.5 text-xs text-rose-600" />
        </div>

        <!-- Recordarme y Olvido de Contraseña -->
        <div class="flex items-center justify-between pt-1">
            <label for="remember_me" class="inline-flex items-center cursor-pointer">
                <input id="remember_me" 
                       type="checkbox" 
                       name="remember" 
                       class="rounded border-slate-300 text-unimar-blue focus:ring-unimar-blue/30 h-4 w-4 transition-all cursor-pointer">
                <span class="ms-2 text-xs text-slate-600 font-medium select-none">Recordarme</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-xs text-unimar-blue hover:text-unimar-blue/80 hover:underline font-semibold transition" href="{{ route('password.request') }}">
                    ¿Olvidaste tu contraseña?
                </a>
            @endif
        </div>

        <!-- Botón de Acción Principal -->
        <div class="pt-2">
            <button type="submit" class="w-full flex justify-center items-center py-3 px-6 bg-unimar-blue hover:bg-unimar-blue/95 text-white font-bold rounded-xl transition-all duration-150 shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-unimar-blue text-sm">
                Ingresar al Panel
            </button>
        </div>
    </form>

    <!-- Enlace de Registro -->
    <div class="mt-8 pt-6 border-t border-slate-100 text-center">
        <p class="text-xs text-slate-500 font-medium">
            ¿No tienes una cuenta de investigador? 
            <a href="{{ route('register') }}" class="text-unimar-blue hover:text-unimar-blue/80 font-bold hover:underline ml-1 transition">
                Regístrate aquí
            </a>
        </p>
    </div>
</x-guest-layout>
