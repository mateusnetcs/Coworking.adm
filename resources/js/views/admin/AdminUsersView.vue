<template>
    <section class="flex-1 overflow-y-auto p-margin-mobile md:p-margin-desktop">
        <div class="max-w-[1000px] mx-auto space-y-lg pb-lg">
            <div>
                <h1 class="text-headline-lg font-bold text-on-surface">Usuários</h1>
                <p class="text-body-sm text-slate-500 mt-1">
                    Lista de pessoas cadastradas no sistema. Edite nome, e-mail e perfil.
                </p>
            </div>

            <p v-if="error" class="text-body-sm text-error bg-error-container px-md py-sm rounded-lg">{{ error }}</p>
            <p v-if="success" class="text-body-sm text-success-green-text bg-success-green-bg px-md py-sm rounded-lg">{{ success }}</p>

            <div v-if="loading" class="text-center py-xl text-slate-500">Carregando usuários…</div>

            <div v-else class="bg-white rounded-xl border border-slate-200 shadow-level-1 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-body-sm">
                        <thead class="bg-slate-50 border-b border-slate-200 text-label-sm text-slate-600">
                            <tr>
                                <th class="px-md py-sm font-semibold">Nome</th>
                                <th class="px-md py-sm font-semibold">E-mail</th>
                                <th class="px-md py-sm font-semibold">Perfil</th>
                                <th class="px-md py-sm font-semibold text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="u in users"
                                :key="u.id"
                                class="border-b border-slate-100 hover:bg-slate-50/80"
                            >
                                <td class="px-md py-sm font-medium">{{ u.name }}</td>
                                <td class="px-md py-sm text-slate-600">{{ u.email }}</td>
                                <td class="px-md py-sm">
                                    <span
                                        class="inline-block text-label-sm px-2 py-0.5 rounded-full"
                                        :class="isUserAdmin(u) ? 'bg-primary-fixed text-on-primary-fixed' : 'bg-slate-100 text-slate-600'"
                                    >
                                        {{ isUserAdmin(u) ? 'Administrador' : 'Estudante' }}
                                    </span>
                                </td>
                                <td class="px-md py-sm text-right">
                                    <button
                                        type="button"
                                        class="h-8 px-sm rounded-lg text-label-sm border border-slate-200 hover:bg-slate-50"
                                        @click="openEdit(u)"
                                    >
                                        Editar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div
            v-if="editing"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
            @click.self="closeEdit"
        >
            <div class="bg-white rounded-xl shadow-level-2 border border-slate-200 w-full max-w-md p-lg">
                <h2 class="text-headline-sm font-semibold mb-md">Editar usuário</h2>
                <form class="space-y-md" @submit.prevent="saveUser">
                    <label class="block">
                        <span class="text-label-sm font-medium text-slate-700 block mb-1.5">Nome</span>
                        <input v-model="editForm.name" type="text" required class="field-input" />
                    </label>
                    <label class="block">
                        <span class="text-label-sm font-medium text-slate-700 block mb-1.5">E-mail</span>
                        <input v-model="editForm.email" type="email" required class="field-input" />
                    </label>
                    <label class="block">
                        <span class="text-label-sm font-medium text-slate-700 block mb-1.5">Perfil</span>
                        <select v-model="editForm.role" required class="field-input">
                            <option value="student">Estudante</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </label>
                    <div class="flex gap-sm pt-sm">
                        <button type="button" class="flex-1 h-10 border border-slate-200 rounded-lg" @click="closeEdit">
                            Cancelar
                        </button>
                        <button type="submit" class="flex-1 h-10 bg-primary text-on-primary rounded-lg" :disabled="saving">
                            {{ saving ? 'Salvando…' : 'Salvar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { api } from '../../bootstrap';

const users = ref([]);
const loading = ref(false);
const saving = ref(false);
const error = ref('');
const success = ref('');
const editing = ref(null);

const editForm = reactive({
    name: '',
    email: '',
    role: 'student',
});

function isUserAdmin(u) {
    return u.roles?.some((r) => r.name === 'admin') ?? false;
}

async function loadUsers() {
    loading.value = true;
    error.value = '';
    try {
        const { data } = await api.get('/api/admin/users');
        users.value = data;
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Falha ao carregar usuários.';
    } finally {
        loading.value = false;
    }
}

function openEdit(u) {
    editing.value = u;
    editForm.name = u.name;
    editForm.email = u.email;
    editForm.role = isUserAdmin(u) ? 'admin' : 'student';
    success.value = '';
}

function closeEdit() {
    editing.value = null;
}

async function saveUser() {
    if (!editing.value) {
        return;
    }
    saving.value = true;
    error.value = '';
    success.value = '';
    try {
        const { data } = await api.put(`/api/admin/users/${editing.value.id}`, {
            name: editForm.name.trim(),
            email: editForm.email.trim(),
            role: editForm.role,
        });
        const idx = users.value.findIndex((x) => x.id === editing.value.id);
        if (idx !== -1) {
            users.value[idx] = data;
        }
        success.value = 'Usuário atualizado com sucesso.';
        closeEdit();
    } catch (e) {
        const bag = e.response?.data?.errors;
        error.value = bag
            ? Object.values(bag).flat().join(' ')
            : (e.response?.data?.message ?? 'Não foi possível salvar.');
    } finally {
        saving.value = false;
    }
}

onMounted(loadUsers);
</script>
