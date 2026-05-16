import axios from 'axios';

export const TOKEN_KEY = 'coworking_sanctum_token';

export const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL ?? '',
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

export function getStoredToken() {
    return localStorage.getItem(TOKEN_KEY);
}

export function applyAuthToken(token) {
    if (token) {
        localStorage.setItem(TOKEN_KEY, token);
        api.defaults.headers.common.Authorization = `Bearer ${token}`;
    } else {
        localStorage.removeItem(TOKEN_KEY);
        delete api.defaults.headers.common.Authorization;
    }
}

const bootToken = getStoredToken();

if (bootToken) {
    applyAuthToken(bootToken);
}
