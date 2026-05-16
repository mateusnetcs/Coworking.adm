<template>
    <div class="min-h-screen flex flex-col items-center justify-center p-margin-mobile bg-slate-50">
        <div class="w-full max-w-md">
            <div class="flex items-center justify-center gap-sm mb-xl">
                <div class="w-12 h-12 rounded-lg bg-primary flex items-center justify-center text-on-primary shadow-sm">
                    <AppIcon name="calendar_month" size="lg" />
                </div>
                <div>
                    <h1 class="text-headline-md font-bold text-primary">Coworking UEMASUL</h1>
                    <p class="text-label-sm text-on-surface-variant">Criar conta de estudante</p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-level-1 border border-slate-200 p-lg">
                <h2 class="text-headline-sm font-semibold text-on-surface mb-md">Cadastro</h2>

                <form class="space-y-4" @submit.prevent="submit">
                    <AppTextField
                        v-model="name"
                        label="Nome completo"
                        type="text"
                        name="name"
                        icon="person"
                        placeholder="Ex.: Maria Silva"
                        autocomplete="name"
                        required
                    />
                    <AppTextField
                        v-model="email"
                        label="E-mail"
                        type="email"
                        name="email"
                        icon="mail"
                        placeholder="seu@email.com"
                        autocomplete="email"
                        required
                    />
                    <AppTextField
                        v-model="password"
                        label="Senha"
                        type="password"
                        name="password"
                        icon="lock"
                        placeholder="Mínimo 8 caracteres"
                        autocomplete="new-password"
                        hint="Use letras e números para uma senha mais segura."
                        required
                    />
                    <AppTextField
                        v-model="passwordConfirmation"
                        label="Confirmar senha"
                        type="password"
                        name="password_confirmation"
                        icon="lock"
                        placeholder="Repita a senha"
                        autocomplete="new-password"
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
                        {{ loading ? 'Criando conta…' : 'Criar conta' }}
                    </button>
                </form>

                <p class="text-center text-body-sm text-slate-500 mt-lg">
                    Já tem conta?
                    <RouterLink to="/login" class="text-primary font-medium hover:underline">Entrar</RouterLink>
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import AppTextField from '../components/AppTextField.vue';
import { api, applyAuthToken } from '../bootstrap';

const router = useRouter();

const name = ref('');
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const loading = ref(false);
const error = ref('');

async function submit() {
    loading.value = true;
    error.value = '';
    try {
        const { data } = await api.post('/api/register', {
            name: name.value,
            email: email.value,
            password: password.value,
            password_confirmation: passwordConfirmation.value,
        });
        applyAuthToken(data.token);
        await router.replace('/');
    } catch (e) {
        const bag = e.response?.data?.errors;
        error.value = bag
            ? Object.values(bag).flat().join(' ')
            : (e.response?.data?.message ?? 'Não foi possível criar a conta.');
    } finally {
        loading.value = false;
    }
}
</script>
