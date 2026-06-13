<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitud de Reclamación Rechazada</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #dc2626; border-bottom: 2px solid #eaeaea; padding-bottom: 10px;">Solicitud de Reclamación Rechazada</h2>
    <p>Estimado/a {{ $claim->user->name }},</p>
    <p>Le informamos que su solicitud para reclamar la vinculación al siguiente trabajo científico ha sido rechazada por la Coordinación de Investigación:</p>
    <div style="background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #dc2626;">
        <strong>Detalles del Trabajo Científico:</strong><br>
        Título: {{ $claim->production->title }}<br>
        Rol Solicitado: {{ $claim->role === 'author' ? 'Autor' : 'Tutor' }}<br><br>
        <strong>Motivo del Rechazo:</strong><br>
        {{ $claim->rejection_reason }}
    </div>
    <p>Si considera que se trata de un error o requiere mayor aclaración, le recomendamos ponerse en contacto directo con la Coordinación de Investigación.</p>
    <p style="margin-top: 30px; font-size: 0.9em; color: #777; border-top: 1px solid #eaeaea; padding-top: 10px;">
        Este es un correo automático enviado por SKMS-Unimar.
    </p>
</body>
</html>
