<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SKMS Unimar') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-800 antialiased min-h-screen bg-slate-50 flex">
        
        <!-- Lado Izquierdo: Ilustración Académica/Científica (solo en pantallas grandes) -->
        <div class="hidden lg:flex lg:w-1/2 relative bg-unimar-blue items-center justify-center overflow-hidden">
            <!-- Imagen de fondo optimizada -->
            <div class="absolute inset-0 bg-cover bg-center opacity-80 mix-blend-luminosity" style="background-image: url('/images/engineering_blue_hero.jpg');"></div>
            <!-- Capa de gradiente azul institucional para dar contraste -->
            <div class="absolute inset-0 bg-gradient-to-tr from-unimar-blue via-unimar-blue/90 to-unimar-blue/40"></div>
            
            <!-- Contenido textual decorativo premium -->
            <div class="relative z-10 p-16 text-white max-w-xl flex flex-col space-y-6">
                <div class="flex items-center space-x-3">
                    <div class="p-2.5 bg-white/10 rounded-xl border border-white/20 text-unimar-gold">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                    <div>
                        <span class="font-extrabold text-2xl tracking-tight block">SKMS</span>
                        <span class="text-xs text-white/70 tracking-widest font-semibold uppercase block">Universidad de Margarita</span>
                    </div>
                </div>
                
                <div class="border-l-4 border-unimar-gold pl-6 py-2">
                    <h3 class="text-2xl font-bold text-white tracking-tight">Decanato de Ingeniería y Afines</h3>
                    <p class="text-sm text-white/80 mt-2 font-medium">
                        Sistema de Gestión de Conocimiento Científico para la administración del ciclo de vida de producciones de investigación.
                    </p>
                </div>
                
                <div class="pt-8 flex items-center space-x-2.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span class="text-xs text-white/60 font-semibold uppercase tracking-wider">UNIMAR Prestige &bull; Ingeniería</span>
                </div>
            </div>
            
            <!-- Elemento decorativo técnico en el fondo -->
            <div class="absolute -bottom-32 -left-32 w-96 h-96 bg-white/5 rounded-full border border-white/10 pointer-events-none"></div>
        </div>

        <!-- Lado Derecho: Contenedor del Formulario (Login/Registro/Otras vistas de invitado) -->
        <div class="w-full lg:w-1/2 flex flex-col justify-center items-center p-6 sm:p-12 bg-slate-50">
            <div class="w-full max-w-md flex flex-col">
                
                <!-- Logo para móviles (oculto en pantallas grandes ya que el sidebar lo tiene) -->
                <div class="flex lg:hidden justify-center mb-8">
                    <a href="/" class="flex items-center space-x-2">
                        <div class="p-2 bg-unimar-blue rounded-lg text-unimar-gold">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                        </div>
                        <span class="font-extrabold text-lg text-slate-800 tracking-tight">SKMS UNIMAR</span>
                    </a>
                </div>

                <!-- Tarjeta Blanca de Autenticación Premium -->
                <div class="bg-white border border-slate-200/80 shadow-sm rounded-2xl p-8 sm:p-10 transition-all duration-300 hover:shadow-md">
                    {{ $slot }}
                </div>
                
                <!-- Footer decorativo en el lado derecho -->
                <div class="mt-8 text-center">
                    <p class="text-[10px] text-slate-400">
                        &copy; {{ date('Y') }} Decanato de Ingeniería - Universidad de Margarita.
                    </p>
                </div>
            </div>
        </div>

    </body>
</html>
