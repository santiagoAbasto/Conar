<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva solicitud de presupuesto</title>
    <style>
        body { margin: 0; padding: 24px; background: #f6f8fb; color: #1f2937; font-family: Arial, sans-serif; }
        .card { max-width: 680px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb; }
        .header { background: #136CBA; color: #fff; padding: 22px 24px; }
        .header h2 { margin: 0; font-size: 20px; }
        .body { padding: 24px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .item { padding: 12px; border: 1px solid #eef2f7; border-radius: 8px; }
        .label { color: #6b7280; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 5px; }
        .value { font-size: 14px; line-height: 1.45; }
        .section { margin-top: 18px; }
        .message { white-space: pre-wrap; padding: 14px; background: #f9fafb; border-radius: 8px; font-size: 14px; line-height: 1.55; }
        .cta { display: inline-block; margin-top: 18px; background: #25A7CA; color: #fff !important; text-decoration: none; padding: 10px 16px; border-radius: 999px; font-size: 14px; font-weight: 700; }
        .footer { margin-top: 20px; color: #9ca3af; font-size: 12px; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h2>Nueva solicitud de presupuesto</h2>
        </div>
        <div class="body">
            <div class="grid">
                <div class="item">
                    <div class="label">Solicitante</div>
                    <div class="value">{{ $quoteRequest->name }}</div>
                </div>
                <div class="item">
                    <div class="label">Email</div>
                    <div class="value"><a href="mailto:{{ $quoteRequest->email }}">{{ $quoteRequest->email }}</a></div>
                </div>
                <div class="item">
                    <div class="label">Teléfono</div>
                    <div class="value">{{ $quoteRequest->phone ?: 'No informado' }}</div>
                </div>
                <div class="item">
                    <div class="label">Empresa</div>
                    <div class="value">{{ $quoteRequest->company ?: 'No informada' }}</div>
                </div>
                <div class="item">
                    <div class="label">Instrumental</div>
                    <div class="value">{{ $quoteRequest->instrumental }}</div>
                </div>
                <div class="item">
                    <div class="label">Solución</div>
                    <div class="value">{{ $quoteRequest->solution }}</div>
                </div>
                <div class="item">
                    <div class="label">Cantidad</div>
                    <div class="value">{{ $quoteRequest->quantity ?: 'No indicada' }}</div>
                </div>
                <div class="item">
                    <div class="label">Adjuntos</div>
                    <div class="value">{{ count($attachments) }} archivo(s)</div>
                </div>
            </div>

            @if(!empty($quoteRequest->process_data))
                <div class="section">
                    <div class="label">Datos técnicos del proceso</div>
                    <div class="message">{{ json_encode($quoteRequest->process_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</div>
                </div>
            @endif

            <div class="section">
                <div class="label">Mensaje</div>
                <div class="message">{{ $quoteRequest->message }}</div>
            </div>

            <a class="cta" href="mailto:{{ $quoteRequest->email }}">Responder por correo</a>

            <div class="footer">
                <p>Este mensaje fue generado automáticamente desde el formulario de presupuesto de CONAR.</p>
            </div>
        </div>
    </div>
</body>
</html>
