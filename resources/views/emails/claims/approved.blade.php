<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitud de Reclamación Aprobada</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #16a34a; border-bottom: 2px solid #eaeaea; padding-bottom: 10px;">¡Solicitud de Reclamación Aprobada!</h2>
    <p>Estimado/a {{ $claim->user->name }},</p>
    <p>Nos complace informarle que la Coordinación de Investigación ha aprobado su solicitud para vincularse al siguiente trabajo científico:</p>
    <div style="background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #16a34a;">
        <strong>Detalles del Trabajo Científico:</strong><br>
        Título: {{ $claim->production->title }}<br>
        Rol Vinculado: {{ $claim->role === 'author' ? 'Autor' : 'Tutor' }}
    </div>
    <p>A partir de este momento, el trabajo ya se encuentra oficialmente enlazado con su cuenta de usuario y figurará en su perfil/dashboard.</p>
    <p style="margin-top: 30px; font-size: 0.9em; color: #777; border-top: 1px solid #eaeaea; padding-top: 10px;">
        Este es un correo automático enviado por SKMS-Unimar.
    </p>
</body>
</html>
