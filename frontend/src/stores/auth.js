import { defineStore } from 'pinia';
import api, { API_TOKEN_KEY_NAME } from '@/lib/api';
import { API_ROUTES } from '@/constants/routes';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: localStorage.getItem(API_TOKEN_KEY_NAME) || null,
        user: null,
    }),

    actions: {
        async login(email, password) {
            const { data } = await api.post(API_ROUTES.AUTH.LOGIN, { email, password });

            this.token = data.access_token;
            localStorage.setItem(API_TOKEN_KEY_NAME, this.token);

            this.user = data.user;
        },

        async logout() {
            await api.post(API_ROUTES.AUTH.LOGOUT);
            this.token = null;
            this.user = null;
            localStorage.removeItem(API_TOKEN_KEY_NAME);
        }
    },
});
