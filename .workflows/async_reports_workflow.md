# Flujo de Generación Asíncrona de Reportes (Módulo 8)

Este documento describe la secuencia de eventos técnicos que ocurren cuando un Coordinador de Investigación genera un reporte consolidado en Excel o PDF de forma asíncrona usando Laravel Jobs, Reverb (WebSockets) y Echo.

## Diagrama de Secuencia (Mermaid)

```mermaid
sequenceDiagram
    actor C as Coordinador
    participant WB as Navegador Web (Echo/Alpine)
    participant RC as ReportController
    participant Q as Laravel Queue (Job)
    participant WS as WebSocket (Reverb)

    C->>WB: Hace clic en "Generate Report" (con filtros)
    WB->>RC: HTTP POST /admin/reports/generate
    RC->>Q: Despacha GenerateReportJob
    RC-->>WB: HTTP 200 JSON (Encolado exitosamente)
    WB->>C: Muestra spinner de "Generando reporte..."
    activate Q
    Q->>Q: Recopila datos de la BD y compila Excel/PDF
    Q->>Q: Guarda archivo en almacenamiento privado (private/reports/)
    Q->>WS: Transmite evento ReportGenerated (ShouldBroadcast)
    deactivate Q
    WS-->>WB: Payload WebSocket (Echo detecta el evento)
    WB->>C: Quita spinner, muestra botón de "Download"
    C->>WB: Hace clic en descargar
    WB->>RC: HTTP GET /admin/reports/download/{filename}
    RC-->>C: Stream de descarga del archivo
```

## Flujo de Negocio

1.  **Solicitud Filtrada:** El coordinador entra al panel de reportes e indica los parámetros (ej: período académico 2026-I, facultad de Ingeniería, exportar a PDF).
2.  **Encolamiento del Trabajo:** El servidor procesa la petición de inmediato, crea un identificador único para el reporte, encola el Job y retorna una respuesta JSON rápida.
3.  **Procesamiento Asíncrono:** La cola ejecuta el Job. Esto previene que se caiga la petición HTTP si hay más de 500 registros y la compilación de DomPDF toma más de 30 segundos.
4.  **Aviso WebSocket:** Mediante Laravel Reverb y Laravel Echo en el cliente, el navegador escucha en el canal privado del usuario la finalización del reporte.
5.  **Descarga Segura:** La descarga se realiza mediante un controlador que verifica los permisos del usuario activo sobre el archivo, evitando exposición pública directa de los documentos de auditoría.
