import axios from "axios";
import { useAuthStore } from "@/stores/auth";

export const API_TOKEN_KEY_NAME = 'token';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
});

// Attach token automatically
api.interceptors.request.use((config) => {
  const auth = useAuthStore();
  if (auth.token) {
    config.headers.Authorization = `Bearer ${auth.token}`;
  }
  return config;
});

export default api;
