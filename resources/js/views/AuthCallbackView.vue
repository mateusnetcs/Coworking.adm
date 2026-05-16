<template>
    <div class="min-h-screen flex flex-col items-center justify-center bg-slate-50 p-margin-mobile">
        <div class="bg-white rounded-xl shadow-level-1 border border-slate-200 p-xl text-center max-w-sm w-full">
            <div class="w-12 h-12 rounded-lg bg-primary/10 text-primary flex items-center justify-center mx-auto mb-md">
                <AppIcon name="progress_activity" class="w-6 h-6 animate-spin text-primary" />
            </div>
            <h1 class="text-headline-sm font-semibold text-on-surface mb-sm">Autenticando</h1>
            <p class="text-body-sm text-slate-500">
                {{ message || 'Conectando com Google…' }}
            </p>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { applyAuthToken } from '../bootstrap';

const route = useRoute();
const router = useRouter();
const message = ref('');

onMounted(() => {
    const token = typeof route.query.token === 'string' ? route.query.token : '';

    if (!token) {
        message.value = 'Token ausente. Tente fazer login novamente.';
        return;
    }

    applyAuthToken(token);
    router.replace('/');
});
</script>
