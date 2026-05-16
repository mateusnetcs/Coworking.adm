<template>
    <!-- Layout autenticado (dashboard) -->
    <div
        v-if="isAuthenticated && !isGuestRoute"
        class="bg-slate-50 text-on-surface h-screen overflow-hidden flex flex-col md:flex-row"
    >
        <!-- Mobile TopNavBar -->
        <header class="md:hidden flex justify-between items-center w-full px-margin-mobile h-16 sticky top-0 z-50 bg-surface/90 backdrop-blur-md shadow-sm shrink-0">
            <div class="text-headline-md font-bold text-primary">Coworking UEMASUL</div>
            <div class="flex items-center gap-sm text-primary">
                <button type="button" class="p-1" @click="goNewReservation">
                    <AppIcon name="add" />
                </button>
                <img
                    v-if="user?.avatar"
                    :src="user.avatar"
                    :alt="user.name"
                    class="w-8 h-8 rounded-full ml-sm object-cover"
                />
                <div
                    v-else
                    class="w-8 h-8 rounded-full ml-sm bg-primary text-on-primary flex items-center justify-center text-label-sm font-bold"
                >
                    {{ userInitial }}
                </div>
            </div>
        </header>

        <!-- SideNavBar (Desktop) -->
        <nav class="hidden md:flex flex-col h-screen p-md fixed left-0 top-0 z-40 bg-surface-container-low w-64 border-r border-outline-variant">
            <div class="flex items-center gap-sm mb-lg px-sm">
                <div class="w-10 h-10 rounded-lg bg-primary flex items-center justify-center text-on-primary">
                    <AppIcon name="calendar_month" size="md" />
                </div>
                <div>
                    <div class="text-headline-sm font-black text-primary">Coworking</div>
                    <div class="text-label-sm text-on-surface-variant">UEMASUL · Administração</div>
                </div>
            </div>

            <button
                type="button"
                class="w-full h-10 bg-primary text-on-primary rounded-lg font-label-md flex items-center justify-center gap-sm mb-lg hover:bg-primary-container transition-colors"
                @click="goNewReservation"
            >
                <AppIcon name="add" />
                Nova reserva
            </button>

            <ul class="flex-1 space-y-sm">
                <li>
                    <RouterLink
                        to="/"
                        class="flex items-center gap-md px-md py-sm rounded-lg transition-all"
                        :class="route.name === 'console'
                            ? 'bg-secondary-container text-on-secondary-container font-bold'
                            : 'text-secondary hover:bg-surface-container-high'"
                    >
                        <AppIcon name="calendar_month" />
                        <span class="text-label-md">Agenda</span>
                    </RouterLink>
                </li>
                <template v-if="isAdmin">
                    <li>
                        <RouterLink
                            to="/admin/reservas"
                            class="flex items-center gap-md px-md py-sm rounded-lg transition-all"
                            :class="route.name === 'admin-reservations'
                                ? 'bg-secondary-container text-on-secondary-container font-bold'
                                : 'text-secondary hover:bg-surface-container-high'"
                        >
                            <AppIcon name="schedule" />
                            <span class="text-label-md">Reservas (admin)</span>
                        </RouterLink>
                    </li>
                    <li>
                        <RouterLink
                            to="/admin/usuarios"
                            class="flex items-center gap-md px-md py-sm rounded-lg transition-all"
                            :class="route.name === 'admin-users'
                                ? 'bg-secondary-container text-on-secondary-container font-bold'
                                : 'text-secondary hover:bg-surface-container-high'"
                        >
                            <AppIcon name="people" />
                            <span class="text-label-md">Usuários</span>
                        </RouterLink>
                    </li>
                </template>
            </ul>

            <ul class="mt-auto space-y-sm pt-md border-t border-outline-variant">
                <li>
                    <button
                        type="button"
                        class="w-full flex items-center gap-md px-md py-sm text-secondary hover:bg-surface-container-high rounded-lg transition-all"
                        @click="logout"
                    >
                        <AppIcon name="logout" />
                        <span class="text-label-md">Sair</span>
                    </button>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col h-full md:ml-64 bg-slate-50 overflow-hidden min-h-0">
            <header class="hidden md:flex justify-between items-center w-full px-margin-desktop h-16 sticky top-0 z-30 bg-surface/90 backdrop-blur-md shadow-sm border-b border-outline-variant shrink-0">
                <div class="text-headline-md font-bold text-primary">Coworking UEMASUL</div>
                <div class="flex items-center gap-lg">
                    <div v-if="user" class="text-right hidden lg:block">
                        <div class="text-label-md font-medium text-on-surface">{{ user.name }}</div>
                        <div class="text-label-sm text-slate-500">{{ user.email }}</div>
                        <span
                            v-if="isAdmin"
                            class="inline-block mt-0.5 text-[10px] uppercase tracking-wider bg-primary-fixed text-on-primary-fixed px-2 py-0.5 rounded-full font-semibold"
                        >
                            Admin
                        </span>
                    </div>
                    <img
                        v-if="user?.avatar"
                        :src="user.avatar"
                        :alt="user.name"
                        class="w-10 h-10 rounded-full border-2 border-white shadow-sm object-cover"
                    />
                    <div
                        v-else
                        class="w-10 h-10 rounded-full border-2 border-white shadow-sm bg-primary text-on-primary flex items-center justify-center font-bold"
                    >
                        {{ userInitial }}
                    </div>
                </div>
            </header>

            <div class="flex-1 overflow-hidden min-h-0">
                <RouterView />
            </div>
        </main>
    </div>

    <!-- Layout convidado (login / registro) -->
    <div v-else class="min-h-screen bg-slate-50 flex flex-col">
        <RouterView />
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { api, applyAuthToken, getStoredToken } from './bootstrap';

const router = useRouter();
const route = useRoute();

const token = ref(getStoredToken());
const user = ref(null);

const isAuthenticated = computed(() => Boolean(token.value));
const isGuestRoute = computed(() => ['login', 'register', 'auth-callback'].includes(route.name));
const isAdmin = computed(() => user.value?.roles?.some((r) => r.name === 'admin') ?? false);
const userInitial = computed(() => (user.value?.name?.[0] ?? '?').toUpperCase());

async function loadUser() {
    if (!token.value) {
        user.value = null;
        return;
    }
    try {
        const { data } = await api.get('/api/user');
        user.value = data;
    } catch {
        user.value = null;
    }
}

function syncToken() {
    token.value = getStoredToken();
}

router.afterEach(() => {
    syncToken();
    loadUser();
});

onMounted(() => {
    syncToken();
    loadUser();
});

function goNewReservation() {
    router.push({ name: 'console', query: { action: 'new' } });
}

async function logout() {
    try {
        await api.post('/api/logout');
    } catch {
        /* ignore */
    }
    applyAuthToken(null);
    token.value = null;
    user.value = null;
    await router.push('/login');
}

watch(isAuthenticated, (val) => {
    if (val) {
        loadUser();
    }
});
</script>
