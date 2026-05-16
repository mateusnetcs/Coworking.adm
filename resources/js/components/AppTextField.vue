<template>
    <label class="block w-full">
        <span v-if="label" class="text-label-sm font-medium text-slate-700 block mb-1.5">
            {{ label }}
        </span>
        <div class="relative">
            <AppIcon
                v-if="icon"
                :name="icon"
                class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"
            />

            <input
                :id="inputId"
                :type="resolvedType"
                :value="modelValue"
                :name="name"
                :placeholder="placeholder"
                :required="required"
                :autocomplete="autocomplete"
                :disabled="disabled"
                :class="inputClasses"
                @input="onInput"
            />

            <button
                v-if="type === 'password' && showToggle"
                type="button"
                class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 flex items-center justify-center rounded-md text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
                :aria-label="passwordVisible ? 'Ocultar senha' : 'Mostrar senha'"
                tabindex="-1"
                @click="passwordVisible = !passwordVisible"
            >
                <AppIcon :name="passwordVisible ? 'visibility_off' : 'visibility'" />
            </button>
        </div>
        <p v-if="hint" class="mt-1.5 text-body-sm text-slate-500">{{ hint }}</p>
    </label>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    label: { type: String, default: '' },
    type: { type: String, default: 'text' },
    name: { type: String, default: undefined },
    placeholder: { type: String, default: '' },
    icon: { type: String, default: '' },
    hint: { type: String, default: '' },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    autocomplete: { type: String, default: undefined },
    showToggle: { type: Boolean, default: true },
    id: { type: String, default: undefined },
});

const emit = defineEmits(['update:modelValue']);

const passwordVisible = ref(false);

const inputId = computed(() => props.id ?? `field-${Math.random().toString(36).slice(2, 9)}`);

const resolvedType = computed(() => {
    if (props.type === 'password' && passwordVisible.value) {
        return 'text';
    }
    return props.type;
});

const inputClasses = computed(() => {
    const base = 'field-input';
    const withIcon = props.icon ? 'field-input--icon' : '';
    const withToggle = props.type === 'password' && props.showToggle ? 'field-input--toggle' : '';
    return [base, withIcon, withToggle].filter(Boolean).join(' ');
});

function onInput(event) {
    emit('update:modelValue', event.target.value);
}
</script>
