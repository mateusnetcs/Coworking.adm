<template>
    <AppActionDialog
        :open="open"
        variant="success"
        title="Reserva confirmada!"
        :message="messageText"
        confirm-label="Entendi"
        :show-cancel="false"
        @confirm="$emit('acknowledge')"
        @cancel="$emit('acknowledge')"
    >
        <div class="mt-4 space-y-3">
            <motion class="rounded-xl border border-primary/25 bg-primary/5 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-primary">Tempo para editar</p>
                <p class="text-2xl font-bold text-primary mt-1 font-mono tabular-nums">{{ editCountdown }}</p>
                <p class="text-xs text-slate-600 mt-1">
                    Você pode alterar qualquer informação da reserva durante este período.
                </p>
            </div>
            <button
                type="button"
                class="w-full h-11 rounded-lg border border-primary text-primary font-semibold text-sm hover:bg-primary/5 transition-colors flex items-center justify-center gap-2 disabled:opacity-50"
                :disabled="downloadingPdf || !reservation?.id"
                @click="downloadConfirmationPdf"
            >
                <AppIcon name="calendar_month" size="sm" />
                {{ downloadingPdf ? 'Gerando PDF…' : 'Baixar comprovante (PDF)' }}
            </button>
            <p class="text-xs text-slate-500 leading-relaxed text-center">
                Apresente o PDF no dia da visita. O QR Code confirma a autenticidade da reserva.
            </p>
            <p class="text-xs text-slate-500 leading-relaxed">
                Após {{ editWindowLabel }}, o contador de cancelamento (mínimo de {{ cancelHours }}h antes do início) será exibido nas suas reservas.
            </p>
        </div>
    </AppActionDialog>
</template>

<script setup>
import { computed, onUnmounted, ref, watch } from 'vue';
import AppActionDialog from './AppActionDialog.vue';
import AppIcon from './AppIcon.vue';
import { api } from '../bootstrap';
import { formatCountdown, refreshRulesMeta } from '../utils/reservationRules';

const props = defineProps({
    open: { type: Boolean, default: false },
    reservation: { type: Object, default: null },
});

defineEmits(['acknowledge']);

const rules = ref(null);
const downloadingPdf = ref(false);
let timer = null;

const editWindowLabel = computed(() => {
    const seconds = rules.value?.edit_window_seconds;
    if (seconds != null && seconds > 0) {
        return seconds === 1 ? '1 segundo' : `${seconds} segundos`;
    }
    const minutes = rules.value?.edit_window_minutes ?? 10;
    return minutes === 1 ? '1 minuto' : `${minutes} minutos`;
});
const cancelHours = computed(() => rules.value?.cancel_hours_before ?? 3);

const editCountdown = computed(() => formatCountdown(rules.value?.edit_seconds_remaining ?? 0));

const messageText = computed(() => {
    if (!props.reservation) {
        return '';
    }
    const start = new Date(props.reservation.starts_at);
    const end = new Date(props.reservation.ends_at);
    const fmt = new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
    return `Sua reserva foi registrada com sucesso.\n\nHorário: ${fmt.format(start)} – ${fmt.format(end)}`;
});

function tick() {
    if (rules.value) {
        rules.value = refreshRulesMeta({ ...rules.value });
    }
}

async function downloadConfirmationPdf() {
    if (!props.reservation?.id || downloadingPdf.value) {
        return;
    }
    downloadingPdf.value = true;
    try {
        const { data } = await api.get(`/api/reservations/${props.reservation.id}/confirmation-pdf`, {
            responseType: 'blob',
        });
        const url = URL.createObjectURL(data);
        const link = document.createElement('a');
        link.href = url;
        link.download = `comprovante-coworking-${props.reservation.id}.pdf`;
        link.click();
        URL.revokeObjectURL(url);
    } catch {
        window.alert('Não foi possível gerar o comprovante. Tente novamente em alguns instantes.');
    } finally {
        downloadingPdf.value = false;
    }
}

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
            return;
        }
        rules.value = refreshRulesMeta(props.reservation?.rules ?? {});
        timer = setInterval(tick, 1000);
    },
);

onUnmounted(() => {
    if (timer) {
        clearInterval(timer);
    }
});
</script>
