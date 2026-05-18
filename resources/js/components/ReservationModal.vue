<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-black/40 backdrop-blur-sm"
        @click.self="$emit('close')"
    >
        <div
            class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-4xl max-h-[92vh] flex flex-col overflow-hidden"
            role="dialog"
            aria-labelledby="modal-title"
        >
            <div class="shrink-0 flex items-start justify-between gap-4 px-6 py-5 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white">
                <div class="min-w-0">
                    <h2 id="modal-title" class="text-xl font-bold text-on-surface">
                        {{ title }}
                    </h2>
                    <p v-if="scheduleSummary" class="text-sm text-slate-500 mt-1 flex items-center gap-1.5">
                        <AppIcon name="schedule" size="sm" class="text-primary" />
                        {{ scheduleSummary }}
                    </p>
                </div>
                <button
                    type="button"
                    class="shrink-0 w-10 h-10 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 transition-colors"
                    aria-label="Fechar"
                    @click="$emit('close')"
                >
                    <AppIcon name="close" />
                </button>
            </div>

            <form class="flex flex-col min-h-0 flex-1" @submit.prevent="submit">
                <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">
                <label v-if="forUser" class="block">
                    <span class="text-label-sm font-medium text-slate-700 block mb-1.5">Usuário da reserva</span>
                    <select v-model="localForm.userId" required class="field-input">
                        <option disabled value="">Selecione o usuário</option>
                        <option v-for="u in users" :key="u.id" :value="u.id">
                            {{ u.name }} ({{ u.email }})
                        </option>
                    </select>
                </label>
                <section class="modal-section">
                    <header class="modal-section__head">
                        <span class="modal-section__icon"><AppIcon name="schedule" size="sm" /></span>
                        <h3 class="modal-section__title">Horário da reserva</h3>
                    </header>
                    <div class="modal-section__body grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <label class="block">
                        <span class="field-label">Início</span>
                        <input
                            v-model="localForm.startsAt"
                            type="datetime-local"
                            required
                            :min="minDatetime"
                            class="field-input"
                        />
                    </label>
                    <label class="block">
                        <span class="field-label">Fim</span>
                        <input
                            v-model="localForm.endsAt"
                            type="datetime-local"
                            required
                            :min="localForm.startsAt || minDatetime"
                            class="field-input"
                        />
                    </label>
                    </div>
                </section>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <section class="modal-section h-full">
                    <header class="modal-section__head">
                        <span class="modal-section__icon"><AppIcon name="mail" size="sm" /></span>
                        <h3 class="modal-section__title">Contato</h3>
                    </header>
                    <div class="modal-section__body space-y-4">
                    <label class="block">
                        <span class="field-label">E-mail <span class="text-error">*</span></span>
                        <input v-model="localForm.contactEmail" type="email" required autocomplete="email" placeholder="seu@email.com" class="field-input" />
                    </label>
                    <label class="block">
                        <span class="field-label">Telefone (WhatsApp) <span class="text-error">*</span></span>
                        <input
                            :value="localForm.phone"
                            type="tel"
                            required
                            inputmode="numeric"
                            autocomplete="tel"
                            placeholder="(99) 9999-9999"
                            maxlength="15"
                            class="field-input"
                            @input="onPhoneInput"
                        />
                        <p class="mt-1 text-xs text-slate-500">DDD + número do celular (com 9 na frente).</p>
                    </label>
                    <label class="block">
                        <span class="field-label">Instituição <span class="text-slate-400 font-normal">(opcional)</span></span>
                        <input v-model="localForm.institution" type="text" maxlength="120" placeholder="Ex.: Uemasul" class="field-input" />
                    </label>
                    </div>
                </section>

                <section class="modal-section h-full">
                    <header class="modal-section__head">
                        <span class="modal-section__icon"><AppIcon name="calendar_month" size="sm" /></span>
                        <h3 class="modal-section__title">Qual espaço deseja reservar? <span class="text-error">*</span></h3>
                    </header>
                    <div class="modal-section__body space-y-4">
                        <div class="space-y-2">
                            <label
                                v-for="option in spaceOptions"
                                :key="option.value"
                                class="space-option"
                                :class="{ 'space-option--active': localForm.spaceType === option.value }"
                            >
                                <input v-model="localForm.spaceType" type="radio" class="sr-only" :value="option.value" required @change="onSpaceTypeChange" />
                                <span class="space-option__radio" aria-hidden="true" />
                                <span class="min-w-0">
                                    <span class="text-sm font-semibold text-on-surface block">{{ option.label }}</span>
                                    <span class="text-xs text-slate-500 leading-snug">{{ option.description }}</span>
                                </span>
                            </label>
                        </div>
                        <div v-if="showComputerPicker" class="rounded-lg border border-primary/20 bg-primary/5 p-4 space-y-3">
                            <p class="text-sm font-semibold text-on-surface">Computador(es) <span class="text-error">*</span></p>
                            <p class="text-xs text-slate-600">2 disponíveis — selecione 1 ou ambos.</p>
                            <div class="grid grid-cols-2 gap-3">
                                <label
                                    v-for="id in computerIds"
                                    :key="id"
                                    class="computer-card"
                                    :class="{ 'computer-card--active': localForm.computers.includes(id) }"
                                >
                                    <input v-model="localForm.computers" type="checkbox" :value="id" class="sr-only" />
                                    <AppIcon name="check_circle" size="sm" :class="localForm.computers.includes(id) ? 'text-primary' : 'text-slate-300'" />
                                    <span class="text-sm font-semibold">PC {{ id }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </section>
                </div>

                <section class="modal-section">
                    <header class="modal-section__head">
                        <span class="modal-section__icon"><AppIcon name="person" size="sm" /></span>
                        <h3 class="modal-section__title">{{ forUser ? 'Informações da reserva' : 'Suas informações acadêmicas' }}</h3>
                    </header>
                    <div class="modal-section__body grid grid-cols-1 md:grid-cols-3 gap-4">
                        <label class="block md:col-span-1">
                            <span class="field-label">Período do curso <span class="text-slate-400 font-normal">(opcional)</span></span>
                            <select v-model="localForm.coursePeriod" class="field-input">
                                <option disabled value="">Selecione</option>
                                <option v-for="period in coursePeriods" :key="period" :value="period">{{ period }}º período</option>
                            </select>
                        </label>
                        <label class="block md:col-span-2">
                            <span class="field-label">Atividade no coworking <span class="text-error">*</span></span>
                            <textarea v-model="localForm.activity" required rows="2" maxlength="500" placeholder="Ex.: Estudo em grupo, TCC…" class="field-input resize-y min-h-[72px]" />
                        </label>
                    </div>
                </section>

                <div>
                    <div class="flex items-center justify-between mb-sm">
                        <span class="text-label-md font-medium text-on-surface">Colegas presentes</span>
                        <button
                            type="button"
                            class="text-primary text-label-sm font-semibold flex items-center gap-1 hover:underline"
                            @click="addCompanion"
                        >
                            <AppIcon name="person_add" size="sm" />
                            Adicionar
                        </button>
                    </div>
                    <p class="text-body-sm text-slate-500 mb-sm">
                        Opcional. Para cada colega, informe nome e atividade (período do curso é opcional).
                    </p>
                    <div class="space-y-md">
                        <div
                            v-for="(companion, idx) in localCompanions"
                            :key="idx"
                            class="rounded-lg border border-slate-200 p-md space-y-sm bg-white"
                        >
                            <div class="flex items-center justify-between gap-sm">
                                <span class="text-label-sm font-semibold text-slate-600">Colega {{ idx + 1 }}</span>
                                <button
                                    type="button"
                                    class="w-9 h-9 flex items-center justify-center text-error hover:bg-error-container rounded-lg transition-colors shrink-0"
                                    title="Remover colega"
                                    @click="removeCompanion(idx)"
                                >
                                    <AppIcon name="delete" />
                                </button>
                            </div>
                            <input
                                v-model="companion.name"
                                type="text"
                                placeholder="Nome do colega"
                                class="field-input"
                            />
                            <label class="block">
                                <span class="text-label-sm font-medium text-slate-700 block mb-1.5">Período do curso</span>
                                <select v-model="companion.coursePeriod" class="field-input">
                                    <option disabled value="">Selecione</option>
                                    <option v-for="period in coursePeriods" :key="`c-${idx}-${period}`" :value="period">
                                        {{ period }}º período
                                    </option>
                                </select>
                            </label>
                            <label class="block">
                                <span class="text-label-sm font-medium text-slate-700 block mb-1.5">
                                    Atividade no coworking
                                </span>
                                <textarea
                                    v-model="companion.activity"
                                    rows="2"
                                    maxlength="500"
                                    placeholder="Atividade que o colega desenvolverá"
                                    class="field-input resize-y min-h-[72px]"
                                />
                            </label>
                        </div>
                    </div>
                    <p v-if="!localCompanions.length" class="text-body-sm text-slate-500 text-center py-sm border border-dashed border-slate-200 rounded-lg">
                        Nenhum colega adicionado. Clique em Adicionar se houver mais pessoas.
                    </p>
                </div>

                <section v-if="!termsAlreadyAccepted" class="modal-section border-amber-200/80 bg-amber-50/40">
                    <div class="modal-section__body space-y-3">
                        <p class="text-sm text-slate-700 italic border-l-4 border-amber-400 pl-3">
                            "Declaro que utilizarei o espaço e equipamentos de forma responsável."
                            <span class="text-error not-italic">*</span>
                        </p>
                        <label class="flex items-start gap-3 cursor-pointer p-3 rounded-lg bg-white border border-slate-200 hover:border-primary/40 transition-colors">
                            <input v-model="localForm.termsAccepted" type="checkbox" required class="mt-0.5 w-5 h-5 rounded border-slate-300 text-primary shrink-0" />
                            <span class="text-sm text-on-surface leading-relaxed">
                                Aceito as normas para uso do espaço físico <span class="text-error">*</span>
                            </span>
                        </label>
                    </div>
                </section>
                <section v-else class="modal-section border-slate-200 bg-slate-50/60">
                    <div class="modal-section__body">
                        <p class="text-sm text-slate-600 flex items-center gap-2">
                            <AppIcon name="check_circle" size="sm" class="text-success-green-text shrink-0" />
                            Termo de responsabilidade já aceito ao criar esta reserva.
                        </p>
                    </div>
                </section>

                <p v-if="error" class="text-sm text-error bg-error-container px-4 py-3 rounded-lg">{{ error }}</p>
                </div>

                <div class="shrink-0 flex flex-col-reverse sm:flex-row gap-3 px-6 py-4 border-t border-slate-200 bg-slate-50">
                    <button type="button" class="sm:flex-1 h-11 border border-slate-300 text-secondary rounded-lg font-label-md hover:bg-white transition-colors" @click="$emit('close')">
                        Cancelar
                    </button>
                    <button type="submit" class="sm:flex-[2] h-11 bg-primary text-on-primary rounded-lg font-label-md font-semibold hover:bg-primary-container transition-colors disabled:opacity-50 shadow-sm" :disabled="saving">
                        {{ saving ? 'Salvando…' : (editReservation ? 'Salvar alterações' : 'Confirmar reserva') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import {
    COMPUTER_IDS,
    SPACE_BOTH,
    SPACE_COMPUTER,
    SPACE_MEETING_ROOM,
    SPACE_OPTIONS,
} from '../constants/reservationSpaces';
import { digitsOnly, formatBrazilPhoneInput, isValidBrazilMobile } from '../utils/phone';

const props = defineProps({
    open: { type: Boolean, default: false },
    startsAt: { type: String, default: '' },
    endsAt: { type: String, default: '' },
    minDatetime: { type: String, default: '' },
    saving: { type: Boolean, default: false },
    error: { type: String, default: '' },
    forUser: { type: Boolean, default: false },
    users: { type: Array, default: () => [] },
    userEmail: { type: String, default: '' },
    title: { type: String, default: 'Nova reserva' },
    editReservation: { type: Object, default: null },
});

const emit = defineEmits(['close', 'submit']);

const coursePeriods = [1, 2, 3, 4, 5, 6, 7, 8];
const spaceOptions = SPACE_OPTIONS;
const computerIds = COMPUTER_IDS;

const localForm = reactive({
    startsAt: '',
    endsAt: '',
    contactEmail: '',
    phone: '',
    institution: '',
    spaceType: '',
    computers: [],
    coursePeriod: '',
    activity: '',
    termsAccepted: false,
    userId: '',
});
const localCompanions = ref([]);

const showComputerPicker = computed(
    () => localForm.spaceType === SPACE_COMPUTER || localForm.spaceType === SPACE_BOTH,
);

const termsAlreadyAccepted = computed(
    () => Boolean(props.editReservation?.terms_accepted_at),
);

const scheduleSummary = computed(() => {
    if (!localForm.startsAt || !localForm.endsAt) {
        return '';
    }
    const start = new Date(localForm.startsAt);
    const end = new Date(localForm.endsAt);
    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
        return '';
    }
    const dateFmt = new Intl.DateTimeFormat('pt-BR', { weekday: 'short', day: 'numeric', month: 'short' });
    const timeFmt = new Intl.DateTimeFormat('pt-BR', { hour: '2-digit', minute: '2-digit' });
    return `${dateFmt.format(start)} · ${timeFmt.format(start)} – ${timeFmt.format(end)}`;
});

function onSpaceTypeChange() {
    if (localForm.spaceType === SPACE_MEETING_ROOM) {
        localForm.computers = [];
    }
}

function onPhoneInput(event) {
    localForm.phone = formatBrazilPhoneInput(event.target.value);
}

function emptyCompanion() {
    return { name: '', coursePeriod: '', activity: '' };
}

function toLocalDatetimeValue(date) {
    const y = date.getFullYear();
    const mo = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    const h = String(date.getHours()).padStart(2, '0');
    const mi = String(date.getMinutes()).padStart(2, '0');
    return `${y}-${mo}-${d}T${h}:${mi}`;
}

function populateFromReservation(r) {
    localForm.startsAt = toLocalDatetimeValue(new Date(r.starts_at));
    localForm.endsAt = toLocalDatetimeValue(new Date(r.ends_at));
    localForm.contactEmail = r.contact_email ?? props.userEmail ?? '';
    localForm.phone = formatBrazilPhoneInput(r.phone ?? '');
    localForm.institution = r.institution ?? '';
    localForm.spaceType = r.space_type ?? '';
    localForm.computers = Array.isArray(r.computers) ? [...r.computers] : [];
    localForm.coursePeriod = r.course_period ?? '';
    localForm.activity = r.activity ?? '';
    localForm.termsAccepted = true;
    localCompanions.value = (r.companions ?? []).map((c) => ({
        name: c.name ?? '',
        coursePeriod: c.course_period ?? '',
        activity: c.activity ?? '',
    }));
}

function resetForm() {
    if (props.editReservation) {
        populateFromReservation(props.editReservation);
        return;
    }
    localForm.startsAt = props.startsAt;
    localForm.endsAt = props.endsAt;
    localForm.contactEmail = props.userEmail || '';
    localForm.phone = '';
    localForm.institution = '';
    localForm.spaceType = '';
    localForm.computers = [];
    localForm.coursePeriod = '';
    localForm.activity = '';
    localForm.termsAccepted = false;
    localForm.userId = '';
    localCompanions.value = [];
}

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            return;
        }
        resetForm();
    },
);

watch(
    () => [props.startsAt, props.endsAt],
    ([start, end]) => {
        if (props.open) {
            localForm.startsAt = start;
            localForm.endsAt = end;
        }
    },
);

function addCompanion() {
    localCompanions.value.push(emptyCompanion());
}

function removeCompanion(index) {
    localCompanions.value.splice(index, 1);
}

function companionIsComplete(companion) {
    return companion.name.trim().length >= 2 && companion.activity.trim().length >= 3;
}

function companionIsEmpty(companion) {
    return (
        companion.name.trim() === ''
        && companion.coursePeriod === ''
        && companion.activity.trim() === ''
    );
}

function submit() {
    if (!localForm.contactEmail.trim()) {
        emit('submit', { validationError: 'Informe seu e-mail.' });
        return;
    }
    if (!isValidBrazilMobile(localForm.phone)) {
        emit('submit', { validationError: 'Informe um celular válido com DDD (ex.: (99) 9999-9999).' });
        return;
    }
    const phoneDigits = digitsOnly(localForm.phone);
    if (!localForm.spaceType) {
        emit('submit', { validationError: 'Selecione qual espaço deseja reservar.' });
        return;
    }
    if (showComputerPicker.value && localForm.computers.length === 0) {
        emit('submit', { validationError: 'Selecione ao menos um computador.' });
        return;
    }
    if (!termsAlreadyAccepted.value && !localForm.termsAccepted) {
        emit('submit', { validationError: 'É necessário aceitar o termo de responsabilidade.' });
        return;
    }

    const incomplete = localCompanions.value.filter(
        (companion) => !companionIsEmpty(companion) && !companionIsComplete(companion),
    );
    if (incomplete.length > 0) {
        emit('submit', { validationError: 'Preencha nome e atividade de cada colega adicionado.' });
        return;
    }

    const companions = localCompanions.value
        .filter(companionIsComplete)
        .map((companion) => ({
            name: companion.name.trim(),
            course_period: companion.coursePeriod ? Number(companion.coursePeriod) : null,
            activity: companion.activity.trim(),
        }));

    const computers = showComputerPicker.value
        ? [...localForm.computers].map(Number).sort((a, b) => a - b)
        : [];

    const payload = {
        reservationId: props.editReservation?.id ?? null,
        startsAt: localForm.startsAt,
        endsAt: localForm.endsAt,
        contactEmail: localForm.contactEmail.trim(),
        phone: phoneDigits,
        institution: localForm.institution.trim() || null,
        spaceType: localForm.spaceType,
        computers,
        termsAccepted: true,
        coursePeriod: localForm.coursePeriod ? Number(localForm.coursePeriod) : null,
        activity: localForm.activity.trim(),
        companions,
    };

    if (props.forUser) {
        if (!localForm.userId) {
            emit('submit', { validationError: 'Selecione o usuário da reserva.' });
            return;
        }
        payload.userId = Number(localForm.userId);
    }

    emit('submit', payload);
}
</script>

<style scoped>
.field-label {
    @apply text-sm font-medium text-slate-700 block mb-1.5;
}

.modal-section {
    @apply rounded-xl border border-slate-200 bg-white overflow-hidden;
}

.modal-section__head {
    @apply flex items-center gap-2.5 px-5 py-3.5 border-b border-slate-100 bg-slate-50/80;
}

.modal-section__icon {
    @apply w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0;
}

.modal-section__title {
    @apply text-sm font-semibold text-on-surface;
}

.modal-section__body {
    @apply p-5;
}

.space-option {
    @apply flex gap-3 p-3.5 rounded-lg border border-slate-200 cursor-pointer transition-all hover:border-slate-300 hover:bg-slate-50/80;
}

.space-option--active {
    @apply border-primary bg-primary/5 ring-1 ring-primary/20;
}

.space-option__radio {
    @apply mt-0.5 w-4 h-4 shrink-0 rounded-full border-2 border-slate-300 bg-white;
}

.space-option--active .space-option__radio {
    @apply border-primary bg-primary;
    box-shadow: inset 0 0 0 3px white;
}

.computer-card {
    @apply flex flex-col items-center justify-center gap-2 p-4 rounded-xl border-2 border-slate-200 bg-white cursor-pointer transition-all hover:border-slate-300;
}

.computer-card--active {
    @apply border-primary bg-primary/5 ring-2 ring-primary/20;
}
</style>
