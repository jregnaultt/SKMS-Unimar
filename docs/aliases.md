# Guía de Alias y Comandos Rápidos - SKMS-Unimar

Se ha creado un archivo `~/.bash_aliases` en tu directorio personal de Linux en WSL. Este archivo es cargado automáticamente por tu archivo `~/.bashrc` y define los siguientes atajos de terminal para acelerar tu desarrollo.

---

## 🚀 Control del Proyecto SKMS-Unimar

Estos comandos gestionan todo el ciclo de vida de ejecución de Docker Sail y el servidor de desarrollo de Vite de forma automática.

| Comando | Descripción | Qué hace por detrás |
| :--- | :--- | :--- |
| `start_skms` | **Enciende todo el proyecto** | Inicia los contenedores de Sail (`up -d`) y levanta el servidor de desarrollo de Vite en segundo plano. |
| `stop_skms` | **Apaga todo el proyecto** | Detiene los contenedores de Sail (`down`) y cierra el servidor de desarrollo de Vite. |

> [!IMPORTANT]
> **Arquitectura de Contenedores Automatizada:**
> Hemos configurado el archivo [compose.yaml](file:///home/regna/SKMS-Unimar/compose.yaml) para automatizar el ciclo de vida de todos los servicios. Al ejecutar `start_skms`, Docker iniciará de forma independiente y automática:
> - **Aplicación Web (`laravel.test`):** [http://localhost:8000](http://localhost:8000)
> - **Procesador de Colas (`laravel.worker`):** Ejecuta `queue:work` automáticamente para procesar tareas asíncronas en segundo plano.
> - **Servidor Reverb (`laravel.reverb`):** Corre el servidor de WebSockets en el puerto `8090` automáticamente.
> - **Base de Datos (`mysql`):** Servidor MySQL disponible externamente en el puerto `3309`.
> - **Servidor Vite (Local):** Iniciado automáticamente en segundo plano en [http://localhost:5174](http://localhost:5174) (Logs guardados en `storage/logs/vite.log`).

---

## 🐳 Laravel Sail

Herramientas para interactuar con el entorno Docker de Laravel sin escribir rutas largas.

| Alias | Comando Completo | Descripción |
| :--- | :--- | :--- |
| `sail` | `bash vendor/bin/sail` | Ejecuta el script base de Laravel Sail |
| `sa` | `sail artisan` | Ejecuta comandos de Artisan en el contenedor |
| `sup` | `sail up -d` | Enciende los contenedores en segundo plano |
| `sdown` | `sail down` | Apaga los contenedores |
| `sps` | `sail ps` | Muestra el estado de los contenedores activos |
| `scomposer` | `sail composer` | Corre comandos de Composer dentro del contenedor |
| `snpm` | `sail npm` | Corre comandos de NPM dentro del contenedor |
| `st` | `sail test` | Ejecuta la suite de pruebas (PHPUnit) |
| `stint` | `sail pint` | Formatea el código PHP usando Laravel Pint |
| `saclear` | `sail artisan optimize:clear` | Borra todos los cachés (config, rutas, vistas, etc.) en Docker |
| `sacache` | `sail artisan optimize` | Genera caché de configuración y rutas optimizado en Docker |
| `sacclear` | `sail artisan config:clear` | Borra el caché de configuración en Docker |
| `saccache` | `sail artisan config:cache` | Crea caché de configuración en Docker |

---

## 🖥️ Laravel Artisan (Local)

Para cuando decidas ejecutar comandos rápidos directamente en tu WSL sin usar Docker (si tienes PHP instalado localmente).

| Alias | Comando Completo | Descripción |
| :--- | :--- | :--- |
| `pa` | `php artisan` | Comando base de Artisan |
| `pas` | `php artisan serve` | Levanta el servidor local de PHP de Artisan |
| `paclear` | `php artisan optimize:clear` | Borra todos los cachés localmente |
| `pacache` | `php artisan optimize` | Genera caché de configuración y rutas optimizado localmente |
| `pacclear` | `php artisan config:clear` | Borra el caché de configuración localmente |
| `paccache` | `php artisan config:cache` | Crea caché de configuración localmente |

---

## 🐙 Control de Versiones con Git

Atajos rápidos para los flujos de trabajo cotidianos en Git.

| Alias | Comando Completo | Descripción |
| :--- | :--- | :--- |
| `gs` | `git status` | Muestra el estado actual del repositorio |
| `ga` | `git add` | Prepara archivos para commit |
| `gaa` | `git add .` | Prepara todos los cambios del directorio |
| `gc` | `git commit` | Crea un commit abriendo tu editor configurado |
| `gcm` | `git commit -m` | Crea un commit rápido con mensaje (ej: `gcm "mi mensaje"`) |
| `gca` | `git commit --amend` | Modifica el último commit |
| `gp` | `git push` | Sube tus commits a la rama remota |
| `gpl` | `git pull` | Trae y fusiona los últimos cambios remotos |
| `gsw` | `git switch` | Cambia de rama (ej: `gsw main`) |
| `gco` | `git checkout` | Cambia de rama o descarta cambios |
| `gb` | `git branch` | Muestra o gestiona las ramas locales |
| `gd` | `git diff` | Muestra diferencias de archivos no preparados |
| `gl` | `git log --oneline -n 10` | Muestra los últimos 10 commits de forma compacta |
| `glg` | `git log --graph --abbrev-commit ...` | Historial de Git detallado con gráfico a color |

---

## ⚙️ Cómo empezar a usarlos ahora mismo

Para que tu terminal actual reconozca los nuevos alias inmediatamente, ejecuta el siguiente comando en tu consola:

```bash
source ~/.bashrc
```

¡Listo! A partir de ese momento, podrás usar comandos rápidos como `start_skms`, `sa route:list`, `gs`, `gcm "mensaje"`, etc.
