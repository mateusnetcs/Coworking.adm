<template>
    <div class="relative flex-1 overflow-y-auto p-margin-mobile md:p-margin-desktop h-full">
        <div class="max-w-[1120px] mx-auto w-full h-full flex flex-col lg:flex-row gap-lg pb-lg">
            <!-- Grade principal -->
            <div class="flex-1 bg-white rounded-xl shadow-level-1 border border-slate-200 flex flex-col overflow-hidden min-h-[600px]">
                <!-- Cabeçalho da data -->
                <div class="flex flex-wrap items-center justify-between gap-md p-lg border-b border-slate-200 bg-white z-10 sticky top-0">
                    <div class="flex items-center gap-md">
                        <button
                            type="button"
                            class="w-10 h-10 rounded-lg border border-slate-200 flex items-center justify-center text-slate-700 hover:bg-slate-50 transition-colors"
                            title="Dia anterior"
                            aria-label="Dia anterior"
                            @click="shiftDay(-1)"
                        >
                            <AppIcon name="chevron_left" />
                        </button>
                        <h1 class="text-headline-lg-mobile md:text-headline-lg font-bold text-on-surface capitalize">
                            {{ formattedSelectedDay }}
                        </h1>
                        <button
                            type="button"
                            class="w-10 h-10 rounded-lg border border-slate-200 flex items-center justify-center text-slate-700 hover:bg-slate-50 transition-colors"
                            title="Próximo dia"
                            aria-label="Próximo dia"
                            @click="shiftDay(1)"
                        >
                            <AppIcon name="chevron_right" />
                        </button>
                    </div>
                    <div class="flex flex-wrap gap-sm justify-end">
                        <button
                            type="button"
                            class="px-md h-10 bg-primary text-on-primary rounded-lg font-label-md flex items-center gap-xs hover:bg-primary-container transition-colors"
                            @click="goToday"
                        >
                            Hoje
                        </button>
                        <button
                            v-if="isAdmin && dayClosure?.reopenable"
                            type="button"
                            class="px-md h-10 border border-primary text-primary rounded-lg font-label-md hover:bg-primary/5 transition-colors flex items-center gap-xs"
                            @click="requestReopenDay"
                        >
                            Reabrir dia
                        </button>
                        <button
                            v-else-if="isAdmin && !dayClosure"
                            type="button"
                            class="px-md h-10 border border-error text-error rounded-lg font-label-md hover:bg-error-container transition-colors flex items-center gap-xs"
                            @click="openCloseDayDialog"
                        >
                            <AppIcon name="close" size="sm" />
                            Cancelar dia
                        </button>
                        <button
                            type="button"
                            class="px-md h-10 border border-slate-200 text-primary rounded-lg font-label-md hover:bg-slate-50 transition-colors hidden sm:flex items-center gap-xs disabled:opacity-50 disabled:pointer-events-none"
                            :disabled="Boolean(dayClosure)"
                            @click="openModal()"
                        >
                            <AppIcon name="add" size="sm" />
                            Nova reserva
                        </button>
                    </div>
                </div>

                <!-- Grade de horários -->
                <div class="flex-1 overflow-y-auto bg-slate-50 relative p-md flex flex-col gap-md min-h-0">
                    <div
                        v-if="dayClosure"
                        class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 text-center shrink-0"
                    >
                        <p class="text-body-sm font-semibold text-amber-900">
                            {{ dayClosure.message }}
                        </p>
                        <p v-if="dayClosure.label" class="text-label-sm text-amber-800/80 mt-1">
                            Não é possível criar ou alterar reservas neste dia.
                        </p>
                        <button
                            v-if="isAdmin && dayClosure.reopenable"
                            type="button"
                            class="mt-3 text-label-sm font-semibold text-primary hover:underline"
                            @click="requestReopenDay"
                        >
                            Reabrir dia para reservas
                        </button>
                    </div>

                    <p v-else class="text-label-sm text-slate-500 flex items-center gap-1 shrink-0">
                        <AppIcon name="check_box" size="sm" />
                        Marque os horários em sequência (sem pular horas).
                    </p>
                    <div
                        v-if="hasSelection && !selectionConflict && !selectionNotContiguous"
                        class="selection-banner shrink-0"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Horários selecionados</p>
                            <p class="text-lg font-bold text-primary mt-0.5">{{ selectionLabel }}</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto shrink-0">
                            <button type="button" class="h-10 px-4 bg-primary text-on-primary rounded-lg text-sm font-semibold hover:bg-primary-container transition-colors" @click="confirmSelection">
                                Reservar
                            </button>
                            <button type="button" class="h-10 px-4 border border-slate-300 text-slate-700 rounded-lg text-sm font-medium hover:bg-slate-50 transition-colors" @click="clearSelection">
                                Limpar
                            </button>
                        </div>
                    </div>
                    <p v-if="selectionNotContiguous" class="text-body-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 shrink-0">
                        Selecione horários em sequência (ex.: 15h, 16h e 17h). Não deixe horas vazias no meio.
                    </p>
                    <p v-if="hasSelection && selectionConflict" class="text-body-sm text-error bg-error-container rounded-lg px-4 py-3 shrink-0">
                        Algum horário marcado conflita com reserva existente.
                    </p>
                    <div v-if="loadingGrid" class="absolute inset-0 flex items-center justify-center bg-slate-50/80 z-30">
                        <span class="text-body-sm text-slate-500">Carregando agenda…</span>
                    </div>

                    <ul v-if="!dayClosure" class="space-y-2 list-none p-0 m-0">
                        <li v-for="hour in hours" :key="hour">
                            <label class="slot-row" :class="slotPickerClass(hour)">
                                <input
                                    type="checkbox"
                                    class="slot-row__checkbox"
                                    :checked="isHourSelected(hour)"
                                    :disabled="slotHasReservation(hour) || isHourPast(hour)"
                                    @change="toggleHour(hour)"
                                />
                                <div class="slot-row__body min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-2 flex-wrap">
                                        <span class="text-sm font-semibold text-on-surface">
                                            {{ padHour(hour) }}:00 – {{ padHour(hour + 1) }}:00
                                        </span>
                                        <span
                                            v-if="isCurrentHour(hour)"
                                            class="text-[10px] font-bold uppercase tracking-wide text-error bg-error-container px-1.5 py-0.5 rounded"
                                        >
                                            Agora
                                        </span>
                                        <span
                                            v-else-if="slotHasReservation(hour)"
                                            class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 bg-slate-200 px-1.5 py-0.5 rounded"
                                        >
                                            Ocupado
                                        </span>
                                        <span
                                            v-else-if="isHourPast(hour)"
                                            class="text-[10px] font-semibold uppercase tracking-wide text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded"
                                        >
                                            Passado
                                        </span>
                                    </div>
                                    <div
                                        v-if="reservationForHour(hour)"
                                        class="mt-1 flex flex-wrap items-center gap-2"
                                    >
                                        <button
                                            type="button"
                                            class="text-xs text-slate-500 truncate text-left hover:text-primary min-w-0 flex-1"
                                            @click.stop="selectReservation(reservationForHour(hour))"
                                        >
                                            {{ reservationForHour(hour).booker?.name ?? 'Reserva' }}
                                            · {{ formatTimeRange(reservationForHour(hour).starts_at, reservationForHour(hour).ends_at) }}
                                        </button>
                                        <button
                                            v-if="canCancel(reservationForHour(hour))"
                                            type="button"
                                            class="text-[10px] font-semibold uppercase tracking-wide text-error hover:bg-error-container px-1.5 py-0.5 rounded shrink-0"
                                            title="Cancelar reserva"
                                            @click.stop="requestCancelReservation(reservationForHour(hour))"
                                        >
                                            Cancelar
                                        </button>
                                    </div>
                                </div>
                            </label>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Painel lateral -->
            <aside class="w-full lg:w-80 flex flex-col gap-lg shrink-0">
                <!-- Resumo -->
                <div class="bg-white rounded-xl shadow-level-1 border border-slate-200 p-lg">
                    <h2 class="text-headline-sm font-semibold text-on-surface mb-md">Resumo do dia</h2>
                    <div class="grid grid-cols-2 gap-sm">
                        <div class="bg-slate-50 rounded-lg p-sm border border-slate-100 text-center">
                            <div class="text-headline-lg font-bold text-primary">{{ dayReservations.length }}</div>
                            <div class="text-label-sm text-slate-500 uppercase tracking-wider">Agendados</div>
                        </div>
                        <div class="bg-success-green-bg rounded-lg p-sm border border-success-green text-center">
                            <div class="text-headline-lg font-bold text-success-green-text">{{ freeSlotsCount }}</div>
                            <div class="text-label-sm text-success-green-text uppercase tracking-wider">Disponíveis</div>
                        </div>
                    </div>
                    <div class="mt-lg">
                        <div class="flex justify-between items-center mb-xs text-label-sm text-slate-600">
                            <span>Ocupação</span>
                            <span>{{ occupancyPercent }}%</span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-2">
                            <div
                                class="bg-primary h-2 rounded-full transition-all duration-300"
                                :style="{ width: `${occupancyPercent}%` }"
                            />
                        </div>
                    </div>
                </div>

                <!-- Próximos -->
                <div class="bg-white rounded-xl shadow-level-1 border border-slate-200 p-lg flex-1 min-h-[200px]">
                    <div
                        v-if="isAdmin && dayReservations.length && !dayClosure"
                        class="mb-md rounded-lg border border-error/30 bg-error-container/40 px-3 py-2 flex flex-wrap items-center justify-between gap-2"
                    >
                        <p class="text-label-sm text-error font-medium">
                            {{ dayReservations.length }} reserva(s) neste dia
                        </p>
                        <button
                            type="button"
                            class="h-8 px-3 rounded-lg text-label-sm font-semibold border border-error text-error hover:bg-error-container"
                            @click="openCloseDayDialog"
                        >
                            Cancelar dia inteiro
                        </button>
                    </div>
                    <div class="flex justify-between items-center mb-md">
                        <h2 class="text-headline-sm font-semibold text-on-surface">Próximos</h2>
                        <button
                            type="button"
                            class="text-primary hover:bg-slate-50 p-1 rounded"
                            title="Atualizar"
                            aria-label="Atualizar lista"
                            @click="loadReservations"
                        >
                            <AppIcon name="refresh" />
                        </button>
                    </div>
                    <ul v-if="upcomingList.length" class="space-y-sm">
                        <li
                            v-for="r in upcomingList"
                            :key="r.id"
                            class="p-sm hover:bg-slate-50 rounded-lg transition-colors flex gap-sm items-start cursor-pointer border border-transparent hover:border-slate-200"
                            @click="selectReservation(r)"
                        >
                            <div class="w-2 h-2 mt-2 rounded-full bg-primary shrink-0" />
                            <div class="min-w-0 flex-1">
                                <div class="text-label-md font-medium text-on-surface truncate">
                                    {{ r.booker?.name ?? 'Reserva' }}
                                </div>
                                <div class="text-label-sm text-slate-500">
                                    {{ formatTimeRange(r.starts_at, r.ends_at) }}
                                </div>
                                <p v-if="companionNames(r.companions)" class="text-label-sm text-slate-400 truncate mt-0.5">
                                    {{ companionNames(r.companions) }}
                                </p>
                            </div>
                            <button
                                v-if="canCancel(r)"
                                type="button"
                                class="text-error hover:bg-error-container p-1 rounded shrink-0"
                                title="Cancelar"
                                @click.stop="requestCancelReservation(r)"
                            >
                                <AppIcon name="close" size="sm" />
                            </button>
                        </li>
                    </ul>
                    <p v-else class="text-body-sm text-slate-500 text-center py-md">
                        Nenhuma reserva neste dia.
                    </p>
                </div>
            </aside>
        </div>

        <!-- Modal nova reserva -->
        <ReservationModal
            :open="modalOpen"
            :starts-at="modalStartsAt"
            :ends-at="modalEndsAt"
            :min-datetime="minDatetimeLocal"
            :user-email="user?.email ?? ''"
            :edit-reservation="editingReservation"
            :title="editingReservation ? 'Editar reserva' : 'Nova reserva'"
            :saving="saving"
            :error="formError"
            @close="closeModal"
            @submit="saveReservation"
        />

        <ReservationSuccessAlert
            :open="successAlertOpen"
            :reservation="lastCreatedReservation"
            @acknowledge="closeSuccessAlert"
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
            :open="closeDayDialogOpen"
            variant="danger"
            title="Cancelar dia de reservas?"
            :message="closeDayDialogMessage"
            confirm-label="Sim, cancelar dia"
            cancel-label="Voltar"
            @confirm="confirmCloseDay"
            @cancel="closeDayDialogOpen = false"
        >
            <label class="block mt-4">
                <span class="text-label-sm text-slate-600">Motivo (ex.: ponto facultativo, evento)</span>
                <input
                    v-model="closeDayReason"
                    type="text"
                    maxlength="120"
                    class="mt-1 w-full h-10 px-3 border border-slate-200 rounded-lg text-sm"
                    placeholder="Ponto facultativo"
                />
            </label>
            <label class="flex items-center gap-2 mt-3 text-sm text-slate-700">
                <input v-model="closeDayCancelReservations" type="checkbox" class="rounded border-slate-300" />
                Cancelar todas as reservas existentes ({{ dayReservations.length }})
            </label>
            <label class="flex items-center gap-2 mt-2 text-sm text-slate-700">
                <input v-model="closeDayBlockNew" type="checkbox" class="rounded border-slate-300" />
                Bloquear novas reservas neste dia
            </label>
        </AppActionDialog>

        <AppActionDialog
            :open="reopenDayDialogOpen"
            variant="confirm"
            title="Reabrir dia?"
            message="O dia voltará a aceitar novas reservas. Reservas já canceladas não serão restauradas."
            confirm-label="Reabrir dia"
            cancel-label="Voltar"
            @confirm="confirmReopenDay"
            @cancel="reopenDayDialogOpen = false"
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

        <!-- Painel detalhe / cancelar -->
        <div
            v-if="selected"
            class="fixed inset-0 z-40 flex items-end sm:items-center justify-center p-4 bg-black/30"
            @click.self="selected = null"
        >
            <div class="bg-white rounded-xl shadow-level-2 border border-slate-200 w-full max-w-md p-lg">
                <h3 class="text-headline-sm font-semibold text-on-surface mb-sm">
                    {{ selected.booker?.name ?? 'Reserva' }}
                </h3>
                <p class="text-body-sm text-slate-600 mb-md">
                    {{ formatTimeRange(selected.starts_at, selected.ends_at) }}
                </p>
                <p
                    v-if="isAdmin"
                    class="mb-md rounded-lg border border-primary/30 bg-primary/5 px-3 py-2 text-xs font-medium text-primary"
                >
                    Como administrador, você pode cancelar esta reserva em qualquer dia e horário.
                </p>
                <div v-else-if="selected.rules" class="mb-md space-y-2">
                    <div
                        v-if="selected.rules.in_edit_window"
                        class="rounded-lg border border-primary/30 bg-primary/5 px-3 py-2"
                    >
                        <p class="text-xs font-semibold text-primary">Edição disponível</p>
                        <p class="text-lg font-bold text-primary font-mono tabular-nums">{{ formatCountdown(selected.rules.edit_seconds_remaining) }}</p>
                    </div>
                    <div
                        v-if="selected.rules.cancel_countdown_visible"
                        class="rounded-lg border px-3 py-2"
                        :class="selected.rules.can_cancel ? 'border-amber-300 bg-amber-50' : 'border-slate-200 bg-slate-100'"
                    >
                        <p class="text-xs font-semibold" :class="selected.rules.can_cancel ? 'text-amber-800' : 'text-slate-600'">
                            Prazo para cancelar ({{ selected.rules.cancel_hours_before }}h antes)
                        </p>
                        <p v-if="selected.rules.can_cancel" class="text-lg font-bold font-mono tabular-nums text-amber-900">
                            {{ formatCountdown(selected.rules.cancel_seconds_remaining) }}
                        </p>
                        <p v-else class="text-xs text-slate-600 mt-1">{{ selected.rules.cancel_blocked_reason }}</p>
                    </div>
                </div>
                <div class="text-body-sm text-slate-600 space-y-xs mb-md">
                    <p v-if="selected.course_period">
                        <span class="font-medium text-on-surface">Período:</span>
                        {{ formatCoursePeriod(selected.course_period) }}
                    </p>
                    <p v-if="selected.activity">
                        <span class="font-medium text-on-surface">Atividade:</span>
                        {{ selected.activity }}
                    </p>
                    <p v-if="selected.attended_at" class="text-success-green-text font-medium">
                        Presença confirmada no coworking
                    </p>
                </div>
                <div v-if="selected.companions?.length" class="text-body-sm text-slate-500 mb-lg space-y-sm">
                    <p class="font-medium text-on-surface">Colegas presentes</p>
                    <ul class="space-y-xs list-disc pl-5">
                        <li v-for="(companion, idx) in selected.companions" :key="idx">
                            <span class="font-medium text-on-surface">{{ companionLabel(companion) }}</span>
                            <span v-if="companionPeriod(companion)"> — {{ formatCoursePeriod(companionPeriod(companion)) }}</span>
                            <span v-if="companionActivity(companion)">: {{ companionActivity(companion) }}</span>
                        </li>
                    </ul>
                </div>
                <div class="flex flex-col gap-2">
                    <button
                        v-if="canEdit(selected)"
                        type="button"
                        class="w-full h-10 bg-primary text-on-primary rounded-lg font-medium hover:bg-primary-container"
                        @click="openEditModal(selected)"
                    >
                        Editar reserva
                    </button>
                    <div class="flex gap-sm">
                        <button
                            type="button"
                            class="flex-1 h-10 border border-slate-200 rounded-lg text-secondary hover:bg-slate-50"
                            @click="selected = null"
                        >
                            Fechar
                        </button>
                        <button
                            v-if="canCancel(selected)"
                            type="button"
                            class="flex-1 h-10 bg-error text-on-error rounded-lg hover:opacity-90"
                            @click="requestCancelReservation(selected)"
                        >
                            Cancelar reserva
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ReservationModal from '../components/ReservationModal.vue';
import ReservationSuccessAlert from '../components/ReservationSuccessAlert.vue';
import AppActionDialog from '../components/AppActionDialog.vue';
import { END_HOUR, HOUR_SLOTS, START_HOUR } from '../constants/coworkingHours';
import { api } from '../bootstrap';
import { closeBookingDay, fetchDayBookingStatus, reopenBookingDay } from '../utils/bookingCalendar';
import { formatCountdown, refreshRulesMeta } from '../utils/reservationRules';

const route = useRoute();
const router = useRouter();

const user = ref(null);
const reservations = ref([]);
const loadingGrid = ref(false);
const dayClosure = ref(null);
const saving = ref(false);
const formError = ref('');
const selectedDate = ref(startOfDay(new Date()));
const selected = ref(null);
const modalOpen = ref(false);
const modalStartsAt = ref('');
const modalEndsAt = ref('');
const editingReservation = ref(null);
const successAlertOpen = ref(false);
const lastCreatedReservation = ref(null);
const cancelDialogOpen = ref(false);
const cancelTarget = ref(null);
const closeDayDialogOpen = ref(false);
const closeDayReason = ref('Ponto facultativo');
const closeDayCancelReservations = ref(true);
const closeDayBlockNew = ref(true);
const reopenDayDialogOpen = ref(false);
const feedbackDialog = ref({ open: false, variant: 'success', title: '', message: '' });

const selectedHours = ref([]);
let rulesTimer = null;

const hours = HOUR_SLOTS;

const formattedSelectedDay = computed(() =>
    selectedDate.value.toLocaleDateString('pt-BR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    }),
);

const dayReservations = computed(() =>
    reservations.value.filter((r) => isSameDay(new Date(r.starts_at), selectedDate.value)),
);

const upcomingList = computed(() =>
    [...dayReservations.value].sort(
        (a, b) => new Date(a.starts_at) - new Date(b.starts_at),
    ),
);

const bookedSlotsCount = computed(() => {
    const occupied = new Set();
    dayReservations.value.forEach((r) => {
        const start = new Date(r.starts_at);
        const end = new Date(r.ends_at);
        for (let h = start.getHours(); h < end.getHours() || (h === end.getHours() && end.getMinutes() > 0); h++) {
            if (h >= START_HOUR && h < END_HOUR) {
                occupied.add(h);
            }
        }
    });
    return occupied.size;
});

/** Horários que ainda podem ser reservados (não ocupados e não passados). */
const freeSlotsCount = computed(() => {
    if (dayClosure.value) {
        return 0;
    }

    return hours.filter((hour) => !slotHasReservation(hour) && !isHourPast(hour)).length;
});

const occupancyPercent = computed(() => {
    if (hours.length === 0) {
        return 0;
    }
    return Math.round((bookedSlotsCount.value / hours.length) * 100);
});

const isAdmin = computed(() => user.value?.roles?.some((role) => role.name === 'admin') ?? false);

const minDatetimeLocal = computed(() => toLocalDatetimeValue(new Date()));

const selectionNotContiguous = computed(() => {
    if (selectedHours.value.length <= 1) {
        return false;
    }
    const sorted = [...selectedHours.value].sort((a, b) => a - b);
    for (let i = 1; i < sorted.length; i++) {
        if (sorted[i] - sorted[i - 1] !== 1) {
            return true;
        }
    }
    return false;
});

const selectionRange = computed(() => {
    if (selectedHours.value.length === 0 || selectionNotContiguous.value) {
        return null;
    }
    const sorted = [...selectedHours.value].sort((a, b) => a - b);

    return { startHour: sorted[0], endHour: sorted[sorted.length - 1] + 1 };
});

const hasSelection = computed(() => selectedHours.value.length > 0);

const selectionConflict = computed(() => {
    if (selectedHours.value.some((hour) => slotHasReservation(hour) || isHourPast(hour))) {
        return true;
    }
    const range = selectionRange.value;
    if (!range) {
        return false;
    }

    return rangeOverlapsReservation(range.startHour, range.endHour);
});

const selectionLabel = computed(() => {
    const range = selectionRange.value;
    if (!range) {
        return '';
    }

    const start = new Date(selectedDate.value);
    start.setHours(range.startHour, 0, 0, 0);
    const end = new Date(selectedDate.value);
    end.setHours(range.endHour, 0, 0, 0);

    return `${pad(start.getHours())}:${pad(start.getMinutes())} – ${pad(end.getHours())}:${pad(end.getMinutes())}`;
});

function startOfDay(d) {
    const x = new Date(d);
    x.setHours(0, 0, 0, 0);
    return x;
}

function isSameDay(a, b) {
    return (
        a.getFullYear() === b.getFullYear()
        && a.getMonth() === b.getMonth()
        && a.getDate() === b.getDate()
    );
}

function pad(n) {
    return String(n).padStart(2, '0');
}

function padHour(h) {
    return pad(h);
}

function toLocalDatetimeValue(d) {
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function parseLocalDatetime(value) {
    if (!value) {
        return null;
    }
    const [datePart, timePart] = value.split('T');
    const [y, mo, d] = datePart.split('-').map(Number);
    const [h, mi] = (timePart || '00:00').split(':').map(Number);

    return new Date(y, mo - 1, d, h, mi, 0, 0);
}

function isHourPast(hour) {
    if (!isSameDay(selectedDate.value, new Date())) {
        return false;
    }
    const slotStart = new Date(selectedDate.value);
    slotStart.setHours(hour, 0, 0, 0);

    return slotStart.getTime() <= Date.now();
}

function nextAvailableHour() {
    for (const hour of hours) {
        if (!isHourPast(hour) && !slotHasReservation(hour)) {
            return hour;
        }
    }
    return null;
}

function formatTimeRange(start, end) {
    const a = new Date(start);
    const b = new Date(end);
    return `${pad(a.getHours())}:${pad(a.getMinutes())} - ${pad(b.getHours())}:${pad(b.getMinutes())}`;
}

function formatCoursePeriod(period) {
    if (!period) {
        return '';
    }
    return `${period}º período`;
}

function companionNames(companions) {
    if (!companions?.length) {
        return '';
    }
    return companions
        .map((companion) => companionLabel(companion))
        .filter(Boolean)
        .join(', ');
}

function companionLabel(companion) {
    if (typeof companion === 'string') {
        return companion;
    }
    return companion?.name ?? '';
}

function companionPeriod(companion) {
    if (typeof companion === 'object' && companion !== null) {
        return companion.course_period ?? companion.coursePeriod ?? null;
    }
    return null;
}

function companionActivity(companion) {
    if (typeof companion === 'object' && companion !== null) {
        return companion.activity ?? '';
    }
    return '';
}

function slotHasReservation(hour) {
    return reservationForHour(hour) !== null;
}

function reservationForHour(hour) {
    const slotStart = new Date(selectedDate.value);
    slotStart.setHours(hour, 0, 0, 0);
    const slotEnd = new Date(selectedDate.value);
    slotEnd.setHours(hour + 1, 0, 0, 0);

    return dayReservations.value.find((r) => {
        const start = new Date(r.starts_at);
        const end = new Date(r.ends_at);
        return start < slotEnd && end > slotStart;
    }) ?? null;
}

function isCurrentHour(hour) {
    if (!isSameDay(selectedDate.value, new Date())) {
        return false;
    }
    return new Date().getHours() === hour;
}

function rangeOverlapsReservation(startHour, endHour) {
    const rangeStart = new Date(selectedDate.value);
    rangeStart.setHours(startHour, 0, 0, 0);
    const rangeEnd = new Date(selectedDate.value);
    rangeEnd.setHours(endHour, 0, 0, 0);

    return dayReservations.value.some((r) => {
        const start = new Date(r.starts_at);
        const end = new Date(r.ends_at);

        return start < rangeEnd && end > rangeStart;
    });
}

function isHourSelected(hour) {
    return selectedHours.value.includes(hour);
}

function toggleHour(hour) {
    if (dayClosure.value || slotHasReservation(hour) || isHourPast(hour)) {
        return;
    }
    if (isHourSelected(hour)) {
        selectedHours.value = selectedHours.value.filter((h) => h !== hour);
    } else {
        selectedHours.value = [...selectedHours.value, hour];
    }
}

function slotPickerClass(hour) {
    if (slotHasReservation(hour) || isHourPast(hour)) {
        return 'slot-row--disabled';
    }
    if (isHourSelected(hour)) {
        return 'slot-row--selected';
    }
    if (isCurrentHour(hour)) {
        return 'slot-row--now';
    }
    return 'slot-row--available';
}

function clearSelection() {
    selectedHours.value = [];
}

function confirmSelection() {
    if (dayClosure.value) {
        return;
    }

    const range = selectionRange.value;
    if (!range || selectionConflict.value || selectionNotContiguous.value) {
        return;
    }

    const start = new Date(selectedDate.value);
    start.setHours(range.startHour, 0, 0, 0);
    const end = new Date(selectedDate.value);
    end.setHours(range.endHour, 0, 0, 0);

    if (start.getTime() < Date.now()) {
        formError.value = 'Um ou mais horários selecionados já passaram. Escolha horários futuros.';
        return;
    }

    modalStartsAt.value = toLocalDatetimeValue(start);
    modalEndsAt.value = toLocalDatetimeValue(end);
    formError.value = '';
    modalOpen.value = true;
    clearSelection();
}

function shiftDay(delta) {
    const d = new Date(selectedDate.value);
    d.setDate(d.getDate() + delta);
    selectedDate.value = startOfDay(d);
    clearSelection();
    loadReservations();
}

function goToday() {
    selectedDate.value = startOfDay(new Date());
    clearSelection();
    loadReservations();
}

function openModal() {
    if (dayClosure.value) {
        showFeedback('warning', 'Dia indisponível', dayClosure.value.message);
        return;
    }

    editingReservation.value = null;
    const first = isSameDay(selectedDate.value, new Date())
        ? nextAvailableHour()
        : START_HOUR;
    if (first === null) {
        formError.value = 'Não há mais horários disponíveis para hoje.';
        return;
    }
    const start = new Date(selectedDate.value);
    start.setHours(first, 0, 0, 0);
    const end = new Date(start);
    end.setHours(Math.min(first + 2, END_HOUR), 0, 0, 0);
    modalStartsAt.value = toLocalDatetimeValue(start);
    modalEndsAt.value = toLocalDatetimeValue(end);
    formError.value = '';
    modalOpen.value = true;
    router.replace({ name: 'console', query: {} });
}

const cancelDialogMessage = computed(() => {
    if (!cancelTarget.value) {
        return '';
    }
    const r = cancelTarget.value;
    const who = r.booker?.name ?? 'usuário';
    const when = formatTimeRange(r.starts_at, r.ends_at);
    if (isAdmin.value) {
        return `Cancelar a reserva de ${who} (${when})?\n\nComo administrador, o cancelamento vale para qualquer dia. Esta ação não pode ser desfeita.`;
    }
    return `Deseja cancelar a reserva de ${when}?\n\nEsta ação não pode ser desfeita.`;
});

function closeModal() {
    modalOpen.value = false;
    editingReservation.value = null;
    formError.value = '';
}

function closeSuccessAlert() {
    successAlertOpen.value = false;
    lastCreatedReservation.value = null;
}

function openEditModal(r) {
    if (!canEdit(r)) {
        return;
    }
    if (dayClosure.value) {
        showFeedback('warning', 'Dia indisponível', dayClosure.value.message);
        return;
    }
    editingReservation.value = r;
    selected.value = null;
    formError.value = '';
    modalOpen.value = true;
}

function showFeedback(variant, title, message) {
    feedbackDialog.value = { open: true, variant, title, message };
}

function normalizeReservations(list) {
    return list.map((r) => ({
        ...r,
        rules: r.rules ? refreshRulesMeta(r.rules, { isAdmin: isAdmin.value }) : r.rules,
    }));
}

function tickRules() {
    reservations.value = normalizeReservations(reservations.value);
    if (selected.value) {
        const updated = reservations.value.find((r) => r.id === selected.value.id);
        if (updated) {
            selected.value = updated;
        }
    }
    if (lastCreatedReservation.value?.rules) {
        lastCreatedReservation.value = {
            ...lastCreatedReservation.value,
            rules: refreshRulesMeta(lastCreatedReservation.value.rules, { isAdmin: isAdmin.value }),
        };
    }
}

function selectReservation(r) {
    selected.value = r;
}

function canEdit(r) {
    if (!user.value || r.user_id !== user.value.id) {
        return false;
    }
    return Boolean(r.rules?.can_edit);
}

function canCancel(r) {
    if (!user.value || !r) {
        return false;
    }
    if (isAdmin.value) {
        return true;
    }
    if (r.user_id !== user.value.id) {
        return false;
    }
    return Boolean(r.rules?.can_cancel);
}

async function loadMe() {
    const { data } = await api.get('/api/user');
    user.value = data;
}

const closeDayDialogMessage = computed(() => {
    const dateLabel = selectedDate.value.toLocaleDateString('pt-BR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    });
    const count = dayReservations.value.length;
    if (count === 0) {
        return `Fechar ${dateLabel} para reservas?\n\nNão há reservas agendadas, mas o dia ficará bloqueado para novos agendamentos.`;
    }
    return `Cancelar todas as reservas de ${dateLabel}?\n\n${count} reserva(s) serão removidas. Use para eventos ou ponto facultativo.`;
});

function applyDayClosureStatus(status) {
    dayClosure.value = status.bookable
        ? null
        : {
            message: status.message,
            type: status.type,
            label: status.label,
            reopenable: Boolean(status.reopenable),
        };
}

async function loadDayClosure() {
    const status = await fetchDayBookingStatus(selectedDate.value);
    applyDayClosureStatus(status);
}

async function loadReservations() {
    loadingGrid.value = true;
    try {
        await loadDayClosure();
        clearSelection();

        const from = new Date(selectedDate.value);
        from.setDate(from.getDate() - 1);
        const to = new Date(selectedDate.value);
        to.setDate(to.getDate() + 2);
        const { data } = await api.get('/api/reservations', {
            params: {
                from: from.toISOString(),
                to: to.toISOString(),
            },
        });
        reservations.value = normalizeReservations(data.data ?? data);
    } catch (e) {
        formError.value = e.response?.data?.message ?? 'Falha ao carregar reservas.';
    } finally {
        loadingGrid.value = false;
    }
}

async function saveReservation(payload) {
    if (payload.validationError) {
        formError.value = payload.validationError;
        return;
    }
    if (payload.reservationId) {
        await updateReservation(payload);
        return;
    }
    await createReservation(payload);
}

async function createReservation(payload) {
    if (payload.validationError) {
        formError.value = payload.validationError;
        return;
    }

    const {
        startsAt,
        endsAt,
        contactEmail,
        phone,
        institution,
        spaceType,
        computers,
        termsAccepted,
        coursePeriod,
        activity,
        companions,
    } = payload;
    const start = parseLocalDatetime(startsAt);
    const end = parseLocalDatetime(endsAt);
    if (!start || !end) {
        formError.value = 'Informe data e horário válidos.';
        return;
    }
    if (coursePeriod && (coursePeriod < 1 || coursePeriod > 8)) {
        formError.value = 'O período do curso deve ser entre 1 e 8.';
        return;
    }
    if (!activity || activity.length < 3) {
        formError.value = 'Descreva a atividade que será desenvolvida no coworking.';
        return;
    }
    if (start.getTime() < Date.now()) {
        formError.value = 'O horário de início já passou. Escolha um horário futuro.';
        return;
    }
    if (end <= start) {
        formError.value = 'O horário de término deve ser depois do início.';
        return;
    }
    const startMinutes = start.getHours() * 60 + start.getMinutes();
    const endMinutes = end.getHours() * 60 + end.getMinutes();
    if (startMinutes < START_HOUR * 60 || endMinutes > END_HOUR * 60) {
        formError.value = `As reservas são permitidas apenas entre ${padHour(START_HOUR)}:00 e ${padHour(END_HOUR)}:00.`;
        return;
    }

    saving.value = true;
    formError.value = '';
    try {
        const { data } = await api.post('/api/reservations', {
            starts_at: start.toISOString(),
            ends_at: end.toISOString(),
            contact_email: contactEmail,
            phone,
            institution: institution || null,
            space_type: spaceType,
            computers,
            terms_accepted: termsAccepted,
            course_period: coursePeriod || null,
            activity,
            companions,
        });
        closeModal();
        selected.value = null;
        await loadReservations();
        lastCreatedReservation.value = data.data ?? data;
        successAlertOpen.value = true;
    } catch (e) {
        const bag = e.response?.data?.errors;
        formError.value = bag
            ? Object.values(bag).flat().join(' ')
            : (e.response?.data?.message ?? 'Não foi possível criar a reserva.');
    } finally {
        saving.value = false;
    }
}

async function updateReservation(payload) {
    const {
        reservationId,
        startsAt,
        endsAt,
        contactEmail,
        phone,
        institution,
        spaceType,
        computers,
        termsAccepted,
        coursePeriod,
        activity,
        companions,
    } = payload;

    saving.value = true;
    formError.value = '';
    try {
        const start = parseLocalDatetime(startsAt);
        const end = parseLocalDatetime(endsAt);
        await api.put(`/api/reservations/${reservationId}`, {
            starts_at: start.toISOString(),
            ends_at: end.toISOString(),
            contact_email: contactEmail,
            phone,
            institution,
            space_type: spaceType,
            computers,
            terms_accepted: termsAccepted ?? true,
            course_period: coursePeriod,
            activity,
            companions,
        });
        closeModal();
        showFeedback('success', 'Reserva atualizada', 'Suas alterações foram salvas com sucesso.');
        await loadReservations();
    } catch (e) {
        const bag = e.response?.data?.errors;
        formError.value = bag
            ? Object.values(bag).flat().join(' ')
            : (e.response?.data?.message ?? 'Não foi possível atualizar a reserva.');
    } finally {
        saving.value = false;
    }
}

function requestCancelReservation(r) {
    if (!canCancel(r)) {
        showFeedback('info', 'Cancelamento indisponível', r.rules?.cancel_blocked_reason ?? 'Não é possível cancelar esta reserva.');
        return;
    }
    cancelTarget.value = r;
    cancelDialogOpen.value = true;
}

function openCloseDayDialog() {
    closeDayReason.value = 'Ponto facultativo';
    closeDayCancelReservations.value = dayReservations.value.length > 0;
    closeDayBlockNew.value = true;
    closeDayDialogOpen.value = true;
}

function requestReopenDay() {
    reopenDayDialogOpen.value = true;
}

async function confirmCloseDay() {
    const reason = closeDayReason.value.trim();
    if (reason.length < 3) {
        showFeedback('info', 'Informe o motivo', 'Descreva o motivo do fechamento (mínimo 3 caracteres).');
        return;
    }
    closeDayDialogOpen.value = false;
    try {
        const result = await closeBookingDay(selectedDate.value, {
            reason,
            cancelReservations: closeDayCancelReservations.value,
            blockNewReservations: closeDayBlockNew.value,
        });
        if (result.day) {
            applyDayClosureStatus(result.day);
        }
        const cancelled = result.cancelled_reservations ?? 0;
        showFeedback(
            'success',
            'Dia cancelado',
            cancelled > 0
                ? `${cancelled} reserva(s) cancelada(s). O dia está bloqueado para novas reservas.`
                : 'O dia foi bloqueado para novas reservas.',
        );
        await loadReservations();
    } catch (e) {
        showFeedback(
            'info',
            'Não foi possível cancelar o dia',
            e.response?.data?.message ?? 'Falha ao cancelar o dia.',
        );
    }
}

async function confirmReopenDay() {
    reopenDayDialogOpen.value = false;
    try {
        const result = await reopenBookingDay(selectedDate.value);
        if (result.day) {
            applyDayClosureStatus(result.day);
        } else {
            dayClosure.value = null;
        }
        showFeedback('success', 'Dia reaberto', 'Este dia voltou a aceitar novas reservas.');
        await loadReservations();
    } catch (e) {
        showFeedback(
            'info',
            'Não foi possível reabrir',
            e.response?.data?.message ?? 'Falha ao reabrir o dia.',
        );
    }
}

async function confirmCancelReservation() {
    const r = cancelTarget.value;
    if (!r) {
        return;
    }
    cancelDialogOpen.value = false;
    try {
        if (isAdmin.value) {
            await api.delete(`/api/admin/reservations/${r.id}`);
        } else {
            await api.delete(`/api/reservations/${r.id}`);
        }
        selected.value = null;
        cancelTarget.value = null;
        showFeedback('success', 'Reserva cancelada', 'A reserva foi cancelada com sucesso.');
        await loadReservations();
    } catch (e) {
        const msg = e.response?.data?.errors?.reservation?.[0]
            ?? e.response?.data?.message
            ?? 'Falha ao cancelar.';
        showFeedback('info', 'Não foi possível cancelar', msg);
    }
}

watch(
    () => route.query.action,
    (action) => {
        if (action === 'new') {
            openModal();
        }
    },
);

onMounted(async () => {
    await loadMe();
    await loadReservations();
    rulesTimer = setInterval(tickRules, 1000);
    if (route.query.action === 'new') {
        openModal();
    }
});

onUnmounted(() => {
    if (rulesTimer) {
        clearInterval(rulesTimer);
    }
});

</script>

<style scoped>
.selection-banner {
    @apply flex flex-col sm:flex-row sm:items-center gap-4 rounded-xl border border-primary/25 bg-white px-4 py-4 shadow-sm;
}

.slot-row {
    @apply flex items-start gap-3 w-full px-4 py-3.5 rounded-xl border border-slate-200 bg-white transition-all;
}

.slot-row__checkbox {
    @apply mt-0.5 w-[18px] h-[18px] rounded border-slate-300 text-primary focus:ring-2 focus:ring-primary/30 shrink-0 cursor-pointer disabled:cursor-not-allowed;
}

.slot-row__body {
    @apply pt-0.5;
}

.slot-row--available {
    @apply hover:border-primary/40 hover:bg-slate-50 cursor-pointer;
}

.slot-row--selected {
    @apply border-primary bg-primary/5 ring-2 ring-primary/15 cursor-pointer;
}

.slot-row--now {
    @apply border-slate-300 bg-slate-50 cursor-pointer;
}

.slot-row--disabled {
    @apply bg-slate-100 border-slate-200 opacity-80 cursor-not-allowed;
}

.slot-row--selected .slot-row__checkbox {
    @apply border-primary;
}
</style>

