import { api } from '../bootstrap';

export function formatDateParam(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');

    return `${y}-${m}-${d}`;
}

export async function fetchDayBookingStatus(date) {
    const { data } = await api.get('/api/booking-calendar/day', {
        params: { date: formatDateParam(date) },
    });

    return data;
}
