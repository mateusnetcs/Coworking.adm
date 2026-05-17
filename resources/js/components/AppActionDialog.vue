<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/45 backdrop-blur-sm"
            @click.self="onCancel"
        >
            <div
                class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-md overflow-hidden"
                role="dialog"
                aria-modal="true"
                :aria-labelledby="titleId"
            >
                <div class="px-6 pt-6 pb-4">
                    <div
                        class="w-12 h-12 rounded-full flex items-center justify-center mb-4"
                        :class="iconWrapClass"
                    >
                        <AppIcon :name="iconName" size="md" :class="iconClass" />
                    </div>
                    <h2 :id="titleId" class="text-lg font-bold text-on-surface">{{ title }}</h2>
                    <p class="text-sm text-slate-600 mt-2 leading-relaxed whitespace-pre-line">{{ message }}</p>
                    <slot />
                </div>
                <div class="flex flex-col-reverse sm:flex-row gap-2 px-6 py-4 bg-slate-50 border-t border-slate-200">
                    <button
                        v-if="showCancel"
                        type="button"
                        class="flex-1 h-11 border border-slate-300 rounded-lg text-slate-700 font-medium hover:bg-white"
                        @click="onCancel"
                    >
                        {{ cancelLabel }}
                    </button>
                    <button
                        type="button"
                        class="flex-1 h-11 rounded-lg font-semibold text-white transition-colors"
                        :class="confirmClass"
                        @click="$emit('confirm')"
                    >
                        {{ confirmLabel }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    variant: { type: String, default: 'confirm' },
    title: { type: String, required: true },
    message: { type: String, default: '' },
    confirmLabel: { type: String, default: 'Confirmar' },
    cancelLabel: { type: String, default: 'Cancelar' },
    showCancel: { type: Boolean, default: true },
});

const emit = defineEmits(['confirm', 'cancel']);

const titleId = `dialog-title-${Math.random().toString(36).slice(2, 9)}`;

const iconName = computed(() => {
    if (props.variant === 'success') {
        return 'check_circle';
    }
    if (props.variant === 'danger') {
        return 'delete';
    }
    if (props.variant === 'info') {
        return 'info';
    }
    return 'info';
});

const iconWrapClass = computed(() => {
    if (props.variant === 'success') {
        return 'bg-success-green-bg';
    }
    if (props.variant === 'danger') {
        return 'bg-error-container';
    }
    return 'bg-primary/10';
});

const iconClass = computed(() => {
    if (props.variant === 'success') {
        return 'text-success-green-text';
    }
    if (props.variant === 'danger') {
        return 'text-error';
    }
    return 'text-primary';
});

const confirmClass = computed(() => {
    if (props.variant === 'danger') {
        return 'bg-error hover:opacity-90';
    }
    if (props.variant === 'success') {
        return 'bg-primary hover:bg-primary-container';
    }
    return 'bg-primary hover:bg-primary-container';
});

function onCancel() {
    emit('cancel');
}
</script>
