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
        <div>
            <label for="password" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Contraseña</label>
            <input id="password" 
                   type="password" 
                   name="password" 
                   required 
                   autocomplete="current-password" 
                   class="block w-full rounded-xl border-slate-200/80 bg-slate-50/50 text-slate-700 placeholder-slate-400 text-sm py-2.5 px-4 focus:border-unimar-blue focus:ring focus:ring-unimar-blue/10 transition-all duration-150" 
                   placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" />
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
