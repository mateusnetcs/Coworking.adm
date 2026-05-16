<template>
    <section class="flex-1 overflow-y-auto p-margin-mobile md:p-margin-desktop">
        <div class="max-w-[1200px] mx-auto space-y-lg pb-lg">
            <div class="flex flex-wrap items-center justify-between gap-md">
                <div>
                    <h1 class="text-headline-lg font-bold text-on-surface">Reservas</h1>
                    <p class="text-body-sm text-slate-500 mt-1">
                        Visualize, cancele, registre presença ou crie reservas para qualquer usuário.
                    </p>
                </div>
                <button
                    type="button"
                    class="h-10 px-md bg-primary text-on-primary rounded-lg font-label-md flex items-center gap-sm hover:bg-primary-container transition-colors"
                    @click="openCreateModal"
                >
                    <AppIcon name="add" size="sm" />
                    Nova reserva (usuário)
                </button>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-level-1 p-md flex flex-wrap gap-md items-end">
                <label class="block min-w-[160px]">
                    <span class="text-label-sm text-slate-600 block mb-1">De</span>
                    <input v-model="filters.from" type="date" class="field-input" @change="loadReservations" />
                </label>
                <label class="block min-w-[160px]">
                    <span class="text-label-sm text-slate-600 block mb-1">Até</span>
                    <input v-model="filters.to" type="date" class="field-input" @change="loadReservations" />
                </label>
                <label class="block min-w-[220px] flex-1">
                    <span class="text-label-sm text-slate-600 block mb-1">Usuário</span>
                    <select v-model="filters.userId" class="field-input" @change="loadReservations">
                        <option value="">Todos</option>
                        <option v-for="u in users" :key="u.id" :value="String(u.id)">
                            {{ u.name }}
                        </option>
                    </select>
                </label>
                <button
                    type="button"
                    class="h-10 px-md border border-slate-200 rounded-lg text-secondary hover:bg-slate-50 flex items-center gap-1"
                    @click="loadReservations"
                >
                    <AppIcon name="refresh" size="sm" />
                    Atualizar
                </button>
            </div>

            <p v-if="error" class="text-body-sm text-error bg-error-container px-md py-sm rounded-lg">{{ error }}</p>

            <div v-if="loading" class="text-center py-xl text-slate-500">Carregando reservas…</div>

            <div v-else-if="!reservations.length" class="text-center py-xl text-slate-500 bg-white rounded-xl border border-slate-200">
                Nenhuma reserva encontrada no período.
            </div>

            <div v-else class="bg-white rounded-xl border border-slate-200 shadow-level-1 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-body-sm">
                        <thead class="bg-slate-50 border-b border-slate-200 text-label-sm text-slate-600">
                            <tr>
                                <th class="px-md py-sm font-semibold">Data / horário</th>
                                <th class="px-md py-sm font-semibold">Usuário</th>
                                <th class="px-md py-sm font-semibold">Período</th>
                                <th class="px-md py-sm font-semibold">Atividade</th>
                                <th class="px-md py-sm font-semibold">Presença</th>
                                <th class="px-md py-sm font-semibold text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="r in reservations"
                                :key="r.id"
                                class="border-b border-slate-100 hover:bg-slate-50/80"
                            >
                                <td class="px-md py-sm whitespace-nowrap">
                                    {{ formatDate(r.starts_at) }}
                                    <div class="text-label-sm text-slate-500">
                                        {{ formatTimeRange(r.starts_at, r.ends_at) }}
                                    </div>
                                </td>
                                <td class="px-md py-sm">
                                    <div class="font-medium text-on-surface">{{ r.booker?.name ?? '—' }}</div>
                                    <div class="text-label-sm text-slate-500">{{ r.booker?.email }}</div>
                                </td>
                                <td class="px-md py-sm">{{ r.course_period }}º</td>
                                <td class="px-md py-sm max-w-[200px] truncate" :title="r.activity">{{ r.activity }}</td>
                                <td class="px-md py-sm">
                                    <span
                                        v-if="r.attended_at"
                                        class="inline-flex items-center gap-1 text-success-green-text bg-success-green-bg px-2 py-0.5 rounded-full text-label-sm font-medium"
                                    >
                                        <AppIcon name="check_circle" size="sm" />
                                        Compareceu
                                    </span>
                                    <span v-else class="text-slate-400 text-label-sm">Pendente</span>
                                </td>
                                <td class="px-md py-sm">
                                    <div class="flex justify-end gap-1 flex-wrap">
                                        <button
                                            type="button"
                                            class="h-8 px-sm rounded-lg text-label-sm border transition-colors"
                                            :class="r.attended_at
                                                ? 'border-slate-200 text-slate-600 hover:bg-slate-50'
                                                : 'border-success-green text-success-green-text hover:bg-success-green-bg'"
                                            @click="toggleAttendance(r)"
                                        >
                                            {{ r.attended_at ? 'Remover presença' : 'Marcar presença' }}
                                        </button>
                                        <button
                                            type="button"
                                            class="h-8 px-sm rounded-lg text-label-sm border border-error text-error hover:bg-error-container"
                                            @click="cancelReservation(r)"
                                        >
                                            Cancelar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <ReservationModal
            :open="modalOpen"
            :starts-at="modalStartsAt"
            :ends-at="modalEndsAt"
            :min-datetime="minDatetimeLocal"
            :saving="saving"
            :error="formError"
            :for-user="true"
            :users="users"
            title="Nova reserva para usuário"
            @close="closeModal"
            @submit="createReservation"
        />
    </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import ReservationModal from '../../components/ReservationModal.vue';
import { api } from '../../bootstrap';

const users = ref([]);
const reservations = ref([]);
const loading = ref(false);
const saving = ref(false);
const error = ref('');
const formError = ref('');
const modalOpen = ref(false);
const modalStartsAt = ref('');
const modalEndsAt = ref('');

const filters = reactive({
    from: '',
    to: '',
    userId: '',
});

const minDatetimeLocal = computed(() => {
    const d = new Date();
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
});

function pad(n) {
    return String(n).padStart(2, '0');
}

function initFilters() {
    const today = new Date();
    const past = new Date(today);
    past.setDate(past.getDate() - 7);
    const future = new Date(today);
    future.setDate(future.getDate() + 30);
    filters.from = past.toISOString().slice(0, 10);
    filters.to = future.toISOString().slice(0, 10);
}

function formatDate(iso) {
    return new Date(iso).toLocaleDateString('pt-BR', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
    });
}

function formatTimeRange(start, end) {
    const a = new Date(start);
    const b = new Date(end);
    return `${pad(a.getHours())}:${pad(a.getMinutes())} – ${pad(b.getHours())}:${pad(b.getMinutes())}`;
}

function parseLocalDatetime(value) {
    const [datePart, timePart] = value.split('T');
    const [y, mo, d] = datePart.split('-').map(Number);
    const [h, mi] = (timePart || '00:00').split(':').map(Number);
    return new Date(y, mo - 1, d, h, mi, 0, 0);
}

async function loadUsers() {
    const { data } = await api.get('/api/admin/users');
    users.value = data;
}

async function loadReservations() {
    loading.value = true;
    error.value = '';
    try {
        const params = {};
        if (filters.from) {
            params.from = new Date(`${filters.from}T00:00:00`).toISOString();
        }
        if (filters.to) {
            params.to = new Date(`${filters.to}T23:59:59`).toISOString();
        }
        if (filters.userId) {
            params.user_id = filters.userId;
        }
        const { data } = await api.get('/api/admin/reservations', { params });
        reservations.value = data;
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Falha ao carregar reservas.';
    } finally {
        loading.value = false;
    }
}

function openCreateModal() {
    const start = new Date();
    start.setMinutes(0, 0, 0);
    start.setHours(start.getHours() + 1);
    const end = new Date(start);
    end.setHours(end.getHours() + 2);
    modalStartsAt.value = `${start.getFullYear()}-${pad(start.getMonth() + 1)}-${pad(start.getDate())}T${pad(start.getHours())}:${pad(start.getMinutes())}`;
    modalEndsAt.value = `${end.getFullYear()}-${pad(end.getMonth() + 1)}-${pad(end.getDate())}T${pad(end.getHours())}:${pad(end.getMinutes())}`;
    formError.value = '';
    modalOpen.value = true;
}

function closeModal() {
    modalOpen.value = false;
    formError.value = '';
}

async function createReservation(payload) {
    if (payload.validationError) {
        formError.value = payload.validationError;
        return;
    }
    const start = parseLocalDatetime(payload.startsAt);
    const end = parseLocalDatetime(payload.endsAt);
    saving.value = true;
    formError.value = '';
    try {
        await api.post('/api/admin/reservations', {
            user_id: payload.userId,
            starts_at: start.toISOString(),
            ends_at: end.toISOString(),
            course_period: payload.coursePeriod,
            activity: payload.activity,
            companions: payload.companions,
        });
        closeModal();
        await loadReservations();
    } catch (e) {
        const bag = e.response?.data?.errors;
        formError.value = bag
            ? Object.values(bag).flat().join(' ')
            : (e.response?.data?.message ?? 'Não foi possível criar a reserva.');
    } finally {
        saving.value = false;
    }
}

async function toggleAttendance(r) {
    try {
        const { data } = await api.patch(`/api/admin/reservations/${r.id}/attendance`, {
            attended: !r.attended_at,
        });
        const idx = reservations.value.findIndex((x) => x.id === r.id);
        if (idx !== -1) {
            reservations.value[idx] = data;
        }
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Falha ao atualizar presença.';
    }
}

async function cancelReservation(r) {
    if (!confirm(`Cancelar reserva de ${r.booker?.name ?? 'usuário'}?`)) {
        return;
    }
    try {
        await api.delete(`/api/admin/reservations/${r.id}`);
        reservations.value = reservations.value.filter((x) => x.id !== r.id);
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Falha ao cancelar reserva.';
    }
}

onMounted(async () => {
    initFilters();
    await loadUsers();
    await loadReservations();
});
</script>
