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
                                <th class="px-md py-sm font-semibold">Espaço</th>
                                <th class="px-md py-sm font-semibold">Contato</th>
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
                                <td class="px-md py-sm max-w-[180px]">
                                    <div class="font-medium">{{ formatSpace(r) }}</div>
                                    <div v-if="r.institution" class="text-label-sm text-slate-500">{{ r.institution }}</div>
                                </td>
                                <td class="px-md py-sm">
                                    <div class="text-label-sm">{{ r.contact_email }}</div>
                                    <div v-if="r.phone" class="mt-1 flex flex-wrap items-center gap-2">
                                        <span class="text-label-sm text-slate-500">{{ formatPhoneDisplay(r.phone) }}</span>
                                        <a
                                            v-if="whatsappLink(r.phone)"
                                            :href="whatsappLink(r.phone)"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1 h-7 px-2.5 rounded-lg text-label-sm font-semibold bg-[#25D366] text-white hover:bg-[#1da851] transition-colors shrink-0"
                                            title="Abrir conversa no WhatsApp"
                                        >
                                            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                            </svg>
                                            WhatsApp
                                        </a>
                                    </div>
                                </td>
                                <td class="px-md py-sm">{{ r.course_period ? `${r.course_period}º` : '—' }}</td>
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
                                            @click="requestCancelReservation(r)"
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

        <AppActionDialog
            :open="cancelDialogOpen"
            variant="danger"
            title="Cancelar reserva?"
            :message="cancelDialogMessage"
            confirm-label="Sim, cancelar"
            cancel-label="Voltar"
            @confirm="confirmCancelReservation"
            @cancel="cancelDialogOpen = false"
        />

        <AppActionDialog
            :open="feedbackDialog.open"
            :variant="feedbackDialog.variant"
            :title="feedbackDialog.title"
            :message="feedbackDialog.message"
            confirm-label="OK"
            :show-cancel="false"
            @confirm="feedbackDialog.open = false"
            @cancel="feedbackDialog.open = false"
        />
    </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import ReservationModal from '../../components/ReservationModal.vue';
import AppActionDialog from '../../components/AppActionDialog.vue';
import { api } from '../../bootstrap';
import { formatBrazilPhoneDisplay, whatsappUrl } from '../../utils/phone';

const users = ref([]);
const reservations = ref([]);
const loading = ref(false);
const saving = ref(false);
const error = ref('');
const formError = ref('');
const modalOpen = ref(false);
const cancelDialogOpen = ref(false);
const cancelTarget = ref(null);
const feedbackDialog = ref({ open: false, variant: 'success', title: '', message: '' });
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

function formatSpace(r) {
    const labels = {
        computer: 'Computador',
        meeting_room: 'Sala de reunião',
        both: 'Sala + computador(es)',
    };
    const base = labels[r.space_type] ?? r.space_type ?? '—';
    if (r.computers?.length) {
        const pcs = [...r.computers].sort((a, b) => a - b).map((n) => `PC ${n}`).join(', ');
        return r.space_type === 'computer' ? pcs : `${base} (${pcs})`;
    }
    return base;
}

function formatPhoneDisplay(phone) {
    return formatBrazilPhoneDisplay(phone);
}

function whatsappLink(phone) {
    return whatsappUrl(phone);
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
            contact_email: payload.contactEmail,
            phone: payload.phone,
            institution: payload.institution,
            space_type: payload.spaceType,
            computers: payload.computers,
            terms_accepted: payload.termsAccepted,
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

const cancelDialogMessage = computed(() => {
    if (!cancelTarget.value) {
        return '';
    }
    const name = cancelTarget.value.booker?.name ?? 'usuário';
    return `Deseja cancelar a reserva de ${name}?\n\nEsta ação não pode ser desfeita.`;
});

function requestCancelReservation(r) {
    cancelTarget.value = r;
    cancelDialogOpen.value = true;
}

async function confirmCancelReservation() {
    const r = cancelTarget.value;
    if (!r) {
        return;
    }
    cancelDialogOpen.value = false;
    try {
        await api.delete(`/api/admin/reservations/${r.id}`);
        reservations.value = reservations.value.filter((x) => x.id !== r.id);
        cancelTarget.value = null;
        feedbackDialog.value = {
            open: true,
            variant: 'success',
            title: 'Reserva cancelada',
            message: 'A reserva foi cancelada com sucesso.',
        };
    } catch (e) {
        feedbackDialog.value = {
            open: true,
            variant: 'info',
            title: 'Não foi possível cancelar',
            message: e.response?.data?.message ?? 'Falha ao cancelar reserva.',
        };
    }
}

onMounted(async () => {
    initFilters();
    await loadUsers();
    await loadReservations();
});
</script>
