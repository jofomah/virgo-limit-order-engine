import { defineStore } from 'pinia';
import { ref } from 'vue';
import { useEcho } from '@laravel/echo-vue';

import api, { API_TOKEN_KEY_NAME } from '@/lib/api';
import { API_ROUTES } from '@/constants/routes';

const AUTH_USER_STORE_KEY = 'auth_user';
const BROADCAST_EVENT_NAME = 'OrderMatched';
const CHANNEL_NAME = 'user';

export const useAuthStore = defineStore('auth', () => {
    // --- STATE ---
    const token = ref(localStorage.getItem(API_TOKEN_KEY_NAME) || null);
    const user = ref(JSON.parse(localStorage.getItem(AUTH_USER_STORE_KEY) || 'null'));
    const channel = ref(null);

    // --- INTERNAL/NON-REACTIVE STATE ---
    let eventHandler = null;
    let globalBindingsRegistered = false;
    let authRetryCount = 0;
    const MAX_AUTH_RETRIES = 3;

    // Timers / debounce handles
    let reconnectDebounceTimer = null;
    let authRetryTimer = null;

    // Visibility handler registered once
    let visibilityListenerRegistered = false;

    /**
     * Safely attempts to refresh the access token via API.
     * Returns true when refreshed and token.value updated, false otherwise.
     */
    const refreshToken = async () => {
        try {
            const { data } = await api.post(API_ROUTES.AUTH.REFRESH_TOKEN);
            if (!data?.access_token) {
                console.warn('refreshToken: no access_token returned');
                return false;
            }

            token.value = data.access_token;
            localStorage.setItem(API_TOKEN_KEY_NAME, token.value);

            console.log('🔄 Token refreshed.');
            return true;
        } catch (e) {
            console.warn('❌ Token refresh failed:', e);
            return false;
        }
    };

    /**
     * Cleanup — removes channel listener and leaves the channel.
     */
    const stopListening = () => {
        const echo = useEcho();

        if (channel.value) {
            try {
                // leave channel by name to ensure the underlying connector cleans up
                echo.leaveChannel(channel.value.name);
            } catch (e) {
                // ignore errors during leave
            }
            channel.value = null;
        }

        eventHandler = null;

        // Clear any pending auth retry timer
        if (authRetryTimer) {
            clearTimeout(authRetryTimer);
            authRetryTimer = null;
        }
    };

    /**
     * Force-safe logout and cleanup. If forced = true, skip calling logout API
     */
    const logout = async (forced = false) => {
        try {
            if (!forced) {
                await api.post(API_ROUTES.AUTH.LOGOUT);
            }
        } catch (e) {
            console.warn('Logout API failed (server side) — continuing local cleanup.', e);
        }

        stopListening();

        token.value = null;
        user.value = null;
        authRetryCount = 0;

        localStorage.removeItem(API_TOKEN_KEY_NAME);
        localStorage.removeItem(AUTH_USER_STORE_KEY);

        console.log('🔒 Logged out (client cleanup done).');
    };

    /**
     * Subscribe to the user's private channel.
     * This function only handles the *subscription* attempt and local setup.
     * Auth-related retries / refresh are handled by the global error binding.
     */
    const subscribeToNotifications = () => {
        // guard: if user logged out while a timer was pending, abort
        if (!token.value || !user.value) return;

        const echo = useEcho();

        // Always clean before re-subscribing to avoid double listeners / leaks
        stopListening();

        try {
            // Ensure Pusher/Echo will use the latest token for auth
            echo.connector.pusher.config.auth = {
                headers: { Authorization: `Bearer ${token.value}` },
            };

            const ch = echo.private(`${CHANNEL_NAME}.${user.value.id}`);
            channel.value = ch;

            eventHandler = (payload) => {
                // handle notifications safely
                console.log('Notification received:', payload?.message ?? payload);
            };

            ch.listen(BROADCAST_EVENT_NAME, eventHandler);

            // Success: reset retry counter
            authRetryCount = 0;
            if (authRetryTimer) {
                clearTimeout(authRetryTimer);
                authRetryTimer = null;
            }

            console.log('📡 Subscribed to notifications.');
        } catch (err) {
            // This is unlikely for auth failures (those are surfaced via connection 'error'),
            // but could happen for immediate JS/Echo errors. Don't logout here.
            console.error('❌ Failed to initiate channel subscription (non-auth error):', err);
            // Do not increment authRetryCount or logout; let reconnect/error handlers manage recovery.
        }
    };

    /**
     * Called when Pusher reports an auth failure (4009).
     * Implements controlled refresh + retry logic with backoff, abort-safe on logout.
     */
    const handleAuthFailure = async () => {
        // If user already logged out, abort
        if (!token.value || !user.value) return;

        if (authRetryCount >= MAX_AUTH_RETRIES) {
            console.error('❌ Max auth retries reached. Forcing logout.');
            return logout(true);
        }

        authRetryCount++;

        // Attempt refresh first
        const refreshed = await refreshToken();

        // After refresh attempt, verify we still have an active session to avoid race with logout
        if (!token.value || !user.value) return;

        if (refreshed) {
            // Ensure Echo connectors pick up the new token immediately
            const echo = useEcho();
            echo.connector.pusher.config.auth = {
                headers: { Authorization: `Bearer ${token.value}` },
            };

            // small backoff proportional to attempt number to avoid thundering retries
            const backoffMs = 500 * authRetryCount;

            if (authRetryTimer) clearTimeout(authRetryTimer);
            authRetryTimer = setTimeout(() => {
                // double-check still logged in
                if (!token.value || !user.value) {
                    return;
                }
                console.warn(`Retrying subscription with refreshed token (${authRetryCount}/${MAX_AUTH_RETRIES})`);
                subscribeToNotifications();
            }, backoffMs);

            return;
        }

        // Refresh failed — schedule a final retry attempt (in case of transient issues)
        const finalBackoffMs = 1000 * authRetryCount;
        if (authRetryTimer) clearTimeout(authRetryTimer);
        authRetryTimer = setTimeout(() => {
            if (!token.value || !user.value) return;
            console.warn(`Retrying subscription after failed refresh (${authRetryCount}/${MAX_AUTH_RETRIES})`);
            subscribeToNotifications();
            // If this fails again and error fires, handleAuthFailure will be called by the error binding and
            // eventually force logout after MAX_AUTH_RETRIES.
        }, finalBackoffMs);
    };

    /**
     * Setup Echo auth headers and registers global connection bindings ONCE.
     * This binds connected, state_change and error handlers and ensures they are registered only once.
     */
    const initializeEcho = () => {
        if (!token.value || !user.value) return;

        const echo = useEcho();

        // ensure latest token is available to connector
        echo.connector.pusher.config.auth = {
            headers: { Authorization: `Bearer ${token.value}` },
        };

        // Subscribe immediately
        subscribeToNotifications();

        // Register global connection listeners ONCE
        if (!globalBindingsRegistered) {
            globalBindingsRegistered = true;

            // Debounced resubscribe on connected to avoid rapid re-subscriptions
            echo.connector.pusher.connection.bind('connected', () => {
                if (reconnectDebounceTimer) clearTimeout(reconnectDebounceTimer);
                reconnectDebounceTimer = setTimeout(() => {
                    // abort if user logged out during debounce
                    if (!token.value || !user.value) return;
                    console.log('🔌 Connected/reconnected. Debounced resubscribe.');
                    subscribeToNotifications();
                }, 300); // 300ms debounce
            });

            // Auth failure — code 4009 indicates auth problem in Pusher
            echo.connector.pusher.connection.bind('error', (err) => {
                // Only handle explicit auth error code; other errors are not token problems
                try {
                    if (err?.data?.code === 4009) {
                        console.warn('⚠ Pusher auth error (4009) detected.');
                        // handleAuthFailure is async but we don't await here to avoid blocking the event loop
                        void handleAuthFailure();
                    }
                } catch (e) {
                    console.warn('Error while handling Pusher error event:', e);
                }
            });

            // Network state changes for debugging / optional UI updates
            echo.connector.pusher.connection.bind('state_change', (states) => {
                if (states.current === 'unavailable') console.log('📡 Network unavailable');
                if (states.current === 'connecting') console.log('🔄 Attempting reconnect…');
            });
        }

        // Register a visibilitychange handler once to resubscribe when tab becomes active
        if (!visibilityListenerRegistered) {
            visibilityListenerRegistered = true;
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden && token.value && user.value) {
                    // small debounce to allow page to settle
                    setTimeout(() => {
                        if (!token.value || !user.value) return;
                        console.log('📣 Tab visible again — ensuring subscription.');
                        subscribeToNotifications();
                    }, 200);
                }
            });
        }
    };

    const login = async (email, password) => {
        const { data } = await api.post(API_ROUTES.AUTH.LOGIN, { email, password });

        token.value = data.access_token;
        user.value = data.user;

        localStorage.setItem(API_TOKEN_KEY_NAME, token.value);
        localStorage.setItem(AUTH_USER_STORE_KEY, JSON.stringify(user.value));

        // Initialize echo & subscriptions
        initializeEcho();
    };

    // Auto-initialize on app load if there's an active session
    if (token.value && user.value) {
        initializeEcho();
    }

    return {
        token,
        user,
        channel,
        login,
        logout,
        initializeEcho,
        stopListening,
    };
});
