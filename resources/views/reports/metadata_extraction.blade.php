<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Extracción de Metadatos - SKMS Unimar</title>
    <style>
        @page {
            margin: 1.5cm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 11px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #1a365d;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #1a365d;
            font-size: 18px;
            margin: 0 0 5px 0;
            text-transform: uppercase;
        }
        .header p {
            margin: 0;
            color: #4a5568;
            font-size: 11px;
        }
        .summary-box {
            background-color: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            padding: 12px;
            margin-bottom: 20px;
        }
        .summary-box h2 {
            color: #2b6cb0;
            font-size: 13px;
            margin: 0 0 10px 0;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 5px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-table th, .summary-table td {
            padding: 6px;
            text-align: left;
            border-bottom: 1px solid #edf2f7;
        }
        .summary-table th {
            color: #4a5568;
            font-weight: bold;
        }
        .thesis-card {
            background-color: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            padding: 12px;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        .thesis-card.error {
            border-left: 4px solid #e53e3e;
            background-color: #fffaf0;
        }
        .thesis-card.success {
            border-left: 4px solid #319795;
        }
        .thesis-header {
            border-bottom: 1px solid #edf2f7;
            padding-bottom: 5px;
            margin-bottom: 8px;
            overflow: hidden;
        }
        .thesis-filename {
            font-weight: bold;
            font-size: 11px;
            color: #2d3748;
        }
        .thesis-status {
            float: right;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            padding: 1px 5px;
            border-radius: 3px;
        }
        .status-ok {
            background-color: #e6fffa;
            color: #234e52;
        }
        .status-error {
            background-color: #fed7d7;
            color: #742a2a;
        }
        .field-group {
            margin-bottom: 5px;
        }
        .field-label {
            font-weight: bold;
            color: #4a5568;
            display: inline-block;
            width: 100px;
        }
        .field-value {
            color: #2d3748;
        }
        .abstract-box {
            margin-top: 8px;
            background-color: #f7fafc;
            padding: 8px;
            border-radius: 4px;
            border-left: 2px solid #cbd5e0;
            font-style: italic;
            color: #4a5568;
            text-align: justify;
        }
        .page-break {
            page-break-after: always;
        }
        .footer {
            position: fixed;
            bottom: -30px;
            left: 0;
            right: 0;
            height: 30px;
            text-align: center;
            font-size: 9px;
            color: #a0aec0;
            border-top: 1px solid #edf2f7;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Sistema de Gestión de Conocimiento Científico (SKMS-Unimar)</h1>
        <p>Reporte de Extracción Masiva de Metadatos de Trabajos de Grado (Ingeniería)</p>
        <p>Fecha de Generación: {{ now()->format('d/m/Y h:i A') }} | Total Documentos: {{ $summary['total'] }}</p>
    </div>

    <div class="summary-box">
        <h2>Resumen de Rendimiento de Extracción</h2>
        <table class="summary-table">
            <thead>
                <tr>
                    <th>Métrica</th>
                    <th>Éxitos</th>
                    <th>Porcentaje de Éxito</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Títulos Extraídos</td>
                    <td>{{ $summary['title_ok'] }} / {{ $summary['total'] }}</td>
                    <td>{{ number_format(($summary['title_ok'] / $summary['total']) * 100, 2) }}%</td>
                </tr>
                <tr>
                    <td>Autores Extraídos</td>
                    <td>{{ $summary['authors_ok'] }} / {{ $summary['total'] }}</td>
                    <td>{{ number_format(($summary['authors_ok'] / $summary['total']) * 100, 2) }}%</td>
                </tr>
                <tr>
                    <td>Tutores Extraídos</td>
                    <td>{{ $summary['tutor_ok'] }} / {{ $summary['total'] }}</td>
                    <td>{{ number_format(($summary['tutor_ok'] / $summary['total']) * 100, 2) }}%</td>
                </tr>
                <tr>
                    <td>Resúmenes Extraídos</td>
                    <td>{{ $summary['abstract_ok'] }} / {{ $summary['total'] }}</td>
                    <td>{{ number_format(($summary['abstract_ok'] / $summary['total']) * 100, 2) }}%</td>
                </tr>
                <tr>
                    <td>Palabras Clave Extraídas</td>
                    <td>{{ $summary['keywords_ok'] }} / {{ $summary['total'] }}</td>
                    <td>{{ number_format(($summary['keywords_ok'] / $summary['total']) * 100, 2) }}%</td>
                </tr>
                <tr>
                    <td>Errores de Lectura (Archivos corruptos/encriptados)</td>
                    <td>{{ $summary['errors'] }} / {{ $summary['total'] }}</td>
                    <td>{{ number_format(($summary['errors'] / $summary['total']) * 100, 2) }}%</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="page-break"></div>

    <h2>Detalle de Extracción por Tesis</h2>

    @foreach($results as $index => $item)
        <div class="thesis-card {{ $item['status'] === 'OK' ? 'success' : 'error' }}">
            <div class="thesis-header">
                <span class="thesis-filename">{{ $index + 1 }}. {{ $item['filename'] }}</span>
                <span class="thesis-status {{ $item['status'] === 'OK' ? 'status-ok' : 'status-error' }}">
                    {{ $item['status'] }}
                </span>
            </div>
            
            @if($item['status'] === 'OK')
                <div class="field-group">
                    <span class="field-label">Título:</span>
                    <span class="field-value">{{ $item['title_raw'] ?: '✘ No encontrado' }}</span>
                </div>
                <div class="field-group">
                    <span class="field-label">Autor(es):</span>
                    <span class="field-value">{{ $item['authors_raw'] ?: '✘ No encontrado' }}</span>
                </div>
                <div class="field-group">
                    <span class="field-label">Tutor:</span>
                    <span class="field-value">{{ $item['tutor_raw'] ?: '✘ No encontrado' }}</span>
                </div>
                <div class="field-group">
                    <span class="field-label">P. Clave:</span>
                    <span class="field-value">{{ $item['keywords_raw'] ?: '✘ No encontrado' }}</span>
                </div>
                
                @if($item['abstract_raw'])
                    <div class="abstract-box">
                        <strong>Resumen Extraído:</strong><br>
                        {{ $item['abstract_raw'] }}
                    </div>
                @else
                    <div class="field-group" style="margin-top: 5px;">
                        <span class="field-label">Resumen:</span>
                        <span class="field-value" style="color: #e53e3e;">✘ No encontrado</span>
                    </div>
                @endif
            @else
                <div class="field-group">
                    <span class="field-label" style="color: #e53e3e;">Error:</span>
                    <span class="field-value" style="color: #e53e3e; font-weight: bold;">{{ $item['error'] }}</span>
                </div>
            @endif
        </div>
    @endforeach

    <div class="footer">
        Universidad de Margarita | Decanato de Ingeniería y Afines | SKMS Unimar
    </div>
</body>
</html>
