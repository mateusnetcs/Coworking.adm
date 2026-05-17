<?php

return [

    'admin_email' => env('ADMIN_EMAIL'),

    'frontend_url' => rtrim(env('FRONTEND_URL', env('APP_URL', 'http://localhost')), '/'),

    /** Primeiro horário reservável (início da faixa, ex.: 14 = 14:00–15:00). */
    'booking_start_hour' => (int) env('COWORKING_START_HOUR', 14),

    /** Último horário de término permitido (ex.: 22 = reservas até 21:00–22:00). */
    'booking_end_hour' => (int) env('COWORKING_END_HOUR', 22),

    /** Quantidade de computadores disponíveis para reserva. */
    'computer_count' => (int) env('COWORKING_COMPUTER_COUNT', 2),

    /** Minutos após criar a reserva em que o usuário pode editar (ignorado se edit_window_seconds estiver definido). */
    'edit_window_minutes' => (int) env('COWORKING_EDIT_WINDOW_MINUTES', 10),

    /** Segundos para editar (tem prioridade sobre minutos). Ex.: 30 para testes. */
    'edit_window_seconds' => ($v = env('COWORKING_EDIT_WINDOW_SECONDS')) !== null && $v !== ''
        ? (int) $v
        : null,

    /** Horas de antecedência mínimas para cancelar (após o fim da janela de edição). */
    'cancel_hours_before' => (int) env('COWORKING_CANCEL_HOURS_BEFORE', 3),

    'space_types' => [
        'computer' => 'Uso de computador',
        'meeting_room' => 'Sala para reunião / apresentação',
        'both' => 'Sala de reunião + computador(es)',
    ],

];
