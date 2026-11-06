/**
 * Created by Zura on 12/25/2021.
 */
import axios from "axios";
import store from "./store";
import router from "./router/index.js";

const axiosClient = axios.create({
  // Empty VITE_API_BASE_URL => same-origin requests (/api).
  baseURL: `${import.meta.env.VITE_API_BASE_URL || ''}/api`,
  // Render free instances can take up to a minute to wake up, so allow plenty
  // of time, but do not hang forever on a dead connection.
  timeout: 120000,
})

axiosClient.interceptors.request.use(config => {
  if (store.state.user.token) {
    config.headers.Authorization = `Bearer ${store.state.user.token}`
  }
  return config;
})

axiosClient.interceptors.response.use(response => {
  return response;
}, error => {
  // Network errors and timeouts have no `response`; never throw a second error
  // while handling the first one.
  if (error?.response?.status === 401) {
    store.commit('setToken', null)
    router.push({name: 'login'})
  }
  throw error;
})

export default axiosClient;
