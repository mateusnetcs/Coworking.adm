<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Validação de comprovante — {{ config('app.name') }}</title>
    <style>
        body { font-family: Inter, system-ui, sans-serif; background: #f8fafc; color: #0f172a; margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 32px; max-width: 480px; width: 100%; box-shadow: 0 10px 30px rgba(15,23,42,.08); text-align: center; }
        h1 { font-size: 1.25rem; margin: 0 0 8px; color: #2563eb; }
        .ok { color: #15803d; font-weight: 700; font-size: 1.1rem; }
        .bad { color: #b91c1c; font-weight: 700; font-size: 1.1rem; }
        p { color: #475569; line-height: 1.5; margin: 8px 0; }
        .meta { font-size: .9rem; background: #f1f5f9; border-radius: 8px; padding: 12px; margin-top: 16px; text-align: left; }
        a { color: #2563eb; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Coworking UEMASUL</h1>
        @if($valid && $reservation)
            <p class="ok">Comprovante autêntico</p>
            <p>Esta reserva está registrada no sistema.</p>
            <div class="meta">
                <p><strong>{{ $reservation->booker?->name ?? 'Reservante' }}</strong></p>
                <p>{{ $reservation->starts_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                    – {{ $reservation->ends_at?->timezone(config('app.timezone'))->format('H:i') }}</p>
                <p style="font-size:.8rem;color:#64748b;">Código: {{ $code }}</p>
            </div>
        @else
            <p class="bad">Comprovante não encontrado</p>
            <p>O código informado não corresponde a uma reserva válida ou o documento pode ter sido alterado.</p>
        @endif
        <p style="margin-top:20px;"><a href="/">Voltar ao sistema</a></p>
    </div>
</body>
</html>
