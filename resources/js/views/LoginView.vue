<template>
    <div class="min-h-screen flex flex-col items-center justify-center p-margin-mobile bg-slate-50">
        <div class="w-full max-w-md">
            <div class="flex items-center justify-center gap-sm mb-xl">
                <div class="w-12 h-12 rounded-lg bg-primary flex items-center justify-center text-on-primary shadow-sm">
                    <AppIcon name="calendar_month" size="lg" />
                </div>
                <div>
                    <h1 class="text-headline-md font-bold text-primary">Coworking UEMASUL</h1>
                    <p class="text-label-sm text-on-surface-variant">Administração · Espaço compartilhado</p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-level-1 border border-slate-200 p-lg">
                <h2 class="text-headline-sm font-semibold text-on-surface mb-md">Entrar</h2>

                <form class="space-y-4" @submit.prevent="submit">
                    <AppTextField
                        v-model="email"
                        label="E-mail"
                        type="email"
                        name="email"
                        icon="mail"
                        placeholder="seu@email.com"
                        autocomplete="username"
                        required
                    />
                    <AppTextField
                        v-model="password"
                        label="Senha"
                        type="password"
                        name="password"
                        icon="lock"
                        placeholder="Digite sua senha"
                        autocomplete="current-password"
                        required
                    />

                    <p v-if="error" class="text-body-sm text-error bg-error-container px-md py-sm rounded-lg border border-error/20">
                        {{ error }}
                    </p>

                    <button
                        type="submit"
                        class="w-full h-11 bg-primary text-on-primary rounded-lg font-label-md font-medium hover:bg-primary-container transition-colors disabled:opacity-50 shadow-sm"
                        :disabled="loading"
                    >
                        {{ loading ? 'Entrando…' : 'Entrar' }}
                    </button>
                </form>

                <div class="relative my-lg">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-slate-200"></div>
                    </div>
                    <div class="relative flex justify-center text-label-sm">
                        <span class="bg-white px-sm text-slate-500">ou</span>
                    </div>
                </div>

                <div
                    v-if="!googleEnabled"
                    class="rounded-lg border border-amber-200 bg-amber-50 px-md py-sm text-body-sm text-amber-900"
                >
                    <p class="font-medium flex items-center gap-1 mb-1">
                        <AppIcon name="info" size="sm" />
                        Google Login desativado
                    </p>
                    <p class="text-amber-800/90">
                        Configure <code class="text-xs bg-white/80 px-1 rounded">GOOGLE_CLIENT_ID</code> e
                        <code class="text-xs bg-white/80 px-1 rounded">GOOGLE_CLIENT_SECRET</code> no arquivo
                        <code class="text-xs bg-white/80 px-1 rounded">.env</code> e reinicie o servidor.
                    </p>
                    <p v-if="googleRedirectUri" class="mt-2 text-xs text-amber-800/80 break-all">
                        URI de redirecionamento: <strong>{{ googleRedirectUri }}</strong>
                    </p>
                </div>

                <a
                    v-else
                    href="/auth/google/redirect"
                    class="w-full h-11 border border-slate-200 rounded-lg font-label-md flex items-center justify-center gap-sm text-on-surface hover:bg-slate-50 hover:border-slate-300 transition-colors shadow-sm"
                >
                    <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Continuar com Google
                </a>

                <p class="text-center text-body-sm text-slate-500 mt-lg">
                    Não tem conta?
                    <RouterLink to="/register" class="text-primary font-medium hover:underline">Cadastre-se</RouterLink>
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppTextField from '../components/AppTextField.vue';
import { api, applyAuthToken } from '../bootstrap';

const router = useRouter();
const route = useRoute();

const email = ref('');
const password = ref('');
const loading = ref(false);
const error = ref('');
const googleEnabled = ref(false);
const googleRedirectUri = ref('');

onMounted(async () => {
    const queryError = route.query.error;
    if (typeof queryError === 'string' && queryError !== '') {
        error.value = queryError;
        const { error: _removed, ...rest } = route.query;
        router.replace({ name: 'login', query: rest });
    }

    try {
        const { data } = await api.get('/api/auth/google/status');
        googleEnabled.value = Boolean(data.enabled);
        googleRedirectUri.value = data.redirect_uri ?? '';
    } catch {
        googleEnabled.value = false;
    }
});

async function submit() {
    loading.value = true;
    error.value = '';
    try {
        const redirect = route.query.redirect;
        const target = typeof redirect === 'string' && redirect.startsWith('/') ? redirect : '/';
        const { data } = await api.post('/api/login', {
            email: email.value,
            password: password.value,
        });
        applyAuthToken(data.token);
        await router.replace(target);
    } catch (e) {
        error.value = e.response?.data?.message ?? 'E-mail ou senha incorretos.';
    } finally {
        loading.value = false;
    }
}
</script>
