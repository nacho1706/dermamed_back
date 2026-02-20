<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4f46e5;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            padding: 30px;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 0 0 8px 8px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #4f46e5;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin-top: 20px;
        }
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #6b7280;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>DermaMED</h1>
    </div>
    <div class="content">
        <p>Hola <strong>{{ $user->name }}</strong>,</p>
        <p>Te han invitado a unirte a la plataforma de gestión médica <strong>DermaMED</strong>.</p>
        <p>Para activar tu cuenta y configurar tu contraseña, por favor haz clic en el siguiente botón:</p>
        
        <div style="text-align: center;">
            <a href="{{ $setupUrl }}" class="button" style="color: white;">Configurar mi cuenta</a>
        </div>
        
        <p>Este enlace expirará en 24 horas.</p>
        <p>Si no esperabas esta invitación, puedes ignorar este correo.</p>
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} DermaMED. Todos los derechos reservados.
    </div>
</body>
</html>
