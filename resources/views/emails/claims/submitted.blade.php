<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva Solicitud de Reclamación</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #1a56db; border-bottom: 2px solid #eaeaea; padding-bottom: 10px;">Nueva Solicitud de Reclamación de Tesis</h2>
    <p>Hola,</p>
    <p>Se ha recibido una nueva solicitud para reclamar la autoría o tutoría de un trabajo científico registrado en el SKMS Unimar.</p>
    <div style="background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #1a56db;">
        <strong>Detalles del Reclamante:</strong><br>
        Nombre: {{ $claim->user->name }}<br>
        Correo: {{ $claim->user->email }}<br><br>
        <strong>Detalles del Trabajo Científico:</strong><br>
        Título: {{ $claim->production->title }}<br>
        Rol Solicitado: {{ $claim->role === 'author' ? 'Autor' : 'Tutor' }}<br>
        Autores Originales (Texto Plano): {{ $claim->production->authors ?? 'No especificados' }}<br>
        Tutor Original (Texto Plano): {{ $claim->production->tutor ?? 'No especificado' }}
    </div>
    <p>Por favor, ingrese al panel administrativo para evaluar y resolver esta solicitud.</p>
    <p style="margin-top: 30px; font-size: 0.9em; color: #777; border-top: 1px solid #eaeaea; padding-top: 10px;">
        Este es un correo automático enviado por SKMS-Unimar.
    </p>
</body>
</html>
