<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva suscripción al newsletter</title>
    <style>
        body { font-family: Arial, sans-serif; color: #1f2937; background: #f9fafb; padding: 24px; }
        .card { max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 8px; padding: 24px; }
        h2 { color: #136CBA; margin-top: 0; }
        .label { color: #6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; }
        .value { margin-top: 6px; font-size: 16px; }
        .footer { margin-top: 24px; font-size: 12px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Nueva suscripción al newsletter</h2>

        <div class="label">Email</div>
        <div class="value">
            <a href="mailto:{{ $subscriber->email }}">{{ $subscriber->email }}</a>
        </div>

        <div class="footer">
            <p>Este mensaje fue generado automáticamente desde el formulario de newsletter.</p>
        </div>
    </div>
</body>
</html>
