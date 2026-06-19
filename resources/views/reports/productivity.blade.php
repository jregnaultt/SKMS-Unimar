<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Productividad Científica - SKMS Unimar</title>
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
            font-size: 16px;
            margin: 0 0 5px 0;
            text-transform: uppercase;
            font-weight: bold;
        }
        .header h2 {
            color: #4a5568;
            font-size: 12px;
            margin: 0 0 5px 0;
            font-weight: normal;
        }
        .header p {
            margin: 0;
            color: #718096;
            font-size: 10px;
        }
        .meta-info {
            width: 100%;
            margin-bottom: 20px;
            font-size: 10px;
            color: #4a5568;
        }
        .meta-info td {
            padding: 2px 0;
        }
        .summary-box {
            background-color: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            padding: 12px;
            margin-bottom: 20px;
        }
        .summary-box h3 {
            color: #2b6cb0;
            font-size: 12px;
            margin: 0 0 8px 0;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
        }
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .report-table th, .report-table td {
            padding: 8px 6px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .report-table th {
            background-color: #2b6cb0;
            color: white;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
        }
        .report-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            font-size: 9px;
            font-weight: bold;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .badge-published { background-color: #c6f6d5; color: #22543d; }
        .badge-approved { background-color: #ebf8ff; color: #2b6cb0; }
        .badge-review { background-color: #feebc8; color: #744210; }
        .badge-draft { background-color: #edf2f7; color: #4a5568; }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #a0aec0;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Universidad de Margarita</h1>
        <h2>Decanato de Ingeniería y Afines</h2>
        <h1>Reporte de Productividad Científica</h1>
        <p>Sistema de Gestión del Conocimiento Científico (SKMS-Unimar)</p>
    </div>

    <table class="meta-info">
        <tr>
            <td style="width: 50%;"><strong>Fecha de Generación:</strong> {{ now()->format('d/m/Y h:i A') }}</td>
            <td style="text-align: right;"><strong>Generado por:</strong> {{ $user->name }}</td>
        </tr>
        <tr>
            <td><strong>Total Registros:</strong> {{ $productions->count() }}</td>
            <td style="text-align: right;"><strong>Área:</strong> Decanato de Ingeniería y Afines</td>
        </tr>
    </table>

    <div class="summary-box">
        <h3>Resumen Ejecutivo</h3>
        <p>Este reporte compila las producciones científicas registradas en el sistema SKMS. Se detallan a continuación los manuscritos que cumplen con los filtros académicos seleccionados, incluyendo tesis, proyectos de grado y artículos de investigación.</p>
    </div>

    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 35%;">Título</th>
                <th style="width: 20%;">Autor(es)</th>
                <th style="width: 20%;">Tutor</th>
                <th style="width: 15%;">Programa</th>
                <th style="width: 10%;">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($productions as $p)
                <tr>
                    <td>
                        <strong>{{ $p->title }}</strong>
                        <div style="font-size: 9px; color: #718096; margin-top: 2px;">
                            {{ $p->productionType->name ?? 'N/A' }} | {{ $p->academicPeriod->name ?? 'N/A' }}
                        </div>
                    </td>
                    <td>{{ $p->authors }}</td>
                    <td>{{ $p->tutor ?? 'No asignado' }}</td>
                    <td>{{ $p->academicProgram->name ?? 'N/A' }}</td>
                    <td>
                        <span class="badge 
                            @if($p->workflow_state === 'published') badge-published
                            @elseif($p->workflow_state === 'approved') badge-approved
                            @elseif($p->workflow_state === 'under_review') badge-review
                            @else badge-draft
                            @endif">
                            {{ $p->workflow_state }}
                        </span>
                    </td>
                </tr>
            @endforeach
            @if($productions->isEmpty())
                <tr>
                    <td colspan="5" style="text-align: center; color: #a0aec0; padding: 20px;">
                        No se encontraron producciones científicas con los criterios especificados.
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        Universidad de Margarita - Alma Mater del Caribe &copy; {{ date('Y') }}
    </div>
</body>
</html>
