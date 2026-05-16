<template>
    <div class="flex-1 overflow-y-auto p-margin-mobile md:p-margin-desktop h-full">
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
                    <div class="flex gap-sm">
                        <button
                            type="button"
                            class="px-md h-10 bg-primary text-on-primary rounded-lg font-label-md flex items-center gap-xs hover:bg-primary-container transition-colors"
                            @click="goToday"
                        >
                            Hoje
                        </button>
                        <button
                            type="button"
                            class="px-md h-10 border border-slate-200 text-primary rounded-lg font-label-md hover:bg-slate-50 transition-colors hidden sm:flex items-center gap-xs"
                            @click="openModal()"
                        >
                            <AppIcon name="add" size="sm" />
                            Nova reserva
                        </button>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="flex-1 overflow-y-auto bg-slate-50 relative p-md flex flex-col min-h-0">
                    <p class="text-label-sm text-slate-500 mb-sm flex items-center gap-1 shrink-0">
                        <AppIcon name="check_box" size="sm" />
                        Marque as caixas dos horários que deseja reservar (em sequência, sem pular horas).
                    </p>
                    <div
                        v-if="hasSelection && !selectionConflict && !selectionNotContiguous"
                        class="mb-sm flex flex-wrap items-center gap-sm rounded-lg border border-success-green bg-success-green-bg px-md py-sm shrink-0"
                    >
                        <span class="text-label-md font-medium text-success-green-text">{{ selectionLabel }}</span>
                        <button type="button" class="h-9 px-md bg-primary text-on-primary rounded-lg text-label-sm font-medium" @click="confirmSelection">
                            Reservar horários selecionados
                        </button>
                        <button type="button" class="h-9 px-md border border-slate-300 text-slate-600 rounded-lg text-label-sm" @click="clearSelection">
                            Limpar seleção
                        </button>
                    </div>
                    <p v-if="selectionNotContiguous" class="mb-sm text-body-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-md py-sm shrink-0">
                        Selecione horários em sequência (ex.: 10h, 11h e 12h). Não deixe horas vazias no meio.
                    </p>
                    <p v-if="hasSelection && selectionConflict" class="mb-sm text-body-sm text-error bg-error-container rounded-lg px-md py-sm shrink-0">
                        Algum horário marcado conflita com reserva existente.
                    </p>
                    <div v-if="loadingGrid" class="absolute inset-0 flex items-center justify-center bg-slate-50/80 z-30">
                        <span class="text-body-sm text-slate-500">Carregando agenda…</span>
                    </div>

                    <div class="relative w-full shrink-0" :style="{ height: `${timelineHeight}px` }">
                        <!-- Marcadores de hora -->
                        <div class="absolute left-0 top-0 bottom-0 w-16 border-r border-slate-200 flex flex-col text-slate-400 text-label-sm pb-8">
                            <div
                                v-for="hour in hours"
                                :key="hour"
                                class="relative h-20 -mt-3 text-right pr-sm shrink-0"
                            >
                                {{ padHour(hour) }}:00
                            </div>
                        </div>

                        <!-- Linhas da grade -->
                        <div class="absolute left-16 right-0 top-0 bottom-0 flex flex-col pointer-events-none">
                            <div
                                v-for="hour in hours"
                                :key="`line-${hour}`"
                                class="border-b border-slate-200 w-full h-20 shrink-0"
                            />
                        </div>

                        <!-- Caixas de seleção por horário -->
                        <div
                            class="absolute left-16 right-0 top-0 bottom-0 pl-sm z-[5]"
                        >
                            <label
                                v-for="hour in hours"
                                :key="`pick-${hour}`"
                                class="flex items-center gap-sm h-20 px-sm mx-xs border rounded-lg transition-all"
                                :class="slotPickerClass(hour)"
                            >
                                <input
                                    type="checkbox"
                                    class="w-4 h-4 rounded border-slate-300 text-primary focus:ring-primary shrink-0"
                                    :checked="isHourSelected(hour)"
                                    :disabled="slotHasReservation(hour) || isHourPast(hour)"
                                    @change="toggleHour(hour)"
                                />
                                <span class="text-label-sm font-medium flex-1">
                                    <template v-if="slotHasReservation(hour)">
                                        <span class="text-slate-400">Ocupado</span>
                                    </template>
                                    <template v-else-if="isHourPast(hour)">
                                        <span class="text-slate-400">Horário passado</span>
                                    </template>
                                    <template v-else>
                                        {{ padHour(hour) }}:00 – {{ padHour(hour + 1) }}:00
                                    </template>
                                </span>
                                <AppIcon
                                    v-if="isHourSelected(hour)"
                                    name="check_circle"
                                    class="text-success-green-text"
                                />
                            </label>
                        </div>

                        <div class="absolute left-16 right-0 top-0 bottom-0 pl-sm pointer-events-none">
                            <!-- Blocos ocupados -->
                            <div
                                v-for="block in dayBlocks"
                                :key="block.id"
                                class="absolute left-sm right-sm bg-primary-fixed border-l-4 border-primary rounded-r-lg shadow-sm p-sm flex flex-col z-10 cursor-pointer hover:shadow-md transition-shadow overflow-hidden pointer-events-auto"
                                :style="{ top: `${block.top}px`, height: `${block.height}px` }"
                                @click="selectReservation(block)"
                            >
                                <div class="flex justify-between items-start gap-sm min-w-0">
                                    <h3 class="text-headline-sm font-semibold text-on-primary-fixed truncate">
                                        {{ block.booker?.name ?? 'Reserva' }}
                                    </h3>
                                    <button
                                        type="button"
                                        class="text-primary shrink-0 hover:bg-white/50 rounded p-0.5"
                                        @click.stop="selectReservation(block)"
                                    >
                                        <AppIcon name="more_vert" />
                                    </button>
                                </div>
                                <p v-if="companionNames(block.companions)" class="text-body-sm text-on-surface-variant mt-xs truncate">
                                    {{ companionNames(block.companions) }}
                                </p>
                                <div class="mt-auto flex flex-wrap items-center gap-xs text-primary font-label-sm">
                                    <AppIcon name="schedule" size="sm" />
                                    {{ formatTimeRange(block.starts_at, block.ends_at) }}
                                    <span
                                        v-if="block.attended_at"
                                        class="inline-flex items-center gap-0.5 text-success-green-text bg-white/80 px-1.5 py-0.5 rounded text-[11px] font-semibold"
                                    >
                                        <AppIcon name="check_circle" size="sm" />
                                        Presente
                                    </span>
                                </div>
                            </div>

                            <!-- Indicador de hora atual -->
                            <div
                                v-if="currentTimeTop !== null"
                                class="absolute left-0 right-0 z-20 flex items-center pointer-events-none"
                                :style="{ top: `${currentTimeTop}px` }"
                            >
                                <div class="w-2 h-2 rounded-full bg-error -ml-[5px] shrink-0" />
                                <div class="h-0.5 bg-error w-full" />
                            </div>
                        </div>
                    </div>
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
                                @click.stop="cancelReservation(r)"
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
            :saving="saving"
            :error="formError"
            @close="closeModal"
            @submit="createReservation"
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
                        @click="cancelReservation(selected)"
                    >
                        Cancelar reserva
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ReservationModal from '../components/ReservationModal.vue';
import { api } from '../bootstrap';

const START_HOUR = 8;
const END_HOUR = 18;
const HOUR_HEIGHT = 80;

const route = useRoute();
const router = useRouter();

const user = ref(null);
const reservations = ref([]);
const loadingGrid = ref(false);
const saving = ref(false);
const formError = ref('');
const selectedDate = ref(startOfDay(new Date()));
const selected = ref(null);
const modalOpen = ref(false);
const modalStartsAt = ref('');
const modalEndsAt = ref('');

const selectedHours = ref([]);

const hours = Array.from({ length: END_HOUR - START_HOUR }, (_, i) => START_HOUR + i);
const timelineHeight = (END_HOUR - START_HOUR) * HOUR_HEIGHT;

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

const dayBlocks = computed(() =>
    dayReservations.value.map((r) => {
        const start = new Date(r.starts_at);
        const end = new Date(r.ends_at);
        const startMinutes = (start.getHours() - START_HOUR) * 60 + start.getMinutes();
        const durationMinutes = Math.max((end - start) / 60000, 15);
        return {
            ...r,
            top: (startMinutes / 60) * HOUR_HEIGHT,
            height: Math.max((durationMinutes / 60) * HOUR_HEIGHT, 48),
        };
    }),
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
const freeSlotsCount = computed(() =>
    hours.filter((hour) => !slotHasReservation(hour) && !isHourPast(hour)).length,
);

const occupancyPercent = computed(() => {
    if (hours.length === 0) {
        return 0;
    }
    return Math.round((bookedSlotsCount.value / hours.length) * 100);
});

const currentTimeTop = computed(() => {
    if (!isSameDay(selectedDate.value, new Date())) {
        return null;
    }
    const now = new Date();
    const h = now.getHours();
    const m = now.getMinutes();
    if (h < START_HOUR || h >= END_HOUR) {
        return null;
    }
    return ((h - START_HOUR) * 60 + m) / 60 * HOUR_HEIGHT;
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
    return dayReservations.value.some((r) => {
        const start = new Date(r.starts_at);
        const end = new Date(r.ends_at);
        const slotStart = new Date(selectedDate.value);
        slotStart.setHours(hour, 0, 0, 0);
        const slotEnd = new Date(selectedDate.value);
        slotEnd.setHours(hour + 1, 0, 0, 0);
        return start < slotEnd && end > slotStart;
    });
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
    if (slotHasReservation(hour) || isHourPast(hour)) {
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
        return 'bg-slate-100/90 border-slate-200 cursor-not-allowed opacity-70';
    }
    if (isHourSelected(hour)) {
        return 'bg-success-green-bg border-success-green cursor-pointer shadow-sm';
    }
    return 'bg-white border-slate-200 hover:border-primary hover:bg-slate-50 cursor-pointer';
}

function clearSelection() {
    selectedHours.value = [];
}

function confirmSelection() {
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

function closeModal() {
    modalOpen.value = false;
    formError.value = '';
}

function selectReservation(r) {
    selected.value = r;
}

function canCancel(r) {
    if (!user.value) {
        return false;
    }
    return r.user_id === user.value.id || isAdmin.value;
}

async function loadMe() {
    const { data } = await api.get('/api/user');
    user.value = data;
}

async function loadReservations() {
    loadingGrid.value = true;
    try {
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
        reservations.value = data;
    } catch (e) {
        formError.value = e.response?.data?.message ?? 'Falha ao carregar reservas.';
    } finally {
        loadingGrid.value = false;
    }
}

async function createReservation(payload) {
    if (payload.validationError) {
        formError.value = payload.validationError;
        return;
    }

    const { startsAt, endsAt, coursePeriod, activity, companions } = payload;
    const start = parseLocalDatetime(startsAt);
    const end = parseLocalDatetime(endsAt);
    if (!start || !end) {
        formError.value = 'Informe data e horário válidos.';
        return;
    }
    if (!coursePeriod || coursePeriod < 1 || coursePeriod > 8) {
        formError.value = 'Selecione o período do curso (1 a 8).';
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

    saving.value = true;
    formError.value = '';
    try {
        await api.post('/api/reservations', {
            starts_at: start.toISOString(),
            ends_at: end.toISOString(),
            course_period: coursePeriod,
            activity,
            companions,
        });
        closeModal();
        selected.value = null;
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

async function cancelReservation(r) {
    if (!confirm('Deseja cancelar esta reserva?')) {
        return;
    }
    try {
        if (isAdmin.value && r.user_id !== user.value?.id) {
            await api.delete(`/api/admin/reservations/${r.id}`);
        } else {
            await api.delete(`/api/reservations/${r.id}`);
        }
        selected.value = null;
        await loadReservations();
    } catch (e) {
        formError.value = e.response?.data?.message ?? 'Falha ao cancelar.';
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
    if (route.query.action === 'new') {
        openModal();
    }
});

</script>




