import { createRouter, createWebHashHistory } from 'vue-router';
import AuthCallbackView from '../views/AuthCallbackView.vue';
import AdminReservationsView from '../views/admin/AdminReservationsView.vue';
import AdminUsersView from '../views/admin/AdminUsersView.vue';
import CoworkingConsole from '../views/CoworkingConsole.vue';
import LoginView from '../views/LoginView.vue';
import RegisterView from '../views/RegisterView.vue';
import { api, getStoredToken } from '../bootstrap';

const router = createRouter({
    history: createWebHashHistory(),
    routes: [
        { path: '/', name: 'console', component: CoworkingConsole, meta: { requiresAuth: true } },
        {
            path: '/admin/reservas',
            name: 'admin-reservations',
            component: AdminReservationsView,
            meta: { requiresAuth: true, requiresAdmin: true },
        },
        {
            path: '/admin/usuarios',
            name: 'admin-users',
            component: AdminUsersView,
            meta: { requiresAuth: true, requiresAdmin: true },
        },
        { path: '/login', name: 'login', component: LoginView, meta: { guestOnly: true } },
        { path: '/register', name: 'register', component: RegisterView, meta: { guestOnly: true } },
        { path: '/auth/callback', name: 'auth-callback', component: AuthCallbackView },
    ],
});

router.beforeEach(async (to) => {
    const token = getStoredToken();

    if (to.meta.requiresAuth && !token) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.guestOnly && token) {
        return { name: 'console' };
    }

    if (to.meta.requiresAdmin) {
        if (!token) {
            return { name: 'login' };
        }
        try {
            const { data } = await api.get('/api/user');
            const isAdmin = data.roles?.some((r) => r.name === 'admin');
            if (!isAdmin) {
                return { name: 'console' };
            }
        } catch {
            return { name: 'login' };
        }
    }

    return true;
});

export default router;
