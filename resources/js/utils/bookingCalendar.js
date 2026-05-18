import { api } from '../bootstrap';

/** Feriados nacionais fixos (mês-dia → nome). Fallback se a API falhar. */
const BR_FIXED_HOLIDAYS = {
    '01-01': 'Confraternização Universal',
    '04-21': 'Tiradentes',
    '05-01': 'Dia do Trabalho',
    '09-07': 'Independência do Brasil',
    '10-12': 'Nossa Senhora Aparecida',
    '11-02': 'Finados',
    '11-15': 'Proclamação da República',
    '11-20': 'Dia da Consciência Negra',
    '12-25': 'Natal',
};

export function formatDateParam(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');

    return `${y}-${m}-${d}`;
}

function monthDayKey(date) {
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');

    return `${m}-${d}`;
}

/**
 * Verificação local (fim de semana + feriados fixos). Usada se a API não responder.
 */
export function localDayBookingStatus(date) {
    const dayOfWeek = date.getDay();

    if (dayOfWeek === 0 || dayOfWeek === 6) {
        return {
            date: formatDateParam(date),
            bookable: false,
            type: 'weekend',
            message: 'Reservas canceladas - final de semana',
            label: null,
        };
    }

    const label = BR_FIXED_HOLIDAYS[monthDayKey(date)];

    if (label) {
        return {
            date: formatDateParam(date),
            bookable: false,
            type: 'holiday',
            message: `Reservas canceladas - feriado de ${label}`,
            label,
        };
    }

    return {
        date: formatDateParam(date),
        bookable: true,
        type: null,
        message: null,
        label: null,
    };
}

export async function fetchDayBookingStatus(date) {
    try {
        const { data } = await api.get('/api/booking-calendar/day', {
            params: { date: formatDateParam(date) },
        });

        return data;
    } catch {
        return localDayBookingStatus(date);
    }
}

export async function closeBookingDay(date, { reason, cancelReservations = true, blockNewReservations = true }) {
    const { data } = await api.post('/api/admin/booking-calendar/close-day', {
        date: formatDateParam(date),
        reason,
        cancel_reservations: cancelReservations,
        block_new_reservations: blockNewReservations,
    });

    return data;
}

export async function reopenBookingDay(date) {
    const { data } = await api.delete('/api/admin/booking-calendar/close-day', {
        params: { date: formatDateParam(date) },
    });

    return data;
}
