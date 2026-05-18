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
        .info { color: #0369a1; font-weight: 600; font-size: .95rem; }
        p { color: #475569; line-height: 1.5; margin: 8px 0; }
        .meta { font-size: .9rem; background: #f1f5f9; border-radius: 8px; padding: 12px; margin-top: 16px; text-align: left; }
        .alert { border-radius: 8px; padding: 10px 12px; margin-top: 14px; font-size: .9rem; text-align: left; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-muted { background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }
        .btn { display: inline-block; margin-top: 16px; padding: 12px 20px; border: none; border-radius: 10px; background: #2563eb; color: #fff; font-size: 1rem; font-weight: 600; cursor: pointer; width: 100%; max-width: 280px; }
        .btn:hover { background: #1d4ed8; }
        .btn:disabled { background: #94a3b8; cursor: not-allowed; }
        a { color: #2563eb; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Coworking UEMASUL</h1>

        @if(session('attendance_success'))
            <div class="alert alert-success">{{ session('attendance_success') }}</div>
        @endif
        @if(session('attendance_error'))
            <div class="alert alert-error">{{ session('attendance_error') }}</div>
        @endif

        @if($valid && $reservation)
            <p class="ok">Comprovante autêntico</p>
            <p>Esta reserva está registrada no sistema.</p>
            <div class="meta">
                <p><strong>{{ $reservation->booker?->name ?? 'Reservante' }}</strong></p>
                <p>{{ $reservation->starts_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                    – {{ $reservation->ends_at?->timezone(config('app.timezone'))->format('H:i') }}</p>
                <p style="font-size:.8rem;color:#64748b;">Código: {{ $code }}</p>
            </div>

            @if($attendance)
                @if($attendance['attended'])
                    <div class="alert alert-success" style="margin-top:16px;">
                        ✓ {{ $attendance['message'] }}
                    </div>
                @elseif($attendance['can_mark'])
                    <form method="post" action="{{ route('reservation.mark-attendance', ['code' => $code]) }}" style="margin-top:8px;">
                        @csrf
                        <button type="submit" class="btn">Marcar presença</button>
                    </form>
                    <p style="font-size:.85rem;color:#64748b;margin-top:8px;">
                        Confirme sua chegada ao coworking no dia da reserva.
                    </p>
                @else
                    <div class="alert alert-muted">{{ $attendance['message'] }}</div>
                @endif
            @endif
        @else
            <p class="bad">Comprovante não encontrado</p>
            <p>O código informado não corresponde a uma reserva válida ou o documento pode ter sido alterado.</p>
        @endif

        <p style="margin-top:20px;"><a href="/">Voltar ao sistema</a></p>
    </div>
</body>
</html>
