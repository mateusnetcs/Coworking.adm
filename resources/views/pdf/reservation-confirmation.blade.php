<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Comprovante de reserva</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 12px; margin: 0; padding: 24px; }
        .header { text-align: center; border-bottom: 2px solid #2563eb; padding-bottom: 16px; margin-bottom: 20px; }
        .header h1 { color: #2563eb; font-size: 22px; margin: 0 0 4px; }
        .header p { margin: 0; color: #64748b; font-size: 11px; }
        .badge { display: inline-block; background: #dbeafe; color: #1d4ed8; padding: 4px 10px; border-radius: 999px; font-size: 10px; font-weight: bold; margin-top: 8px; }
        .grid { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .grid td { padding: 8px 10px; border: 1px solid #e2e8f0; vertical-align: top; }
        .grid td.label { background: #f8fafc; font-weight: bold; width: 32%; color: #475569; }
        .qr-box { text-align: center; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-top: 8px; }
        .qr-box img { width: 180px; height: 180px; }
        .footer { margin-top: 20px; font-size: 10px; color: #64748b; text-align: center; line-height: 1.5; }
        .code { font-family: DejaVu Sans Mono, monospace; font-size: 10px; word-break: break-all; color: #334155; }
        h2 { font-size: 14px; color: #0f172a; margin: 18px 0 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Coworking UEMASUL</h1>
        <p>Administração · Comprovante de agendamento</p>
        <span class="badge">Apresente no dia da visita</span>
    </div>

    <table class="grid">
        <tr>
            <td class="label">Responsável</td>
            <td>{{ $bookerName }}</td>
        </tr>
        <tr>
            <td class="label">E-mail</td>
            <td>{{ $bookerEmail }}</td>
        </tr>
        @if($institution)
        <tr>
            <td class="label">Instituição</td>
            <td>{{ $institution }}</td>
        </tr>
        @endif
        @if($coursePeriod)
        <tr>
            <td class="label">Período do curso</td>
            <td>{{ $coursePeriod }}º período</td>
        </tr>
        @endif
        <tr>
            <td class="label">Data</td>
            <td>{{ $startsAt->locale('pt_BR')->translatedFormat('l, d \d\e F \d\e Y') }}</td>
        </tr>
        <tr>
            <td class="label">Horário</td>
            <td>{{ $startsAt->format('H:i') }} às {{ $endsAt->format('H:i') }}</td>
        </tr>
        <tr>
            <td class="label">Espaço</td>
            <td>{{ $spaceLabel }}</td>
        </tr>
        <tr>
            <td class="label">Atividade</td>
            <td>{{ $activity }}</td>
        </tr>
        <tr>
            <td class="label">Código</td>
            <td class="code">{{ $confirmationCode }}</td>
        </tr>
    </table>

    @if(count($companions) > 0)
        <h2>Colegas presentes</h2>
        <table class="grid">
            @foreach($companions as $companion)
                <tr>
                    <td class="label">{{ $companion['name'] ?? 'Colega' }}</td>
                    <td>
                        @if(!empty($companion['course_period']))
                            {{ $companion['course_period'] }}º período —
                        @endif
                        {{ $companion['activity'] ?? '' }}
                    </td>
                </tr>
            @endforeach
        </table>
    @endif

    <div class="qr-box">
        <p style="font-weight: bold; margin: 0 0 10px;">Validação do comprovante</p>
        @if($qrCodeBase64)
            <img src="data:image/png;base64,{{ $qrCodeBase64 }}" alt="QR Code" width="180" height="180">
        @else
            {!! $qrCodeHtml !!}
        @endif
        <p class="code" style="margin-top: 10px;">{{ $verificationUrl }}</p>
    </div>

    <div class="footer">
        Emitido em {{ $issuedAt->format('d/m/Y H:i') }} ({{ config('app.timezone') }}).<br>
        Escaneie o QR Code para confirmar a autenticidade deste documento.
    </div>
</body>
</html>
