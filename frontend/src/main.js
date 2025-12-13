import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import { API_ROUTES } from '@/constants/routes';

import Echo from 'laravel-echo';
import { configureEcho } from '@laravel/echo-vue';
import Pusher from 'pusher-js';

const app = createApp(App)

configureEcho({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
    encrypted: true,
    disableStats: true,
    authEndpoint: `${import.meta.env.VITE_API_URL}${API_ROUTES.BROADCAST_AUTH}`,
});

app.use(createPinia())
app.use(router)

app.mount('#app')
