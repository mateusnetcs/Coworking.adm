<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
        @click.self="$emit('close')"
    >
        <div
            class="bg-white rounded-xl shadow-level-2 border border-slate-200 w-full max-w-lg max-h-[90vh] overflow-y-auto"
            role="dialog"
            aria-labelledby="modal-title"
        >
            <div class="flex items-center justify-between p-lg border-b border-slate-200">
                <h2 id="modal-title" class="text-headline-sm font-semibold text-on-surface">
                    {{ title }}
                </h2>
                <button
                    type="button"
                    class="text-on-surface-variant hover:bg-slate-100 p-1 rounded-lg transition-colors"
                    @click="$emit('close')"
                >
                    <AppIcon name="close" />
                </button>
            </div>

            <form class="p-lg space-y-md" @submit.prevent="submit">
                <label v-if="forUser" class="block">
                    <span class="text-label-sm font-medium text-slate-700 block mb-1.5">Usuário da reserva</span>
                    <select v-model="localForm.userId" required class="field-input">
                        <option disabled value="">Selecione o usuário</option>
                        <option v-for="u in users" :key="u.id" :value="u.id">
                            {{ u.name }} ({{ u.email }})
                        </option>
                    </select>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-md">
                    <label class="block">
                        <span class="text-label-sm font-medium text-slate-700 block mb-1.5">Início</span>
                        <input
                            v-model="localForm.startsAt"
                            type="datetime-local"
                            required
                            :min="minDatetime"
                            class="field-input"
                        />
                    </label>
                    <label class="block">
                        <span class="text-label-sm font-medium text-slate-700 block mb-1.5">Fim</span>
                        <input
                            v-model="localForm.endsAt"
                            type="datetime-local"
                            required
                            :min="localForm.startsAt || minDatetime"
                            class="field-input"
                        />
                    </label>
                </div>

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-md space-y-md">
                    <h3 class="text-label-md font-semibold text-on-surface">
                        {{ forUser ? 'Informações da reserva' : 'Suas informações' }}
                    </h3>
                    <label class="block">
                        <span class="text-label-sm font-medium text-slate-700 block mb-1.5">Período do curso</span>
                        <select v-model="localForm.coursePeriod" required class="field-input">
                            <option disabled value="">Selecione</option>
                            <option v-for="period in coursePeriods" :key="period" :value="period">
                                {{ period }}º período
                            </option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-label-sm font-medium text-slate-700 block mb-1.5">
                            Atividade que será desenvolvida no coworking
                        </span>
                        <textarea
                            v-model="localForm.activity"
                            required
                            rows="3"
                            maxlength="500"
                            placeholder="Ex.: Estudo em grupo, desenvolvimento de TCC…"
                            class="field-input resize-y min-h-[88px]"
                        />
                    </label>
                </div>

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
                        Opcional. Para cada colega, informe nome, período do curso e atividade.
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

                <p v-if="error" class="text-body-sm text-error bg-error-container px-md py-sm rounded-lg">
                    {{ error }}
                </p>

                <div class="flex gap-sm pt-sm">
                    <button
                        type="button"
                        class="flex-1 h-10 border border-slate-200 text-secondary rounded-lg font-label-md hover:bg-slate-50 transition-colors"
                        @click="$emit('close')"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="flex-1 h-10 bg-primary text-on-primary rounded-lg font-label-md hover:bg-primary-container transition-colors disabled:opacity-50"
                        :disabled="saving"
                    >
                        {{ saving ? 'Salvando…' : 'Confirmar reserva' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref, watch } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    startsAt: { type: String, default: '' },
    endsAt: { type: String, default: '' },
    minDatetime: { type: String, default: '' },
    saving: { type: Boolean, default: false },
    error: { type: String, default: '' },
    forUser: { type: Boolean, default: false },
    users: { type: Array, default: () => [] },
    title: { type: String, default: 'Nova reserva' },
});

const emit = defineEmits(['close', 'submit']);

const coursePeriods = [1, 2, 3, 4, 5, 6, 7, 8];

const localForm = reactive({
    startsAt: '',
    endsAt: '',
    coursePeriod: '',
    activity: '',
    userId: '',
});
const localCompanions = ref([]);

function emptyCompanion() {
    return { name: '', coursePeriod: '', activity: '' };
}

function resetForm() {
    localForm.startsAt = props.startsAt;
    localForm.endsAt = props.endsAt;
    localForm.coursePeriod = '';
    localForm.activity = '';
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
    return (
        companion.name.trim() !== ''
        && companion.coursePeriod !== ''
        && companion.activity.trim().length >= 3
    );
}

function companionIsEmpty(companion) {
    return (
        companion.name.trim() === ''
        && companion.coursePeriod === ''
        && companion.activity.trim() === ''
    );
}

function submit() {
    const incomplete = localCompanions.value.filter(
        (companion) => !companionIsEmpty(companion) && !companionIsComplete(companion),
    );
    if (incomplete.length > 0) {
        emit('submit', { validationError: 'Preencha nome, período e atividade de cada colega adicionado.' });
        return;
    }

    const companions = localCompanions.value
        .filter(companionIsComplete)
        .map((companion) => ({
            name: companion.name.trim(),
            course_period: Number(companion.coursePeriod),
            activity: companion.activity.trim(),
        }));

    const payload = {
        startsAt: localForm.startsAt,
        endsAt: localForm.endsAt,
        coursePeriod: Number(localForm.coursePeriod),
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
