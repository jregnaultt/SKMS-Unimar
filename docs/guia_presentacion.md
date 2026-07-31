# Guía de Presentación del Sistema: SKMS-Unimar

Esta guía contiene la secuencia exacta de pasos, roles y credenciales para realizar una demostración en vivo fluida y exitosa de todos los módulos del **Scientific Knowledge Management System (SKMS-Unimar)**.

---

## 🔑 Credenciales del Sistema de Demostración

> [!IMPORTANT]
> Todos los usuarios creados por los *seeders* comparten la misma contraseña por defecto: **`Unimar2026!`** (excepto la cuenta *Super Admin*, detallada abajo).

| Rol | Nombre | Correo Electrónico (`email`) | Contraseña (`password`) |
| :--- | :--- | :--- | :--- |
| **Estudiante** | Javier Regnault | `jregnault.6759@unimar.edu.ve` | `Unimar2026!` |
| **Estudiante** | César Vethencourt | `cvethencourt.4518@unimar.edu.ve` | `Unimar2026!` |
| **Tutor / Jurado** | Oswald Marín | `omarin.4205@unimar.edu.ve` | `Unimar2026!` |
| **Tutor / Jurado** | César Requena | `crequena.4866@unimar.edu.ve` | `Unimar2026!` |
| **Jurado** | Isabel Flores | `iflores.7516@unimar.edu.ve` | `Unimar2026!` |
| **Coordinador** | Yemnel Torcat | `ingenieria.investigacion.pasantias@unimar.edu.ve` | `Unimar2026!` |
| **Decano** | Flavio Rosales | `flavio.rosales@unimar.edu.ve` | `Unimar2026!` |
| **Super Admin** | God | `god@unimar.edu.ve` | `--god--` (o tu config `.env`) |

---

## 🎬 Guion Paso a Paso para la Presentación

### Paso 1: Acceso Público e Interoperabilidad (Sin Autenticar)
*Comienza la presentación con una vista limpia del sistema, como si fueras un usuario externo o investigador externo.*
1. **Accede a la página de bienvenida (`/`)** y muestra el diseño premium académico adaptado a la UNIMAR.
2. **Navega al Catálogo Público (`/catalog`):**
   * Muestra la barra de búsqueda avanzada y los filtros dinámicos (por programa académico, línea de investigación y año).
   * Explica que esta sección permite el acceso libre a producciones ya publicadas y aprobadas.
3. **Muestra el Endpoint de Interoperabilidad OAI-PMH (`/oai`):**
   * Accede a la URL `/oai?verb=Identify` para mostrar cómo el sistema se integra con estándares de catalogación internacionales expuestos en Dublin Core XML.
   * Explica que esto permite a repositorios globales (como Google Scholar o Latindex) indexar automáticamente las publicaciones de la universidad.

---

### Paso 2: Carga de Producción y Extracción por IA (Rol: Estudiante)
*Demuestra cómo un estudiante sube su tesis o trabajo y el sistema realiza el procesamiento inteligente.*
1. Inicia sesión como **Javier Regnault** (`jregnault.6759@unimar.edu.ve` / `Unimar2026!`).
2. Haz clic en **"Nueva Producción"** (`/productions/create`).
3. **Sube un PDF de prueba:**
   * Al seleccionar el archivo, muestra cómo el backend procesa el documento y **extrae automáticamente los metadatos** (Título, Resumen, Autores y Tutor sugerido).
   * Explica que esto ahorra tiempo al estudiante y mitiga errores de tipeo en el estándar Dublin Core.
4. **Guarda como Borrador (`borrador`):**
   * Muestra que el documento se guarda inicialmente como Borrador.
   * Ve al panel de seguimiento del estudiante (`/mis-hitos`) y muestra el gráfico visual del progreso de su período académico.
5. **Envía a Revisión:** 
   * Haz clic en **"Enviar a Revisión"**, lo cual cambia el estado a `en_revision`. Muestra que ahora el documento está bloqueado para edición del estudiante hasta que reciba comentarios.

---

### Paso 3: Revisión de Avances y Comentarios Estructurados (Rol: Tutor)
*Demuestra el rol del tutor guiando activamente al estudiante.*
1. Cierra sesión e ingresa como el Tutor asignado **Oswald Marín** (`omarin.4205@unimar.edu.ve` / `Unimar2026!`).
2. Entra a **"Trabajos Asignados"** (`/assigned-productions`). Muestra la lista de estudiantes a su cargo.
3. Abre el trabajo enviado por Javier.
4. **Agrega un Comentario Estructurado (Observación):**
   * Haz clic en el visualizador o área de observaciones y redacta una corrección (ej: *"Corregir el planteamiento del problema en la pág. 4"*).
   * Deja el estado del comentario en **Pendiente**.
5. **Transiciona el Flujo:**
   * Haz clic en **"Solicitar Correcciones"** para cambiar el estado de la producción a `requiere_correcciones`.

---

### Paso 4: Notificación y Corrección en Caliente (Rol: Estudiante)
*Muestra la interacción fluida del ciclo de correcciones.*
1. Vuelve a iniciar sesión como el Estudiante **Javier Regnault**.
2. **Revisa la Campana de Notificaciones (Alpine.js):**
   * Muestra cómo aparece la alerta visual al instante sin recargar la página.
   * Entra a la notificación para ir directo al panel de progreso del trabajo (`/productions/{id}/progreso`).
3. **Marca el avance de la corrección:**
   * Selecciona el comentario del tutor y cambia su estado a **En Progreso** (`en_progreso`).
   * Sube una nueva versión del PDF (`DocumentVersion`) describiendo los cambios realizados.
   * Cambia el estado del comentario a **Resuelto** (`resuelto`).
4. Haz clic en **"Enviar de Nuevo"** para que regrese a la bandeja del Tutor.

---

### Paso 5: Asignación de Jurados y Aprobación (Rol: Coordinador)
*Muestra cómo la coordinación gestiona las fases finales.*
1. Inicia sesión como el Coordinador **Yemnel Torcat** (`ingenieria.investigacion.pasantias@unimar.edu.ve` / `Unimar2026!`).
2. Ve a la **Bandeja de Asignación de Jurados** (`/admin/juries`).
3. Asigna un jurado evaluador al trabajo de Javier (ej: selecciona a **Isabel Flores**).
4. Ve al módulo de **Aprobación de la Coordinación** (`/admin/approvals`).
5. Realiza la aprobación final del trabajo (`aprobado`) y luego publícalo (`publicado`).
6. Explica que a partir de este instante, el trabajo se vuelve visible en el catálogo público y se genera el token de preservación digital.

---

### Paso 6: Análisis Bibliométrico e Informes (Rol: Coordinador / Decano)
*Finaliza mostrando el valor de los datos consolidados.*
1. Estando con el rol de Coordinador, accede a **Bibliometría** (`/bibliometrics`).
   * Muestra los gráficos interactivos de productividad académica (evolución temporal de tesis, rankings de tutores y distribución por líneas de investigación).
2. Ve al panel de **Reportes** (`/admin/reports`):
   * Genera un reporte institucional consolidado en formato **Excel** o **PDF**.
   * Muestra el archivo descargado con el formato formal de la universidad.
3. Ve a **Trazabilidad (Audit Logs)** (`/admin/audit-logs`):
   * Muestra cómo el sistema registra cada evento importante, dirección IP y el cambio de estado exacto, cumpliendo con estándares de auditoría y la normativa LOCTI.

---

## 💡 Consejos para la Demostración
*   **Preparación:** Abre varias pestañas en modo incógnito de tu navegador. Deja una pestaña abierta para el Estudiante, otra para el Tutor y otra para el Coordinador. Esto te evitará tener que cerrar e iniciar sesión repetidamente en la misma ventana.
*   **Conectividad:** Puesto que el sistema está completamente renderizado del lado del servidor (Blade + Tailwind) y usa Alpine.js ligero, responde con tiempos de carga sumamente rápidos (menos de 2 segundos), lo que dará una sensación de alta fluidez ante el jurado o los profesores.
